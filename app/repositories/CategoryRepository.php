<?php

require_once __DIR__ . "/BaseRepository.php";

class CategoryRepository extends BaseRepository {

    public function findAll(): array {
        $stmt = $this->db->query("
            SELECT id, nome, descricao
            FROM categoria_profissional
            ORDER BY nome ASC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
