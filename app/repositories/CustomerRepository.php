<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/CustomerMapper.php";

class CustomerRepository extends BaseRepository {

    protected ?string $mapper = CustomerMapper::class;

    public function find(?int $id = null): mixed {
        $sql = "SELECT * 
                FROM cliente c
                WHERE 1=1";
        $params = [];

        if ($id !== null) {
            $sql .= " AND c.id = :id LIMIT 1";
            $params["id"] = $id;
            return $this->fetch($sql, $params);
        }

        $sql .= " ORDER BY c.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function create(int $id, array $data): int {
        $sql = "INSERT INTO cliente (id, telemovel_validado_otp)
                VALUES (:id, :telemovel_validado_otp)";

        $this->execute($sql, [
            "id" => $id,
            "telemovel_validado_otp" => 0
        ]);

        return $id;
    }
}
