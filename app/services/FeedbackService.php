<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/FeedbackRepository.php";
require_once APP_PATH . "/repositories/ExecutionRepository.php";
require_once APP_PATH . "/repositories/BookingRepository.php";

/**
 * Fase 2/4 — Feedback do cliente (restrição académica: sem moderação real,
 * o feedback é público assim que o serviço é executado).
 */
class FeedbackService extends BaseService {

    private FeedbackRepository $feedbackRepository;
    private ExecutionRepository $executionRepository;
    private BookingRepository $bookingRepository;

    public function __construct() {
        parent::__construct();
        $this->feedbackRepository = new FeedbackRepository();
        $this->executionRepository = new ExecutionRepository();
        $this->bookingRepository = new BookingRepository();
    }

    /**
     * Cria o feedback de um agendamento executado pelo próprio cliente.
     */
    public function createFeedback(int $customerId, array $data): array {
        return $this->executeTransactional(function() use ($customerId, $data) {
            $this->validate($data, function($v) {
                $v  ->required("bookingId", "O agendamento é obrigatório.")
                    ->required("rating", "A classificação é obrigatória.");
            });

            $bookingId = (int)$data["bookingId"];
            $rating    = (int)$data["rating"];

            if ($rating < 1 || $rating > 5) {
                throw new Exception("A classificação tem de estar entre 1 e 5 estrelas.", 422);
            }

            $booking = $this->bookingRepository->find($bookingId);

            if (!$booking) {
                throw new Exception("Agendamento não encontrado.", 404);
            }

            if ((int)$booking["customerId"] !== $customerId) {
                throw new Exception("Só pode avaliar os seus próprios agendamentos.", 403);
            }

            if (!in_array($booking["status"], ["executado", "concluido"], true)) {
                throw new Exception("Só é possível avaliar agendamentos já executados.", 409);
            }

            $execution = $this->executionRepository->findByBooking($bookingId);

            if (!$execution) {
                throw new Exception("A execução deste agendamento ainda não foi registada.", 409);
            }

            if ($this->feedbackRepository->findByBooking($bookingId)) {
                throw new Exception("Este agendamento já foi avaliado.", 409);
            }

            $feedbackId = $this->feedbackRepository->create(
                (int)$execution["id"],
                $rating,
                $data["comment"] ?? null
            );

            return [
                "feedbackId" => $feedbackId,
                "bookingId"  => $bookingId,
                "message"    => "Obrigado pela sua avaliação!"
            ];
        });
    }

    /**
     * Feedback público (Main) — usado na página inicial e no "ver feedback".
     */
    public function findPublicFeedback(int $limit = 6): array {
        $items = $this->feedbackRepository->recent($limit);

        $total = 0; $sum = 0;
        foreach ($items as $item) {
            $sum += (int)($item["rating"] ?? 0);
            $total++;
        }

        return [
            "feedback" => $items,
            "average"  => $total > 0 ? round($sum / $total, 1) : null,
            "count"    => $total
        ];
    }

    /**
     * Estado do feedback do cliente para os seus agendamentos executados.
     */
    public function findCustomerFeedbackState(int $customerId): array {
        $bookings = $this->bookingRepository->findByCustomer($customerId);

        $items = [];
        foreach ($bookings as $booking) {
            if (!in_array($booking["status"], ["executado", "concluido"], true)) continue;

            $items[] = [
                "bookingId" => $booking["id"],
                "dateTime"  => $booking["dateTime"],
                "local"     => $booking["local"],
                "feedback"  => $this->feedbackRepository->findByBooking((int)$booking["id"])
            ];
        }

        return ["items" => $items];
    }
}