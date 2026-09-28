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
}
