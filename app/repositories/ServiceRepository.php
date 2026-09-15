<?php

require_once __DIR__ . "/BaseRepository.php";

class ServiceRepository extends BaseRepository {

    public function findByCategory(int $categoryId): array {
        $stmt = $this->db->prepare("
            SELECT id, categoria_id, nome, descricao, preco_base, duracao_estimada_minutos
            FROM servico
            WHERE categoria_id = :categoryId
            ORDER BY nome ASC
        ");

        $stmt->execute([
            "categoryId" => $categoryId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
