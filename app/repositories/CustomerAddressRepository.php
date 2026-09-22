<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/CustomerAddressMapper.php";

class CustomerAddressRepository extends BaseRepository {

    protected ?string $mapper = CustomerAddressMapper::class;

    public function find(int $customerId, ?int $id=null, bool $onlyPrincipal=false): mixed {
        $sql = "SELECT 
                    cm.id, cm.cliente_id, cm.cidade_id, cm.designacao, cm.rua, 
                    cm.numero_porta, cm.andar_bloco, cm.codigo_postal, 
                    cm.principal, c.nome AS cidade_nome, c.distrito
                FROM cliente_morada cm
                LEFT JOIN cidade c ON cm.cidade_id = c.id
                WHERE cm.cliente_id = :cliente_id";

        $params = ["cliente_id" => $customerId];

        if ($id !== null) {
            $sql .= " AND cm.id = :id LIMIT 1";
            $params["id"] = $id;
            return $this->fetch($sql, $params);
        }

        if ($onlyPrincipal) {
            $sql .= " AND cm.principal = 1 LIMIT 1";
            return $this->fetch($sql, $params);
        }

        $sql .= " ORDER BY cm.principal DESC, cm.id DESC";
        return $this->fetchAll($sql, $params);
    }

    public function create(int $customerId, int $cityId, array $data): int {
        $sql = "INSERT INTO cliente_morada (cliente_id, cidade_id, designacao, rua, numero_porta, andar_bloco, codigo_postal, principal) 
                VALUES (:cliente_id, :cidade_id, :designacao, :rua, :numero_porta, :andar_bloco, :codigo_postal, :principal)";

        $this->execute($sql, [
            "cliente_id"      => $customerId,
            "cidade_id"       => $cityId,
            "designacao"      => $data["designation"] ?? "Casa",
            "rua"             => $data["street"] ?? "",
            "numero_porta"    => $data["doorNumber"] ?? null,
            "andar_bloco"     => $data["floor"] ?? null,
            "codigo_postal"   => $data["zipCode"] ?? null,
            "principal"       => isset($data["isMain"]) ? (int)$data["isMain"] : 0
        ]);

        return (int)$this->lastInsertId();
    }

    public function updatePrincipal(int $customerId, int $status, ?int $id=null): bool {
        $sql = "UPDATE cliente_morada SET principal = :principal WHERE cliente_id = :cliente_id";
        $params = [
            "principal" => $status,
            "cliente_id" => $customerId
        ];

        if ($id !== null) {
            $sql .= " AND id = :id";
            $params["id"] = $id;
        }

        $this->execute($sql, $params);
        return true;
    }

    public function delete(int $customerId, ?int $id=null): bool {
        $sql = "DELETE FROM cliente_morada WHERE cliente_id = :cliente_id";
        $params = ["cliente_id" => $customerId];

        if ($id !== null) {
            $sql .= " AND id = :id";
            $params["id"] = $id;
        }

        $this->execute($sql, $params);
        return true;
    }
}
