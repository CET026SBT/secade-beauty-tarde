const boServices = (() => {
    const state = {
        pending: [],
        accepted: [],
        categories: [],
        totals: { employee: 0, platform: 0, count: 0 },
        config: null
    };

    function money(value) { return generalUtils.formatCurrency(value); }

    function pendingCard(service) {
        // A API devolve as chaves já mapeadas (camelCase) — ver BookingServiceMapper.
        const person = service.personName ? ` <span class="badge bg-light text-dark">${generalUtils.escapeHtml(service.personName)}</span>` : "";

        return `<div class="border rounded p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <span class="fw-bold">${generalUtils.escapeHtml(service.serviceName || "")}</span>${person}
                    <div class="small text-muted">
                        <i class="bi bi-tag me-1"></i>${generalUtils.escapeHtml(service.categoryName || "")}
                        · ${generalUtils.formatDateTime(service.dateTime)}
                    </div>
                </div>
                <span class="fw-bold">${money(service.price)}</span>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="small text-muted">
                    <i class="bi bi-person-badge me-1"></i>Agendamento #${service.bookingId}
                    · ${generalUtils.formatDuration(service.durationMinutes)}
                </span>
                <button type="button" class="btn btn-sm btn-success"
                        data-accept="${service.id}" data-booking="${service.bookingId}">
                    <i class="bi bi-hand-thumbs-up me-1"></i> Aceitar serviço
                </button>
            </div>
        </div>`;
    }

    function acceptedCard(service) {
        const person = service.personName ? ` <span class="badge bg-light text-dark">${generalUtils.escapeHtml(service.personName)}</span>` : "";

        return `<div class="border rounded p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <span class="fw-bold">${generalUtils.escapeHtml(service.serviceName || "")}</span>${person}
                    <div class="small text-muted">${generalUtils.formatDateTime(service.dateTime)}
                        · ${generalUtils.escapeHtml(service.customerName || "")}</div>
                </div>
                <span class="badge bg-success">Aceite</span>
            </div>
            <div class="row g-2 small mb-2">
                <div class="col-6">
                    <span class="text-muted d-block">A receber (${service.employeePercentage ?? "-"}%)</span>
                    <span class="fw-bold text-success">${money(service.greenReceiptEmployee)}</span>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block">Plataforma</span>
                    <span class="fw-bold">${money(service.greenReceiptPlatform)}</span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger"
                    data-unaccept="${service.id}" data-booking="${service.bookingId}">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Desfazer
            </button>
        </div>`;
    }

    function renderPending() {
        const $list = $("#pendingList");
        $list.empty();

        $("#pendingCount").text(`${state.pending.length} serviço(s) por aceitar`);

        if (state.pending.length === 0) {
            $list.html(`<div class="text-center text-muted py-4">
                <i class="bi bi-check2-all fs-3 d-block mb-2"></i>Não existem serviços por aceitar.
            </div>`);
            return;
        }

        for (const service of state.pending) $list.append(pendingCard(service));
    }

    function renderAccepted() {
        const $list = $("#acceptedList");
        $list.empty();

        $("#acceptedTotals").text(
            `${state.totals.count} aceite(s) · ${money(state.totals.employee)} a receber`
        );

        if (state.accepted.length === 0) {
            $list.html(`<div class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>Ainda não aceitou serviços.
            </div>`);
            return;
        }

        for (const service of state.accepted) $list.append(acceptedCard(service));
    }

    function renderGreenReceiptInfo() {
        const config = state.config || {};
        const percentage = config.employeePercentage ?? 70;
        const platform = config.platformPercentage ?? 30;

        $("#greenReceiptInfo").text(`Simulador de recibos verdes: ${percentage}% / ${platform}%`);
    }

    function fillCategories() {
        const $select = $("#pendingCategory");
        const options = ['<option value="">Todas</option>'];

        for (const category of state.categories) {
            options.push(`<option value="${category.id}">${generalUtils.escapeHtml(category.name)}</option>`);
        }

        $select.html(options.join(""));
    }

    async function loadPending() {
        const promise = API.admin.services.pending({
            data: $("#pendingDate").val(),
            categoriaId: $("#pendingCategory").val()
        });

        const preloader = $("#pendingList").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            state.pending = response?.services || [];
            state.categories = response?.categories || [];
            state.config = response?.config || state.config;

            if ($("#pendingCategory option").length <= 1) fillCategories();

            renderPending();
            renderGreenReceiptInfo();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar os serviços por aceitar.");
        } finally {
            await preloader;
        }
    }

    async function loadAccepted() {
        const promise = API.admin.services.accepted({ data: $("#pendingDate").val() });
        const preloader = $("#acceptedList").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            state.accepted = response?.services || [];
            state.totals = response?.totals || state.totals;

            renderAccepted();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar os serviços aceites.");
        } finally {
            await preloader;
        }
    }

    function showError(message) {
        $("#servicesError").removeClass("d-none").text(message);
    }

    function showSuccess(message) {
        $("#servicesSuccess").removeClass("d-none").text(message);
        setTimeout(() => $("#servicesSuccess").addClass("d-none"), 6000);
    }

    async function acceptService(bookingServiceId, bookingId) {
        $("#servicesError, #servicesSuccess").addClass("d-none");

        const promise = API.admin.services.accept(Number(bookingServiceId), Number(bookingId));
        const preloader = $("#pendingList").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            const simulation = response?.greenReceipt;
            let message = response?.message || "Serviço aceite.";

            if (simulation) {
                message += ` Recibo verde simulado: ${money(simulation.employeeValue)} para si e ${money(simulation.platformValue)} para a plataforma.`;
            }
            if (response?.consolidated) {
                message += " Agendamento TOTALMENTE ACEITE (janela temporal bloqueada).";
            }

            showSuccess(message);

            await Promise.all([loadPending(), loadAccepted()]);
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível aceitar o serviço.");
        } finally {
            await preloader;
        }
    }

    async function unacceptService(bookingServiceId, bookingId) {
        if (!confirm("Desfazer a aceitação deste serviço?")) return;

        $("#servicesError, #servicesSuccess").addClass("d-none");

        const promise = API.admin.services.unaccept(Number(bookingServiceId), Number(bookingId));
        const preloader = $("#acceptedList").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            showSuccess(response?.message || "Aceitação desfeita.");

            await Promise.all([loadPending(), loadAccepted()]);
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível desfazer a aceitação.");
        } finally {
            await preloader;
        }
    }

    function bindEvents() {
        $(document).on("click", "[data-accept]", function () {
            acceptService($(this).data("accept"), $(this).data("booking"));
        });

        $(document).on("click", "[data-unaccept]", function () {
            unacceptService($(this).data("unaccept"), $(this).data("booking"));
        });

        $("#pendingDate, #pendingCategory").on("change", function () {
            loadPending();
            loadAccepted();
        });
    }

    $(() => {
        bindEvents();
        loadPending();
        loadAccepted();
    });

    return { state, loadPending, loadAccepted, acceptService, unacceptService };
})();