<?php

require_once __DIR__ . '/BaseService.php';
require_once APP_PATH . '/repositories/UserRepository.php';
require_once APP_PATH . '/utils/Session.php';

class AuthService extends BaseService {
    private $userRepository;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
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