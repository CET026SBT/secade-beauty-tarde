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

    /**
     * Atualiza os dados de perfil do utilizador (RH · F7). O `foto` só é tocado
     * quando vem explicitamente (o upload tem o seu próprio caminho).
     */
    public function updateProfile(int $id, array $data): bool {
        $sql = "UPDATE utilizador
                SET nome = :nome, email = :email, telemovel = :telemovel, nif = :nif
                WHERE id = :id";

        return $this->execute($sql, [
            "nome"      => $data["name"],
            "email"     => $data["email"],
            "telemovel" => $data["phone"],
            "nif"       => $data["nif"] ?? null,
            "id"        => $id
        ]) >= 0;
    }

    /** Guarda o caminho relativo da fotografia de perfil (`uploads/users/<id>.<ext>`). */
    public function setPhoto(int $id, ?string $photo): bool {
        return $this->execute("UPDATE utilizador SET foto = :foto WHERE id = :id", [
            "foto" => $photo,
            "id"   => $id
        ]) >= 0;
    }

    /** Existe outro utilizador com este e-mail? (para a unicidade na edição). */
    public function emailTakenByOther(string $email, int $excludeId): bool {
        $row = $this->fetchRaw(
            "SELECT id FROM utilizador WHERE email = :email AND id <> :id LIMIT 1",
            ["email" => $email, "id" => $excludeId]
        );

        return !empty($row);
    }
}
