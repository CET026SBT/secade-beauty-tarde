function getServicosPorCategoria(categoriaId, nomeCategoria) {
    // Cria o FormData exatamente como no vosso exemplo
    let dados = new FormData();
    dados.append("endpoint", 'getServicesByCategory'); // Opção 1
    dados.append("categoria_id", categoriaId);

    $.ajax({
        url: BASE_URL + '/app/controllers/controllerServico.php',
        type: 'POST',
        data: dados,
        dataType: 'html',
        cache: false,
        contentType: false,
        processData: false,
        success: function(response) {
            console.log("Serviços recebidos do servidor:", response);
            // Aqui atualizam o Modal ou a div com os dados recebidos
        },
        error: function(xhr, status, error) {
            console.error("Erro no pedido AJAX:", error);
        }
    });
}