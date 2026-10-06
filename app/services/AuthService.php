<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/UserRepository.php";
require_once APP_PATH . "/repositories/EmployeeRepository.php";
require_once APP_PATH . "/services/CustomerService.php";
require_once APP_PATH . "/services/EmployeeService.php";
require_once APP_PATH . "/services/ManagerService.php";
require_once __DIR__ . "/MaintenanceService.php";

class AuthService extends BaseService {
    private UserRepository $userRepository;
    private EmployeeRepository $employeeRepository;
    private CustomerService $customerService;
    private EmployeeService $employeeService;
    private ManagerService $managerService;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
        $this->employeeRepository = new EmployeeRepository();
        $this->customerService = new CustomerService();
        $this->employeeService = new EmployeeService();
        $this->managerService = new ManagerService();
    }

    public function validateLoginInput(array $data, ?array $user): void {
        $this->validate($data, function($v) use ($data, $user) {
            $v  ->required("email", "O e-mail é obrigatório.")
                ->email("email", "Endereço de email inválido.")
                ->required("password", "A password é obrigatória.")
                ->custom("email", function() use ($data, $user) {
                    return !$user || !password_verify($data["password"], $user["passwordHash"]);
                }, "Credenciais inválidas.");
        });
    }

    public function register(array $data): array {
        $profileType = $data["profileType"] = $data["profileType"] ?? "cliente";

        if ($profileType === "cliente") {
            return $this->customerService->createCustomer($data);
        }

        Session::requireProfileApi(["gestor"]);

        if ($profileType === "funcionario") {
            return $this->employeeService->createEmployee($data);
        } else {
            return $this->managerService->createManager($data);
        }
    }

    public function authenticateLogin(array $data): array {
        $user = $this->userRepository->find(null, $data["email"]);

        $this->validateLoginInput($data, $user);

        // G-02: o funcionário leva o tipo de contrato para a sessão (os métodos do
        // Session passam a poder distingui-lo — RV vs efetivo).
        if (($user["profileType"] ?? null) === "funcionario") {
            $employee = $this->employeeRepository->find((int)$user["id"]);
            $user["contractType"] = is_array($employee) ? ($employee["contractType"] ?? null) : null;
        }

        Session::createLoginSession($user);

        // F6 (§9.3): ponto único e previsível — garante que o sistema se atualiza
        // pelo menos uma vez por sessão (o guard de tempo evita repetições).
        (new MaintenanceService())->runIfDue();

        return [
            "message" => "Login efetuado com sucesso!",
            "user" => [
                "name"        => $user["name"],
                "email"       => $user["email"],
                "profileType" => $user["profileType"]
            ]
        ];
    }

    public function terminateSession(): array {
        Session::destroy();
        return [
            "message" => "Sessão terminada com sucesso!"
        ];
    }
}
