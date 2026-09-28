<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/EmployeeAgendaService.php";

/**
 * Fase 6.5 — Agenda do FUNCIONÁRIO (`/gestao/agenda`, RF-78 · RN-33).
 *
 * O funcionário só vê a sua própria agenda: o id sai da sessão, nunca do pedido.
 */
class AgendaController extends BaseController {
    private EmployeeAgendaService $agendaService;

    public function __construct() {
        $this->agendaService = new EmployeeAgendaService();
    }

    public function month(): array {
        Session::requireProfileApi(["funcionario"]);
        return $this->agendaService->findMonth(Session::userId(), $_GET);
    }
}