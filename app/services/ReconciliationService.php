<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/ReconciliationRepository.php";

/**
 * Reconciliação de estados — F6 (§8 · §12.3 · D1/D4/D5/D9/D11).
 *
 * Serviço **transacional e idempotente**: corre no início das leituras que
 * mostram estados ao utilizador (ver os hooks em `BookingService`,
 * `RotaService`, `EmployeeAgendaService`, `DashboardService`) e no
 * `MaintenanceService` (login). É **bottom-up** — netos → filhos → pai — para o
 * pai olhar sempre para filhos já reconciliados.
 *
 * Nada aqui decide ou bloqueia nada por indicador (§3.1): só **alinha o estado
 * guardado** com o que o tempo já determinou. O guard estático impede repetir a
 * operação mais de uma vez por pedido (a escrita em GET fica contida — §12.3).
 */
class ReconciliationService extends BaseService {

    /** R2 — horas de arrumação depois do fim/prazo antes de fechar executado→concluído. */
    private const HOURS_AFTER_END = 4;

    /** R1a — corte à frente da hora de início (§20.4). A F10 sobe este valor para 24. */
    private const R1A_CUTOFF_HOURS = 0;

    /** Guard de pedido: a reconciliação corre no máximo uma vez por request. */
    private static bool $done = false;

    private ReconciliationRepository $repository;

    public function __construct() {
        parent::__construct();
        $this->repository = new ReconciliationRepository();
    }

    /**
     * Reconcilia todos os níveis. Devolve o número de linhas afetadas por regra.
     * Idempotente: chamadas repetidas no mesmo pedido devolvem zeros (guard).
     * `$force = true` ignora o guard (usado pela manutenção e pelo harness de testes,
     * que simula vários pedidos no mesmo processo).
     */
    public function reconcile(bool $force = false): array {
        if (self::$done && !$force) {
            return ["skipped" => true];
        }
        self::$done = true;

        return $this->executeTransactional(function () {
            // Nível 2 (filhos) primeiro: netos não têm estado (D2).
            $r1 = $this->repository->cancelExpiredBookings(self::R1A_CUTOFF_HOURS);
            $r2 = $this->repository->completeExecutedBookings(self::HOURS_AFTER_END);

            // Nível 3 (pai) por último, já a olhar para filhos reconciliados.
            $r3 = $this->repository->completeRoutesWithAllChildrenDone();
            $r4 = $this->repository->refuseRoutesWithAllChildrenRefused();

            return [
                "refusedBookings"   => $r1,
                "completedBookings" => $r2,
                "completedRoutes"   => $r3,
                "refusedRoutes"     => $r4
            ];
        });
    }

    /** Modo seco (D11): mostra o que mudaria, sem escrever. */
    public function preview(): array {
        return $this->repository->countCandidates(self::HOURS_AFTER_END, self::R1A_CUTOFF_HOURS);
    }
}