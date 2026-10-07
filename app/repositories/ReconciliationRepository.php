<?php

require_once __DIR__ . "/BaseRepository.php";

/**
 * Reconciliação de estados — F6 (§8 · `relatorio_reconciliacao-estados.md`).
 *
 * SQL set-based: um `UPDATE` de **uma só tabela** por statement (§18.2), com as
 * condições sobre os filhos em `EXISTS`/`NOT EXISTS` (nunca JOIN de escrita). A
 * única fonte de tempo é o `NOW()` do MySQL (D9) — nada de ir e vir ao PHP.
 *
 * Estados (nomes já normalizados na F1):
 *   agendamento: pendente_alocacao · pendente_validacao_logistica_loja · totalmente_alocado ·
 *                confirmado · recusado · cancelado · executado · concluido
 *   rota:        planeada · aprovada · recusada · em_execucao · concluida
 */
class ReconciliationRepository extends BaseRepository {

    /** R1a — o tempo passou e o agendamento nunca entrou numa rota confirmada.
     *
     * `$hoursBeforeStart` é o corte à frente de `NOW()`: a F6 corre com 0 (o
     * agendamento só é recusado já depois da hora); a F10 (RF-59/RN-25 · §20.4)
     * sobe-o para 24 h, passando a auto-recusar **antes** de começar.
     */
    public function cancelExpiredBookings(int $hoursBeforeStart = 0): int {
        $hours = max(0, $hoursBeforeStart);

        $sql = "UPDATE agendamento
                SET estado_reserva = 'recusado'
                WHERE estado_reserva IN ('pendente_alocacao','pendente_validacao_logistica_loja','totalmente_alocado')
                  AND data_hora_pretendida < DATE_ADD(NOW(), INTERVAL {$hours} HOUR)";

        return $this->execute($sql);
    }

    /**
     * R2 — o agendamento foi executado e já passou o prazo de arrumação
     * (`GREATEST(fim planeado, fim real) + N horas`).
     */
    public function completeExecutedBookings(int $hoursAfterEnd): int {
        $hours = max(0, $hoursAfterEnd);

        $sql = "UPDATE agendamento a
                JOIN (SELECT agendamento_id, COALESCE(SUM(duracao_minutos),0) AS dur
                        FROM agendamento_servico GROUP BY agendamento_id) d
                  ON d.agendamento_id = a.id
                LEFT JOIN execucao_agendamento e ON e.agendamento_id = a.id
                SET a.estado_reserva = 'concluido'
                WHERE a.estado_reserva = 'executado'
                  AND DATE_ADD(
                        GREATEST(
                            DATE_ADD(a.data_hora_pretendida, INTERVAL d.dur MINUTE),
                            COALESCE(e.data_hora_fim_real, a.data_hora_pretendida)
                        ),
                        INTERVAL {$hours} HOUR
                      ) < NOW()";

        return $this->execute($sql);
    }

    /** R3 — rota concluída quando todos os filhos diretos estão concluídos (e há ≥ 1). */
    public function completeRoutesWithAllChildrenDone(): int {
        $sql = "UPDATE rota_ambulante r
                SET r.estado_rota = 'concluida'
                WHERE r.estado_rota IN ('aprovada','em_execucao')
                  AND EXISTS (
                        SELECT 1 FROM agendamento a
                        INNER JOIN cliente_morada cm ON cm.id = a.cliente_morada_id
                        WHERE a.local_prestacao = 'carrinha_ambulante'
                          AND DATE(a.data_hora_pretendida) = r.data_rota
                          AND cm.cidade_id = r.cidade_id
                      )
                  AND NOT EXISTS (
                        SELECT 1 FROM agendamento a
                        INNER JOIN cliente_morada cm ON cm.id = a.cliente_morada_id
                        WHERE a.local_prestacao = 'carrinha_ambulante'
                          AND DATE(a.data_hora_pretendida) = r.data_rota
                          AND cm.cidade_id = r.cidade_id
                          AND a.estado_reserva <> 'concluido'
                      )";

        return $this->execute($sql);
    }

    /** R4 — rota recusada quando todos os filhos diretos foram recusados (e há ≥ 1). */
    public function refuseRoutesWithAllChildrenRefused(): int {
        $sql = "UPDATE rota_ambulante r
                SET r.estado_rota = 'recusada'
                WHERE r.estado_rota IN ('planeada','aprovada')
                  AND EXISTS (
                        SELECT 1 FROM agendamento a
                        INNER JOIN cliente_morada cm ON cm.id = a.cliente_morada_id
                        WHERE a.local_prestacao = 'carrinha_ambulante'
                          AND DATE(a.data_hora_pretendida) = r.data_rota
                          AND cm.cidade_id = r.cidade_id
                      )
                  AND NOT EXISTS (
                        SELECT 1 FROM agendamento a
                        INNER JOIN cliente_morada cm ON cm.id = a.cliente_morada_id
                        WHERE a.local_prestacao = 'carrinha_ambulante'
                          AND DATE(a.data_hora_pretendida) = r.data_rota
                          AND cm.cidade_id = r.cidade_id
                          AND a.estado_reserva <> 'recusado'
                      )";

        return $this->execute($sql);
    }

    /** Modo seco (D11): conta candidatos sem alterar nada. */
    public function countCandidates(int $hoursAfterEnd, int $hoursBeforeStart = 0): array {
        $hours = max(0, $hoursAfterEnd);
        $cut   = max(0, $hoursBeforeStart);

        $toRefuse = (int)$this->db->query(
            "SELECT COUNT(*) FROM agendamento
             WHERE estado_reserva IN ('pendente_alocacao','pendente_validacao_logistica_loja','totalmente_alocado')
               AND data_hora_pretendida < DATE_ADD(NOW(), INTERVAL {$cut} HOUR)"
        )->fetchColumn();

        $toComplete = (int)$this->db->query(
            "SELECT COUNT(*) FROM agendamento a
             JOIN (SELECT agendamento_id, COALESCE(SUM(duracao_minutos),0) AS dur
                     FROM agendamento_servico GROUP BY agendamento_id) d ON d.agendamento_id = a.id
             LEFT JOIN execucao_agendamento e ON e.agendamento_id = a.id
             WHERE a.estado_reserva = 'executado'
               AND DATE_ADD(GREATEST(DATE_ADD(a.data_hora_pretendida, INTERVAL d.dur MINUTE),
                                     COALESCE(e.data_hora_fim_real, a.data_hora_pretendida)),
                            INTERVAL {$hours} HOUR) < NOW()"
        )->fetchColumn();

        return ["bookingsToRefuse" => $toRefuse, "bookingsToComplete" => $toComplete];
    }
}