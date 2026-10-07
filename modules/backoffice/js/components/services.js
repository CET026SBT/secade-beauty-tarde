const boServices = (() => {
    const isManager = !!window.BO_IS_MANAGER;

    const state = {
        pending: [],
        accepted: [],
        categories: [],
        cities: [],
        employees: [],
        employeeOptions: {},
        totals: { employee: 0, platform: 0, count: 0 },
        config: null
    };

    function money(value) { return generalUtils.formatCurrency(value); }

    function pendingFilters() {
        const params = {};
        const date = $("#pendingDate").val();
        const city = $("#pendingCity").val();
        const category = $("#pendingCategory").val();
        const booking = $("#pendingBooking").val();

        if (date) params.data = date;
        if (city) params.cidadeId = city;
        if (category) params.categoriaId = category;
        if (booking) params.bookingId = booking;

        return params;
    }

    function acceptedFilters() {
        const params = {};
        const date = $("#acceptedDate").val();
        const city = $("#acceptedCity").val();
        const employee = $("#acceptedEmployee").val();
        const booking = $("#acceptedBooking").val();

        if (date) params.data = date;
        if (city) params.cidadeId = city;
        if (employee) params.funcionarioId = employee;
        if (booking) params.bookingId = booking;

        return params;
    }

    function cityLabel(service) {
        return service.cityName ? generalUtils.escapeHtml(service.cityName) : "Sem cidade";
    }

    function pendingCard(service) {
        const conflicting = !!service.windowConflict;
        const tag = conflicting
            ? '<span class="badge bg-danger">Conflito de janela</span>'
            : "";

        let action;

        if (isManager) {
            const options = state.employeeOptions[service.bookingId] || [];
            const optionHtml = options.map(o =>
                `<option value="${o.id}" ${o.blocked ? "disabled" : ""}>${generalUtils.escapeHtml(o.name)}${o.blocked ? " (noutra cidade)" : ""}</option>`
            ).join("");

            action = `<div class="d-flex gap-2">
                <select class="form-select form-select-sm" data-assign-select="${service.bookingId}" ${conflicting ? "disabled" : ""}>
                    <option value="">Escolher funcionário…</option>
                    ${optionHtml}
                </select>
                <button type="button" class="btn btn-sm btn-success flex-shrink-0"
                        data-assign="${service.id}" data-booking="${service.bookingId}" ${conflicting ? "disabled" : ""}>
                    <i class="bi bi-person-plus me-1"></i> Alocar
                </button>
            </div>`;
        } else {
            const blocked = conflicting || service.acceptBlocked;
            action = `<button type="button" class="btn btn-sm btn-success"
                        data-accept="${service.id}" data-booking="${service.bookingId}" ${blocked ? "disabled" : ""}>
                    <i class="bi bi-hand-thumbs-up me-1"></i> Aceitar serviço
                </button>`;
        }

        return `<div class="border rounded p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <span class="fw-bold">${generalUtils.escapeHtml(service.serviceName || "")}</span>${tag}
                    <div class="small text-muted">
                        <i class="bi bi-tag me-1"></i>${generalUtils.escapeHtml(service.categoryName || "")}
                        · <i class="bi bi-geo-alt me-1"></i>${cityLabel(service)}
                        · ${generalUtils.formatDateTime(service.dateTime)}
                    </div>
                </div>
                <span class="fw-bold">${money(service.price)}</span>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="small text-muted">
                    <i class="bi bi-hash me-1"></i>Agendamento ${service.bookingId}
                    · ${generalUtils.formatDuration(service.durationMinutes)}
                </span>
                ${action}
            </div>
        </div>`;
    }

    function acceptedCard(service) {
        const tag = isManager
            ? '<span class="badge bg-info text-dark">Alocado</span>'
            : '<span class="badge bg-success">Aceite</span>';

        const employeeLine = isManager && service.employeeName
            ? `<div class="small text-muted mb-1"><i class="bi bi-person-badge me-1"></i>${generalUtils.escapeHtml(service.employeeName)}</div>`
            : "";

        const employeeLabel = isManager ? "A receber (funcionário)" : "A receber";
        const canUndo = isManager || service.bookingStatus !== "totalmente_alocado";

        return `<div class="border rounded p-3 mb-3" data-card-booking="${service.bookingId}">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <span class="fw-bold">${generalUtils.escapeHtml(service.serviceName || "")}</span>
                    <div class="small text-muted">
                        <i class="bi bi-geo-alt me-1"></i>${cityLabel(service)}
                        · ${generalUtils.formatDateTime(service.dateTime)}
                        · ${generalUtils.escapeHtml(service.customerName || "")}
                    </div>
                </div>
                ${tag}
            </div>
            ${employeeLine}
            <div class="row g-2 small mb-2">
                <div class="col-6">
                    <span class="text-muted d-block">${employeeLabel} (${service.employeePercentage ?? "-"}%)</span>
                    <span class="fw-bold text-success">${money(service.greenReceiptEmployee)}</span>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block">Empresa</span>
                    <span class="fw-bold">${money(service.greenReceiptPlatform)}</span>
                </div>
            </div>
            ${canUndo ? `<button type="button" class="btn btn-sm btn-outline-danger"
                    data-unaccept="${service.id}" data-booking="${service.bookingId}">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Desfazer
            </button>` : ""}
        </div>`;
    }

    function renderPending() {
        const $list = $("#pendingList");
        $list.empty();

        $("#pendingCount").text(`${state.pending.length} por tratar`);

        if (state.pending.length === 0) {
            $list.html(`<div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>Nada por ${isManager ? "alocar" : "aceitar"}.
            </div>`);
            return;
        }

        state.pending.forEach(service => $list.append(pendingCard(service)));
    }

    function renderAccepted() {
        const $list = $("#acceptedList");
        $list.empty();

        $("#acceptedTotals").text(`${state.totals.count} · ${money(state.totals.employee)}`);

        if (state.accepted.length === 0) {
            $list.html(`<div class="text-center text-muted py-5">
                <i class="bi bi-check2-circle fs-3 d-block mb-2"></i>Sem serviços ${isManager ? "alocados" : "aceites"}.
            </div>`);
            return;
        }

        state.accepted.forEach(service => $list.append(acceptedCard(service)));
    }

    function fillSelectors() {
        const cityOptions = state.cities.map(c => `<option value="${c.id}">${generalUtils.escapeHtml(c.name)}</option>`).join("");
        $("#pendingCity, #acceptedCity").each(function () {
            const current = $(this).val();
            $(this).html(`<option value="">Todas</option>${cityOptions}`).val(current ?? "");
        });

        const categoryOptions = state.categories.map(c => `<option value="${c.id}">${generalUtils.escapeHtml(c.name)}</option>`).join("");
        $("#pendingCategory").html(`<option value="">Todas</option>${categoryOptions}`);

        const employeeOptions = state.employees.map(e => `<option value="${e.id}">${generalUtils.escapeHtml(e.name)}</option>`).join("");
        $("#acceptedEmployee").html(`<option value="">Todos</option>${employeeOptions}`);
    }

    function renderConfig() {
        const config = state.config || {};
        const employee = config.employeePercentage ?? "-";
        const company = config.platformPercentage ?? "-";

        $("#greenReceiptInfo").text(`Repartição padrão: ${employee}% funcionário · ${company}% empresa`);
    }

    function showError(message) {
        $("#servicesSuccess").addClass("d-none");
        $("#servicesError").removeClass("d-none").text(message);
    }

    function showSuccess(message) {
        $("#servicesError").addClass("d-none");
        $("#servicesSuccess").removeClass("d-none").text(message);
        setTimeout(() => $("#servicesSuccess").addClass("d-none"), 7000);
    }

    async function loadPending() {
        const promise = API.admin.services.pending(pendingFilters());
        const preloader = $("#pendingList").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            state.pending = response?.services || [];
            state.categories = response?.categories || state.categories;
            state.cities = response?.cities || state.cities;
            state.employeeOptions = response?.employeeOptions || {};
            state.config = response?.config || state.config;

            fillSelectors();
            renderConfig();
            renderPending();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar os serviços por tratar.");
        } finally {
            await preloader;
        }
    }

    async function loadAccepted() {
        const promise = API.admin.services.accepted(acceptedFilters());
        const preloader = $("#acceptedList").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            state.accepted = response?.services || [];
            state.totals = response?.totals || state.totals;
            state.employees = response?.employees || state.employees;
            state.cities = response?.cities || state.cities;

            fillSelectors();
            renderAccepted();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar os serviços alocados.");
        } finally {
            await preloader;
        }
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
                message += ` Repartição: ${money(simulation.employeeValue)} para si e ${money(simulation.platformValue)} para a empresa.`;
            }
            if (response?.consolidated) {
                message += " Agendamento totalmente alocado.";
            }

            showSuccess(message);
            await Promise.all([loadPending(), loadAccepted()]);
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível aceitar o serviço.");
        } finally {
            await preloader;
        }
    }

    async function assignService(bookingServiceId, bookingId, employeeId) {
        if (!employeeId) {
            showError("Escolha o funcionário a alocar.");
            return;
        }

        $("#servicesError, #servicesSuccess").addClass("d-none");

        const promise = API.admin.services.assign(Number(bookingServiceId), Number(bookingId), Number(employeeId));
        const preloader = $("#pendingList").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            showSuccess(response?.message || "Serviço alocado.");
            await Promise.all([loadPending(), loadAccepted()]);
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível alocar o serviço.");
        } finally {
            await preloader;
        }
    }

    async function unacceptService(bookingServiceId, bookingId) {
        if (!(await generalUtils.confirmDialog({ title: "Desfazer esta alocação?", icon: "warning" }))) return;

        $("#servicesError, #servicesSuccess").addClass("d-none");

        const promise = API.admin.services.unaccept(Number(bookingServiceId), Number(bookingId));
        const preloader = $("#acceptedList").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            showSuccess(response?.message || "Alocação desfeita.");
            await Promise.all([loadPending(), loadAccepted()]);
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível desfazer a alocação.");
        } finally {
            await preloader;
        }
    }

    function bindEvents() {
        $(document).on("click", "[data-accept]", function () {
            acceptService($(this).data("accept"), $(this).data("booking"));
        });

        $(document).on("click", "[data-assign]", function () {
            const bookingId = $(this).data("booking");
            const employeeId = $(`[data-assign-select="${bookingId}"]`).val();
            assignService($(this).data("assign"), bookingId, employeeId);
        });

        $(document).on("click", "[data-unaccept]", function () {
            unacceptService($(this).data("unaccept"), $(this).data("booking"));
        });

        $("#pendingDate, #pendingCity, #pendingCategory, #pendingBooking").on("change", loadPending);
        $("#acceptedDate, #acceptedCity, #acceptedEmployee, #acceptedBooking").on("change", loadAccepted);
    }

    $(() => {
        if (!$("#pendingList").length) return;
        bindEvents();
        loadPending();
        loadAccepted();
    });

    return { state, loadPending, loadAccepted, acceptService, assignService, unacceptService, isManager };
})();
