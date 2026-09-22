const boRoutes = (() => {
    const state = {
        routes: [],
        fixedCost: 50,
        referenceProfitability: 50
    };

    const tableUI = {
        get $body() { return $("#routesTableBody"); },

        render(routes) {
            this.$body.empty();

            if (routes.length === 0) {
                this.$body.html(`<tr><td colspan="9" class="text-center text-muted py-5">
                    <i class="bi bi-signpost fs-3 d-block mb-2"></i>Não existem rotas para os filtros selecionados.
                </td></tr>`);
                return;
            }

            for (const route of routes) {
                this.$body.append(row(route));
            }
        }
    };

    function row(route) {
        const profitClass = route.meetsReference ? "bo-profit-positive" : "bo-profit-negative";
        const referenceIcon = route.meetsReference
            ? `<i class="bi bi-check-circle text-success ms-1" title="Rentabilidade igual ou acima da referência de ${state.referenceProfitability} €"></i>`
            : `<i class="bi bi-exclamation-triangle text-warning ms-1" title="Abaixo da referência de ${state.referenceProfitability} € (apenas indicador visual)"></i>`;

        const canDecide = route.canDecide && route.bookings > 0;

        const actions = canDecide
            ? `<div class="d-flex gap-1 justify-content-end">
                   <button type="button" class="btn btn-sm btn-success" data-decide="aprovada"
                           data-city="${route.cityId}" data-date="${route.routeDate}" title="Aprovar rota">
                       <i class="bi bi-check-lg"></i>
                   </button>
                   <button type="button" class="btn btn-sm btn-danger" data-decide="recusada"
                           data-city="${route.cityId}" data-date="${route.routeDate}" title="Recusar rota">
                       <i class="bi bi-x-lg"></i>
                   </button>
               </div>`
            : '<span class="text-muted small">-</span>';

        return `<tr>
            <td class="small">${generalUtils.formatDateTime(route.routeDate)}</td>
            <td>
                <span class="d-block fw-bold">${generalUtils.escapeHtml(route.cityName || "-")}</span>
                <span class="d-block small text-muted">${generalUtils.escapeHtml(route.district || "")}</span>
            </td>
            <td class="text-center">
                <span class="d-block fw-bold">${route.bookings}</span>
                <span class="d-block small text-muted">${route.consolidated} aceite(s)</span>
            </td>
            <td class="text-end">${generalUtils.formatCurrency(route.revenue)}</td>
            <td class="text-end">${generalUtils.formatCurrency(route.fuelCost)}</td>
            <td class="text-end">${generalUtils.formatCurrency(route.totalCost)}</td>
            <td class="text-end ${profitClass}">${generalUtils.formatCurrency(route.profitability)}${referenceIcon}</td>
            <td class="text-center">${boUtils.routeStatusBadge(route.status)}</td>
            <td class="text-end">${actions}</td>
        </tr>`;
    }

    async function load() {
        const promise = API.admin.routes({
            date: $("#routeDate").val(),
            cityId: $("#routeCity").val(),
            status: $("#routeStatus").val()
        });

        const preloader = $("#routesTableContainer").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            state.routes = response?.routes || [];
            state.fixedCost = Number(response?.fixedCost || 50);
            state.referenceProfitability = Number(response?.referenceProfitability || 50);

            tableUI.render(state.routes);
            $("#routesCount").text(`${state.routes.length} rota(s)`);
        } catch (error) {
            $("#routesError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível carregar as rotas.");
        } finally {
            await preloader;
        }
    }

    async function decideRoute(cityId, routeDate, decision) {
        const label = decision === "aprovada" ? "APROVAR" : "RECUSAR";

        if (!confirm(`${label} a rota de ${routeDate}?`)) return;

        $("#routesError, #routesResult").addClass("d-none");

        const promise = API.admin.decideRoute({
            cityId: Number(cityId),
            date: routeDate,
            decision
        });
        const preloader = $("#routesTableContainer").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            $("#routesResult")
                .removeClass("d-none alert-warning")
                .addClass("alert-success")
                .html(`<i class="bi bi-clipboard-check me-1"></i>${generalUtils.escapeHtml(response?.message || "Decisão registada.")}
                    <span class="d-block small mt-1">
                        Receita ${generalUtils.formatCurrency(response?.revenue)} ·
                        Custo total ${generalUtils.formatCurrency(response?.totalCost)} ·
                        Rentabilidade <strong>${generalUtils.formatCurrency(response?.profitability)}</strong>
                        ${response?.meetsReference
                            ? '<span class="badge bg-success ms-2">acima da referência</span>'
                            : '<span class="badge bg-warning text-dark ms-2">abaixo da referência (indicador)</span>'}
                    </span>`);

            await load();
        } catch (error) {
            $("#routesError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível registar a decisão.");
        } finally {
            await preloader;
        }
    }

    async function loadCities() {
        try {
            const response = await API.cities.getSupported();
            boUtils.fillCitySelect("#routeCity", response?.cities || [], "Todas");
        } catch (error) {
            // Sem cidades disponíveis, o filtro fica apenas com "Todas"
        }
    }

    function bindEvents() {
        boUtils.fillStatusSelect("#routeStatus", boUtils.ROUTE_STATUS_LABELS, "Todos");

        $("#reloadRoutesBtn").on("click", function () {
            $("#routesError").addClass("d-none");
            load();
        });

        $(document).on("click", "[data-decide]", function () {
            decideRoute($(this).data("city"), $(this).data("date"), $(this).data("decide"));
        });

        $("#routeDate, #routeCity, #routeStatus").on("change", load);
    }

    $(() => {
        bindEvents();
        loadCities();
        load();
    });

    return { state, load, decideRoute };
})();