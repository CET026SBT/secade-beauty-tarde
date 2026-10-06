<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/EmployeeRepository.php";
require_once APP_PATH . "/repositories/GreenReceiptConfigRepository.php";
require_once __DIR__ . "/UserService.php";
require_once APP_PATH . "/utils/Session.php";

/**
 * Recursos Humanos (F7 · §7).
 *
 * A página `/gestao/rh` lista, cria, edita e **desativa (soft delete)** funcionários.
 * A desativação tem **fluxo de impacto** (§7.5): se o funcionário tiver serviços por
 * executar, o gestor é avisado; ao confirmar, esses serviços voltam a `pendente` e o
 * agendamento deixa de estar `totalmente_alocado`. Os agendamentos `confirmado`
 * (compromisso já comunicado) **nunca** são mexidos — D-07.3.
 */
class EmployeeService extends BaseService {
    private EmployeeRepository $employeeRepository;
    private GreenReceiptConfigRepository $configRepository;
    private UserService $userService;

    public function __construct() {
        parent::__construct();
        $this->employeeRepository = new EmployeeRepository();
        $this->configRepository = new GreenReceiptConfigRepository();
        $this->userService = new UserService();
    }

    public function validateInput(array $data): void {
        $this->validate($data, function($v) use ($data) {
            $v  ->required("cc", "O número de Cartão de Cidadão é obrigatório.")
                ->cc("cc", "Número de Cartão de Cidadão inválido.")
                ->required("contractType", "O tipo de contrato é obrigatório.")
                ->contains("contractType", ["efetivo_contratado", "recibo_verde"], "Tipo de contrato inválido.");
        });
    }

    /** Percentagem em vigor por tipo de contrato (salvaguarda se a tabela estiver vazia). */
    public function defaultPercentageFor(string $contractType): float {
        $config = $this->configRepository->findActiveByContractType($contractType);

        if ($config && isset($config["commissionPercentage"])) {
            return (float)$config["commissionPercentage"];
        }

        return $contractType === "recibo_verde" ? 70.0 : 0.0;
    }

    public function createEmployee(array $data): array {
        return $this->executeTransactional(function() use ($data) {
            $this->validateInput($data);

            $data["profileType"] = "funcionario";

            // C-10: só os efetivos têm salário base; os RV ficam a 0.
            if ($data["contractType"] === "recibo_verde") {
                $data["salary"] = 0.0;
            }

            // C-09/D-21: a percentagem nasce com o padrão do tipo de contrato.
            if (!isset($data["commissionPercentage"]) || $data["commissionPercentage"] === "") {
                $data["commissionPercentage"] = $this->defaultPercentageFor($data["contractType"]);
            }

            $userResult = $this->userService->createUser($data);
            $data["userId"] = $userResult["userId"];

            $employeeId = $this->employeeRepository->create($data);

            return [
                "id" => $employeeId,
                "message" => "Funcionário registado com sucesso!"
            ];
        });
    }

    public function findEmployee(int $employeeId): ?array {
        return $this->employeeRepository->find($employeeId);
    }

    /** Lista de funcionários (ativos e inativos) para a página de RH. */
    public function listEmployees(): array {
        $employees = $this->employeeRepository->find();

        return ["employees" => is_array($employees) ? $employees : []];
    }

    /**
     * Contexto do formulário: identidades, percentagens por omissão e indicadores
     * de topo (§7.4 item 5) — sem números inventados.
     */
    public function formContext(): array {
        $employees = $this->employeeRepository->find();
        $employees = is_array($employees) ? $employees : [];

        $active = array_values(array_filter($employees, fn($e) => !empty($e["isActive"])));
        $fixed  = 0.0;
        $reciboVerde = 0;
        $efetivos = 0;

        foreach ($active as $employee) {
            $fixed += (float)($employee["salary"] ?? 0);
            if (($employee["contractType"] ?? "") === "recibo_verde") { $reciboVerde++; } else { $efetivos++; }
        }

        return [
            "employees" => $employees,
            "defaults"  => [
                "recibo_verde"       => $this->defaultPercentageFor("recibo_verde"),
                "efetivo_contratado" => $this->defaultPercentageFor("efetivo_contratado")
            ],
            "totals" => [
                "active"      => count($active),
                "efetivos"    => $efetivos,
                "reciboVerde" => $reciboVerde,
                "fixedCost"   => round($fixed, 2)
            ]
        ];
    }

    public function updateEmployee(int $employeeId, array $data): array {
        return $this->executeTransactional(function() use ($employeeId, $data) {
            $employee = $this->employeeRepository->find($employeeId);
            if (!$employee) {
                throw new Exception("Funcionário não encontrado.", 404);
            }

            $this->validateInput($data);

            // C-10: os RV não têm salário base (0).
            if ($data["contractType"] === "recibo_verde") {
                $data["salary"] = 0.0;
            }

            if (!isset($data["commissionPercentage"]) || $data["commissionPercentage"] === "") {
                $data["commissionPercentage"] = $this->defaultPercentageFor($data["contractType"]);
            }

            $this->employeeRepository->update($employeeId, $data);

            return [
                "id" => $employeeId,
                "message" => "Funcionário atualizado com sucesso."
            ];
        });
    }

    /** Pré-visualização do impacto da desativação (§7.5) — nunca altera nada. */
    public function deactivationImpact(int $employeeId): array {
        $employee = $this->employeeRepository->find($employeeId);
        if (!$employee) {
            throw new Exception("Funcionário não encontrado.", 404);
        }

        $affected  = $this->employeeRepository->countAssignedServices($employeeId);
        $confirmed = max(0, $this->employeeRepository->countAssignedServices($employeeId, false) - $affected);

        return [
            "employeeId"       => $employeeId,
            "affectedServices" => $affected,
            "confirmedKept"    => $confirmed,
            "hasImpact"        => $affected > 0,
            "message"          => $affected > 0
                ? "Este funcionário tem {$affected} serviço(s) por executar. Ao desativar, voltam a ficar por alocar."
                : "Sem serviços por executar — a desativação não afeta alocações."
        ];
    }

    /** Desativação (soft delete) com o impacto de §7.5. */
    public function deactivateEmployee(int $employeeId): array {
        return $this->executeTransactional(function() use ($employeeId) {
            $employee = $this->employeeRepository->find($employeeId);
            if (!$employee) {
                throw new Exception("Funcionário não encontrado.", 404);
            }

            $released = $this->employeeRepository->releaseAssignedServices($employeeId);
            $reset    = $this->employeeRepository->resetBookingsWithoutAllocation();
            $this->employeeRepository->setActive($employeeId, false);

            return [
                "id"               => $employeeId,
                "releasedServices" => $released,
                "resetBookings"    => $reset,
                "message"          => $released > 0
                    ? "Funcionário desativado. {$released} serviço(s) voltaram a ficar por alocar."
                    : "Funcionário desativado."
            ];
        });
    }

    /** Reativa um funcionário desativado. */
    public function activateEmployee(int $employeeId): array {
        $employee = $this->employeeRepository->find($employeeId);
        if (!$employee) {
            throw new Exception("Funcionário não encontrado.", 404);
        }

        $this->employeeRepository->setActive($employeeId, true);

        return ["id" => $employeeId, "message" => "Funcionário reativado."];
    }
}
