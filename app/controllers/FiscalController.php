<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/FiscalService.php";
require_once APP_PATH . "/services/GreenReceiptService.php";

/**
 * Fase 4 — Backoffice do GESTOR: calendário fiscal, alertas e
 * configuração do simulador de recibos verdes.
 */
class FiscalController extends BaseController {
    private FiscalService $fiscalService;
    private GreenReceiptService $greenReceiptService;

    public function __construct() {
        $this->fiscalService = new FiscalService();
        $this->greenReceiptService = new GreenReceiptService();
    }

    public function calendar(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->fiscalService->findCalendar($_GET);
    }

    public function alerts(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->fiscalService->findAlerts();
    }

    public function createObligation(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->fiscalService->createObligation($this->getRequestData());
    }

    public function markPaid(): array {
        Session::requireProfileApi(["gestor"]);

        $data = $this->getRequestData();
        $obligationId = (int)($data["obligationId"] ?? $data["id"] ?? 0);

        if ($obligationId <= 0) {
            throw new Exception("Identificador de obrigação fiscal inválido.", 422);
        }

        return $this->fiscalService->markAsPaid($obligationId, $data);
    }

    public function markAlertsRead(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->fiscalService->markAlertsAsRead();
    }

    // ------------------------------------------------------------------
    // Simulador de Recibos Verdes (configuração)
    // ------------------------------------------------------------------

    public function greenReceiptConfig(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->greenReceiptService->findConfigHistory();
    }

    public function saveGreenReceiptConfig(): array {
        Session::requireProfileApi(["gestor"]);
        return $this->greenReceiptService->createConfig($this->getRequestData());
    }

    public function greenReceiptSimulate(): array {
        Session::requireProfileApi(["gestor"]);

        $amount = (float)($_GET["amount"] ?? 0);
        return ["simulation" => $this->greenReceiptService->simulate($amount)];
    }
}