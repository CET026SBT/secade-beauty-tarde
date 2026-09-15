<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/CityService.php";

class CityController extends BaseController {
    private CityService $cityService;

    public function __construct() {
        $this->cityService = new CityService();
    }

    public function getSupportedCities(): array {
        return $this->cityService->fetchSupportedCities();
    }
}
