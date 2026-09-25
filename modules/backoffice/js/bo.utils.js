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

    return {
        BOOKING_STATUS_LABELS,
        ROUTE_STATUS_LABELS,
        bookingStatusBadge,
        routeStatusBadge,
        localLabel,
        fillStatusSelect,
        fillCitySelect
    };
})();