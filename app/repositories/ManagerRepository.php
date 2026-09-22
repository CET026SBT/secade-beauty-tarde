<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/UserMapper.php";

class ManagerRepository extends BaseRepository {

    protected ?string $mapper = UserMapper::class;

    public function find(?int $id = null): mixed {
        $sql = "SELECT u.*
                FROM utilizador u
                WHERE u.tipo_perfil = 'gestor'";
        $params = [];

        if ($id !== null) {
            $sql .= " AND u.id = :id LIMIT 1";
            $params["id"] = $id;
            return $this->fetch($sql, $params);
        }

        $sql .= " ORDER BY u.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function createWithUser(array $data): int {
        $sql = "INSERT INTO utilizador (nome, email, password_hash, telemovel, nif, tipo_perfil)
                VALUES (:nome, :email, :password_hash, :telemovel, :nif, 'gestor')";

        $this->execute($sql, [
            "nome"          => $data["name"],
            "email"         => $data["email"],
            "password_hash" => password_hash($data["password"], PASSWORD_BCRYPT),
            "telemovel"     => $data["phone"],
            "nif"           => $data["nif"] ?? null
        ]);

        return (int)$this->lastInsertId();
    }
}
