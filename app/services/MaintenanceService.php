<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/MaintenanceRepository.php";
require_once APP_PATH . "/repositories/BookingRepository.php";
require_once APP_PATH . "/repositories/RotaRepository.php";
require_once APP_PATH . "/repositories/NotificationRepository.php";
require_once __DIR__ . "/FiscalService.php";

/**
 * Serviço de manutenção (F6 · §8 · §9.3).
 *
 * Faz o trabalho periódico **sem CRON**, com duas tarefas:
 *   1. `reconcileStates()`      — R1a/R1b/R2/R3/R4 (§8.2)
 *   2. `generateFiscalAlerts()` — os alertas do calendário fiscal
 *
 * Corre **a pedido** (no login e nas leituras relevantes) e tem um **guard de
 * tempo** que impede repetir antes de `MIN_INTERVAL_MINUTES`. É idempotente:
 * repetir não duplica nada.
 */
class MaintenanceService extends BaseService {

    private const MIN_INTERVAL_MINUTES = 5;
    private const KEY = "manutencao";

    /** R1a (RF-58/RN-24): a auto-recusa acontece 24 h antes da execução. */
    private const CUTOFF_HOURS = 24;

    private MaintenanceRepository $maintenanceRepository;
    private BookingRepository $bookingRepository;
    private RotaRepository $rotaRepository;
    private NotificationRepository $notificationRepository;
    private FiscalService $fiscalService;

    public function __construct() {
        parent::__construct();
        $this->maintenanceRepository  = new MaintenanceRepository();
        $this->bookingRepository      = new BookingRepository();
        $this->rotaRepository         = new RotaRepository();
        $this->notificationRepository = new NotificationRepository();
        $this->fiscalService          = new FiscalService();
    }

    /**
     * Corre se já passou o intervalo mínimo. Nunca lança: a manutenção não pode
     * partir a página que a invocou.
     */
    public function runIfDue(): array {
        $lastRun = $this->maintenanceRepository->lastRun(self::KEY);

        if ($lastRun !== null) {
            $elapsed = (time() - strtotime($lastRun)) / 60;
            if ($elapsed < self::MIN_INTERVAL_MINUTES) {
                return ["ran" => false, "reason" => "guard", "lastRun" => $lastRun];
            }
        }

        return $this->run();
    }

    /** Corre as duas tarefas e regista a execução. */
    public function run(): array {
        $result = ["ran" => true, "reconciliation" => [], "fiscalAlertsCreated" => 0];

        try {
            $result["reconciliation"] = $this->executeTransactional(fn() => $this->reconcileStates());
            $result["fiscalAlertsCreated"] = $this->fiscalService->generateAlerts();

            $this->maintenanceRepository->markRun(self::KEY, date("Y-m-d H:i:s"));
        } catch (Exception $e) {
            // Falha silenciosa: o utilizador não pode ser bloqueado por isto.
            $result["ran"] = false;
            $result["error"] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Reconciliação de estados (§8.2 · C-13 · 6.1).
     *
     * - **R1a** `NOW() > início` e o agendamento **ainda não está em rota** → `recusado`
     * - **R1b** `NOW() > início` e o estado é `confirmado` → aviso ao funcionário
     * - **R2**  `NOW() > fim + 4 h` e o estado é `executado` → `concluido`
     * - **R3**  todos os filhos `concluido` → rota `concluida`
     * - **R4**  todos os filhos `recusado` → rota `recusada`
     */
    public function reconcileStates(): array {
        $now = date("Y-m-d H:i:s");

        // R1a (RF-59/RN-25): 24 h antes da execução, sem rota confirmada, o
        // agendamento é auto-recusado — e o cliente é avisado (C-14).
        $refusedRows = $this->bookingRepository->refuseWithoutRouteAtCutoff($now, self::CUTOFF_HOURS);
        $refusedNotices = $this->notifyCustomers($refusedRows, "agendamento_recusado", function() {
            return "A sua marcação não conseguiu ser incluída numa rota a tempo. Nenhum valor lhe será cobrado.";
        });

        // RF-13: lembrete 24 h com alternativas, para marcações confirmadas.
        $reminders = $this->remindConfirmedWithin24Hours($now);

        $notifiedConfirmed = $this->notifyConfirmedStarted();
        $completed = $this->bookingRepository->completeExecutedAfterWindow($now, 4);
        $routesClosed = $this->closeRoutesByChildren();

        return [
            "refusedWithoutRoute"    => count($refusedRows),
            "refusedNotices"         => $refusedNotices,
            "reminders24h"           => $reminders,
            "confirmedAlertsCreated" => $notifiedConfirmed,
            "completedAfterWindow"   => $completed,
            "routesClosed"           => $routesClosed
        ];
    }

    /**
     * RF-13: lembrete 24 h para marcações confirmadas, com **alternativas** de data
     * (o compromisso de `aboutCommitment.php`). Não se repete: a ausência de aviso
     * anterior é o critério.
     */
    private function remindConfirmedWithin24Hours(string $now): int {
        $created = 0;

        foreach ($this->bookingRepository->findConfirmedWithin24Hours($now) as $booking) {
            $alternatives = $this->bookingRepository->findAlternativeSlots($booking);

            $dates = array_map(
                fn($row) => date("d/m/Y", strtotime((string)$row["data_alt"])),
                $alternatives
            );

            $message = "Agendamento #" . (int)$booking["id"] . " é a "
                . date("d/m/Y H:i", strtotime((string)$booking["data_hora_pretendida"])) . ".";

            if (!empty($dates)) {
                $message .= " Se precisar de alterar, temos disponibilidade em: " . implode(", ", $dates) . ".";
            } else {
                $message .= " Se precisar de alterar, contacte-nos.";
            }

            $created += $this->notificationRepository->create(
                (int)$booking["cliente_id"],
                "lembrete_24h",
                $message
            );
        }

        return $created;
    }

    /** Avisa os clientes das linhas afetadas por uma decisão automática (C-14). */
    private function notifyCustomers(array $rows, string $type, callable $messageFor): int {
        $created = 0;

        foreach ($rows as $row) {
            if (empty($row["cliente_id"])) continue;

            $created += $this->notificationRepository->create(
                (int)$row["cliente_id"],
                $type,
                "Agendamento #" . (int)$row["id"] . " " . $messageFor($row)
            );
        }

        return $created;
    }

    /** R1b: avisa o(s) funcionário(s) de serviços já começados que continuam abertos. */
    private function notifyConfirmedStarted(): int {
        $started = $this->bookingRepository->findConfirmedStarted(date("Y-m-d H:i:s"));
        $created = 0;

        foreach ($started as $booking) {
            foreach ($this->bookingRepository->findAmbulatoryBookingEmployees((int)$booking["id"]) as $employeeId) {
                $created += $this->notificationRepository->create(
                    (int)$employeeId,
                    "sistema",
                    "O agendamento #" . (int)$booking["id"] . " já começou. Registe o desfecho (executado/concluído)."
                );
            }
        }

        return $created;
    }

    /** R3/R4: fecha as rotas cujo destino já está determinado pelos filhos. */
    private function closeRoutesByChildren(): int {
        $closed = 0;

        foreach ($this->rotaRepository->listOpen() as $route) {
            $states = $this->bookingRepository->childStatesOfRoute((int)$route["id"]);

            if (empty($states)) {
                continue;
            }

            $allDone    = !array_diff($states, ["concluido"]);
            $allRefused = !array_diff($states, ["recusado"]);

            if ($allDone) {
                $this->rotaRepository->updateEstado((int)$route["id"], "concluida");
                $closed++;
            } elseif ($allRefused) {
                $this->rotaRepository->updateEstado((int)$route["id"], "recusada");
                $closed++;
            }
        }

        return $closed;
    }
}