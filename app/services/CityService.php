<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/CityRepository.php";

class CityService extends BaseService {
    private CityRepository $cityRepository;

    public function __construct() {
        parent::__construct();
        $this->cityRepository = new CityRepository();
    }

    public function findSupportedCities(): array {
        return [
            "cities" => $this->cityRepository->findAllNames()
        ];
    }
}
