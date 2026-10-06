<?php

require_once __DIR__ . "/BaseController.php";
require_once APP_PATH . "/services/ServiceAcceptanceService.php";

/**
 * Fase 3 — Backoffice de serviços de ambulatório: listagem, alocação/aceitação
 * individual, desfazer e troca.
 *
 * F4 (C-08): **gestor aloca** (escolhe o funcionário) e o **funcionário aceita os
 * seus**. O id do funcionário-alvo só pode vir do pedido quando quem pede é
 * gestor; o funcionário opera sempre em nome próprio (§18.6).
 */
class ServiceController extends BaseController {
    private ServiceAcceptanceService $acceptanceService;

    public function __construct() {
        $this->acceptanceService = new ServiceAcceptanceService();
    }

    public function pendingList(): array {
        Session::requireProfileApi(["gestor", "funcionario"]);
        return $this->acceptanceService->listPendingServices($_GET);
    }

    public function acceptedList(): array {
        Session::requireProfileApi(["gestor", "funcionario"]);

        // O gestor vê todas as alocações; o funcionário só as suas.
        $employeeId = Session::isManager() ? null : Session::userId();

        return $this->acceptanceService->listAcceptedServices($employeeId, $_GET);
    }

    public function accept(): array {
        Session::requireProfileApi(["gestor", "funcionario"]);
        [$serviceId, $bookingId] = $this->extractTarget();

        return $this->acceptanceService->acceptService($this->resolveTargetEmployee(), $serviceId, $bookingId);
    }

    public function unaccept(): array {
        Session::requireProfileApi(["gestor", "funcionario"]);
        [$serviceId, $bookingId] = $this->extractTarget();

        return $this->acceptanceService->unacceptService(
            $this->resolveTargetEmployee(),
            $serviceId,
            $bookingId,
            Session::isManager()
        );
    }

    /**
     * Funcionário-alvo da operação: o gestor escolhe (`employeeId` no pedido); o
     * funcionário é sempre ele próprio (o id sai da sessão, nunca do pedido).
     */
    private function resolveTargetEmployee(): int {
        if (!Session::isManager()) {
            return (int)Session::userId();
        }

        $data = $this->getRequestData();
        $employeeId = (int)($data["employeeId"] ?? 0);

        if ($employeeId <= 0) {
            throw new Exception("Escolha o funcionário a alocar.", 422);
        }

        return $employeeId;
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