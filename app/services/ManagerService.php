<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/ManagerRepository.php";

class ManagerService extends BaseService {
    private ManagerRepository $managerRepository;

    public function __construct() {
        parent::__construct();
        $this->managerRepository = new ManagerRepository();
    }

    public function createManager(array $data): array {
        return $this->executeTransactional(function() use ($data) {
            $data["profileType"] = "gestor";

            $userId = $this->managerRepository->createWithUser($data);

            return [
                "id" => $userId,
                "message" => "Gestor registado com sucesso!"
            ];
        });
    }

    public function findManager(int $managerId): ?array {
        return $this->managerRepository->find($managerId);
    }
}
