<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/ServiceAcceptanceService.php";

/**
 * F4 — Backoffice de serviços de ambulatório (#servCarrinha).
 *
 * O **GESTOR** aloca (escolhe o funcionário efetivo); o **FUNCIONÁRIO (RV)** aceita
 * por si. Ambos os perfis acedem à listagem, com comportamento por perfil (C-08).
 */
class ServiceController extends BaseController {
    private ServiceAcceptanceService $acceptanceService;

    public function __construct() {
        $this->acceptanceService = new ServiceAcceptanceService();
    }

    public function pendingList(): array {
        Session::requireProfileApi(["funcionario", "gestor"]);

        $employeeId = Session::isEmployee() ? Session::userId() : null;

        return $this->acceptanceService->listPendingServices($_GET, Session::isManager(), $employeeId);
    }

    public function acceptedList(): array {
        Session::requireProfileApi(["funcionario", "gestor"]);

        // O funcionário só vê o que é seu; o gestor vê tudo.
        $employeeId = Session::isEmployee() ? Session::userId() : null;

        return $this->acceptanceService->listAcceptedServices($employeeId, $_GET);
    }

    /** ACEITAÇÃO pelo funcionário (RV). */
    public function accept(): array {
        Session::requireProfileApi(["funcionario"]);
        [$serviceId, $bookingId] = $this->extractTarget();

        return $this->acceptanceService->acceptService(Session::userId(), $serviceId, $bookingId);
    }

    /** ALOCAÇÃO pelo gestor: escolhe o funcionário efetivo (C-08). */
    public function assign(): array {
        Session::requireProfileApi(["gestor"]);
        [$serviceId, $bookingId] = $this->extractTarget();

        $employeeId = (int)($this->getRequestData()["employeeId"] ?? 0);

        return $this->acceptanceService->assignService(Session::userId(), $serviceId, $bookingId, $employeeId);
    }

    public function unaccept(): array {
        Session::requireProfileApi(["funcionario", "gestor"]);
        [$serviceId, $bookingId] = $this->extractTarget();

        $employeeId = Session::isEmployee() ? Session::userId() : null;

        return $this->acceptanceService->unacceptService($employeeId, $serviceId, $bookingId);
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
