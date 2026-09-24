<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/ServiceMapper.php";

class ServiceRepository extends BaseRepository {
    
    protected ?string $mapper = ServiceMapper::class;

    public function find(?int $id=null): mixed {
        $sql = "SELECT s.*, c.nome AS categoria_nome 
                FROM servico s 
                LEFT JOIN categoria_profissional c ON s.categoria_id = c.id
                WHERE 1=1";
        $params = [];

        if ($id !== null) {
            $sql .= " AND s.id = :id LIMIT 1";
            $params["id"] = $id;
            return $this->fetch($sql, $params);
        }

        $sql .= " ORDER BY s.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function search(array $filters=[]): array {
        $sql = "SELECT s.*, c.nome AS categoria_nome 
                FROM servico s 
                LEFT JOIN categoria_profissional c ON s.categoria_id = c.id 
                WHERE 1=1";
        $params = [];

        if (isset($filters['requiresPhysicalSpace'])) {
            $sql .= " AND s.requer_espaco_fisico = :requiresPhysicalSpace";
            $params['requiresPhysicalSpace'] = (int)$filters['requiresPhysicalSpace'];
        }

        if (isset($filters['minPrice'])) {
            $sql .= " AND s.preco_base >= :minPrice";
            $params['minPrice'] = $filters['minPrice'];
        }

        if (isset($filters['maxPrice'])) {
            $sql .= " AND s.preco_base <= :maxPrice";
            $params['maxPrice'] = $filters['maxPrice'];
        }

        if (!empty($filters['categoryId'])) {
            $sql .= " AND s.categoria_id = :categoryId";
            $params['categoryId'] = $filters['categoryId'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (s.nome LIKE :searchName OR s.descricao LIKE :searchDesc)";
            $searchTerm = "%" . $filters['search'] . "%";
            $params['searchName'] = $searchTerm;
            $params['searchDesc'] = $searchTerm;
        }

        if (isset($filters['maxDuration'])) {
            $sql .= " AND s.duracao_estimada_minutos <= :maxDuration";
            $params['maxDuration'] = $filters['maxDuration'];
        }

                $sql .= " ORDER BY s.id DESC";
                return $this->fetchAll($sql, $params);
            }

            public function findActive(?int $categoryId = null): array {
                $sql = "SELECT s.*, c.nome AS categoria_nome
                        FROM servico s
                        LEFT JOIN categoria_profissional c ON s.categoria_id = c.id
                        WHERE s.ativo = 1";
                $params = [];

                if ($categoryId !== null) {
                    $sql .= " AND s.categoria_id = :categoryId";
                    $params["categoryId"] = $categoryId;
                }

                $sql .= " ORDER BY s.id ASC";
                return $this->fetchAll($sql, $params);
            }
        }
