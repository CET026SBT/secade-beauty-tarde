const boServices = (() => {
    const state = {
        pending: [],
        accepted: [],
        categories: [],
        employees: [],
        totals: { employee: 0, platform: 0, count: 0 },
        config: null
    };

    const IS_MANAGER = Boolean(window.BO_IS_MANAGER);

    function money(value) { return generalUtils.formatCurrency(value); }

    /** Opções do seletor de alocação; desativa quem está ocupado noutra cidade (R-ALOC). */
    function employeeOptions(service) {
        const busy = (service.busyEmployeeIds || []).map(Number);

        if (state.employees.length === 0) {
            return `<option value="">Sem funcionários ativos</option>`;
        }

        return state.employees.map((employee) => {
            const isBusy = busy.includes(Number(employee.id));
            return `<option value="${employee.id}" ${isBusy ? "disabled" : ""}>
                ${generalUtils.escapeHtml(employee.name)}${isBusy ? " (noutra cidade)" : ""}
            </option>`;
        }).join("");
    }

    function pendingCard(service) {
        // F4: o gestor ALOCA (escolhe o funcionário); o funcionário ACEITA o serviço.
        const action = IS_MANAGER
            ? `<div class="d-flex flex-wrap gap-2 align-items-center">
                   <select class="form-select form-select-sm w-auto" data-employee-select="${service.id}">
                       ${employeeOptions(service)}
                   </select>
                   <button type="button" class="btn btn-sm btn-primary extended-border"
                           data-allocate="${service.id}" data-booking="${service.bookingId}">
                       <i class="bi bi-person-plus me-1"></i> Alocar
                   </button>
               </div>`
            : `<button type="button" class="btn btn-sm btn-success" data-accept="${service.id}" data-booking="${service.bookingId}">
                   <i class="bi bi-hand-thumbs-up me-1"></i> Aceitar serviço
               </button>`;

        return `<div class="border rounded p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <span class="fw-bold">${generalUtils.escapeHtml(service.serviceName || "")}</span>
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
                ${action}
            </div>
        </div>`;
    }

    function acceptedCard(service) {
        // F4: o gestor vê quem ficou alocado; o funcionário vê só a sua linha.
        const employee = !IS_MANAGER || !service.employeeName
            ? ""
            : `<div class="small text-muted"><i class="bi bi-person-check me-1"></i>${generalUtils.escapeHtml(service.employeeName)}</div>`;

        const tag = IS_MANAGER
            ? '<span class="badge bg-primary">Alocado</span>'
            : '<span class="badge bg-success">Aceite</span>';

        return `<div class="border rounded p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <span class="fw-bold">${generalUtils.escapeHtml(service.serviceName || "")}</span>
                    <div class="small text-muted">${generalUtils.formatDateTime(service.dateTime)}
                        · ${generalUtils.escapeHtml(service.customerName || "")}</div>
                    ${employee}
                </div>
                ${tag}
            </div>
            <div class="row g-2 small mb-2">
                <div class="col-6">
                    <span class="text-muted d-block">Funcionário (${service.employeePercentage ?? "-"}%)</span>
                    <span class="fw-bold text-success">${money(service.greenReceiptEmployee)}</span>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block">Empresa</span>
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
            `${state.totals.count} ${IS_MANAGER ? "alocado(s)" : "aceite(s)"} · ${money(state.totals.employee)} ${IS_MANAGER ? "a pagar" : "a receber"}`
        );

        if (state.accepted.length === 0) {
            $list.html(`<div class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>${IS_MANAGER ? "Ainda não há serviços alocados." : "Ainda não aceitou serviços."}
            </div>`);
            return;
        }

        for (const service of state.accepted) $list.append(acceptedCard(service));
    }

    function renderGreenReceiptInfo() {
        const config = state.config || {};
        const configs = Array.isArray(config.configs) ? config.configs : [];
        const recibo = configs.find(c => c.contractType === "recibo_verde");
        const percentage = recibo?.commissionPercentage ?? config.reciboVerde ?? 70;

        $("#greenReceiptInfo").text(`Repartição do serviço: ${percentage}% funcionário · ${Math.round((100 - percentage) * 100) / 100}% empresa`);
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
            state.employees = response?.employees || state.employees;
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

    async function acceptService(bookingServiceId, bookingId, employeeId = null) {
        $("#servicesError, #servicesSuccess").addClass("d-none");

        const promise = API.admin.services.accept(Number(bookingServiceId), Number(bookingId), employeeId ? Number(employeeId) : null);
        const preloader = $("#pendingList").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            const simulation = response?.greenReceipt;
            let message = response?.message || (IS_MANAGER ? "Serviço alocado." : "Serviço aceite.");

            if (simulation) {
                message += ` Repartição: ${money(simulation.employeeValue)} para o funcionário e ${money(simulation.platformValue)} para a empresa.`;
            }
            if (response?.consolidated) {
                message += " Agendamento TOTALMENTE ALOCADO (pronto para a rota).";
            }

            showSuccess(message);

            await Promise.all([loadPending(), loadAccepted()]);
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível alocar o serviço.");
        } finally {
            await preloader;
        }
    }

    /** F4: o gestor escolhe o funcionário no seletor e aloca. */
    async function allocateService(bookingServiceId, bookingId) {
        const employeeId = $(`[data-employee-select="${bookingServiceId}"]`).val();

        if (!employeeId) {
            showError("Escolha o funcionário a alocar.");
            return;
        }

        await acceptService(bookingServiceId, bookingId, employeeId);
    }

    async function unacceptService(bookingServiceId, bookingId) {
        if (!await generalUtils.confirmDialog({ text: "Desfazer a aceitação deste serviço?" })) return;

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

        $(document).on("click", "[data-allocate]", function () {
            allocateService($(this).data("allocate"), $(this).data("booking"));
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

    return { state, loadPending, loadAccepted, acceptService, allocateService, unacceptService };
})();