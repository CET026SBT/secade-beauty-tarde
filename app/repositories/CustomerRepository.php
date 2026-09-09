<?php

require_once __DIR__ . "/BaseRepository.php";

class CustomerRepository extends BaseRepository {

    public function create(int $userId, array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO cliente (id, morada, telemovel_validado_otp)
            VALUES (:id, :morada, :telemovel_validado_otp)
        ");

        $stmt->execute([
            "id" => $userId,
            "morada" => $data["moradaRaw"] ?? $data["morada"] ?? "",
            "telemovel_validado_otp" => 0
        ]);

        return $userId;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM cliente WHERE id = :id LIMIT 1");
        $stmt->execute(["id" => $id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }
}
