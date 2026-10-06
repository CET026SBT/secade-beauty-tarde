<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/AlertService.php";

/**
 * Fase 6.0 — Avisos por perfil (RF-81 · D-15).
 *
 * O sino (`admin-alert-summary`) é pedido em qualquer página do backoffice;
 * a página `/gestao/avisos` usa `admin-alert-list`.
 */
class AlertController extends BaseController {
    private AlertService $alertService;

    public function __construct() {
        $this->alertService = new AlertService();
    }

    public function summary(): array {
        Session::requireProfileApi(["gestor", "funcionario"]);
        return ["count" => $this->alertService->count()];
    }

    public function list(): array {
        Session::requireProfileApi(["gestor", "funcionario"]);
        return $this->alertService->list();
    }

    /**
     * Marcar como lidos é do gestor: os avisos fiscais são os únicos com estado de leitura.
     */
    public function markRead(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->alertService->markAllAsRead();
    }
}