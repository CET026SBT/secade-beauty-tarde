<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/StatsService.php";

class StatsController extends BaseController {
    private StatsService $statsService;

    public function __construct() {
        $this->statsService = new StatsService();
    }

    /**
     * Endpoint público (usado no site) — apenas números agregados, nenhum dado pessoal.
     */
    public function summary(): array {
        return $this->statsService->siteStats();
    }
}