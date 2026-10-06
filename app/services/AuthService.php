<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/UserRepository.php";
require_once APP_PATH . "/services/CustomerService.php";
require_once APP_PATH . "/services/EmployeeService.php";
require_once APP_PATH . "/services/ManagerService.php";

class AuthService extends BaseService {
    private UserRepository $userRepository;
    private CustomerService $customerService;
    private EmployeeService $employeeService;
    private ManagerService $managerService;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
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

        Session::createLoginSession($user);

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
