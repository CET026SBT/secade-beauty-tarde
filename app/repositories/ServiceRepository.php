<?php
// app/repositories/ServiceRepository.php
require_once __DIR__ . '/BaseRepository.php';

class ServiceRepository extends BaseRepository {

    public function getServicesByCategory($categoryId) {
        $sql = "SELECT s.id, s.nome, s.descricao, s.preco_base, s.duracao_estimada_minutos, f.url_foto 
                FROM servico s
                LEFT JOIN servico_foto f ON f.servico_id = s.id AND f.destaque = 1
                WHERE s.categoria_id = ?";
                
        // Usa o método da tua BaseRepository para executar
        return $this->db->query($sql, [$categoryId]); 
    }
}