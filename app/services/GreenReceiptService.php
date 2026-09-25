<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/GreenReceiptConfigRepository.php";
require_once APP_PATH . "/utils/Session.php";

/**
 * Simulador de Recibos Verdes (simplificação académica: não há emissão real
 * na Segurança Social). Percentagens configuráveis pelo gestor, com vigência
 * por data — na aceitação aplica-se a configuração em vigor.
 */
class GreenReceiptService extends BaseService {

    private const DEFAULT_EMPLOYEE_PERCENTAGE = 70.0;
    private const DEFAULT_PLATFORM_PERCENTAGE = 30.0;

    private GreenReceiptConfigRepository $configRepository;

    public function __construct() {
        parent::__construct();
        $this->configRepository = new GreenReceiptConfigRepository();
    }

    /**
     * Configuração em vigor (ou o default 70/30 se não existir nenhuma).
     */
    public function findActiveConfig(): array {
        $config = $this->configRepository->findActive();

        if (!$config) {
            return [
                "id"                 => null,
                "employeePercentage" => self::DEFAULT_EMPLOYEE_PERCENTAGE,
                "platformPercentage" => self::DEFAULT_PLATFORM_PERCENTAGE,
                "effectiveFrom"      => null,
                "isDefault"          => true
            ];
        }

        return $config + ["isDefault" => false];
    }

    /**
     * Percentagem aplicável ao funcionário no momento da aceitação.
     */
    public function resolveEmployeePercentage(): float {
        return (float)($this->findActiveConfig()["employeePercentage"] ?? self::DEFAULT_EMPLOYEE_PERCENTAGE);
    }

    /**
     * Cálculo do recibo verde simulado para um valor de serviço.
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

    public function findConfigHistory(): array {
        return [
            "configs"  => $this->configRepository->findHistory(),
            "active"   => $this->findActiveConfig(),
            "defaults" => [
                "employeePercentage" => self::DEFAULT_EMPLOYEE_PERCENTAGE,
                "platformPercentage" => self::DEFAULT_PLATFORM_PERCENTAGE
            ]
        ];
    }

    public function createConfig(array $data): array {
        $managerId = Session::userId();

        return $this->executeTransactional(function() use ($data, $managerId) {
            $this->validate($data, function($v) {
                $v  ->required("employeePercentage", "A percentagem do funcionário é obrigatória.")
                    ->required("platformPercentage", "A percentagem da plataforma é obrigatória.")
                    ->required("effectiveFrom", "A data de vigência é obrigatória.");
            });

            $employeePercentage = (float)$data["employeePercentage"];
            $platformPercentage = (float)$data["platformPercentage"];

            if ($employeePercentage < 0 || $employeePercentage > 100 || $platformPercentage < 0 || $platformPercentage > 100) {
                throw new Exception("As percentagens têm de estar entre 0 e 100.", 422);
            }

            if (round($employeePercentage + $platformPercentage, 2) !== 100.0) {
                throw new Exception("A soma das percentagens tem de ser exatamente 100.", 422);
            }

            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", (string)$data["effectiveFrom"])) {
                throw new Exception("Data de vigência inválida. Formato esperado: AAAA-MM-DD.", 422);
            }

            $configId = $this->configRepository->create(
                $employeePercentage,
                $platformPercentage,
                $data["effectiveFrom"],
                $managerId
            );

            return [
                "configId" => $configId,
                "message"  => "Configuração do simulador de recibos verdes registada com sucesso.",
                "simulation" => $this->simulate(100.0, $employeePercentage)
            ];
        });
    }
}