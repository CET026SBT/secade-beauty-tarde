<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/GreenReceiptConfigRepository.php";
require_once APP_PATH . "/utils/Session.php";

/**
 * Percentagens padrão por tipo de contrato (`config_percentagem_padrao`).
 *
 * Guarda os defaults que o gestor define no backoffice: `efetivo_contratado` e
 * `recibo_verde`. São apenas **valores iniciais** — cada funcionário tem a sua
 * própria `funcionario.percentagem_comissao`, editável no formulário de RH.
 */
class GreenReceiptService extends BaseService {

    public const TIPO_EFETIVO       = "efetivo_contratado";
    public const TIPO_RECIBO_VERDE  = "recibo_verde";

    private const DEFAULT_EFETIVO      = 0.0;
    private const DEFAULT_RECIBO_VERDE = 70.0;

    private GreenReceiptConfigRepository $configRepository;

    public function __construct() {
        parent::__construct();
        $this->configRepository = new GreenReceiptConfigRepository();
    }

    /**
     * Defaults em vigor, por tipo de contrato. O que não tiver linha configurada
     * usa o valor de fábrica (efetivo 0% · recibo verde 70%).
     */
    public function findDefaults(): array {
        $defaults = [
            self::TIPO_EFETIVO      => self::DEFAULT_EFETIVO,
            self::TIPO_RECIBO_VERDE => self::DEFAULT_RECIBO_VERDE
        ];

        foreach ($this->configRepository->findDefaults() as $row) {
            $defaults[$row["contractType"]] = (float)$row["defaultPercentage"];
        }

        return $defaults;
    }

    public function defaultFor(string $contractType): float {
        return (float)($this->findDefaults()[$contractType] ?? 0.0);
    }

    /**
     * Percentagem a aplicar quando um serviço é alocado a um funcionário a recibo
     * verde sem percentagem própria definida. (O formulário de RH pré-preenche
     * `funcionario.percentagem_comissao` com este valor.)
     */
    public function resolveEmployeePercentage(): float {
        return $this->defaultFor(self::TIPO_RECIBO_VERDE);
    }

    /**
     * Cálculo da repartição de um valor de serviço pela percentagem do funcionário.
     */
    public function simulate(float $amount, ?float $employeePercentage = null): array {
        $percentage = $employeePercentage ?? $this->resolveEmployeePercentage();
        $percentage = max(0.0, min(100.0, $percentage));

        $employeeValue = round($amount * $percentage / 100, 2);
        $platformValue = round($amount - $employeeValue, 2);

        return [
            "amount"             => round($amount, 2),
            "employeePercentage" => $percentage,
            "platformPercentage" => round(100 - $percentage, 2),
            "employeeValue"      => $employeeValue,
            "platformValue"      => $platformValue
        ];
    }

    /**
     * Configuração da perspetiva dos recibos verdes (o default em vigor).
     * Compatível com a página atual; reformulado na Fase 8.
     */
    public function findActiveConfig(): array {
        $recibosVerdes = null;
        foreach ($this->configRepository->findDefaults() as $row) {
            if ($row["contractType"] === self::TIPO_RECIBO_VERDE) { $recibosVerdes = $row; break; }
        }

        $employeePercentage = $recibosVerdes ? (float)$recibosVerdes["defaultPercentage"] : self::DEFAULT_RECIBO_VERDE;

        return [
            "id"                 => $recibosVerdes["id"] ?? null,
            "employeePercentage" => $employeePercentage,
            "platformPercentage" => round(100 - $employeePercentage, 2),
            "effectiveFrom"      => $recibosVerdes["effectiveFrom"] ?? null,
            "isDefault"          => $recibosVerdes === null
        ];
    }

    public function findConfigHistory(): array {
        $configs = [];

        foreach ($this->configRepository->findHistory() as $row) {
            if ($row["contractType"] !== self::TIPO_RECIBO_VERDE) { continue; }

            $percentage = (float)$row["defaultPercentage"];
            $configs[] = [
                "id"                 => (int)$row["id"],
                "employeePercentage" => $percentage,
                "platformPercentage" => round(100 - $percentage, 2),
                "effectiveFrom"      => (string)$row["effectiveFrom"]
            ];
        }

        return [
            "configs"  => $configs,
            "active"   => $this->findActiveConfig(),
            "defaults" => [
                "employeePercentage" => self::DEFAULT_RECIBO_VERDE,
                "platformPercentage" => round(100 - self::DEFAULT_RECIBO_VERDE, 2)
            ]
        ];
    }

    public function createConfig(array $data): array {
        $managerId = Session::userId();

        return $this->executeTransactional(function() use ($data, $managerId) {
            $this->validate($data, function($v) {
                $v  ->required("employeePercentage", "A percentagem do funcionário é obrigatória.")
                    ->required("effectiveFrom", "A data de vigência é obrigatória.");
            });

            $employeePercentage = (float)$data["employeePercentage"];

            if ($employeePercentage < 0 || $employeePercentage > 100) {
                throw new Exception("A percentagem tem de estar entre 0 e 100.", 422);
            }

            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", (string)$data["effectiveFrom"])) {
                throw new Exception("Data de vigência inválida. Formato esperado: AAAA-MM-DD.", 422);
            }

            $configId = $this->configRepository->setDefault(
                self::TIPO_RECIBO_VERDE,
                $employeePercentage,
                (string)$data["effectiveFrom"],
                $managerId
            );

            return [
                "configId"   => $configId,
                "message"    => "Configuração de percentagens registada com sucesso.",
                "simulation" => $this->simulate(100.0, $employeePercentage)
            ];
        });
    }
}