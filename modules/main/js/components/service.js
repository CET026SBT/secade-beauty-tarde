<?php
require_once __DIR__ . '/../services/ServiceService.php';

class ServiceController {

    public function handleRequest() {
        // Verifica se a requisição veio com o endpoint correto
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['endpoint'])) {
            
            if ($_POST['endpoint'] === 'getServicesByCategory') {
                $categoryId = $_POST['categoria_id'] ?? null;

                $service = new ServiceService();
                $resultado = $service->getServicesByCategory($categoryId);

                // Envia a resposta limpa em JSON para o frontend
                header('Content-Type: application/json');
                echo json_encode($resultado);
                exit;
            }
        }
    }
}

// Instancia e executa o controlador
$controller = new ServiceController();
$controller->handleRequest();