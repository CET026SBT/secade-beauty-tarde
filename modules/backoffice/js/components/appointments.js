const boAppointments = (() => {
    const state = {
        page: 1,
        perPage: 10,
        total: 0,
        pages: 1,
        bookings: [],
        filters: { date: "", local: "", status: "" },
        detailsId: null
    };

    const tableUI = {
        get $body() { return $("#appointmentsTableBody"); },

        render(bookings) {
            this.$body.empty();

            if (bookings.length === 0) {
                this.$body.html(`<tr><td colspan="8" class="text-center text-muted py-5">
                    <i class="bi bi-calendar-x fs-3 d-block mb-2"></i>Nenhum agendamento encontrado.
                </td></tr>`);
                return;
            }

            for (const booking of bookings) {
                this.$body.append(row(booking));
            }
        }
    };

    function row(booking) {
        const canCancel = !["cancelado", "executado", "concluido"].includes(booking.status);

        return `<tr>
            <td class="fw-bold">#${booking.id}</td>
            <td>
                <span class="d-block">${generalUtils.escapeHtml(booking.customerName || "-")}</span>
                <span class="d-block small text-muted">${generalUtils.escapeHtml(booking.customerPhone || "")}</span>
            </td>
            <td class="small">${generalUtils.formatDateTime(booking.dateTime)}</td>
            <td class="small">${generalUtils.escapeHtml(boUtils.localLabel(booking.local))}</td>
            <td class="small">${generalUtils.escapeHtml(booking.cityName || "-")}</td>
            <td>${boUtils.bookingStatusBadge(booking.status)}</td>
            <td class="text-end">${generalUtils.formatCurrency(booking.totalAmount)}</td>
            <td class="text-end text-nowrap">
                <button type="button" class="btn btn-sm btn-outline-primary" data-details="${booking.id}">
                    <i class="bi bi-eye"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" data-cancel="${booking.id}"
                        ${canCancel ? "" : "disabled"}>
                    <i class="bi bi-x-circle"></i>
                </button>
            </td>
        </tr>`;
    }

    function renderPagination() {
        const $pagination = $("#appointmentsPagination");
        $pagination.empty();

        $("#appointmentsCount").text(`${state.total} agendamento(s)`);
        $("#paginationInfo").text(`Página ${state.page} de ${Math.max(state.pages, 1)}`);

        if (state.pages <= 1) return;

        const items = [];
        items.push(`<li class="page-item ${state.page === 1 ? "disabled" : ""}">
            <button class="page-link" data-page="${state.page - 1}">&laquo;</button></li>`);

        for (let page = 1; page <= state.pages; page++) {
            items.push(`<li class="page-item ${page === state.page ? "active" : ""}">
                <button class="page-link" data-page="${page}">${page}</button></li>`);
        }

        items.push(`<li class="page-item ${state.page === state.pages ? "disabled" : ""}">
            <button class="page-link" data-page="${state.page + 1}">&raquo;</button></li>`);

        $pagination.html(items.join(""));
    }

    async function load() {
        const promise = API.admin.appointments({
            page: state.page,
            perPage: state.perPage,
            date: state.filters.date,
            local: state.filters.local,
            status: state.filters.status
        });

        const preloader = $("#appointmentsTableContainer").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            state.bookings = response?.bookings || [];
            state.total = Number(response?.total || 0);
            state.pages = Number(response?.pages || 1);

            tableUI.render(state.bookings);
            renderPagination();
        } catch (error) {
            tableUI.$body.html(`<tr><td colspan="8" class="text-center text-danger py-5">
                Não foi possível carregar os agendamentos.
            </td></tr>`);
        } finally {
            await preloader;
        }
    }

    function bookingById(id) {
        return state.bookings.find(booking => Number(booking.id) === Number(id));
    }

    // A modal de Bootstrap 5.0 não tem `getOrCreateInstance` (só existe a partir da 5.1);
    // usar esta função evita o erro ao abrir o detalhe do agendamento.
    function modalInstance() {
        const element = $("#appointmentDetailsModal")[0];
        if (!element) return null;

        return bootstrap.Modal.getInstance(element) || new bootstrap.Modal(element);
    }

    async function openDetails(id) {
        const booking = bookingById(id);
        if (!booking) return;

        state.detailsId = Number(id);

        const promise = API.admin.appointmentDetails(Number(id));
        const preloader = $("#appointmentDetailsBody").preloader(".jq-overlay-process", promise);

        modalInstance()?.show();

        try {
            const response = await promise;
            $("#appointmentDetailsBody").html(renderDetails(booking, response));

            $("#modalCancelAppointmentBtn")
                .prop("disabled", ["cancelado", "executado", "concluido"].includes(booking.status));
        } catch (error) {
            $("#appointmentDetailsBody").html(`<div class="alert alert-danger mb-0">
                ${generalUtils.escapeHtml(error?.responseJSON?.message || "Não foi possível carregar o detalhe.")}
            </div>`);
        } finally {
            await preloader;
        }
    }

    function detailRow(label, value) {
        return `<div class="d-flex justify-content-between border-bottom py-2">
            <span class="text-muted">${label}</span><span class="fw-bold text-end">${value}</span>
        </div>`;
    }

    async function executeAppointment(bookingId) {
        if (!confirm("Registar a execução deste agendamento? O cliente poderá depois avaliar o serviço.")) return;

        const promise = API.admin.executeAppointment(Number(bookingId));
        const preloader = $("#appointmentDetailsBody").preloader(".jq-overlay-process", promise);

        try {
            await promise;

            bootstrap.Modal.getInstance($("#appointmentDetailsModal")[0])?.hide();
            await load();
        } catch (error) {
            alert(error?.responseJSON?.message || "Não foi possível registar a execução.");
        } finally {
            await preloader;
        }
    }

    function renderDetails(booking, response) {
        const detail = response?.booking || {};
        const services = response?.services || [];
        const progress = response?.progress || {};
        const execution = response?.execution;

        const servicesRows = services.length === 0
            ? '<li class="text-muted">Sem serviços registados.</li>'
            : services.map(service => {
                const person = service.personName ? ` <span class="badge bg-light text-dark">${generalUtils.escapeHtml(service.personName)}</span>` : "";
                const accepted = service.acceptanceStatus === "aceite";
                const employee = service.employeeName
                    ? `<i class="bi bi-person-check me-1"></i>${generalUtils.escapeHtml(service.employeeName)}`
                    : '<span class="text-muted">por atribuir</span>';
                const greenReceipt = accepted && service.greenReceiptEmployee !== null
                    ? `<span class="text-muted small d-block">RV simulado: ${generalUtils.formatCurrency(service.greenReceiptEmployee)} / ${generalUtils.formatCurrency(service.greenReceiptPlatform)}</span>`
                    : "";

                return `<li class="mb-2">
                    <span class="fw-bold">${generalUtils.escapeHtml(service.serviceName || "")}</span>${person}
                    <span class="badge ${accepted ? "bg-success" : "bg-secondary"} ms-1">${accepted ? "Aceite" : "Pendente"}</span>
                    <span class="d-block small">${employee} · ${generalUtils.formatCurrency(service.price)}</span>
                    ${greenReceipt}
                </li>`;
            }).join("");

        const executionBlock = execution
            ? detailRow("Execução", `<span class="badge bg-primary">${generalUtils.escapeHtml(execution.executionStatus)}</span>
                <span class="d-block small text-muted">${generalUtils.formatDateTime(execution.startedAt)}</span>`)
            : "";

        const canExecute = ["totalmente_aceite_funcionarios", "confirmado", "pendente_validacao_logistica_loja"].includes(booking.status) && !execution;

        return `
            <div class="row g-4">
                <div class="col-lg-6">
                    ${detailRow("Referência", `#${booking.id}`)}
                    ${detailRow("Cliente", generalUtils.escapeHtml(booking.customerName || "-"))}
                    ${detailRow("Telemóvel", generalUtils.escapeHtml(booking.customerPhone || "-"))}
                    ${detailRow("E-mail", generalUtils.escapeHtml(detail.customerEmail || "-"))}
                    ${detailRow("Data / Hora", generalUtils.formatDateTime(booking.dateTime))}
                    ${detailRow("Local", generalUtils.escapeHtml(boUtils.localLabel(booking.local)))}
                    ${detailRow("Cidade", generalUtils.escapeHtml(booking.cityName || "-"))}
                    ${booking.local === "carrinha_ambulante" ? detailRow("Morada", generalUtils.escapeHtml(detail.fullAddress || "-")) : ""}
                </div>
                <div class="col-lg-6">
                    ${detailRow("Estado", boUtils.bookingStatusBadge(booking.status))}
                    ${detailRow("Valor total", generalUtils.formatCurrency(booking.totalAmount))}
                    ${detailRow("Sinal", booking.isDepositPaid
                        ? generalUtils.formatCurrency(booking.depositAmount)
                        : "Não pago (simulação)")}
                    ${detailRow("Progresso aceitação", `
                        <span class="badge bg-success">${progress.accepted ?? 0} aceite(s)</span>
                        <span class="badge bg-secondary">${progress.pending ?? 0} pendente(s)</span>`)}
                    ${detailRow("Consolidado", progress.isConsolidated
                        ? '<span class="badge bg-info text-dark">Sim — janela bloqueada</span>'
                        : '<span class="badge bg-light text-muted">Não</span>')}
                    ${executionBlock}
                    ${detailRow("Criado em", generalUtils.formatDateTime(booking.createdAt))}
                </div>
                <div class="col-12">
                    <h6 class="fw-bold small text-uppercase mt-2">Serviços e funcionários</h6>
                    <ul class="list-unstyled small mb-0">${servicesRows}</ul>
                </div>
            </div>

            ${canExecute ? `<div class="mt-3">
                <button type="button" class="btn btn-sm btn-outline-primary" data-execute="${booking.id}">
                    <i class="bi bi-check2-square me-1"></i> Registar execução do serviço
                </button>
            </div>` : ""}
        `;
    }

    async function cancelAppointment(id) {
        const booking = bookingById(id);
        if (!booking) return;

        if (!confirm(`Cancelar o agendamento #${booking.id} de ${booking.customerName}?`)) return;

        const promise = API.admin.cancelAppointment(Number(id));
        const preloader = $("#appointmentsTableContainer").preloader(".jq-overlay-process", promise);

        try {
            await promise;
            bootstrap.Modal.getInstance($("#appointmentDetailsModal")[0])?.hide();
            await load();
        } catch (error) {
            alert(error?.responseJSON?.message || "Não foi possível cancelar o agendamento.");
        } finally {
            await preloader;
        }
    }

    function bindEvents() {
        boUtils.fillStatusSelect("#filterStatus", boUtils.BOOKING_STATUS_LABELS, "Todos");

        $("#applyFiltersBtn").on("click", function () {
            state.filters = {
                date: $("#filterDate").val(),
                local: $("#filterLocal").val(),
                status: $("#filterStatus").val()
            };
            state.page = 1;
            load();
        });

        $("#clearFiltersBtn").on("click", function () {
            $("#filterDate").val("");
            $("#filterLocal").val("");
            $("#filterStatus").val("");

            state.filters = { date: "", local: "", status: "" };
            state.page = 1;
            load();
        });

        $(document).on("click", "[data-details]", function () {
            openDetails($(this).data("details"));
        });

        $(document).on("click", "[data-cancel]", function () {
            cancelAppointment($(this).data("cancel"));
        });

        $(document).on("click", "#appointmentsPagination [data-page]", function () {
            const page = Number($(this).data("page"));

            if (page < 1 || page > state.pages || page === state.page) return;

            state.page = page;
            load();
        });

        $("#modalCancelAppointmentBtn").on("click", function () {
            if (state.detailsId) cancelAppointment(state.detailsId);
        });

        $(document).on("click", "[data-execute]", function () {
            executeAppointment($(this).data("execute"));
        });
    }

    $(() => {
        bindEvents();
        load();
    });

    return { state, load };
})();