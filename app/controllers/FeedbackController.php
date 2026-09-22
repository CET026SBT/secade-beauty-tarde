<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/FeedbackService.php";

/**
 * Fase 2/4 — Feedback do cliente e listagem pública.
 */
class FeedbackController extends BaseController {
    private FeedbackService $feedbackService;

    public function __construct() {
        $this->feedbackService = new FeedbackService();
    }

    /** Público (Main): feedback recente para a página inicial. */
    public function publicList(): array {
        $limit = min(20, max(1, (int)($_GET["limit"] ?? 6)));
        return $this->feedbackService->findPublicFeedback($limit);
    }

    /** Cliente autenticado: estado do feedback dos seus agendamentos executados. */
    public function myState(): array {
        $customerId = $this->requireCustomer();
        return $this->feedbackService->findCustomerFeedbackState($customerId);
    }

    /** Cliente autenticado: criar avaliação de um agendamento executado. */
    public function create(): array {
        $customerId = $this->requireCustomer();
        return $this->feedbackService->createFeedback($customerId, $this->getRequestData());
    }
}