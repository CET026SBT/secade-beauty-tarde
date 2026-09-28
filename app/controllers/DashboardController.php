<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/DashboardService.php";

/**
 * Fase 6.0 — Painel do gestor (`/gestao/painel`).
 *
 * O funcionário é encaminhado para a sua agenda (D-14); o painel é do gestor.
 */
class DashboardController extends BaseController {
    private DashboardService $dashboardService;

    public function __construct() {
        $this->dashboardService = new DashboardService();
    }

    public function summary(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->dashboardService->summary();
    }
}