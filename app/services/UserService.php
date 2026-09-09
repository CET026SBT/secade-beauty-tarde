<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/UserRepository.php";

class UserService extends BaseService {
    private $userRepository;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
    }

    public function validateInput(array $data): void {
        $this->validate($data, function($v) {
            $v  ->required("nome", "O nome completo é obrigatório.")
                ->required("email", "O e-mail é obrigatório.")
                ->email("email", "Endereço de email inválido.")
                ->custom(
                    "email",
                    fn($email) => $this->userRepository->findByEmail($email),
                    "Este email já se encontra registado."
                )
                ->required("telemovel", "O número de telemóvel é obrigatório.")
                ->phone("telemovel", "Insira um número de telemóvel válido com 9 dígitos.")
                ->required("password", "A password é obrigatória.")
                ->password("password");
        });
    }

    public function createUser(array $data): int {
        $this->validateInput($data);

        $passwordHash = password_hash($data["password"], PASSWORD_BCRYPT);
        return $this->userRepository->create($data, $passwordHash);
    }
}
