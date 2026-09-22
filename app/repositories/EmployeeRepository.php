<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/EmployeeMapper.php";

class EmployeeRepository extends BaseRepository {

    protected ?string $mapper = EmployeeMapper::class;

    public function find(?int $id = null): mixed {
        $sql = "SELECT f.id, f.tipo_contrato, f.salario_base, f.cc, f.ativo,
                       u.nome, u.email, u.telemovel, u.nif
                FROM funcionario f
                INNER JOIN utilizador u ON f.id = u.id
                WHERE 1=1";
        $params = [];

        if ($id !== null) {
            $sql .= " AND f.id = :id LIMIT 1";
            $params["id"] = $id;
            return $this->fetch($sql, $params);
        }

        $sql .= " ORDER BY f.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function create(array $data): int {
        $sql = "INSERT INTO funcionario (id, tipo_contrato, salario_base, cc, ativo)
                VALUES (:id, :tipo_contrato, :salario_base, :cc, 1)";

        $this->execute($sql, [
            "id"            => $data["userId"],
            "tipo_contrato" => $data["contractType"],
            "salario_base"  => (float)($data["salary"] ?? 0),
            "cc"            => $data["cc"] ?? null
        ]);

        return $data["userId"];
    }
}
