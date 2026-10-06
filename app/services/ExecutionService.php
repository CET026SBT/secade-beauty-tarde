<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/ExecutionRepository.php";
require_once APP_PATH . "/repositories/BookingRepository.php";
require_once APP_PATH . "/repositories/BookingServiceRepository.php";
require_once APP_PATH . "/repositories/RotaRepository.php";

/**
 * Registo da execução real de um agendamento (pré-requisito do feedback do cliente).
 * Concluir um agendamento cria a linha em `execucao_agendamento` e passa
 * o agendamento a 'executado'.
 */
class ExecutionService extends BaseService {

    private const EXECUTABLE_STATES = ["totalmente_aceite_funcionarios", "confirmado", "pendente_validacao_logistica_loja"];

    private ExecutionRepository $executionRepository;
    private BookingRepository $bookingRepository;
    private BookingServiceRepository $bookingServiceRepository;
    private RotaRepository $rotaRepository;

    public function __construct() {
        parent::__construct();
        $this->executionRepository = new ExecutionRepository();
        $this->bookingRepository = new BookingRepository();
        $this->bookingServiceRepository = new BookingServiceRepository();
        $this->rotaRepository = new RotaRepository();
    }

    /**
     * Regista a execução do agendamento. Idempotente: se já existir, devolve a existente.
     */
    public function registerExecution(int $bookingId, array $data = []): array {
        return $this->executeTransactional(function() use ($bookingId, $data) {
            $booking = $this->bookingRepository->find($bookingId);

            if (!$booking) {
                throw new Exception("Agendamento não encontrado.", 404);
            }

            $existing = $this->executionRepository->findByBooking($bookingId);

            if ($existing) {
                return [
                    "executionId" => (int)$existing["id"],
                    "bookingId"   => $bookingId,
                    "alreadyRegistered" => true,
                    "message"     => "A execução deste agendamento já se encontra registada."
                ];
            }

            if (!in_array($booking["status"], self::EXECUTABLE_STATES, true)) {
                throw new Exception(
                    "Este agendamento não pode ser executado (estado atual: {$booking['status']}).",
                    409
                );
            }

            $routeId = $this->resolveRouteId($booking, $data);

            $executionId = $this->executionRepository->create(
                $bookingId,
                $routeId,
                $data["executionStatus"] ?? "concluido",
                $data["technicianNotes"] ?? null
            );

            $this->bookingRepository->updateEstado($bookingId, "executado");

            return [
                "executionId" => $executionId,
                "bookingId"   => $bookingId,
                "routeId"     => $routeId,
                "message"     => "Execução registada. O cliente já pode avaliar o serviço."
            ];
        });
    }

    /**
     * Detalhe por serviço/funcionário (Fase 4 — visão do gestor).
     */
    public function findBookingDetailForAdmin(int $bookingId): array {
        $booking = $this->bookingRepository->findDetailed($bookingId);

        if (!$booking) {
            throw new Exception("Agendamento não encontrado.", 404);
        }

        $services = $this->bookingServiceRepository->findByBooking($bookingId);
        $byState  = $this->bookingServiceRepository->countByBookingGroupedByState($bookingId);

        $stateCounts = ["pendente" => 0, "aceite" => 0];
        foreach ($byState as $row) {
            $stateCounts[$row["estado_aceitacao"]] = (int)$row["total"];
        }

        return [
            "booking"   => $booking,
            "services"  => $services,
            "execution" => $this->executionRepository->findByBooking($bookingId),
            "progress"  => [
                "total"    => array_sum($stateCounts),
                "pending"  => $stateCounts["pendente"] ?? 0,
                "accepted" => $stateCounts["aceite"] ?? 0,
                "isConsolidated" => $booking["status"] === "totalmente_aceite_funcionarios"
            ]
        ];
    }

    private function resolveRouteId(array $booking, array $data): ?int {
        if (!empty($data["routeId"])) {
            return (int)$data["routeId"];
        }

        if ($booking["local"] !== "carrinha_ambulante" || empty($booking["cityId"])) {
            return null;
        }

        $route = $this->rotaRepository->findByDateAndCity(
            date("Y-m-d", strtotime($booking["dateTime"])),
            (int)$booking["cityId"]
        );

        return $route ? (int)$route["id"] : null;
    }
}