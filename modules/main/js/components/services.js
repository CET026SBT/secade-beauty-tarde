
category-all

document.addEventListener("DOMContentLoaded", function() {

    const urlParams = new URLSearchParams(window.location.search);
    const categoriaId = urlParams.get("categoria_id");

    if (categoriaId) {
        carregarServicos(categoriaId);
    }

});


function carregarServicos(categoriaId) {

    $.ajax({

        url: BASE_URL + "/app/controllers/ServiceController.php",
        type: "POST",
        data: {
            endpoint: "getServicesByCategory",
            categoria_id: categoriaId
        },
        dataType: "json",
        success: function(response) {

            if (response.status && response.data) {
                renderizarGrelha(response.data);
            } else {
                document.getElementById("grelha-servicos").innerHTML =
                    "<p>Nenhum serviço encontrado.</p>";
            }

        },

        error: function() {
            console.log("Erro ao carregar os serviços.");
        }

    });

}


function renderizarGrelha(servicos) {

    const grelha = document.getElementById("grelha-servicos");

    grelha.innerHTML = "";

    servicos.forEach(function(servico) {

        const card = `
            <div class="card-servico" onclick="abrirModal(${JSON.stringify(servico)})">

                <img src="${servico.url_foto}" alt="${servico.nome}">

                <h3>${servico.nome}</h3>

                <p>${servico.descricao || ""}</p>

                <span>${servico.preco_base} €</span>

                <span>${servico.duracao_estimada_minutos} min</span>

            </div>
        `;

        grelha.insertAdjacentHTML("beforeend", card);
    });
}


function abrirModal(servico) {

    document.getElementById("modal-titulo").innerText = servico.nome;

    document.getElementById("modal-descricao").innerText = servico.descricao;

    document.getElementById("modal-preco").innerText =
        servico.preco_base + " €";

    document.getElementById("modal-duracao").innerText =
        servico.duracao_estimada_minutos + " min";

    document.getElementById("modalDetalhes").style.display = "block";
}


function fecharModal() {

    document.getElementById("modalDetalhes").style.display = "none";

}