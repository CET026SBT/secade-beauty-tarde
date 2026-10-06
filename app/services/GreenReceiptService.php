<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/GreenReceiptConfigRepository.php";
require_once APP_PATH . "/repositories/EmployeeRepository.php";
require_once APP_PATH . "/utils/Session.php";

/**
 * Percentagens e simulação de recibos verdes (simplificação académica: não há
 * emissão real na Segurança Social).
 *
 * Modelo (D-21 · NF-01): a percentagem aplicada a um serviço é **sempre** a do
 * funcionário (`funcionario.percentagem_comissao`). Essa percentagem nasce com o
 * **valor padrão do tipo de contrato** (`config_percentagem_padrao`, configurável
 * pelo gestor no backoffice: efetivo 0 % · RV 70 %) e é editável por funcionário.
 * **Não existe** valor «global» nem cadeia de fallback.
 */
class GreenReceiptService extends BaseService {

    private const DEFAULT_RECIBO_VERDE_PERCENTAGE = 70.0;
    private const DEFAULT_EFETIVO_PERCENTAGE      = 0.0;

    private GreenReceiptConfigRepository $configRepository;
    private EmployeeRepository $employeeRepository;

    public function __construct() {
        parent::__construct();
        $this->configRepository   = new GreenReceiptConfigRepository();
        $this->employeeRepository = new EmployeeRepository();
    }

    /**
     * Percentagem padrão em vigor para um tipo de contrato
     * (com salvaguarda se a tabela de configuração estiver vazia).
     */
    public function resolveDefaultPercentage(string $tipoContrato): float {
        $config = $this->configRepository->findActiveByContractType($tipoContrato);
        if ($config && isset($config["commissionPercentage"])) {
            return (float)$config["commissionPercentage"];
        }

        return $tipoContrato === "recibo_verde"
            ? self::DEFAULT_RECIBO_VERDE_PERCENTAGE
            : self::DEFAULT_EFETIVO_PERCENTAGE;
    }

    /** Configurações padrão em vigor (uma por tipo de contrato). */
    public function findDefaultConfigs(): array {
        $configs = $this->configRepository->findActiveAll();

        if (empty($configs)) {
            return [
                "reciboVerde" => self::DEFAULT_RECIBO_VERDE_PERCENTAGE,
                "efetivo"     => self::DEFAULT_EFETIVO_PERCENTAGE,
                "isDefault"   => true
            ];
        }

        return ["configs" => $configs, "isDefault" => false];
    }

    /**
     * Percentagem aplicável a um funcionário na aceitação: a **sua** percentagem
     * gravada em `funcionario.percentagem_comissao`; se não existir, cai para o
     * padrão do respetivo tipo de contrato.
     */
    public function resolveEmployeePercentage(int $employeeId): float {
        $employee = $this->employeeRepository->find($employeeId);

        if (is_array($employee) && $employee["commissionPercentage"] !== null) {
            return (float)$employee["commissionPercentage"];
        }

        $tipoContrato = is_array($employee) ? (string)($employee["contractType"] ?? "recibo_verde") : "recibo_verde";
        return $this->resolveDefaultPercentage($tipoContrato);
    }

    /**
     * Cálculo do recibo verde simulado para um valor de serviço.
     */
    public function simulate(float $amount, ?float $employeePercentage = null): array {
        $percentage = $employeePercentage ?? self::DEFAULT_RECIBO_VERDE_PERCENTAGE;
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

    public function findConfigHistory(): array {
        return [
            "configs"  => $this->configRepository->findHistory(),
            "active"   => $this->findDefaultConfigs(),
            "defaults" => [
                "reciboVerde" => self::DEFAULT_RECIBO_VERDE_PERCENTAGE,
                "efetivo"     => self::DEFAULT_EFETIVO_PERCENTAGE
            ]
        ];
    }

    public function createConfig(array $data): array {
        $managerId = Session::userId();

        return $this->executeTransactional(function() use ($data, $managerId) {
            $this->validate($data, function($v) {
                $v  ->required("contractType", "O tipo de contrato é obrigatório.")
                    ->required("commissionPercentage", "A percentagem é obrigatória.")
                    ->required("effectiveFrom", "A data de vigência é obrigatória.");
            });

            $tipoContrato = (string)$data["contractType"];
            if (!in_array($tipoContrato, ["efetivo_contratado", "recibo_verde"], true)) {
                throw new Exception("Tipo de contrato inválido.", 422);
            }

            $percentage = (float)$data["commissionPercentage"];

            if ($percentage < 0 || $percentage > 100) {
                throw new Exception("A percentagem tem de estar entre 0 e 100.", 422);
            }

            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", (string)$data["effectiveFrom"])) {
                throw new Exception("Data de vigência inválida. Formato esperado: AAAA-MM-DD.", 422);
            }

            $configId = $this->configRepository->create(
                $tipoContrato,
                $percentage,
                $data["effectiveFrom"],
                $managerId
            );

            return [
                "configId" => $configId,
                "message"  => "Percentagem padrão registada com sucesso.",
                "simulation" => $this->simulate(100.0, $percentage)
            ];
        });
    }
}