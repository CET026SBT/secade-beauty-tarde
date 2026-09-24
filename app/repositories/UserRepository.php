<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/UserMapper.php";

class UserRepository extends BaseRepository {
    
    protected ?string $mapper = UserMapper::class;

    public function find(?int $id = null, ?string $email = null): mixed {
        $sql = "SELECT * 
                FROM utilizador u
                WHERE 1=1";
        $params = [];

        if ($id !== null) {
            $sql .= " AND u.id = :id LIMIT 1";
            $params["id"] = $id;
            return $this->fetch($sql, $params);
        }

        if ($email !== null) {
            $sql .= " AND u.email = :email LIMIT 1";
            $params["email"] = $email;
            return $this->fetch($sql, $params);
        }

        $sql .= " ORDER BY u.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function create(array $data, string $passwordHash): int {
        $sql = "INSERT INTO utilizador (nome, email, password_hash, telemovel, nif, tipo_perfil) 
                VALUES (:nome, :email, :password_hash, :telemovel, :nif, :tipo_perfil)";
        
        // As chaves de $data seguem o contrato do código (inglês), alinhado com UserMapper
        // e com UserService::validateInput (name, phone, profileType).
        $this->execute($sql, [
            "nome"          => $data["name"],
            "email"         => $data["email"],
            "password_hash" => $passwordHash,
            "telemovel"     => $data["phone"],
            "nif"           => $data["nif"] ?? null,
            "tipo_perfil"   => $data["profileType"] ?? "cliente"
        ]);

        return (int)$this->lastInsertId();
    }
}
