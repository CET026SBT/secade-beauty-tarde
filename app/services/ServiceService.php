<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/ServiceRepository.php";
require_once APP_PATH . "/mappers/ServiceMapper.php";

class ServiceService extends BaseService {
    private ServiceRepository $serviceRepository;

    public function __construct() {
        parent::__construct();
        $this->serviceRepository = new ServiceRepository();
    }

    public function listServices(array $queryParams=[]): array {
        $filters = [
            'execution_type' => $queryParams['execution'] ?? null,
            'min_price'      => isset($queryParams['min_price']) ? (float)$queryParams['min_price'] : null,
            'max_price'      => isset($queryParams['max_price']) ? (float)$queryParams['max_price'] : null,
            'category_id'    => isset($queryParams['category_id']) ? (int)$queryParams['category_id'] : null,
            'search'         => $queryParams['q'] ?? null,
            'max_duration'   => isset($queryParams['max_duration']) ? (int)$queryParams['max_duration'] : null,
        ];

        if ($filters['category_id'] !== null && $filters['category_id'] <= 0) {
            throw new InvalidArgumentException("Identificador de categoria inválido.");
        }

        $rawServices = $this->serviceRepository->search($filters);

        $mappedServices = ServiceMapper::map($rawServices);

        return [
            "total" => count($mappedServices),
            "services" => $mappedServices
        ];
    }
}
