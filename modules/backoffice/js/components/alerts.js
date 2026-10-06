const boAlerts = (() => {
    const GROUP_ICONS = {
        fiscal: "bi-receipt-cutoff",
        fiscal_atraso: "bi-exclamation-triangle",
        servicos_pendentes: "bi-list-check",
        rotas: "bi-signpost-split",
        alocacoes: "bi-person-check"
    };

    function groupCard(group) {
        const icon = GROUP_ICONS[group.key] || "bi-info-circle";
        // §3.7/F5: o grupo de atrasos fica REALÇADO (é o mais urgente).
        const isOverdue = group.key === "fiscal_atraso";
        const total = Number(group.count) || 0;

        const badge = total > 0
            ? `<span class="badge ${isOverdue ? "bg-danger" : "bg-primary"}">${total}</span>`
            : `<span class="badge bg-light text-muted">0</span>`;

        const items = (group.items || []).length === 0
            ? `<p class="text-muted small mb-0">Nada por tratar aqui.</p>`
            : `<ul class="list-unstyled mb-0">` + group.items.map((item) => `
                    <li class="border-top py-2 d-flex justify-content-between align-items-start gap-2">
                        <span>
                            <span class="d-block fw-bold small">${generalUtils.escapeHtml(item.title)}</span>
                            <span class="d-block text-muted small">${generalUtils.escapeHtml(item.detail || "")}</span>
                        </span>
                        <a class="btn btn-sm btn-outline-primary flex-shrink-0" title="Abrir a página de origem"
                           href="${BASE_URL}${item.pageUrl}"><i class="bi bi-arrow-right"></i></a>
                    </li>`).join("") + `</ul>`;

        // §3.7/F5 · Q-15: 10 cards por secção + «mostrar mais» (navega para a página).
        const more = group.hasMore
            ? `<a class="btn btn-sm btn-outline-secondary mt-2 w-100" href="${BASE_URL}${group.showMoreUrl}">
                   <i class="bi bi-arrow-down-circle me-1"></i>Mostrar mais ${group.hidden}
               </a>`
            : "";

        return `<div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100 ${isOverdue ? "border-start border-danger border-4" : ""}">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi ${icon} ${isOverdue ? "text-danger" : "text-primary"} me-2"></i>${generalUtils.escapeHtml(group.label)}</span>
                    ${badge}
                </div>
                <div class="card-body">${items}${more}</div>
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
        if (!await generalUtils.confirmDialog({ title: "Marcar alertas", text: "Marcar todos os alertas fiscais como lidos?" })) return;

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
        load();
    });

    return { load, markRead };
})();