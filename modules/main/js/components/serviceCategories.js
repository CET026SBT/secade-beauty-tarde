const serviceCategories = (() => {
    const cardsUI = (() => {
        let $content = $([]);

        return {
            get $container() { return $('.categories-container'); },
            render(categories=[]) {
                let $container = this.$container;
                $container.children($content).remove();

                if (categories.length === 0) {
                    $content = $('<p class="text-center text-muted wow fadeIn">Nenhum serviço disponível.</p>');
                    $container.append($content);
                    return;
                }

                categories.forEach((category, i) => {
                    const delay = `${(i * 2 + 1) / 10}s`;
                    const slugifiedName = generalUtils.slugify(category.nome).toLowerCase();
                    const iconPath = `${BASE_URL ?? ''}/modules/common/img/${generalUtils.escapeHtml(slugifiedName)}.png`;
                    $content = $content.add(`<div class="col-md-6 col-lg-4">
                            <div class="service-item h-100 p-4 border-bottom border-end wow fadeIn" data-wow-delay="${delay}">
                                <img class="img-fluid" src="${iconPath}" alt="${generalUtils.escapeHtml(category.nome)}">
                                <h3 class="mb-3">${generalUtils.escapeHtml(category.nome)}</h3>
                                <p class="mb-3">${generalUtils.escapeHtml(category.descricao)}</p>
                                <a class="btn btn-sm btn-primary text-uppercase" href="${BASE_URL ?? ''}/servicos">mais informações <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>`);
                });

                $container.append($content);
            }
        }
    })();

    async function fetchCategories() {
        const res = await API.categories.getAll();
        return res?.categories || [];
    }

    async function updateCards() {
        const promise = fetchCategories();
        const preloader = cardsUI.$container.preloader('.jq-skeleton-service-category-card', promise);
        
        const categories = await promise;
        cardsUI.render(categories);
        
        await preloader;
    }

    $(() => {
        updateCards();
    });

    return {
        cardsUI,
        fetchCategories,
        updateCards
    }
})();
