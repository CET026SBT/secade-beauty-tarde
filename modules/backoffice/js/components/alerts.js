const boAlerts = (() => {
    const GROUP_ICONS = {
        fiscal: "bi-receipt-cutoff",
        servicos_pendentes: "bi-list-check",
        rotas: "bi-signpost-split",
        alocacoes: "bi-calendar-check"
    };

    const CARD_LIMIT = 10; // #limiteCards: cartões visíveis por secção

    function itemRow(item) {
        const urgent = item.highlighted ? " bo-alert--urgent" : "";

        return `<li class="border-top py-2 d-flex justify-content-between align-items-start gap-2${urgent}">
            <span>
                <span class="d-block fw-bold small">${generalUtils.escapeHtml(item.title)}</span>
                <span class="d-block text-muted small">${generalUtils.escapeHtml(item.detail || "")}</span>
            </span>
            <a class="btn btn-sm btn-outline-primary flex-shrink-0" title="Abrir a página de origem"
               href="${BASE_URL}${item.pageUrl}"><i class="bi bi-arrow-right"></i></a>
        </li>`;
    }

    function groupCard(group) {
        const icon = GROUP_ICONS[group.key] || "bi-info-circle";
        const badge = Number(group.count) > 0
            ? `<span class="badge bg-primary">${Number(group.count)}</span>`
            : `<span class="badge bg-light text-muted">0</span>`;

        const items = group.items || [];
        let body;

        if (items.length === 0) {
            body = `<p class="text-muted small mb-0">Nada por tratar aqui.</p>`;
        } else {
            const visible = items.slice(0, CARD_LIMIT).map(itemRow).join("");
            const hidden = items.slice(CARD_LIMIT).map(itemRow).join("");

            body = `<ul class="list-unstyled mb-0">${visible}</ul>`;

            if (hidden) {
                body += `<ul class="list-unstyled mb-0 d-none" data-more-list>${hidden}</ul>`
                     + `<button type="button" class="btn btn-sm btn-link p-0 mt-2 text-decoration-none" data-show-more>`
                     + `Mostrar mais (${items.length - CARD_LIMIT})</button>`;
            }
        }

        return `<div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi ${icon} text-primary me-2"></i>${generalUtils.escapeHtml(group.label)}</span>
                    ${badge}
                </div>
                <div class="card-body">${body}</div>
            </div>
        </div>`;
    }

    async function load() {
        const promise = API.admin.alerts.list();
        const preloader = $("main").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            $("#alertsGroups").html((response?.groups || []).map(groupCard).join(""));
            $("#alertsCountBadge").text(`${Number(response?.count || 0)} por tratar`);
            boUtils.setBellCount(response?.count || 0);
        } catch (error) {
            $("#alertsError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível carregar os avisos.");
        } finally {
            await preloader;
        }
    }

    async function markRead() {
        if (!(await generalUtils.confirmDialog({ title: "Marcar todos os alertas fiscais como lidos?", icon: "question" }))) return;

        $("#alertsError, #alertsResult").addClass("d-none");

        try {
            const response = await API.admin.alerts.markRead();

            $("#alertsResult").removeClass("d-none")
                .text(response?.message || "Alertas atualizados.");

            await load();
        } catch (error) {
            $("#alertsError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível marcar os alertas como lidos.");
        }
    }

    $(() => {
        if (!$("#alertsGroups").length) return;

        $("#markAlertsReadBtn").on("click", markRead);

        // #limiteCards — «mostrar mais» revela o resto da secção.
        $(document).on("click", "[data-show-more]", function () {
            const $card = $(this).closest(".card-body");
            $card.find("[data-more-list]").removeClass("d-none");
            $(this).remove();
        });

        load();
    });

    return { load, markRead };
})();