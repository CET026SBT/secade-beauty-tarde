const boUtils = (() => {
    // Fonte única: `modules/common/js/utils/bookingStatus.utils.js` (ver lá o porquê).
    // Aqui fica só a projecção para o público "admin" e o que é exclusivo do backoffice.
    const BOOKING_STATUS_LABELS = bookingStatusUtils.booking.labels("admin");
    const ROUTE_STATUS_LABELS = bookingStatusUtils.route.labels;

    const LOCAL_LABELS = {
        loja_fisica: "Loja Física",
        carrinha_ambulante: "Carrinha Ambulante"
    };

    function bookingStatusBadge(status) {
        const label = bookingStatusUtils.booking.label(status, "admin");
        const className = bookingStatusUtils.booking.className(status);
        return `<span class="badge bo-status-badge ${className}">${generalUtils.escapeHtml(label)}</span>`;
    }

    function routeStatusBadge(status) {
        const label = bookingStatusUtils.route.label(status);
        const className = bookingStatusUtils.route.className(status);
        return `<span class="badge bo-status-badge ${className}">${generalUtils.escapeHtml(label)}</span>`;
    }

    function localLabel(local) {
        return LOCAL_LABELS[local] || local || "-";
    }

    /**
     * Preenche um <select> com as opções de estados (inclui opção "Todos").
     */
    function fillStatusSelect(selector, labels, allLabel) {
        const $select = $(selector);
        if ($select.length === 0) return;

        const current = $select.val();
        const options = [`<option value="">${generalUtils.escapeHtml(allLabel)}</option>`];

        for (const [value, label] of Object.entries(labels)) {
            options.push(`<option value="${value}">${generalUtils.escapeHtml(label)}</option>`);
        }

        $select.html(options.join(""));
        $select.val(current ?? "");
    }

    function fillCitySelect(selector, cities, allLabel) {
        const $select = $(selector);
        if ($select.length === 0) return;

        const current = $select.val();
        const options = [`<option value="">${generalUtils.escapeHtml(allLabel)}</option>`];

        for (const city of cities) {
            options.push(`<option value="${city.id}">${generalUtils.escapeHtml(city.name)}</option>`);
        }

        $select.html(options.join(""));
        $select.val(current ?? "");
    }

    /**
     * Convenção única dos botões de ação das listagens (§24.7 item 8, 28/09/2026):
     * sem texto, **ícone + `title`**, fundo transparente e a cor do tipo de ação —
     * dourado = detalhes · azul = editar · vermelho = remover. As cores vivem no
     * `style.css` (`.bo-action--*`), para não haver duas fontes de estilo.
     */
    const ACTION_BUTTONS = {
        details: { icon: "bi-eye",     cssClass: "bo-action--details" },
        edit:    { icon: "bi-pencil",  cssClass: "bo-action--edit" },
        remove:  { icon: "bi-trash",   cssClass: "bo-action--remove" }
    };

    function actionButton(action, title, attributes = "") {
        const definition = ACTION_BUTTONS[action];
        if (!definition) return "";

        return `<button type="button" class="btn btn-sm bo-action ${definition.cssClass}" `
            + `title="${generalUtils.escapeHtml(title || "")}" ${attributes}>`
            + `<i class="bi ${definition.icon}"></i></button>`;
    }

    /**
     * Contador de avisos do sino (RF-81). Zero esconde o badge: um "0" aceso
     * sugeriria avisos que não existem.
     */
    function setBellCount(count) {
        const $badge = $("#boBellCount");
        if (!$badge.length) return;

        const total = Number(count || 0);
        $badge.toggleClass("d-none", total <= 0).text(total > 99 ? "99+" : String(total));
    }

    async function loadBellCount() {
        if (!$("#boBellCount").length || typeof API === "undefined") return;

        try {
            const response = await API.admin.alerts.summary();
            setBellCount(response?.count || 0);
        } catch (error) {
            setBellCount(0);
        }
    }

    return {
        BOOKING_STATUS_LABELS,
        ROUTE_STATUS_LABELS,
        bookingStatusBadge,
        routeStatusBadge,
        localLabel,
        fillStatusSelect,
        fillCitySelect,
        actionButton,
        setBellCount,
        loadBellCount
    };
})();