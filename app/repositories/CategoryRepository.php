<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/CategoryMapper.php";

class CategoryRepository extends BaseRepository {

    protected ?string $mapper = CategoryMapper::class;

    public function find(?int $id=null): mixed {
        $sql = "SELECT cp.id, cp.nome, cp.descricao
                FROM categoria_profissional cp
                WHERE 1=1";
        $params = [];

        if ($id !== null) {
            $sql .= " AND cp.id = :id LIMIT 1";
            $params["id"] = $id;
            return $this->fetch($sql, $params);
        }

        $sql .= " ORDER BY cp.nome ASC";
        return $this->fetchAll($sql, $params);
    }
}
