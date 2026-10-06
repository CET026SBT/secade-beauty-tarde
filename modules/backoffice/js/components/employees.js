const boEmployees = (() => {
    const state = { employees: [], defaults: {}, totals: {}, editingId: null };

    function money(value) { return generalUtils.formatCurrency(value); }

    function contractLabel(type) {
        return type === "recibo_verde" ? "Recibos verdes" : "Efetivo";
    }

    function showError(message) { $("#employeesError").removeClass("d-none").text(message); }
    function showSuccess(message) {
        $("#employeesSuccess").removeClass("d-none").text(message);
        setTimeout(() => $("#employeesSuccess").addClass("d-none"), 6000);
    }
    function clearMessages() { $("#employeesError, #employeesSuccess").addClass("d-none"); }

    function photoUrl(employee) {
        if (employee.photo) {
            return `${BASE_URL ?? ""}/${String(employee.photo).replace(/^\//, "")}`;
        }
        return `${BASE_URL ?? ""}/modules/common/img/sb-logo.png`;
    }

    function renderKpis() {
        const t = state.totals || {};
        $("#employeesKpis").html(`
            <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body">
                <span class="text-muted small d-block">Ativos</span>
                <span class="fs-4 fw-bold">${Number(t.active || 0)}</span>
            </div></div></div>
            <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body">
                <span class="text-muted small d-block">Efetivos</span>
                <span class="fs-4 fw-bold">${Number(t.efetivos || 0)}</span>
            </div></div></div>
            <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body">
                <span class="text-muted small d-block">Recibos verdes</span>
                <span class="fs-4 fw-bold">${Number(t.reciboVerde || 0)}</span>
            </div></div></div>
            <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body">
                <span class="text-muted small d-block">Custo fixo mensal</span>
                <span class="fs-4 fw-bold">${money(t.fixedCost || 0)}</span>
            </div></div></div>
        `);
    }

    function row(employee) {
        const actions = boUtils.actionButton("edit", "Editar funcionário", `data-employee-edit="${employee.id}"`)
            + (employee.isActive
                ? boUtils.actionButton("remove", "Desativar funcionário", `data-employee-deactivate="${employee.id}"`)
                : boUtils.actionButton("approve", "Reativar funcionário", `data-employee-activate="${employee.id}"`));

        return `<tr>
            <td>
                <img src="${photoUrl(employee)}" class="bo-employee-thumb rounded-circle me-2" alt="">
                <span class="fw-bold">${generalUtils.escapeHtml(employee.name || "")}</span>
                <span class="d-block small text-muted">${generalUtils.escapeHtml(employee.email || "")}</span>
            </td>
            <td class="small">${contractLabel(employee.contractType)}</td>
            <td class="text-end small">${money(employee.salary || 0)}</td>
            <td class="text-end small">${employee.commissionPercentage ?? 0}%</td>
            <td class="text-center">
                <span class="badge ${employee.isActive ? "bg-success" : "bg-secondary"}">
                    ${employee.isActive ? "Ativo" : "Inativo"}
                </span>
            </td>
            <td class="text-end text-nowrap">${actions}</td>
        </tr>`;
    }

    function renderTable() {
        $("#employeesCount").text(state.employees.length);

        if (state.employees.length === 0) {
            $("#employeesTableContainer").html(`<div class="text-center text-muted py-5">
                <i class="bi bi-people fs-3 d-block mb-2"></i>Ainda não há funcionários registados.
            </div>`);
            return;
        }

        $("#employeesTableContainer").html(`<div class="table-responsive">
            <table class="table table-hover align-middle mb-0 bo-table">
                <thead><tr>
                    <th>Funcionário</th>
                    <th>Contrato</th>
                    <th class="text-end">Salário base</th>
                    <th class="text-end">% por serviço</th>
                    <th class="text-center">Estado</th>
                    <th class="text-end">Ações</th>
                </tr></thead>
                <tbody>${state.employees.map(row).join("")}</tbody>
            </table>
        </div>`);
    }

    async function load() {
        const promise = API.admin.employees.list();
        const preloader = $("#employeesTableContainer").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            state.employees = response?.employees || [];
            state.defaults  = response?.defaults || {};
            state.totals    = response?.totals || {};

            renderKpis();
            renderTable();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar os funcionários.");
        } finally {
            await preloader;
        }
    }

    function openForm(employee = null) {
        clearMessages();
        state.editingId = employee ? Number(employee.id) : null;

        const isEdit = state.editingId !== null;
        $("#employeeModalTitle").text(isEdit ? "Editar funcionário" : "Novo funcionário");

        $("#passwordBlock").toggleClass("d-none", isEdit);
        $("#employeeName").val(employee?.name || "");
        $("#employeeEmail").val(employee?.email || "");
        $("#employeePhone").val(employee?.phone || "");
        $("#employeeNif").val(employee?.nif || "");
        $("#employeeCc").val(employee?.cc || "");
        $("#employeeId").val(state.editingId || "");
        $("#employeePassword").val("");

        $("#employeeContractType").val(employee?.contractType || "recibo_verde");
        $("#employeeCommission").val(employee?.commissionPercentage ?? "");
        $("#employeeSalary").val(employee?.salary ?? 0);
        $("#employeePhotoPreview").attr("src", employee ? photoUrl(employee) : `${BASE_URL ?? ""}/modules/common/img/sb-logo.png`);

        // A foto só depois de o funcionário existir (precisa do id).
        $("#employeePhotoUploadBtn").prop("disabled", !isEdit);
        $("#employeePhotoFile").prop("disabled", !isEdit);

        applyContractMode();
        generalUtils.bsModalGetOrCreateInstance($("#employeeFormModal")[0]).show();
    }

    /** C-10: os RV não têm salário base; a % sugerida vem do tipo de contrato. */
    function applyContractMode() {
        const type = $("#employeeContractType").val();
        const isEfetivo = type === "efetivo_contratado";

        $("#salaryBlock").toggleClass("d-none", !isEfetivo);
        if (!isEfetivo) $("#employeeSalary").val(0);

        const suggestion = state.defaults?.[type];
        if (suggestion !== undefined) {
            $("#employeeCommission").attr("placeholder", `Sugerida: ${suggestion}`);
        }
    }

    function payload() {
        const data = {
            name: $("#employeeName").val(),
            email: $("#employeeEmail").val(),
            phone: $("#employeePhone").val(),
            nif: $("#employeeNif").val(),
            cc: $("#employeeCc").val(),
            contractType: $("#employeeContractType").val(),
            commissionPercentage: $("#employeeCommission").val(),
            salary: $("#employeeSalary").val(),
            profileType: "funcionario"
        };

        if (state.editingId === null) {
            data.password = $("#employeePassword").val();
        } else {
            data.employeeId = state.editingId;
        }

        return data;
    }

    async function save() {
        clearMessages();

        const isEdit = state.editingId !== null;
        const promise = isEdit
            ? API.admin.employees.update(payload())
            : API.admin.employees.create(payload());

        const preloader = $("#employeesTableContainer").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            showSuccess(response?.message || "Guardado.");
            bootstrap.Modal.getInstance($("#employeeFormModal")[0])?.hide();
            await load();
        } catch (error) {
            const errors = error?.responseJSON?.errors;
            showError(errors ? Object.values(errors).join(" ") : (error?.responseJSON?.message || "Não foi possível guardar."));
        } finally {
            await preloader;
        }
    }

    async function uploadPhoto() {
        if (state.editingId === null) return;

        const input = document.getElementById("employeePhotoFile");
        if (!input?.files?.length) { showError("Escolha uma fotografia."); return; }

        const formData = new FormData();
        formData.append("employeeId", state.editingId);
        formData.append("photo", input.files[0]);

        try {
            const response = await API.admin.employees.uploadPhoto(formData);
            showSuccess(response?.message || "Fotografia atualizada.");
            await load();
            $("#employeePhotoPreview").attr("src", `${BASE_URL ?? ""}/${String(response?.photo).replace(/^\//, "")}`);
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar a fotografia.");
        }
    }

    /** Desativação com aviso de impacto (§7.5): avisa e só depois age. */
    async function deactivate(employeeId) {
        clearMessages();

        let impact = null;
        try {
            impact = (await API.admin.employees.impact(Number(employeeId))) || {};
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível avaliar o impacto.");
            return;
        }

        const confirmed = await generalUtils.confirmDialog({
            icon: impact.hasImpact ? "warning" : "question",
            title: "Desativar funcionário",
            text: impact.message || "Desativar este funcionário?"
        });
        if (!confirmed) return;

        const promise = API.admin.employees.deactivate(Number(employeeId));
        const preloader = $("#employeesTableContainer").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            showSuccess(response?.message || "Funcionário desativado.");
            await load();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível desativar.");
        } finally {
            await preloader;
        }
    }

    async function activate(employeeId) {
        clearMessages();

        const promise = API.admin.employees.activate(Number(employeeId));
        const preloader = $("#employeesTableContainer").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            showSuccess(response?.message || "Funcionário reativado.");
            await load();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível reativar.");
        } finally {
            await preloader;
        }
    }

    function bindEvents() {
        $("#newEmployeeBtn").on("click", () => openForm(null));
        $("#saveEmployeeBtn").on("click", save);
        $("#employeeContractType").on("change", applyContractMode);
        $("#employeePhotoUploadBtn").on("click", uploadPhoto);

        $(document).on("click", "[data-employee-edit]", function () {
            const employee = state.employees.find((item) => Number(item.id) === Number($(this).data("employee-edit")));
            if (employee) openForm(employee);
        });

        $(document).on("click", "[data-employee-deactivate]", function () {
            deactivate($(this).data("employee-deactivate"));
        });

        $(document).on("click", "[data-employee-activate]", function () {
            activate($(this).data("employee-activate"));
        });
    }

    $(() => {
        if (!$("#employeesTableContainer").length) return;

        bindEvents();
        load();
    });

    return { state, load, openForm, save, deactivate, activate, uploadPhoto, applyContractMode };
})();