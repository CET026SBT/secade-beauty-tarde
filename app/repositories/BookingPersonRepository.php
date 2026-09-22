<?php

require_once __DIR__ . "/BaseRepository.php";

class BookingPersonRepository extends BaseRepository {

    public function findByBooking(int $bookingId): array {
        $sql = "SELECT id, agendamento_id, nome_pessoa, observacoes
                FROM agendamento_pessoa
                WHERE agendamento_id = :agendamento_id
                ORDER BY id ASC";

        $rows = $this->fetchAll($sql, ["agendamento_id" => $bookingId]);
        return is_array($rows) ? $rows : [];
    }

    public function create(int $bookingId, string $nomePessoa, ?string $observacoes = null): int {
        $sql = "INSERT INTO agendamento_pessoa (agendamento_id, nome_pessoa, observacoes)
                VALUES (:agendamento_id, :nome_pessoa, :observacoes)";

        $this->execute($sql, [
            "agendamento_id" => $bookingId,
            "nome_pessoa"    => $nomePessoa,
            "observacoes"    => $observacoes
        ]);

        return (int)$this->lastInsertId();
    }

    public function findByIdAndBooking(int $id, int $bookingId): ?array {
        $sql = "SELECT id, agendamento_id, nome_pessoa FROM agendamento_pessoa
                WHERE id = :id AND agendamento_id = :agendamento_id LIMIT 1";

        $row = $this->fetch($sql, ["id" => $id, "agendamento_id" => $bookingId]);
        return is_array($row) ? $row : null;
    }
}
