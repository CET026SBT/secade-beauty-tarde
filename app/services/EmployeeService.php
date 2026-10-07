<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/EmployeeRepository.php";
require_once APP_PATH . "/repositories/UserRepository.php";
require_once APP_PATH . "/utils/ValidationException.php";
require_once __DIR__ . "/UserService.php";

/**
 * Recursos Humanos — F7 (§7 · §25).
 *
 * O gestor gere aqui a equipa: listar, criar, editar e **desativar** (soft delete,
 * §7.5). Não há eliminação física. A percentagem de comissão nasce com o **default
 * do tipo de contrato** (§7.6) e é editável por funcionário; o IRS é **dado** por
 * funcionário (coluna `funcionario.irs_taxa`, §7.3), nunca constante de código.
 *
 * Camadas (§18.1): o Service valida e orquestra as transações; o Repository escreve
 * na própria tabela; o Mapper traduz BD→EN.
 */
class EmployeeService extends BaseService {

    /** Percentagem de comissão por omissão, por tipo de contrato (§7.6 · C-09/C-10). */
    private const DEFAULT_PERCENTAGE = [
        "efetivo_contratado" => 0.0,
        "recibo_verde"       => 70.0
    ];

    private EmployeeRepository $employeeRepository;
    private UserRepository $userRepository;
    private UserService $userService;

    public function __construct() {
        parent::__construct();
        $this->employeeRepository = new EmployeeRepository();
        $this->userRepository = new UserRepository();
        $this->userService = new UserService();
    }

    /** Lista de funcionários (ativos + inativos) + indicadores (§7.4). */
    public function listEmployees(): array {
        return [
            "employees" => $this->employeeRepository->findAllWithUser(),
            "summary"   => $this->employeeRepository->summary(),
            "defaults"  => self::DEFAULT_PERCENTAGE
        ];
    }

    /** Regras do funcionário (uma só fonte para criar e editar). */
    public function validateInput(array $data, int $excludeUserId = 0): void {
        $this->validate($data, function($v) use ($data, $excludeUserId) {
            $v  ->required("name", "O nome completo é obrigatório.")
                ->required("email", "O e-mail é obrigatório.")
                ->email("email", "Endereço de email inválido.")
                ->custom("email", fn($email) => $this->userRepository->emailTakenByOther((string)$email, $excludeUserId), "Este email já se encontra registado.")
                ->required("phone", "O número de telemóvel é obrigatório.")
                ->phone("phone", "Insira um número de telemóvel válido.")
                ->required("cc", "O número de Cartão de Cidadão é obrigatório.")
                ->cc("cc", "Número de Cartão de Cidadão inválido.")
                ->required("contractType", "O tipo de contrato é obrigatório.")
                ->contains("contractType", array_keys(self::DEFAULT_PERCENTAGE), "Tipo de contrato inválido.")
                ->regex("salary", "/^\d{0,8}(\.\d{1,2})?$/", "Salário inválido.")
                ->regex("commissionPercentage", "/^\d{0,3}(\.\d{1,2})?$/", "Percentagem inválida.")
                ->regex("irsRate", "/^\d{0,3}(\.\d{1,2})?$/", "Taxa de IRS inválida.");

            // Só os efetivos têm salário base (§7.6 · C-10).
            if (($data["contractType"] ?? "") === "efetivo_contratado") {
                $v->required("salary", "O salário base é obrigatório para funcionários efetivos.");
            }
        });
    }

    /** Percentagem efetiva: a indicada pelo gestor, ou o default do tipo de contrato. */
    private function resolvePercentage(array $data): float {
        $raw = $data["commissionPercentage"] ?? "";
        if ($raw !== "" && $raw !== null) {
            return (float)$raw;
        }

        return self::DEFAULT_PERCENTAGE[$data["contractType"]] ?? 0.0;
    }

    /** Cria o funcionário reutilizando o caminho de criação de utilizador (UserService). */
    public function createEmployee(array $data): array {
        return $this->executeTransactional(function() use ($data) {
            $this->validateInput($data);

            $data["profileType"]          = "funcionario";
            $data["commissionPercentage"] = $this->resolvePercentage($data);

            if ($data["contractType"] !== "efetivo_contratado") {
                $data["salary"] = 0;   // RV não tem salário base (§7.6)
            }

            $userResult = $this->userService->createUser($data);
            $data["userId"] = $userResult["userId"];

            $this->employeeRepository->create($data);

            return [
                "employeeId" => $data["userId"],
                "message"    => "Funcionário registado com sucesso!"
            ];
        });
    }

    /** Edita os dados do utilizador e do funcionário (§7.4 item 3). */
    public function updateEmployee(int $employeeId, array $data): array {
        return $this->executeTransactional(function() use ($employeeId, $data) {
            $existing = $this->employeeRepository->find($employeeId);
            if (!$existing) {
                throw new Exception("Funcionário não encontrado.", 404);
            }

            $this->validateInput($data, $employeeId);

            $data["commissionPercentage"] = $this->resolvePercentage($data);
            if ($data["contractType"] !== "efetivo_contratado") {
                $data["salary"] = 0;
            }

            $this->userRepository->updateProfile($employeeId, $data);
            $this->employeeRepository->update($employeeId, $data);

            return [
                "employeeId" => $employeeId,
                "message"    => "Funcionário atualizado com sucesso."
            ];
        });
    }

    public function findEmployee(int $employeeId): ?array {
        return $this->employeeRepository->find($employeeId);
    }

    /**
     * Pré-visualização do impacto da desativação (§7.5): o que o gestor tem de
     * saber antes de confirmar.
     */
    public function deactivateImpact(int $employeeId): array {
        $employee = $this->employeeRepository->find($employeeId);
        if (!$employee) {
            throw new Exception("Funcionário não encontrado.", 404);
        }

        $confirmed = $this->employeeRepository->countAssignedInConfirmed($employeeId);
        $services  = $this->employeeRepository->listAssignedServices($employeeId);

        return [
            "employeeId"     => $employeeId,
            "name"           => $employee["name"] ?? null,
            "blocked"        => $confirmed > 0,
            "confirmedCount" => $confirmed,
            "services"       => $services,
            "servicesCount"  => count($services)
        ];
    }

    /**
     * Desativa (soft delete) ou reativa. A desativação segue o fluxo de §7.5:
     *  - se o funcionário estiver numa ROTA CONFIRMADA -> bloqueia (409);
     *  - senão, liberta os serviços não terminais, reverte os agendamentos
     *    para «por alocar» e marca o funcionário inativo (nada é apagado).
     */
    public function setActive(int $employeeId, bool $active): array {
        return $this->executeTransactional(function() use ($employeeId, $active) {
            $employee = $this->employeeRepository->find($employeeId);
            if (!$employee) {
                throw new Exception("Funcionário não encontrado.", 404);
            }

            if ($active) {
                $this->employeeRepository->setActive($employeeId, true);
                return [
                    "employeeId" => $employeeId,
                    "active"     => true,
                    "message"    => "Funcionário reativado."
                ];
            }

            if (($employee["isActive"] ?? false) === false) {
                throw new Exception("O funcionário já está inativo.", 409);
            }

            if ($this->employeeRepository->countAssignedInConfirmed($employeeId) > 0) {
                throw new Exception(
                    "O funcionário tem serviços em rota confirmada. Retire-o da rota antes de o desativar.",
                    409
                );
            }

            $bookingIds = $this->employeeRepository->listAssignedBookingIds($employeeId);
            $released   = $this->employeeRepository->unassignForEmployee($employeeId);
            $this->employeeRepository->revertToPendingAllocation($bookingIds);
            $this->employeeRepository->setActive($employeeId, false);

            return [
                "employeeId"       => $employeeId,
                "active"           => false,
                "releasedServices" => $released,
                "revertedBookings" => count($bookingIds),
                "message"          => $released > 0
                    ? "Funcionário desativado. " . $released . " serviço(s) voltaram a ficar por alocar."
                    : "Funcionário desativado."
            ];
        });
    }
}
