<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/CityMapper.php";

class CityRepository extends BaseRepository {

    protected ?string $mapper = CityMapper::class;

    public function find(?int $id=null, ?string $name=null): mixed {
        $sql = "SELECT c.id, c.nome, c.distrito
                FROM cidade c
                WHERE 1=1";
        $params = [];

        if ($id !== null) {
            $sql .= " AND c.id = :id LIMIT 1";
            $params["id"] = $id;
            return $this->fetch($sql, $params);
        }

        if ($name !== null) {
            $sql .= " AND c.nome = :nome LIMIT 1";
            $params["nome"] = $name;
            return $this->fetch($sql, $params);
        }

        $sql .= " ORDER BY c.nome ASC";
        return $this->fetchAll($sql, $params);
    }
}
