<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/EmployeeRepository.php";
require_once __DIR__ . "/UserService.php";

class EmployeeService extends BaseService {
    private EmployeeRepository $employeeRepository;
    private UserService $userService;

    public function __construct() {
        parent::__construct();
        $this->employeeRepository = new EmployeeRepository();
        $this->userService = new UserService();
    }

    public function validateInput(array $data): void {
        $this->validate($data, function($v) use ($data) {
            $v  ->required("cc", "O número de Cartão de Cidadão é obrigatório.")
                ->cc("cc", "Número de Cartão de Cidadão inválido.")
                ->required("contractType", "O tipo de contrato é obrigatório.")
                ->contains("contractType", ["efetivo_contratado", "recibo_verde"], "Tipo de contrato inválido.")
                ->required("salary", "O salário base é obrigatório.");
        });
    }

    public function createEmployee(array $data): array {
        return $this->executeTransactional(function() use ($data) {
            $this->validateInput($data);

            $data["profileType"] = "funcionario";

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
}
