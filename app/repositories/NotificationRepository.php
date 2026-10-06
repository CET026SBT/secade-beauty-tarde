<?php

require_once __DIR__ . "/BaseRepository.php";

/**
 * Notificações por utilizador (`notificacao`) — C-07/C-14 (F6).
 *
 * `create()` é **idempotente por dia**: a mesma mensagem no mesmo dia não se repete
 * (a geração automática corre várias vezes por sessão).
 */
class NotificationRepository extends BaseRepository {

    public function create(int $userId, string $type, string $message): int {
        $sql = "INSERT INTO notificacao (utilizador_id, tipo, mensagem)
                SELECT :utilizador_id, :tipo, :mensagem
                WHERE NOT EXISTS (
                    SELECT 1 FROM notificacao
                    WHERE utilizador_id = :utilizador_id
                      AND mensagem = :mensagem
                      AND DATE(criado_em) = CURDATE()
                )";

        return $this->execute($sql, [
            "utilizador_id" => $userId,
            "tipo"          => $type,
            "mensagem"      => $message
        ]);
    }

    /** Notificações não lidas de um utilizador, mais recentes primeiro. */
    public function findUnread(int $userId): array {
        $sql = "SELECT id, tipo, mensagem, lida, criado_em
                FROM notificacao
                WHERE utilizador_id = :utilizador_id AND lida = 0
                ORDER BY criado_em DESC, id DESC";

        return $this->fetchAllRaw($sql, ["utilizador_id" => $userId]);
    }

    public function countUnread(int $userId): int {
        $row = $this->fetchRaw(
            "SELECT COUNT(*) AS total FROM notificacao WHERE utilizador_id = :utilizador_id AND lida = 0",
            ["utilizador_id" => $userId]
        );

        return (int)($row["total"] ?? 0);
    }

    public function markAllRead(int $userId): int {
        return $this->execute(
            "UPDATE notificacao SET lida = 1 WHERE utilizador_id = :utilizador_id AND lida = 0",
            ["utilizador_id" => $userId]
        );
    }
}