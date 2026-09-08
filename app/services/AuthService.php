<?php

require_once __DIR__ . '/BaseService.php';
require_once APP_PATH . '/repositories/UserRepository.php';
require_once APP_PATH . '/services/CustomerService.php';
require_once APP_PATH . '/services/EmployeeService.php';
require_once APP_PATH . '/services/ManagerService.php';

class AuthService extends BaseService {
    private $userRepository;
    private $customerService;
    private $employeeService;
    private $managerService;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
        $this->customerService = new CustomerService();
        $this->employeeService = new EmployeeService();
        $this->managerService = new ManagerService();
    }

    public function validateLoginInput(array $data, ?array $user): void {
        $this->validate($data, function($v) use ($data, $user) {
            $v  ->required('email', 'O e-mail é obrigatório.')
                ->email('email', 'Endereço de email inválido.')
                ->required('password', 'A password é obrigatória.')
                ->custom('email', function() use ($data, $user) {
                    return !$user || !password_verify($data['password'], $user['password_hash']);
                }, 'Credenciais inválidas.');
        });
    }

    public function register(array $data) {
        $tipoPerfil = $data['tipoPerfil'] = $data['tipoPerfil'] ?? 'cliente';

        if ($tipoPerfil === 'cliente') {
            return $this->customerService->registerCustomer($data);
        }

        if (!Session::isLoggedIn() || !Session::isManager()) {
            throw new Exception("Não tem permissões para criar um registo com este tipo de perfil.", 403);
        }

        if ($tipoPerfil === 'funcionario') {
            return $this->employeeService->registerEmployee($data);
        } else {
            return $this->managerService->registerManager($data);
        }
    }

    public function login(array $data): array {
        $user = $this->userRepository->findByEmail($data['email']);

        $this->validateLoginInput($data, $user);

        Session::createLoginSession($user);

        return [
            'message' => 'Login efetuado com sucesso!',
            'user' => [
                'name'      => $user['nome'],
                'email'     => $user['email'],
                'profile'   => $user['tipo_perfil']
            ]
        ];
    }

    public function logout(): array {
        Session::destroy();
        return ['message' => 'Sessão terminada com sucesso!'];
    }
}


<?php

require_once __DIR__ . '/BaseService.php';
require_once APP_PATH . '/repositories/UserRepository.php';
require_once APP_PATH . '/services/CustomerService.php';
// Se tiveres um EmployeeService para criar funcionários, inclui-o aqui também

class AuthService extends BaseService {
    private $userRepository;
    private $customerService;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
        $this->customerService = new CustomerService();
    }

    public function register(array $data) {
        $tipoPerfil = $data['tipoPerfil'] ?? 'cliente';

        // Se o perfil a criar NÃO for cliente, exige controlo de permissões
        if ($tipoPerfil !== 'cliente') {
            
            // CONDIÇÃO HARDCODED / VERIFICAÇÃO DE SESSÃO
            // TODO: Substituir por verificação real da sessão (ex: Session::userIsManager() ou similar)
            $isAuthorizedAdminOrManager = false; // Hardcoded a false por segurança nesta fase

            if (!$isAuthorizedAdminOrManager) {
                throw new Exception("Não tem permissões para criar um registo com este tipo de perfil.", 403);
            }

            // TODO: Lógica para criar funcionário/gestor quando a sessão estiver ativa
            // return $this->employeeService->registerEmployee($data);
        }

        // Se for cliente, o fluxo normal que já tinhas continua a funcionar
        return $this->customerService->registerCustomer($data);
    }
}