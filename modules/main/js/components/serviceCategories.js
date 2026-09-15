const serviceCategories = (() => {
    const delays = ["0.1s", "0.3s", "0.5s"];
    
    function renderCategories(categorias) {
        const container = $('.category-cards-container');
        container.empty();

        if (categorias.length === 0) {
            container.append('<p class="text-center">Nenhuma categoria de serviço encontrada.</p>');
        }

        categorias.forEach((categoria, i) => {
            const icon = `${BASE_URL ?? ''}/modules/common/img/${generalUtils.normalizeString(categoria.nome)}.png`;
            const delay = delays[i % delays.length];
            const card = $(`<div class="col-md-6 col-lg-4">
                    <div class="service-item h-100 p-4 border-bottom border-end wow fadeIn" data-wow-delay="${delay}">
                        <img class="img-fluid" src="${icon}" alt="${categoria.nome}">
                        <h3 class="mb-3">${categoria.nome}</h3>
                        <p class="mb-3">${categoria.descricao}</p>
                        <a class="btn btn-sm btn-primary text-uppercase" href="${BASE_URL ?? ''}/servicos">mais informações <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>`);
            container.append(card);
        });
    }

    function fetchCategories() {
        const request = API.categories.getAll()
            .done((response) => {
                renderCategories(response.categories);
            })
            .fail(xhr => {
                console.log("Erro ao carregar as categorias de serviços.");
            });

        $('body').preloader(request);
    }

    $(() => fetchCategories());

    return  { 
        fetchCategories,
        renderCategories
    };
})();