const services = (() => {
    const state = {
        services: [],
        categories: [],
        categoryId: null,
        search: '',
        maxPrice: 50,
        maxDuration: ''
    };

    const cardsUI = {
        get $container() { return $('.services-container'); },
        get $empty() { return $('#servicesEmpty'); },

        render(list) {
            this.$container.empty();

            if (list.length === 0) {
                this.$empty.removeClass('d-none');
                return;
            }

            this.$empty.addClass('d-none');

            for (const service of list) {
                this.$container.append(card(service));
            }
        },

        openDetails(serviceId) {
            const service = state.services.find(s => s.id === Number(serviceId));
            if (!service) return;

            const badge = service.requiresPhysicalSpace
                ? '<span class="badge bg-warning text-dark">Apenas Loja</span>'
                : '<span class="badge bg-success">Loja e Carrinha</span>';

            $('#serviceModalTitle').text(service.name);
            $('#serviceModalBody').html(`
                <p class="text-muted mb-3">${generalUtils.escapeHtml(service.description || '')}</p>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><i class="bi bi-tag me-2 text-primary"></i><strong>Preço:</strong> ${generalUtils.formatCurrency(service.basePrice)}</li>
                    <li class="mb-2"><i class="bi bi-clock me-2 text-primary"></i><strong>Duração estimada:</strong> ${generalUtils.formatDuration(service.estimatedDurationMinutes)}</li>
                    <li class="mb-2"><i class="bi bi-grid me-2 text-primary"></i><strong>Categoria:</strong> ${generalUtils.escapeHtml(service.categoryName || '-')}</li>
                    <li><i class="bi bi-shop me-2 text-primary"></i><strong>Disponível em:</strong> ${badge}</li>
                </ul>
            `);
            $('#serviceModalBook').attr('href', `${BASE_URL ?? ''}/agendar?services=${service.id}`);

            bootstrap.Modal.getOrCreateInstance($('#serviceDetailsModal')[0]).show();
        }
    };

    const filtersUI = {
        get $container() { return $('.filters-container'); },

        render(categories) {
            const buttons = ['<button type="button" class="btn btn-sm btn-outline-primary service-filter active" data-category-id="">Todos</button>'];

            for (const category of categories) {
                buttons.push(`<button type="button" class="btn btn-sm btn-outline-primary service-filter" data-category-id="${category.id}">${generalUtils.escapeHtml(category.name)}</button>`);
            }

            this.$container.html(`<div class="d-flex flex-wrap gap-2 justify-content-center">${buttons.join('')}</div>`);
            this.syncActive();
        },

        syncActive() {
            this.$container.find('.service-filter').each((_, el) => {
                const $el = $(el);
                const value = $el.data('category-id');
                const isActive = (value === '' && state.categoryId === null) || Number(value) === state.categoryId;
                $el.toggleClass('active', isActive);
            });
        }
    };

    function card(service) {
        const badge = service.requiresPhysicalSpace
            ? '<span class="badge bg-warning text-dark">Apenas Loja</span>'
            : '<span class="badge bg-success">Loja e Carrinha</span>';

        return $(`<div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm service-card">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-primary">${generalUtils.escapeHtml(service.categoryName || '')}</span>
                        ${badge}
                    </div>
                    <h5 class="mb-2">${generalUtils.escapeHtml(service.name)}</h5>
                    <p class="text-muted small flex-grow-1 mb-3">${generalUtils.escapeHtml(service.description || '')}</p>
                    <ul class="list-unstyled small text-muted mb-3">
                        <li><i class="bi bi-clock me-1"></i>${generalUtils.formatDuration(service.estimatedDurationMinutes)}</li>
                        <li><i class="bi bi-tag me-1"></i>${generalUtils.formatCurrency(service.basePrice)}</li>
                    </ul>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary flex-fill" data-details="${service.id}">Detalhes</button>
                        <a class="btn btn-sm btn-primary flex-fill" href="${BASE_URL ?? ''}/agendar?services=${service.id}">Agendar</a>
                    </div>
                </div>
            </div>
        </div>`);
    }

    function filteredServices() {
        const search = generalUtils.removeAccents(state.search).toLowerCase();

        return state.services.filter(service => {
            if (state.categoryId !== null && service.categoryId !== state.categoryId) return false;
            if (Number(service.basePrice) > state.maxPrice) return false;
            if (state.maxDuration !== '' && Number(service.estimatedDurationMinutes) > Number(state.maxDuration)) return false;

            if (search !== '') {
                const haystack = generalUtils.removeAccents(`${service.name} ${service.description || ''}`).toLowerCase();
                if (!haystack.includes(search)) return false;
            }

            return true;
        });
    }

    function applyFilters() {
        cardsUI.render(filteredServices());
    }

    function resolveInitialCategory() {
        const slug = window.APP_PARAMS?.category;
        if (!slug) return;

        const category = state.categories.find(c => generalUtils.slugify(c.name).toLowerCase() === String(slug).toLowerCase());
        if (category) state.categoryId = category.id;
    }

    async function loadData() {
        const promise = Promise.all([API.categories.getAll(), API.booking.services()]);
        const preloader = cardsUI.$container.preloader('.jq-skeleton-service-category-card', promise);

        const [categoriesResponse, servicesResponse] = await promise;

        state.categories = categoriesResponse?.categories || [];
        state.services = servicesResponse?.services || [];

        filtersUI.render(state.categories);
        resolveInitialCategory();
        filtersUI.syncActive();
        applyFilters();

        await preloader;
    }

    function bindEvents() {
        $(document).on('click', '.service-filter', function () {
            const value = $(this).data('category-id');
            state.categoryId = value === '' ? null : Number(value);
            filtersUI.syncActive();
            applyFilters();
        });

        $(document).on('click', '[data-details]', function () {
            cardsUI.openDetails($(this).data('details'));
        });

        $('#serviceSearch').on('input', function () {
            state.search = $(this).val();
            applyFilters();
        });

        $('#serviceMaxDuration').on('change', function () {
            state.maxDuration = $(this).val();
            applyFilters();
        });

        $('#serviceMaxPrice').on('input change', function () {
            state.maxPrice = Number($(this).val());
            $('#serviceMaxPriceLabel').text(generalUtils.formatCurrency(state.maxPrice));
            applyFilters();
        });
    }

    $(() => {
        bindEvents();
        loadData();
    });

    return {
        state,
        applyFilters,
        reload: loadData
    };
})();
