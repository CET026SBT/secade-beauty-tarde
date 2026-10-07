const boDashboard = (() => {
    const state = {
        charts: {},
        data: null
    };

    // Paleta do projeto (style.css) + apoio aos gráficos.
    const COLORS = {
        primary: "#c9aa55",
        primaryLight: "#dbc17b",
        dark: "#212529",
        palette: ["#c9aa55", "#212529", "#198754", "#0dcaf0", "#ffc107", "#dc3545", "#6c757d", "#dbc17b"]
    };

    const KPI_CARDS = [
        { key: "bookingsToday",          label: "Agendamentos hoje",         icon: "bi-calendar-day",         format: "int" },
        { key: "revenueToday",           label: "Receita prevista hoje",     icon: "bi-cash-coin",            format: "money" },
        { key: "bookingsForecast",       label: "Agendamentos (7 dias)",     icon: "bi-calendar-week",        format: "int" },
        { key: "revenueForecast",        label: "Receita prevista (7 dias)", icon: "bi-graph-up",             format: "money" },
        { key: "servicesPending",        label: "Serviços por aceitar",      icon: "bi-list-check",           format: "int", page: "/gestao/servicos" },
        { key: "routesAwaitingDecision", label: "Rotas por decidir",         icon: "bi-signpost-split",       format: "int", page: "/gestao/rotas" },
        { key: "alertsUnread",           label: "Avisos por ler",            icon: "bi-bell",                 format: "int", page: "/gestao/avisos" },
        { key: "fiscalOverdue",          label: "Obrigações em atraso",      icon: "bi-exclamation-triangle", format: "int", page: "/gestao/fiscal" },
        { key: "activeSuppliers",        label: "Fornecedores ativos",       icon: "bi-truck",                format: "int", page: "/gestao/fornecedores" },
        { key: "activeServices",         label: "Serviços no catálogo",      icon: "bi-scissors",             format: "int" },
        { key: "customers",              label: "Clientes",                  icon: "bi-people",               format: "int" },
        { key: "confirmedToday",         label: "Confirmados hoje",          icon: "bi-check2-circle",        format: "int" }
    ];

    function formatValue(value, format) {
        return format === "money" ? generalUtils.formatCurrency(value) : String(Number(value ?? 0));
    }

    function renderKpis(kpis) {
        const $container = $("#dashboardKpis").empty();

        for (const card of KPI_CARDS) {
            const value = formatValue(kpis?.[card.key], card.format);
            const content = `
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="bo-kpi-icon"><i class="bi ${card.icon}"></i></span>
                        <div>
                            <span class="d-block text-muted small">${generalUtils.escapeHtml(card.label)}</span>
                            <span class="d-block fs-4 fw-bold">${generalUtils.escapeHtml(value)}</span>
                        </div>
                    </div>
                </div>`;

            const wrapped = card.page
                ? `<a class="text-decoration-none text-reset d-block h-100" href="${BASE_URL}${card.page}">${content}</a>`
                : content;

            $container.append(`<div class="col-sm-6 col-xl-3">${wrapped}</div>`);
        }
    }

    /**
     * Só desenha quando há dados: um gráfico vazio publicaria zeros que não são medição.
     */
    function renderChart(canvasId, emptyId, config, hasData) {
        const canvas = document.getElementById(canvasId);

        if (!hasData) {
            if (canvas) canvas.classList.add("d-none");
            $(emptyId).removeClass("d-none");
            return;
        }

        $(emptyId).addClass("d-none");

        if (state.charts[canvasId]) {
            state.charts[canvasId].destroy();
        }

        state.charts[canvasId] = new Chart(canvas.getContext("2d"), config);
    }

    function renderBar(canvasId, emptyId, chart, formatter) {
        const hasData = (chart?.data || []).some((value) => Number(value) > 0);

        renderChart(canvasId, emptyId, {
            type: "bar",
            data: {
                labels: (chart?.labels || []).map((label) => formatter(label)),
                datasets: [{
                    label: "Total",
                    data: chart?.data || [],
                    backgroundColor: COLORS.primary,
                    borderColor: COLORS.primaryLight,
                    borderWidth: 1
                }]
            },
            options: {
                legend: { display: false },
                scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] }
            }
        }, hasData);
    }

    function renderDoughnut(canvasId, emptyId, chart) {
        const hasData = (chart?.data || []).some((value) => Number(value) > 0);

        renderChart(canvasId, emptyId, {
            type: "doughnut",
            data: {
                labels: (chart?.labels || []).map((label) => generalUtils.humanize(label)),
                datasets: [{ data: chart?.data || [], backgroundColor: COLORS.palette }]
            },
            options: { legend: { position: "right" } }
        }, hasData);
    }

    function renderAccounting(accounting) {
        const $target = $("#accountingState");

        if (!accounting || accounting.available) {
            $target.addClass("d-none");
            return;
        }

        $target.removeClass("d-none")
            .html(`<i class="bi bi-info-circle me-1"></i>${generalUtils.escapeHtml(accounting.message)}`);
    }

    function render(dashboard) {
        renderKpis(dashboard?.kpis);

        renderBar("chartBookingsPerDay", "#chartBookingsPerDayEmpty", dashboard?.charts?.bookingsPerDay, function (label) {
            return String(generalUtils.formatDateTime(label)).split(",")[0];
        });

        renderDoughnut("chartBookingsByState", "#chartBookingsByStateEmpty", dashboard?.charts?.bookingsByState);
        renderDoughnut("chartServicesByAcceptance", "#chartServicesByAcceptanceEmpty", dashboard?.charts?.servicesByAcceptance);

        renderBar("chartFiscalByType", "#chartFiscalByTypeEmpty", dashboard?.charts?.fiscalByType, function (label) {
            return generalUtils.humanize(label);
        });

        renderAccounting(dashboard?.accounting);
    }

    async function load() {
        const promise = API.admin.dashboard();

        const preloader = $("main").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            state.data = response;
            render(response);
        } catch (error) {
            debugger;
            $("#dashboardError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível carregar o painel.");
        } finally {
            await preloader;
        }
    }

    $(() => {
        load();
    });

    return { state, load };
})();
