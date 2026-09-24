const boUtils = (() => {
    const BOOKING_STATUS_LABELS = {
        pendente_aceitacao_funcionarios: "Aguarda aceitação (funcionários)",
        pendente_validacao_logistica_loja: "Pendente validação logística (loja)",
        totalmente_aceite_funcionarios: "Totalmente aceite por funcionários",
        confirmado: "Confirmado",
        recusado: "Recusado",
        cancelado: "Cancelado",
        executado: "Executado",
        concluido: "Concluído"
    };

    const BOOKING_STATUS_CLASSES = {
        pendente_aceitacao_funcionarios: "bg-secondary",
        pendente_validacao_logistica_loja: "bg-warning text-dark",
        totalmente_aceite_funcionarios: "bg-info text-dark",
        confirmado: "bg-success",
        recusado: "bg-danger",
        cancelado: "bg-dark",
        executado: "bg-primary",
        concluido: "bg-primary"
    };

    const ROUTE_STATUS_LABELS = {
        planeada: "Planeada",
        aprovada: "Aprovada",
        recusada: "Recusada",
        em_execucao: "Em execução",
        concluida: "Concluída"
    };

    const ROUTE_STATUS_CLASSES = {
        planeada: "bg-secondary",
        aprovada: "bg-success",
        recusada: "bg-danger",
        em_execucao: "bg-primary",
        concluida: "bg-primary"
    };

    const LOCAL_LABELS = {
        loja_fisica: "Loja Física",
        carrinha_ambulante: "Carrinha Ambulante"
    };

    function bookingStatusBadge(status) {
        const label = BOOKING_STATUS_LABELS[status] || status || "-";
        const className = BOOKING_STATUS_CLASSES[status] || "bg-secondary";
        return `<span class="badge bo-status-badge ${className}">${generalUtils.escapeHtml(label)}</span>`;
    }

    function routeStatusBadge(status) {
        const label = ROUTE_STATUS_LABELS[status] || status || "-";
        const className = ROUTE_STATUS_CLASSES[status] || "bg-secondary";
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