<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/CommissionService.php";

/**
 * Fase 6.4 — Comissões (RF-84 · §25.5).
 *
 * Gestor e funcionário partilham a página; a abrangência é decidida pela sessão
 * dentro do Service (o funcionário só vê o que é dele).
 */
class CommissionController extends BaseController {
    private CommissionService $commissionService;

    public function __construct() {
        $this->commissionService = new CommissionService();
    }

    public function list(): array {
        Session::requireProfileApi(["gestor", "funcionario"]);
        return $this->commissionService->summary($_GET);
    }
}