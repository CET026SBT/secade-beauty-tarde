<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/ManagerRepository.php";
require_once APP_PATH . "/services/UserService.php";

class ManagerService extends BaseService {
    private ManagerRepository $managerRepository;
    private UserService $userService;

    public function __construct() {
        parent::__construct();
        $this->managerRepository = new ManagerRepository();
        $this->userService = new UserService();
    }

    /**
     * Cria o gestor reutilizando o caminho de criação de utilizador (UserService).
     *
     * Antes existia um `ManagerRepository::createWithUser()` com o seu PRÓPRIO
     * `INSERT INTO utilizador` e o `password_hash()` dentro do repository — o único
     * ponto de escrita duplicado na tabela `utilizador` e o único *hashing* fora da
     * camada de serviço. Passou a ser composição: utilizador (validado, com a política
     * de *hashing* num só sítio) → registo de domínio.
     */
    public function createManager(array $data): array {
        return $this->executeTransactional(function() use ($data) {
            $data["profileType"] = "gestor";

            $userResult = $this->userService->createUser($data);

            return [
                "id" => $userResult["userId"],
                "message" => "Gestor registado com sucesso!"
            ];
        });
    }

    public function findManager(int $managerId): ?array {
        return $this->managerRepository->find($managerId);
    }
}
