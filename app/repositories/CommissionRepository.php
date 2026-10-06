<?php

require_once __DIR__ . "/BaseRepository.php";

/**
 * Comissões do funcionário — Fase 6.4 (RF-84 · §25.5 · §11).
 *
 * Os valores são **snapshot da aceitação**: `agendamento_servico` guarda
 * `percentagem_funcionario_aplicada`, `valor_recibo_verde_funcionario` e
 * `valor_recibo_verde_plataforma` no momento em que o serviço é aceite. Esta
 * camada só **lê** — nada é recalculado aqui (a percentagem pode mudar com o
 * tempo e o histórico não se reescreve).
 *
 * Leitura de relatório: agregações com `fetchAllRaw` (sem mapper).
 */
class CommissionRepository extends BaseRepository {

    /**
     * Totais por funcionário num intervalo de datas.
     */
    public function sumByEmployee(string $dateFrom, string $dateTo): array {
        $sql = "SELECT s.funcionario_id AS funcionario_id,
                       u.nome AS funcionario_nome,
                       COUNT(*) AS servicos,
                       COALESCE(SUM(s.preco_praticado), 0) AS valor_servicos,
                       COALESCE(SUM(s.valor_recibo_verde_funcionario), 0) AS valor_funcionario,
                       COALESCE(SUM(s.valor_recibo_verde_plataforma), 0) AS valor_plataforma,
                       COALESCE(AVG(s.percentagem_funcionario_aplicada), 0) AS percentagem_media
                FROM agendamento_servico s
                INNER JOIN utilizador u ON s.funcionario_id = u.id
                WHERE s.estado_aceitacao = 'aceite'
                  AND s.funcionario_id IS NOT NULL
                  AND DATE(s.aceito_em) BETWEEN :de AND :ate
                GROUP BY s.funcionario_id, u.nome
                ORDER BY valor_funcionario DESC";

        return $this->fetchAllRaw($sql, ["de" => $dateFrom, "ate" => $dateTo]);
    }

    /**
     * Comissões ao detalhe — serve a listagem (gestor: todos; funcionário: os seus).
     */
    public function listAccepted(?int $employeeId, string $dateFrom, string $dateTo): array {
        $sql = "SELECT s.id, s.agendamento_id, s.funcionario_id, s.preco_praticado,
                       s.duracao_minutos, s.aceito_em, s.percentagem_funcionario_aplicada,
                       s.valor_recibo_verde_funcionario, s.valor_recibo_verde_plataforma,
                       sv.nome AS servico_nome, u.nome AS funcionario_nome,
                       a.data_hora_pretendida, a.local_prestacao
                FROM agendamento_servico s
                INNER JOIN servico sv ON s.servico_id = sv.id
                INNER JOIN agendamento a ON s.agendamento_id = a.id
                INNER JOIN utilizador u ON s.funcionario_id = u.id
                WHERE s.estado_aceitacao = 'aceite'
                  AND s.funcionario_id IS NOT NULL
                  AND DATE(s.aceito_em) BETWEEN :de AND :ate";
        $params = ["de" => $dateFrom, "ate" => $dateTo];

        if ($employeeId !== null && $employeeId > 0) {
            $sql .= " AND s.funcionario_id = :funcionario_id";
            $params["funcionario_id"] = $employeeId;
        }

        $sql .= " ORDER BY s.aceito_em DESC, s.id DESC";

        return $this->fetchAllRaw($sql, $params);
    }
}