<?php

require_once __DIR__ . "/BaseService.php";
require_once __DIR__ . "/GreenReceiptService.php";
require_once APP_PATH . "/repositories/BookingServiceRepository.php";
require_once APP_PATH . "/repositories/BookingRepository.php";
require_once APP_PATH . "/repositories/CategoryRepository.php";
require_once APP_PATH . "/utils/Session.php";

/**
 * Fase 3 — Aceitação individual de serviços de ambulatório pelos funcionários.
 *
 * Regras de negócio (especificacao_mvp.md §10):
 *   - Aceitação serviço a serviço (categorias são apenas filtros visuais).
 *   - Desfazer/trocar permitido ENQUANTO o agendamento não estiver consolidado.
 *   - O último serviço aceite consolida o agendamento ('totalmente_aceite_funcionarios')
 *     e bloqueia a janela temporal para novos agendamentos concorrentes.
 *   - Na aceitação corre o simulador de recibos verdes (percentagens em vigor).
 */
class ServiceAcceptanceService extends BaseService {

    private const CONSOLIDATED_STATE = "totalmente_aceite_funcionarios";

    private BookingServiceRepository $bookingServiceRepository;
    private BookingRepository $bookingRepository;
    private CategoryRepository $categoryRepository;
    private GreenReceiptService $greenReceiptService;

    public function __construct() {
        parent::__construct();
        $this->bookingServiceRepository = new BookingServiceRepository();
        $this->bookingRepository = new BookingRepository();
        $this->categoryRepository = new CategoryRepository();
        $this->greenReceiptService = new GreenReceiptService();
    }

    // ------------------------------------------------------------------
    // Listagens
    // ------------------------------------------------------------------

    /**
     * Serviços de ambulatório por aceitar (filtros por categoria são visuais).
     */
    public function listPendingServices(array $filters = []): array {
        $services = $this->bookingServiceRepository->findPending($filters);

        return [
            "services"   => $services,
            "categories" => $this->categoryRepository->find(),
            "config"     => $this->greenReceiptService->findActiveConfig()
        ];
    }

    /**
     * Serviços já aceites pelo funcionário autenticado.
     */
    public function listAcceptedServices(int $employeeId, array $filters = []): array {
        $services = $this->bookingServiceRepository->findAcceptedByEmployee($employeeId, $filters);

        $totalEmployee = 0.0;
        $totalPlatform = 0.0;

        foreach ($services as $service) {
            $totalEmployee += (float)($service["valor_recibo_verde_funcionario"] ?? 0);
            $totalPlatform += (float)($service["valor_recibo_verde_plataforma"] ?? 0);
        }

        return [
            "services" => $services,
            "totals"   => [
                "employee" => round($totalEmployee, 2),
                "platform" => round($totalPlatform, 2),
                "count"    => count($services)
            ],
            "config"   => $this->greenReceiptService->findActiveConfig()
        ];
    }

    // ------------------------------------------------------------------
    // Operações
    // ------------------------------------------------------------------

    /**
     * Aceita um serviço individualmente (com recibo verde simulado).
     * Aceitar um serviço já aceito por outro funcionário equivale a TROCAR.
     */
    public function acceptService(int $employeeId, int $bookingServiceId, ?int $bookingId = null): array {
        return $this->executeTransactional(function() use ($employeeId, $bookingServiceId, $bookingId) {
            $service = $this->resolveService($bookingServiceId, $bookingId);
            $booking = $this->requireBooking((int)$service["bookingId"]);

            $this->assertAcceptableBooking($booking);

            $percentage = $this->greenReceiptService->resolveEmployeePercentage();
            $isSwap = !empty($service["employeeId"]) && (int)$service["employeeId"] !== $employeeId;

            $this->bookingServiceRepository->accept($bookingServiceId, $employeeId, $percentage);

            $consolidated = $this->consolidateIfComplete((int)$booking["id"]);

            return [
                "bookingServiceId" => $bookingServiceId,
                "bookingId"        => (int)$booking["id"],
                "isSwap"           => $isSwap,
                "consolidated"     => $consolidated,
                "greenReceipt"     => $this->greenReceiptService->simulate((float)$service["price"], $percentage),
                "message"          => $isSwap
                    ? "Serviço transferido para si com sucesso."
                    : "Serviço aceite com sucesso."
            ];
        });
    }

    /**
     * Desfaz a aceitação de um serviço (bloqueado se o agendamento estiver consolidado).
     */
    public function unacceptService(int $employeeId, int $bookingServiceId, ?int $bookingId = null): array {
        return $this->executeTransactional(function() use ($employeeId, $bookingServiceId, $bookingId) {
            $service = $this->resolveService($bookingServiceId, $bookingId);
            $booking = $this->requireBooking((int)$service["bookingId"]);

            if ($booking["status"] === self::CONSOLIDATED_STATE) {
                throw new Exception(
                    "O agendamento já está totalmente aceite por funcionários e não permite desfazer nem trocar.",
                    409
                );
            }

            if ((int)($service["employeeId"] ?? 0) !== $employeeId) {
                throw new Exception("Só pode desfazer serviços que aceitou.", 403);
            }

            $this->bookingServiceRepository->unaccept($bookingServiceId);

            return [
                "bookingServiceId" => $bookingServiceId,
                "bookingId"        => (int)$booking["id"],
                "message"          => "Aceitação desfeita. O serviço voltou a ficar pendente."
            ];
        });
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    private function resolveService(int $bookingServiceId, ?int $bookingId): array {
        $service = $bookingId !== null
            ? $this->bookingServiceRepository->findByIdAndBooking($bookingServiceId, $bookingId)
            : $this->bookingServiceRepository->findById($bookingServiceId);

        if (!$service) {
            throw new Exception("Serviço de agendamento não encontrado.", 404);
        }

        return $service;
    }

    private function requireBooking(int $bookingId): array {
        $booking = $this->bookingRepository->find($bookingId);

        if (!$booking) {
            throw new Exception("Agendamento não encontrado.", 404);
        }

        return $booking;
    }

    private function assertAcceptableBooking(array $booking): void {
        if ($booking["local"] !== "carrinha_ambulante") {
            throw new Exception("Apenas serviços de ambulatório requerem aceitação por funcionários.", 409);
        }

        if (in_array($booking["status"], ["cancelado", "recusado", "executado", "concluido"], true)) {
            throw new Exception("Este agendamento já não está disponível para aceitação (estado: {$booking['status']}).", 409);
        }
    }

    /**
     * Se já não existirem serviços pendentes, consolida o agendamento
     * (bloqueando a janela temporal para concorrência).
     */
    private function consolidateIfComplete(int $bookingId): bool {
        if ($this->bookingServiceRepository->countPendingByBooking($bookingId) > 0) {
            return false;
        }

        $this->assertNoWindowConflict($bookingId);
        $this->bookingRepository->updateEstado($bookingId, self::CONSOLIDATED_STATE);

        return true;
    }

    /**
     * Bloqueio da janela temporal: ao consolidar, não pode existir outro
     * agendamento de ambulatório consolidado/confirmado a sobrepor-se.
     */
    private function assertNoWindowConflict(int $bookingId): void {
        $booking = $this->bookingRepository->find($bookingId);
        if (!$booking) return;

        $duration = $this->bookingServiceRepository->totalDurationByBooking($bookingId);

        $conflicts = $this->bookingRepository->countByDateWindow(
            $booking["dateTime"],
            max($duration, 1),
            "carrinha_ambulante",
            [$bookingId]
        );

        if ($conflicts > 0) {
            throw new Exception(
                "Existe outro agendamento consolidado na mesma janela temporal. Consolidação bloqueada.",
                409
            );
        }
    }
}