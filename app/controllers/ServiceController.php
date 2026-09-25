<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/ServiceAcceptanceService.php";

/**
 * Fase 3 — Backoffice do FUNCIONÁRIO: listagem, aceitação individual,
 * desfazer e troca de serviços de ambulatório.
 *
 * Todas as operações são scope-aware: o funcionário só opera em nome próprio.
 */
class ServiceController extends BaseController {
    private ServiceAcceptanceService $acceptanceService;

    public function __construct() {
        $this->acceptanceService = new ServiceAcceptanceService();
    }

    public function pendingList(): array {
        Session::requireProfileApi(["funcionario"]);
        return $this->acceptanceService->listPendingServices($_GET);
    }

    public function acceptedList(): array {
        Session::requireProfileApi(["funcionario"]);
        return $this->acceptanceService->listAcceptedServices(Session::userId(), $_GET);
    }

    public function accept(): array {
        Session::requireProfileApi(["funcionario"]);
        [$serviceId, $bookingId] = $this->extractTarget();

        return $this->acceptanceService->acceptService(Session::userId(), $serviceId, $bookingId);
    }

    public function unaccept(): array {
        Session::requireProfileApi(["funcionario"]);
        [$serviceId, $bookingId] = $this->extractTarget();

        return $this->acceptanceService->unacceptService(Session::userId(), $serviceId, $bookingId);
    }

    private function extractTarget(): array {
        $data = $this->getRequestData();

        $serviceId = (int)($data["bookingServiceId"] ?? $data["id"] ?? 0);
        $bookingId = !empty($data["bookingId"]) ? (int)$data["bookingId"] : null;

        if ($serviceId <= 0) {
            throw new Exception("Identificador do serviço de agendamento inválido.", 422);
        }

        return [$serviceId, $bookingId];
    }
}