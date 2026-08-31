<?php

require_once __DIR__ . '/BaseRepository.php';

class CustomerAddressRepository extends BaseRepository {

    public function create(int $clienteId, int $cidadeId, array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO cliente_morada (cliente_id, cidade_id, designacao, rua, numero_porta, andar_bloco, codigo_postal) 
            VALUES (:cliente_id, :cidade_id, :designacao, :rua, :numero_porta, :andar_bloco, :codigo_postal)
        ");
        
        $stmt->execute([
            'cliente_id'    => $clienteId,
            'cidade_id'     => $cidadeId,
            'designacao'    => $data['designacao'] ?? 'Casa',
            'rua'           => $data['moradaRaw'] ?? $data['morada'],
            'numero_porta'  => $data['numPorta'],
            'andar_bloco'   => $data['andarBloco'] ?? null,
            'codigo_postal' => $data['codigoPostal']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findCidadeIdByName(string $cityName): ?int {
        $stmt = $this->db->prepare("SELECT id FROM cidade WHERE nome = :nome LIMIT 1");
        $stmt->execute(['nome' => $cityName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (int)$result['id'] : null;
    }
}
