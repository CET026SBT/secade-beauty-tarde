const boFiscal = (() => {
    const state = { obligations: [], summary: null, alerts: [] };

    const TYPE_LABELS = { iva: "IVA", irc: "IRC", seguranca_social: "Segurança Social", seguros: "Seguros" };
    const PERIODICITY_LABELS = { mensal: "Mensal", trimestral: "Trimestral", anual: "Anual" };

    const ALERT_LABELS = {
        "30_dias": "30 dias", "15_dias": "15 dias", "7_dias": "7 dias",
        "3_dias": "3 dias", "1_dia": "1 dia", "em_atraso": "Em atraso"
    };

    const ALERT_CLASSES = {
        "30_dias": "bg-light text-dark", "15_dias": "bg-info text-dark", "7_dias": "bg-warning text-dark",
        "3_dias": "bg-warning text-dark", "1_dia": "bg-danger", "em_atraso": "bg-danger"
    };

    function alertBadge(level) {
        if (level === "pago") return '<span class="badge bg-secondary">Pago</span>';
        if (level === "sem_alerta") return '<span class="badge bg-light text-muted">Sem alerta</span>';

        const label = ALERT_LABELS[level] || level;
        const className = ALERT_CLASSES[level] || "bg-secondary";
        return `<span class="badge ${className}">${generalUtils.escapeHtml(label)}</span>`;
    }

    function row(obligation) {
        const paid = obligation.status === "pago";
        const daysClass = obligation.isOverdue ? "text-danger fw-bold" : "text-muted";
        const daysText = paid ? "-" : (obligation.isOverdue ? `${obligation.daysLeft} (atraso)` : obligation.daysLeft);

        const paidButton = paid
            ? `<button type="button" class="btn btn-sm btn-outline-secondary" disabled><i class="bi bi-check2-all"></i></button>`
            : `<button type="button" class="btn btn-sm btn-success" data-paid="${obligation.id}" title="Marcar como pago">
                   <i class="bi bi-check2"></i>
               </button>`;

        return `<tr>
            <td><span class="badge bg-primary">${generalUtils.escapeHtml(TYPE_LABELS[obligation.type] || obligation.type)}</span></td>
            <td>${generalUtils.escapeHtml(obligation.name)}</td>
            <td class="small">${generalUtils.escapeHtml(PERIODICITY_LABELS[obligation.periodicity] || obligation.periodicity)}</td>
            <td class="small">${generalUtils.formatDateTime(obligation.dueDate)}</td>
            <td class="text-center ${daysClass}">${daysText}</td>
            <td class="text-end">${generalUtils.formatCurrency(obligation.estimatedValue)}</td>
            <td class="text-center">
                ${paid ? '<span class="badge bg-success">Pago</span>' : '<span class="badge bg-warning text-dark">Pendente</span>'}
            </td>
            <td class="text-center">${alertBadge(obligation.alertLevel)}</td>
            <td class="text-end">${paidButton}</td>
        </tr>`;
    }

    function renderTable() {
        const $body = $("#fiscalTableBody");
        $body.empty();

        if (state.obligations.length === 0) {
            $body.html(`<tr><td colspan="9" class="text-center text-muted py-5">
                <i class="bi bi-receipt fs-3 d-block mb-2"></i>Nenhuma obrigação fiscal registada.
            </td></tr>`);
            return;
        }

        for (const obligation of state.obligations) $body.append(row(obligation));
    }

    function renderSummary() {
        const summary = state.summary || {};

        $("#sumTotal").text(summary.total ?? 0);
        $("#sumPending").text(summary.pending ?? 0);
        $("#sumDueSoon").text(summary.dueSoon ?? 0);
        $("#sumOverdue").text(summary.overdue ?? 0);
    }

    function renderAlerts() {
        const $panel = $("#fiscalAlertPanel");
        const $list = $("#fiscalAlertsList");

        if (state.alerts.length === 0) {
            $panel.addClass("d-none");
            return;
        }

        $panel.removeClass("d-none");
        $list.html(state.alerts.map(alert => {
            const className = ALERT_CLASSES[alert.type] || "bg-secondary";
            const label = ALERT_LABELS[alert.type] || alert.type;

            return `<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom py-2">
                <div>
                    <span class="badge ${className} me-2">${generalUtils.escapeHtml(label)}</span>
                    <strong>${generalUtils.escapeHtml(alert.obligationName)}</strong>
                    <span class="text-muted small">(${generalUtils.escapeHtml(TYPE_LABELS[alert.obligationType] || alert.obligationType)})</span>
                </div>
                <span class="small ${alert.isOverdue ? "text-danger fw-bold" : "text-muted"}">
                    Prazo: ${generalUtils.formatDateTime(alert.dueDate)}
                </span>
            </div>`;
        }).join(""));
    }

    async function loadCalendar() {
        const promise = API.admin.fiscal.calendar({
            type: $("#fiscalTypeFilter").val(),
            status: $("#fiscalStatusFilter").val()
        });

        const preloader = $("#fiscalTableContainer").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            state.obligations = response?.obligations || [];
            state.summary = response?.summary || null;

            renderTable();
            renderSummary();
        } catch (error) {
            $("#fiscalError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível carregar o calendário fiscal.");
        } finally {
            await preloader;
        }
    }

    async function loadAlerts() {
        try {
            const response = await API.admin.fiscal.alerts();
            state.alerts = response?.alerts || [];
            renderAlerts();
        } catch (error) {
            state.alerts = [];
            renderAlerts();
        }
    }

    async function saveObligation() {
        const payload = {
            type: $("#obType").val(),
            name: $("#obName").val(),
            periodicity: $("#obPeriodicity").val(),
            estimatedValue: $("#obValue").val() || 0,
            dueDate: $("#obDueDate").val()
        };

        $("#fiscalError").addClass("d-none");

        const promise = API.admin.fiscal.createObligation(payload);
        const preloader = $("#obligationFormCard").preloader(".jq-overlay-process", promise);

        try {
            await promise;

            $("#obligationFormCard").addClass("d-none");
            $("#obName, #obValue, #obDueDate").val("");

            await Promise.all([loadCalendar(), loadAlerts()]);
        } catch (error) {
            const errors = error?.responseJSON?.errors;
            $("#fiscalError").removeClass("d-none").text(
                errors ? Object.values(errors).join(" ") : (error?.responseJSON?.message || "Não foi possível guardar a obrigação.")
            );
        } finally {
            await preloader;
        }
    }

    async function markPaid(obligationId) {
        const promise = API.admin.fiscal.markPaid(Number(obligationId));
        const preloader = $("#fiscalTableContainer").preloader(".jq-overlay-process", promise);

        try {
            await promise;
            await Promise.all([loadCalendar(), loadAlerts()]);
        } catch (error) {
            $("#fiscalError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível marcar como pago.");
        } finally {
            await preloader;
        }
    }

    function bindEvents() {
        $("#toggleObligationFormBtn").on("click", function () {
            $("#obligationFormCard").toggleClass("d-none");
        });

        $("#cancelObligationBtn").on("click", function () {
            $("#obligationFormCard").addClass("d-none");
        });

        $("#saveObligationBtn").on("click", saveObligation);

        $(document).on("click", "[data-paid]", function () {
            markPaid($(this).data("paid"));
        });

        $("#fiscalTypeFilter, #fiscalStatusFilter").on("change", loadCalendar);

        $("#markAlertsReadBtn").on("click", async function () {
            try {
                await API.admin.fiscal.markAlertsRead();
                await loadAlerts();
            } catch (error) {
                $("#fiscalError").removeClass("d-none")
                    .text(error?.responseJSON?.message || "Não foi possível marcar os alertas como visualizados.");
            }
        });
    }

    $(() => {
        bindEvents();
        loadCalendar();
        loadAlerts();
    });

    return { state, loadCalendar, loadAlerts, saveObligation, markPaid };
})();