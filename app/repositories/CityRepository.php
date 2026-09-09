<?php

require_once __DIR__ . "/BaseRepository.php";

class CityRepository extends BaseRepository {

    public function getAllNames(): array {
        $stmt = $this->db->query("SELECT nome FROM cidade ORDER BY nome ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM cidade ORDER BY nome ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCityIdByName(string $cityName): ?int {
        $stmt = $this->db->prepare("SELECT id FROM cidade WHERE nome = :nome LIMIT 1");
        $stmt->execute(["nome" => $cityName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (int)$result["id"] : null;
    }
}
