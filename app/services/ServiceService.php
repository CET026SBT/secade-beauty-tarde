<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/ServiceRepository.php";

class ServiceService extends BaseService {
    private ServiceRepository $serviceRepository;

    public function __construct() {
        parent::__construct();
        $this->serviceRepository = new ServiceRepository();
    }

    public function findByCategory(int $categoryId): array {
        if ($categoryId <= 0) {
            throw new Exception("Categoria inválida.");
        }

        $dados = $this->serviceRepository->findByCategory($categoryId);

        return [
            "data" => $dados
        ];
    }
}
