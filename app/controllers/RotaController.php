<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/RotaService.php";

/**
 * Backoffice do GESTOR: rotas (Fase 4 — decisão manual).
 */
class RotaController extends BaseController {
    private RotaService $rotaService;

    public function __construct() {
        $this->rotaService = new RotaService();
    }

    public function routesList(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->rotaService->findRouteSummaries($_GET);
    }

    /**
     * Decisão MANUAL (aprovar/recusar) de uma rota dia+cidade.
     */
    public function decideRoute(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->rotaService->decideRoute($this->getRequestData());
    }
}