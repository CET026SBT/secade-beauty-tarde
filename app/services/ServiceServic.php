<?php
require_once __DIR__ . '/../repositories/ServiceRepository.php';

class ServiceService {
    private $repository;

    public function __construct() {
        $this->repository = new ServiceRepository();
    }

    public function getServicesByCategory($categoryId) {
        // Validação simples do ID recebido
        if (empty($categoryId)) {
            return ['status' => false, 'message' => 'Categoria inválida.'];
        }

        $dados = $this->repository->findByCategory($categoryId);

        return [
            'status' => true,
            'data'   => $dados
        ];
    }
}