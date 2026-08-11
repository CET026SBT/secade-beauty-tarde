<?php

require_once '../model/modelServico.php';

$servico = new Servico();

// Captura requisições enviadas por POST ou GET
$dados = !empty($_POST) ? $_POST : $_GET;

if (isset($dados['endpoint']) && $dados['endpoint'] == 'getServicesByCategory') {
    
    if (isset($dados['categoria_id'])) {
        $resp = $servico->getServicosPorCategoria($dados['categoria_id']);
        echo $resp;
    } else {
        echo json_encode(["error" => "ID da categoria não fornecido"]);
    }

} else {
    echo json_encode(["error" => "Operação inválida ou não especificada"]); 
}

?>