<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/FeedbackMapper.php";

/**
 * Acesso à tabela `feedback_cliente` (avaliação de serviços executados).
 */
class FeedbackRepository extends BaseRepository {

    protected ?string $mapper = FeedbackMapper::class;

    public function findByBooking(int $bookingId): mixed {
        $sql = "SELECT f.id, f.execucao_agendamento_id, f.classificacao_estrelas, f.comentario, f.data_feedback
                FROM feedback_cliente f
                INNER JOIN execucao_agendamento e ON f.execucao_agendamento_id = e.id
                WHERE e.agendamento_id = :agendamento_id LIMIT 1";

        return $this->fetch($sql, ["agendamento_id" => $bookingId]);
    }

    public function create(int $executionId, int $rating, ?string $comment): int {
        $sql = "INSERT INTO feedback_cliente (execucao_agendamento_id, classificacao_estrelas, comentario)
                VALUES (:execucao_id, :classificacao, :comentario)";

        $this->execute($sql, [
            "execucao_id"   => $executionId,
            "classificacao" => $rating,
            "comentario"    => $comment
        ]);

        return (int)$this->lastInsertId();
    }

    public function recent(int $limit = 6): array {
        $sql = "SELECT f.id, f.execucao_agendamento_id, f.classificacao_estrelas, f.comentario, f.data_feedback,
                       a.id AS agendamento_id, u.nome AS cliente_nome,
                       a.local_prestacao, a.data_hora_pretendida
                FROM feedback_cliente f
                INNER JOIN execucao_agendamento e ON f.execucao_agendamento_id = e.id
                INNER JOIN agendamento a ON e.agendamento_id = a.id
                INNER JOIN cliente c ON a.cliente_id = c.id
                INNER JOIN utilizador u ON c.id = u.id
                WHERE f.classificacao_estrelas IS NOT NULL
                ORDER BY f.data_feedback DESC
                LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(":limite", $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? FeedbackMapper::map($rows) : [];
    }

    public function countAll(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM feedback_cliente");
        return (int)$stmt->fetchColumn();
    }
}