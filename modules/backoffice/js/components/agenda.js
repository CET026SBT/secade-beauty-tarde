const boAgenda = (() => {
    const state = {
        month: null,
        days: {},
        selectedDay: null
    };

    const WEEKDAYS = ["Seg", "Ter", "Qua", "Qui", "Sex", "Sáb", "Dom"];

    function renderKpis(data) {
        const cards = [
            { label: "Serviços no mês", value: String(Number(data?.totalServices || 0)), icon: "bi-list-check" },
            { label: "Tempo estimado", value: generalUtils.formatDuration(data?.totalMinutes || 0), icon: "bi-clock-history" },
            { label: "Valor dos serviços", value: generalUtils.formatCurrencyWithVat(data?.totalGross || 0), icon: "bi-cash-coin" }
        ];

        $("#agendaKpis").html(cards.map((card) => `
            <div class="col-sm-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="bo-kpi-icon"><i class="bi ${card.icon}"></i></span>
                        <div>
                            <span class="d-block text-muted small">${generalUtils.escapeHtml(card.label)}</span>
                            <span class="d-block fs-4 fw-bold">${generalUtils.escapeHtml(card.value)}</span>
                        </div>
                    </div>
                </div>
            </div>`).join(""));
    }

    /**
     * Calendário em grelha própria (sem biblioteca nova — §1): 7 colunas,
     * começando à segunda-feira, com os dias do mês anterior/seguinte esbatidos.
     */
    function renderCalendar(month, days) {
        const [year, monthNumber] = String(month).split("-").map(Number);
        const firstDay = new Date(year, monthNumber - 1, 1);
        const daysInMonth = new Date(year, monthNumber, 0).getDate();
        const leadingBlanks = (firstDay.getDay() + 6) % 7;

        const header = WEEKDAYS.map((label) => `<div class="bo-calendar-head">${label}</div>`).join("");

        let cells = "";

        for (let i = 0; i < leadingBlanks; i++) {
            cells += `<div class="bo-calendar-day bo-calendar-day--muted"></div>`;
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const isoDate = `${month}-${String(day).padStart(2, "0")}`;
            const entry = days?.[isoDate];
            const count = entry ? entry.services.length : 0;
            const classes = ["bo-calendar-day"];

            if (count > 0) classes.push("bo-calendar-day--busy");
            if (state.selectedDay === isoDate) classes.push("bo-calendar-day--selected");

            cells += `<button type="button" class="${classes.join(" ")}" data-day="${isoDate}">
                <span class="d-block fw-bold">${day}</span>
                ${count > 0 ? `<span class="bo-calendar-badge">${count}</span>` : ""}
            </button>`;
        }

        $("#agendaCalendar").html(`<div class="bo-calendar-grid">${header}${cells}</div>`);
        $("#agendaMonthLabel").text(month.split("-").reverse().join("/"));
    }

    function renderDay(isoDate) {
        const entry = state.days?.[isoDate];

        $("#agendaDayLabel").text(`Serviços de ${isoDate.split("-").reverse().join("/")}`);

        if (!entry || entry.services.length === 0) {
            $("#agendaDayServices").html(`<p class="text-muted small mb-0">Sem serviços de rota confirmada neste dia.</p>`);
            return;
        }

        $("#agendaDayServices").html(entry.services.map((service) => `
            <div class="border rounded p-2 mb-2">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <span class="fw-bold small">${generalUtils.escapeHtml(service.serviceName)}</span>
                    <span class="badge bg-light text-dark">${String(service.dateTime).substring(11, 16)}</span>
                </div>
                <span class="d-block text-muted small">
                    ${generalUtils.escapeHtml(service.customerName)}
                    ${service.cityName ? " · " + generalUtils.escapeHtml(service.cityName) : ""}
                    ${service.personName ? " · " + generalUtils.escapeHtml(service.personName) : ""}
                </span>
                <span class="d-block text-muted small">
                    ${generalUtils.formatDuration(service.duration)} ·
                    ${boUtils.localLabel(service.local)} ·
                    ${generalUtils.formatCurrencyWithVat(service.price)}
                </span>
            </div>`).join("") + `
            <p class="text-muted small mb-0">
                Total do dia: ${generalUtils.formatDuration(entry.minutes)} ·
                ${generalUtils.formatCurrencyWithVat(entry.gross)}
            </p>`);
    }

    function selectDay(isoDate) {
        state.selectedDay = isoDate;
        renderCalendar(state.month, state.days);
        renderDay(isoDate);
    }

    async function load() {
        const month = $("#agendaMonth").val() || state.month || new Date().toISOString().substring(0, 7);

        const promise = API.admin.agenda.month(month);

        const preloader = $("main").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            state.month = response?.month || month;
            state.days = {};

            for (const day of response?.days || []) {
                state.days[day.date] = day;
            }

            $("#agendaMonth").val(state.month);

            renderKpis(response);
            renderCalendar(state.month, state.days);

            if (state.selectedDay && state.days[state.selectedDay]) {
                selectDay(state.selectedDay);
            } else {
                state.selectedDay = null;
                $("#agendaDayLabel").text("Escolha um dia");
                $("#agendaDayServices").html(`<p class="text-muted small mb-0">Clique num dia do calendário para ver os serviços marcados.</p>`);
            }
        } catch (error) {
            $("#agendaError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível carregar a agenda.");
        } finally {
            await preloader;
        }
    }

    $(() => {
        if (!$("#agendaCalendar").length) return;

        $("#agendaMonth").val(new Date().toISOString().substring(0, 7));

        $("#agendaMonth").on("change", load);

        $("#agendaTodayBtn").on("click", function () {
            $("#agendaMonth").val(new Date().toISOString().substring(0, 7));
            load();
        });

        $(document).on("click", "[data-day]", function () {
            selectDay($(this).data("day"));
        });

        load();
    });

    return { load, selectDay, state };
})();