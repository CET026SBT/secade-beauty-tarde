const boRoutes = (() => {
    const state = {
        routes: [],
        referenceProfitability: 50,
        details: null
    };

    const tableUI = {
        get $body() { return $("#routesTableBody"); },

        render(routes) {
            this.$body.empty();

            if (routes.length === 0) {
                this.$body.html(`<tr><td colspan="8" class="text-center text-muted py-5">
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

        const awaiting = Number(route.awaitingAcceptance || 0);
        const canDecide = route.canDecide && route.bookings > 0;

        const actions = [
            boUtils.actionButton("details", "Ver detalhes e escolher os agendamentos incluídos",
                `data-route-details="${route.routeDate}|${route.cityId}"`),
            canDecide ? boUtils.actionButton("approve", "Aprovar a rota (com os agendamentos incluídos)",
                `data-decide="aprovada" data-city="${route.cityId}" data-date="${route.routeDate}"`) : "",
            canDecide ? boUtils.actionButton("refuse", "Recusar a rota (com os agendamentos incluídos)",
                `data-decide="recusada" data-city="${route.cityId}" data-date="${route.routeDate}"`) : "",
            !canDecide && awaiting > 0
                ? `<button type="button" class="btn btn-sm bo-action bo-action--refuse" disabled
                       title="${generalUtils.escapeHtml(route.decideBlockReason || "Rota bloqueada")}">
                       <i class="bi bi-lock"></i></button>`
                : ""
        ].filter(Boolean).join(" ");

        return `<tr>
            <td class="small">${generalUtils.formatDateTime(route.routeDate)}</td>
            <td>
                <span class="d-block fw-bold">${generalUtils.escapeHtml(route.cityName || "-")}</span>
                <span class="d-block small text-muted">${generalUtils.escapeHtml(route.district || "")}</span>
            </td>
            <td class="text-center">
                <span class="d-block fw-bold">${route.bookings}</span>
                <span class="d-block small text-muted">${route.consolidated} aceite(s)</span>
                ${awaiting > 0
                    ? `<span class="d-block small text-danger">${awaiting} por aceitar</span>`
                    : ""}
            </td>
            <td class="text-end">${generalUtils.formatCurrency(route.revenue)}</td>
            <td class="text-end">${generalUtils.formatCurrency(route.fuelCost)}</td>
            <td class="text-end ${profitClass}">${generalUtils.formatCurrency(route.profitability)}${referenceIcon}</td>
            <td class="text-center">${boUtils.routeStatusBadge(route.status)}</td>
            <td class="text-end text-nowrap">${actions}</td>
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

    async function decideRoute(cityId, routeDate, decision, bookingIds = null) {
        const label = decision === "aprovada" ? "APROVAR" : "RECUSAR";
        const scope = bookingIds && bookingIds.length > 0
            ? `${bookingIds.length} agendamento(s) incluído(s)`
            : "todos os agendamentos qualificados";

        if (!confirm(`${label} a rota de ${routeDate} (${scope})?`)) return;

        $("#routesError, #routesResult").addClass("d-none");

        const payload = {
            cityId: Number(cityId),
            date: routeDate,
            decision
        };

        // RN-34: sem lista explícita o servidor decide sobre todos os qualificados.
        if (bookingIds && bookingIds.length > 0) {
            payload.bookingIds = bookingIds;
        }

        const promise = API.admin.decideRoute(payload);
        const preloader = $("#routesTableContainer").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            bootstrap.Modal.getInstance(document.getElementById("routeDetailsModal"))?.hide();

            $("#routesResult")
                .removeClass("d-none alert-warning")
                .addClass("alert-success")
                .html(`<i class="bi bi-clipboard-check me-1"></i>${generalUtils.escapeHtml(response?.message || "Decisão registada.")}
                    <span class="d-block small mt-1">
                        Incluídos: <strong>${Number(response?.bookings || 0)}</strong> ·
                        Fora da rota: <strong>${Number(response?.excluded || 0)}</strong> ·
                        Receita ${generalUtils.formatCurrency(response?.revenue)} ·
                        Combustível ${generalUtils.formatCurrency(response?.fuelCost)} ·
                        Rentabilidade <strong>${generalUtils.formatCurrency(response?.profitability)}</strong>
                        ${response?.meetsReference
                            ? '<span class="badge bg-success ms-2">acima da referência</span>'
                            : '<span class="badge bg-warning text-dark ms-2">abaixo da referência (indicador)</span>'}
                    </span>`);

            await load();
        } catch (error) {
            // 409 = rota com serviços por aceitar (RN-31): o motivo tem de ser visível.
            $("#routesError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível registar a decisão.");
        } finally {
            await preloader;
        }
    }

    // ------------------------------------------------------------------
    // Detalhes da rota: incluir/excluir agendamentos antes de decidir (RN-34)
    // ------------------------------------------------------------------

    function openDetails(key) {
        const [routeDate, cityId] = String(key).split("|");
        const route = state.routes.find((item) => item.routeDate === routeDate && Number(item.cityId) === Number(cityId));

        if (!route) return;

        state.details = route;
        renderDetails(route);

        bootstrap.Modal.getOrCreateInstance(document.getElementById("routeDetailsModal")).show();
    }

    function renderDetails(route) {
        const canDecide = route.canDecide && route.bookings > 0;

        $("#routeDetailsSummary").html(`
            <p class="mb-1"><strong>${generalUtils.escapeHtml(route.cityName || "-")}</strong> ·
               ${generalUtils.formatDateTime(route.routeDate)}</p>
            <p class="mb-0 small text-muted">
                ${canDecide
                    ? "Escolha os agendamentos que entram na rota. Os que ficarem de fora continuam qualificados (RN-34)."
                    : generalUtils.escapeHtml(route.decideBlockReason || "Esta rota já não pode ser decidida.")}
            </p>`);

        const details = route.bookingsDetail || [];

        if (details.length === 0) {
            $("#routeDetailsBody").html(`<p class="text-muted small mb-0">Não existem agendamentos qualificados nesta rota.</p>`);
        } else {
            $("#routeDetailsBody").html(`<ul class="list-unstyled mb-0" id="routeDetailsList">` + details.map((booking) => `
                <li class="border-top py-2 d-flex align-items-start gap-2">
                    <input class="form-check-input mt-1" type="checkbox" data-booking-include
                           value="${booking.bookingId}" ${canDecide ? "checked" : "disabled"}>
                    <span>
                        <span class="d-block fw-bold small">#${booking.bookingId} · ${generalUtils.escapeHtml(booking.customerName)}</span>
                        <span class="d-block text-muted small">
                            ${generalUtils.formatDateTime(booking.dateTime)} ·
                            ${booking.servicesAccepted}/${booking.servicesTotal} serviço(s) aceite(s) ·
                            ${generalUtils.formatCurrency(booking.totalAmount)}
                        </span>
                    </span>
                </li>`).join("") + `</ul>`);
        }

        $("#modalApproveRouteBtn, #modalRefuseRouteBtn").prop("disabled", !canDecide);
        refreshSummary();
    }

    /**
     * Resumo do conjunto incluído: o gestor decide sobre o que está marcado.
     */
    function refreshSummary() {
        const ids = $("#routeDetailsBody [data-booking-include]:checked").map(function () {
            return Number($(this).val());
        }).get();

        const revenue = ids.reduce((total, id) => {
            const booking = (state.details?.bookingsDetail || []).find((item) => Number(item.bookingId) === id);
            return total + Number(booking?.totalAmount || 0);
        }, 0);

        if ($("#routeDetailsIncluded").length === 0) {
            $("#routeDetailsBody").append(`<p class="small text-muted mt-2 mb-0" id="routeDetailsIncluded"></p>`);
        }

        $("#routeDetailsIncluded").html(
            `Incluídos: <strong>${ids.length}</strong> agendamento(s) · receita
             <strong>${generalUtils.formatCurrency(revenue)}</strong>`
        );
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

        $(document).on("click", "[data-route-details]", function () {
            openDetails($(this).data("route-details"));
        });

        // O conjunto incluído muda a cada clique: o resumo tem de acompanhar.
        $(document).on("change", "[data-booking-include]", refreshSummary);

        $("#modalApproveRouteBtn").on("click", function () {
            if (state.details) decideRoute(state.details.cityId, state.details.routeDate, "aprovada", checkedIds());
        });

        $("#modalRefuseRouteBtn").on("click", function () {
            if (state.details) decideRoute(state.details.cityId, state.details.routeDate, "recusada", checkedIds());
        });

        $("#routeDate, #routeCity, #routeStatus").on("change", load);
    }

    /** Ids marcados no diálogo de detalhes (RN-34). */
    function checkedIds() {
        return $("#routeDetailsBody [data-booking-include]:checked").map(function () {
            return Number($(this).val());
        }).get();
    }

    $(() => {
        bindEvents();
        loadCities();
        load();
    });

    return { state, load, decideRoute, openDetails };
})();