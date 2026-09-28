<?php

require_once __DIR__ . "/BaseRepository.php";

/**
 * Indicadores do painel do gestor (`/gestao/painel`) — Fase 6.0 (§24.7).
 *
 * Todos os números saem da BD por agregação (nunca do Service, §18.1–§18.2).
 * Os resultados são agregados: não representam a entidade principal de nenhum
 * repository, pelo que usam `fetchAllRaw`/`fetchRaw` (sem mapper).
 */
class DashboardRepository extends BaseRepository {

    /**
     * Contagem de agendamentos por estado (exclui cancelados/recusados).
     */
    public function countBookingsByState(): array {
        $sql = "SELECT estado_reserva AS estado, COUNT(*) AS total
                FROM agendamento
                GROUP BY estado_reserva
                ORDER BY total DESC";

        return $this->fetchAllRaw($sql);
    }

    /**
     * Agendamentos e receita prevista entre duas datas (inclusive).
     */
    public function summarizeBetween(string $dateFrom, string $dateTo): array {
        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(valor_total), 0) AS receita_prevista,
                       SUM(estado_reserva IN ('confirmado','executado','concluido')) AS confirmados
                FROM agendamento
                WHERE DATE(data_hora_pretendida) BETWEEN :de AND :ate
                  AND estado_reserva NOT IN ('cancelado','recusado')";

        $row = $this->fetchRaw($sql, ["de" => $dateFrom, "ate" => $dateTo]);

        return [
            "total"           => (int)($row["total"] ?? 0),
            "confirmed"       => (int)($row["confirmados"] ?? 0),
            "expectedRevenue" => (float)($row["receita_prevista"] ?? 0)
        ];
    }

    /**
     * Agendamentos por dia (para o gráfico da semana) — só dias com marcações.
     */
    public function countBookingsPerDay(string $dateFrom, string $dateTo): array {
        $sql = "SELECT DATE(data_hora_pretendida) AS dia, COUNT(*) AS total
                FROM agendamento
                WHERE DATE(data_hora_pretendida) BETWEEN :de AND :ate
                  AND estado_reserva NOT IN ('cancelado','recusado')
                GROUP BY DATE(data_hora_pretendida)
                ORDER BY dia ASC";

        return $this->fetchAllRaw($sql, ["de" => $dateFrom, "ate" => $dateTo]);
    }

    /**
     * Carga de trabalho por estado de aceitação (serviços de ambulatório).
     */
    public function countServicesByAcceptance(): array {
        $sql = "SELECT s.estado_aceitacao AS estado, COUNT(*) AS total
                FROM agendamento_servico s
                INNER JOIN agendamento a ON s.agendamento_id = a.id
                WHERE a.estado_reserva NOT IN ('cancelado','recusado','executado','concluido')
                GROUP BY s.estado_aceitacao
                ORDER BY total DESC";

        return $this->fetchAllRaw($sql);
    }

    /**
     * Rotas ambulantes agregadas para o indicador do painel: quantas esperam decisão.
     */
    public function countRoutesAwaitingDecision(): int {
        $sql = "SELECT COUNT(*) AS total FROM rota_ambulante WHERE estado_rota = 'planeada'";
        $row = $this->fetchRaw($sql);

        return (int)($row["total"] ?? 0);
    }

    /**
     * Obrigações fiscais pendentes agrupadas por tipo — gráfico do painel.
     */
    public function sumFiscalByType(): array {
        $sql = "SELECT tipo,
                       COUNT(*) AS total,
                       COALESCE(SUM(valor_estimado), 0) AS valor,
                       MIN(data_prazo) AS proxima_data
                FROM obrigacao_fiscal
                WHERE estado = 'pendente'
                GROUP BY tipo";

        return $this->fetchAllRaw($sql);
    }

    /**
     * Contagens simples de apoio ao painel (uma consulta por tabela de lookup).
     */
    public function countActiveServices(): int {
        $row = $this->fetchRaw("SELECT COUNT(*) AS total FROM servico WHERE ativo = 1");
        return (int)($row["total"] ?? 0);
    }

    public function countActiveSuppliers(): int {
        $row = $this->fetchRaw("SELECT COUNT(*) AS total FROM fornecedor WHERE ativo = 1");
        return (int)($row["total"] ?? 0);
    }

    public function countCustomers(): int {
        $row = $this->fetchRaw("SELECT COUNT(*) AS total FROM cliente");
        return (int)($row["total"] ?? 0);
    }
}