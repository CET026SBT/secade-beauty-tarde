<?php

require_once 'connection.php';

class Servico {

    // Retorna os serviços de uma categoria específica em formato JSON
    function getServicosPorCategoria($categoria_id) {
        global $conn;
        $servicos = array();

        $sql = "SELECT id, nome, descricao, duracao_estimada_minutos, preco_base FROM servico WHERE categoria_id = ?";

        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute([$categoria_id]);

            if ($stmt->rowCount() > 0) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $servicos[] = $row;
                }
            }
        } catch (PDOException $e) {
            $servicos = array(
                "error" => "Error: " . $sql . " - " . $e->getMessage()
            );
        }

        $conn = null;
        return json_encode($servicos);
    }
}

?> 