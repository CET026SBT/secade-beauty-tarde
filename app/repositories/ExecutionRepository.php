<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/ExecutionMapper.php";

/**
 * Acesso à tabela `execucao_agendamento` (registo da execução real do serviço).
 */
class ExecutionRepository extends BaseRepository {

    protected ?string $mapper = ExecutionMapper::class;

    public function findByBooking(int $bookingId): mixed {
        $sql = "SELECT e.id, e.agendamento_id, e.rota_id, e.data_hora_inicio_real,
                       e.data_hora_fim_real, e.estado_execucao, e.observacoes_tecnico
                FROM execucao_agendamento e
                WHERE e.agendamento_id = :agendamento_id LIMIT 1";

        return $this->fetch($sql, ["agendamento_id" => $bookingId]);
    }

    public function create(int $bookingId, ?int $routeId = null, string $estado = "concluido", ?string $notes = null): int {
        $sql = "INSERT INTO execucao_agendamento
                    (agendamento_id, rota_id, data_hora_inicio_real, data_hora_fim_real, estado_execucao, observacoes_tecnico)
                VALUES (:agendamento_id, :rota_id, NOW(), NOW(), :estado, :observacoes)";

        $this->execute($sql, [
            "agendamento_id" => $bookingId,
            "rota_id"        => $routeId,
            "estado"         => $estado,
            "observacoes"    => $notes
        ]);

        return (int)$this->lastInsertId();
    }

    public function updateEstado(int $id, string $estado, ?string $notes = null): bool {
        $sql = "UPDATE execucao_agendamento
                SET estado_execucao = :estado, observacoes_tecnico = :observacoes
                WHERE id = :id";

        return $this->execute($sql, ["estado" => $estado, "observacoes" => $notes, "id" => $id]) >= 0;
    }
}