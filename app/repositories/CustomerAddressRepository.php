<?php

require_once __DIR__ . "/BaseRepository.php";

class CustomerAddressRepository extends BaseRepository {

    public function create(int $clienteId, int $cidadeId, array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO cliente_morada (cliente_id, cidade_id, designacao, rua, numero_porta, andar_bloco, codigo_postal) 
            VALUES (:cliente_id, :cidade_id, :designacao, :rua, :numero_porta, :andar_bloco, :codigo_postal)
        ");

        $stmt->execute([
            "cliente_id"    => $clienteId,
            "cidade_id"     => $cidadeId,
            "designacao"    => $data["designacao"] ?? "Casa",
            "rua"           => $data["moradaRaw"] ?? $data["morada"],
            "numero_porta"  => $data["numPorta"],
            "andar_bloco"   => $data["andarBloco"] ?? null,
            "codigo_postal" => $data["codigoPostal"]
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Find all addresses belonging to a customer (no JOIN - city name resolved via CityRepository in the Service)
     * @param int $customerId
     * @return array
     */
    public function findByCustomerId(int $customerId): array {
        $stmt = $this->db->prepare("
            SELECT 
                id, cliente_id, cidade_id, designacao, rua, numero_porta,
                andar_bloco, codigo_postal, obs_localizacao, principal
            FROM cliente_morada
            WHERE cliente_id = :customerId
            ORDER BY principal DESC, id DESC
        ");

        $stmt->execute(["customerId" => $customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a single address by id, scoped to a customer
     * @param int $addressId
     * @param int $customerId
     * @return array|null
     */
    public function findByIdAndCustomerId(int $addressId, int $customerId): ?array {
        $stmt = $this->db->prepare("
            SELECT 
                id, cliente_id, cidade_id, designacao, rua, numero_porta,
                andar_bloco, codigo_postal, obs_localizacao, principal
            FROM cliente_morada
            WHERE id = :addressId AND cliente_id = :customerId
            LIMIT 1
        ");

        $stmt->execute([
            "addressId"  => $addressId,
            "customerId" => $customerId
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    /**
     * Unset the principal flag on every address of a customer.
     * Granular method - the Service is responsible for wrapping this
     * together with setPrincipal() in a transaction.
     * @param int $customerId
     * @return bool
     */
    public function unsetPrincipalForCustomer(int $customerId): bool {
        $stmt = $this->db->prepare("
            UPDATE cliente_morada 
            SET principal = 0 
            WHERE cliente_id = :customerId
        ");

        return $stmt->execute(["customerId" => $customerId]);
    }

    /**
     * Set a single address as the principal one
     * @param int $addressId
     * @param int $customerId
     * @return bool
     */
    public function setPrincipal(int $addressId, int $customerId): bool {
        $stmt = $this->db->prepare("
            UPDATE cliente_morada 
            SET principal = 1 
            WHERE id = :addressId AND cliente_id = :customerId
        ");

        return $stmt->execute([
            "addressId"  => $addressId,
            "customerId" => $customerId
        ]);
    }

    /**
     * Delete an address
     * @param int $addressId
     * @param int $customerId
     * @return bool
     */
    public function delete(int $addressId, int $customerId): bool {
        $stmt = $this->db->prepare("
            DELETE FROM cliente_morada 
            WHERE id = :addressId AND cliente_id = :customerId
        ");
        
        return $stmt->execute([
            "addressId"  => $addressId,
            "customerId" => $customerId
        ]);
    }
}
