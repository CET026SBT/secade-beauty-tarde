const serviceCategories = (() => {
    function renderCategories(categories) {
        const $container = $('.categories-container');
        $container.empty();

        if (!categories || categories.length === 0) {
            $container.append('<p class="text-center">Nenhuma categoria de serviço encontrada.</p>');
        }

        categories.forEach((category, i) => {
            const delay = `${(i * 2 + 1) / 10}s`;
            const normalizedName = generalUtils.normalizeString(category.nome).toLowerCase();
            const iconPath = `${BASE_URL ?? ''}/modules/common/img/${generalUtils.escapeHtml(normalizedName)}.png`;
            const $card = $(`<div class="col-md-6 col-lg-4">
                    <div class="service-item h-100 p-4 border-bottom border-end wow fadeIn" data-wow-delay="${delay}">
                        <img class="img-fluid" src="${iconPath}" alt="${generalUtils.escapeHtml(category.nome)}">
                        <h3 class="mb-3">${generalUtils.escapeHtml(category.nome)}</h3>
                        <p class="mb-3">${generalUtils.escapeHtml(category.descricao)}</p>
                        <a class="btn btn-sm btn-primary text-uppercase" href="${BASE_URL ?? ''}/servicos">mais informações <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>`);
            $container.append($card);
        });
    }

    function fetchCategories() {
        let apiResponse = null;

        const request = new Promise((resolve, reject) => {
            setTimeout(() => {
                API.categories.getAll()
                    .done((response) => {
                        apiResponse = response; // Capture data while preloader runs
                        resolve();
                    })
                    .fail((xhr) => {
                        alert("Erro ao carregar as categorias de serviços.");
                        reject(xhr);
                    });
            }, 3000);
        });

        $('.categories-container')
            .preloader('.jq-skeleton-service-category-card', request)
            .then(() => {
                if (apiResponse && apiResponse.categories) {
                    renderCategories(apiResponse.categories);
                }
            })
            .catch((error) => {
                console.error("Failed to load or transition categories:", error);
            });
}

    $(() => fetchCategories());

    return  { 
        fetchCategories,
        renderCategories
    };
})();
