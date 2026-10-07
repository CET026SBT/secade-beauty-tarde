<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/BookingService.php";
require_once APP_PATH . "/services/ExecutionService.php";
require_once APP_PATH . "/services/ReconciliationService.php";
require_once APP_PATH . "/services/MaintenanceService.php";

/**
 * Backoffice (perfil gestor): gestão de agendamentos.
 */
class AdminController extends BaseController {
    private BookingService $bookingService;

    public function __construct() {
        $this->bookingService = new BookingService();
    }

    public function appointmentsList(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->bookingService->listBookings($_GET);
    }

    /**
     * Detalhe por serviço/funcionário (Fase 4 — visão do gestor).
     */
    public function appointmentDetails(): array {
        Session::requireProfileApi(["gestor"]);

        $bookingId = (int)($_GET["bookingId"] ?? $_GET["id"] ?? 0);

        if ($bookingId <= 0) {
            throw new Exception("Identificador de agendamento inválido.", 422);
        }

        $executionService = new ExecutionService();
        return $executionService->findBookingDetailForAdmin($bookingId);
    }

    /**
     * Registo da execução de um agendamento (pré-requisito do feedback).
     */
    public function appointmentExecute(): array {
        Session::requireProfileApi(["gestor"]);

        $data = $this->getRequestData();
        $bookingId = (int)($data["bookingId"] ?? $data["id"] ?? 0);

        if ($bookingId <= 0) {
            throw new Exception("Identificador de agendamento inválido.", 422);
        }

        $executionService = new ExecutionService();
        return $executionService->registerExecution($bookingId, $data);
    }

    public function appointmentCancel(): array {
        Session::requireProfileApi(["gestor"]);

        $data = $this->getRequestData();
        $bookingId = (int)($data["bookingId"] ?? $data["id"] ?? 0);

        if ($bookingId <= 0) {
            throw new Exception("Identificador de agendamento inválido.", 422);
        }

        return $this->bookingService->cancelBooking($bookingId);
    }

    /**
     * F6 — manutenção sob pedido: reconciliação de estados (§8) + alertas fiscais
     * (§9.3). Normalmente corre no login; este endpoint permite ao gestor
     * forçá-la (útil nas demonstrações).
     */
    public function reconcile(): array {
        Session::requireProfileApi(["gestor"]);
        return (new MaintenanceService())->runIfDue(true);
    }

    /** F6 — modo seco (D11): mostra o que a reconciliação mudaria, sem escrever. */
    public function reconcilePreview(): array {
        Session::requireProfileApi(["gestor"]);
        return (new ReconciliationService())->preview();
    }
}