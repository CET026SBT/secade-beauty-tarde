const boRh = (() => {
    const state = {
        employees: [],
        summary: null,
        defaults: { efetivo_contratado: 0, recibo_verde: 70 },
        pendingPhotoFile: null,
        cropper: null,
        deactivateId: null
    };

    const CONTRACT_LABELS = {
        efetivo_contratado: "Efetivo",
        recibo_verde: "Recibo verde"
    };

    /** URL da fotografia (ou uma inicial de recurso quando não há). */
    function photoUrl(employee) {
        if (employee.photo) return `${BASE_URL ?? ""}/${employee.photo}`;
        return null;
    }

    function avatar(employee) {
        const url = photoUrl(employee);
        if (url) {
            return `<img class="bo-rh-avatar" src="${generalUtils.escapeHtml(url)}" alt="">`;
        }
        const initial = (employee.name || "?").trim().charAt(0).toUpperCase();
        return `<span class="bo-rh-avatar bo-rh-avatar--placeholder">${generalUtils.escapeHtml(initial)}</span>`;
    }

    function renderKpis(summary) {
        const cards = [
            { label: "Efetivos", value: String(Number(summary?.effective || 0)), icon: "bi-person-badge" },
            { label: "Recibos verdes", value: String(Number(summary?.greenReceipt || 0)), icon: "bi-person-lines-fill" },
            { label: "Custo fixo mensal", value: generalUtils.formatCurrency(summary?.fixedCost || 0), icon: "bi-cash-stack" }
        ];

        $("#rhKpis").html(cards.map((card) => `
            <div class="col-sm-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="bo-kpi-icon"><i class="bi ${card.icon}"></i></span>
                        <div>
                            <span class="d-block text-muted small">${generalUtils.escapeHtml(card.label)}</span>
                            <span class="d-block fs-4 fw-bold">${generalUtils.escapeHtml(card.value)}</span>
                        </div>
                    </div>
                </div>
            </div>`).join(""));
    }

    function row(employee) {
        const contact = [employee.email, employee.phone].filter(Boolean).map(generalUtils.escapeHtml).join("<br>");
        const isEffective = employee.contractType === "efetivo_contratado";
        const irs = (employee.irsRate === null || employee.irsRate === undefined) ? "—" : `${employee.irsRate}%`;

        return `<tr>
            <td>
                <div class="d-flex align-items-center gap-2">
                    ${avatar(employee)}
                    <div>
                        <span class="d-block fw-semibold">${generalUtils.escapeHtml(employee.name || "-")}</span>
                        <span class="d-block small text-muted">#${employee.id}</span>
                    </div>
                </div>
            </td>
            <td class="small">${contact || '<span class="text-muted">em branco</span>'}</td>
            <td><span class="badge ${isEffective ? "bg-info text-dark" : "bg-secondary"}">${CONTRACT_LABELS[employee.contractType] || employee.contractType}</span></td>
            <td class="text-end small">${isEffective ? generalUtils.formatCurrency(employee.salary || 0) : '<span class="text-muted">—</span>'}</td>
            <td class="text-end small">${employee.commissionPercentage}%</td>
            <td class="text-end small">${irs}</td>
            <td class="text-center">
                <span class="badge bo-status-badge ${employee.isActive ? "bg-success" : "bg-secondary"}">
                    ${employee.isActive ? "Ativo" : "Inativo"}
                </span>
            </td>
            <td class="text-end text-nowrap">
                ${boUtils.actionButton("edit", "Editar o funcionário", `data-employee-edit="${employee.id}"`)}
                ${employee.isActive
                    ? boUtils.actionButton("remove", "Desativar (mantém o histórico)", `data-employee-toggle="${employee.id}" data-employee-active="0"`)
                    : boUtils.actionButton("approve", "Reativar o funcionário", `data-employee-toggle="${employee.id}" data-employee-active="1"`)}
            </td>
        </tr>`;
    }

    function render() {
        $("#rhCount").text(`${state.employees.length} funcionário(s)`);

        $("#rhTableBody").html(
            state.employees.length === 0
                ? `<tr><td colspan="8" class="text-center text-muted py-5">
                       <i class="bi bi-people fs-3 d-block mb-2"></i>Sem funcionários registados.
                   </td></tr>`
                : state.employees.map(row).join("")
        );
    }

    async function load() {
        const promise = API.admin.employees.list();
        const preloader = $("#rhTableContainer").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            state.employees = response?.employees || [];
            state.summary = response?.summary || null;
            if (response?.defaults) state.defaults = response.defaults;

            renderKpis(state.summary);
            render();
        } catch (error) {
            $("#rhError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível carregar os funcionários.");
        } finally {
            await preloader;
        }
    }

    const form = new Form('#employeeForm', {
        validators: { ...employeeValidators },
        submit(formData) {
            const payload = Object.fromEntries(formData.entries());
            payload.employeeId = Number(payload.employeeId || 0);
            return saveEmployee(payload);
        }
    });

    function defaultAvatar() {
        return `${BASE_URL ?? ""}/modules/common/img/favicon.ico`;
    }

    function destroyCropper() {
        if (state.cropper) { state.cropper.destroy(); state.cropper = null; }
    }

    function openForm(employee) {
        state.pendingPhotoFile = null;
        destroyCropper();

        const isEdit = !!employee;
        $("#employeeFormTitle").text(isEdit ? "Editar funcionário" : "Novo funcionário");
        $("#employeeId").val(isEdit ? employee.id : "");
        $("#employeeForm .is-invalid").removeClass("is-invalid");
        $("#employeeForm .invalid-feedback").text("");
        $("#employeePhoto").val("");

        $("#employeeName").val(employee?.name || "");
        $("#employeeEmail").val(employee?.email || "");
        $("#employeePhone").val(employee?.phone || "");
        $("#employeeNif").val(employee?.nif || "");
        $("#employeeCc").val(employee?.cc || "");
        $("#employeeContractType").val(employee?.contractType || "efetivo_contratado");
        $("#employeeSalary").val(isEdit ? (employee.salary || "") : "");
        $("#employeeCommission").val(isEdit ? employee.commissionPercentage : "");
        $("#employeeIrs").val(isEdit && employee.irsRate !== null && employee.irsRate !== undefined ? employee.irsRate : "");

        // A password só existe na criação; na edição não se muda por aqui.
        if (isEdit) {
            $("#employeePassword").removeAttr("name").val("");
            $("#employeePasswordWrap").addClass("d-none");
        } else {
            $("#employeePassword").attr("name", "password").val("");
            $("#employeePasswordWrap").removeClass("d-none");
        }

        $("#employeePhotoPreview").attr("src", (isEdit && photoUrl(employee)) || defaultAvatar());

        generalUtils.bsModalGetOrCreateInstance(document.getElementById("employeeFormModal")).show();
    }

    function onPhotoSelected(event) {
        const file = event.target.files && event.target.files[0];
        if (!file) return;
        state.pendingPhotoFile = file;

        const reader = new FileReader();
        reader.onload = (e) => {
            const $img = $("#employeePhotoPreview");
            $img.attr("src", e.target.result);
            destroyCropper();
            if (typeof Cropper !== "undefined") {
                state.cropper = new Cropper($img[0], { aspectRatio: 1, viewMode: 1, autoCropArea: 1, background: false });
            }
        };
        reader.readAsDataURL(file);
    }

    function croppedBlob() {
        return new Promise((resolve) => {
            if (!state.cropper) { resolve(state.pendingPhotoFile); return; }
            state.cropper.getCroppedCanvas({ width: 400, height: 400 })
                .toBlob((blob) => resolve(blob || state.pendingPhotoFile), "image/jpeg", 0.9);
        });
    }

    async function uploadPhoto(employeeId) {
        if (!state.pendingPhotoFile) return null;

        const blob = await croppedBlob();
        const formData = new FormData();
        formData.append("employeeId", employeeId);
        formData.append("photo", blob, "photo.jpg");

        return API.admin.employees.uploadPhoto(formData);
    }

    async function saveEmployee(payload) {
        $("#rhError, #rhResult").addClass("d-none");

        const isEdit = payload.employeeId > 0;
        const promise = isEdit
            ? API.admin.employees.update(payload)
            : API.admin.employees.store(payload);
        const preloader = $("#employeeFormModal").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            const employeeId = response?.employeeId || payload.employeeId;

            await uploadPhoto(employeeId);

            bootstrap.Modal.getInstance(document.getElementById("employeeFormModal"))?.hide();
            $("#rhResult").removeClass("d-none").text(response?.message || "Funcionário guardado.");

            state.pendingPhotoFile = null;
            destroyCropper();
            await load();
        } catch (error) {
            const response = error?.responseJSON || {};
            if (response.errors) form.setErrors(response.errors);
            $("#rhError").removeClass("d-none").text(response.message || "Não foi possível guardar o funcionário.");
        } finally {
            await preloader;
        }
    }

    async function openDeactivate(employeeId) {
        const employee = state.employees.find((item) => Number(item.id) === Number(employeeId));
        if (!employee) return;
        state.deactivateId = employeeId;

        const promise = API.admin.employees.deactivateCheck(employeeId);
        const preloader = $("#employeeDeactivateModal").preloader(".jq-overlay-process", promise);

        try {
            const data = await promise;
            let html = `<p class="mb-2">Vai desativar <strong>${generalUtils.escapeHtml(employee.name || "")}</strong>.</p>`;

            if (data.blocked) {
                html += `<div class="alert alert-warning mb-0">
                    Este funcionário tem <strong>${data.confirmedCount}</strong> serviço(s) em <strong>rota confirmada</strong>.
                    Retire-o da rota antes de o desativar.
                </div>`;
                $("#confirmDeactivateBtn").addClass("d-none");
            } else if (data.servicesCount > 0) {
                html += `<div class="alert alert-warning mb-0">
                    <div class="mb-1">Estes <strong>${data.servicesCount}</strong> serviço(s) voltam a ficar <strong>por alocar</strong>:</div>
                    <ul class="mb-0 small">${(data.services || []).slice(0, 12).map((s) =>
                        `<li>#${s.agendamento_id} · ${generalUtils.escapeHtml(s.servico_nome || "")} · ${generalUtils.formatDateTime(s.data_hora_pretendida)}</li>`).join("")}</ul>
                    ${data.servicesCount > 12 ? `<div class="small mt-1">(+ ${data.servicesCount - 12} outros)</div>` : ""}
                </div>`;
                $("#confirmDeactivateBtn").removeClass("d-none");
            } else {
                html += `<p class="text-muted mb-0">Não tem serviços por executar. O registo mantém-se.</p>`;
                $("#confirmDeactivateBtn").removeClass("d-none");
            }

            $("#employeeDeactivateBody").html(html);
            generalUtils.bsModalGetOrCreateInstance(document.getElementById("employeeDeactivateModal")).show();
        } catch (error) {
            $("#rhError").removeClass("d-none").text(error?.responseJSON?.message || "Não foi possível avaliar a desativação.");
        } finally {
            await preloader;
        }
    }

    async function confirmDeactivate() {
        if (!state.deactivateId) return;

        const promise = API.admin.employees.setActive(state.deactivateId, false);
        const preloader = $("#employeeDeactivateModal").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            bootstrap.Modal.getInstance(document.getElementById("employeeDeactivateModal"))?.hide();
            $("#rhResult").removeClass("d-none").text(response?.message || "Funcionário desativado.");
            await load();
        } catch (error) {
            $("#rhError").removeClass("d-none").text(error?.responseJSON?.message || "Não foi possível desativar.");
        } finally {
            await preloader;
        }
    }

    async function toggleEmployee(employeeId, active) {
        if (Number(active) === 0) {
            return openDeactivate(employeeId);
        }

        const employee = state.employees.find((item) => Number(item.id) === Number(employeeId));
        if (!(await generalUtils.confirmDialog({ title: `Reativar ${employee?.name || "o funcionário"}?`, icon: "question" }))) return;

        const promise = API.admin.employees.setActive(Number(employeeId), true);
        const preloader = $("#rhTableContainer").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            $("#rhResult").removeClass("d-none").text(response?.message || "Funcionário reativado.");
            await load();
        } catch (error) {
            $("#rhError").removeClass("d-none").text(error?.responseJSON?.message || "Não foi possível reativar.");
        } finally {
            await preloader;
        }
    }

    $(() => {
        if (!$("#rhTableBody").length) return;

        $("#newEmployeeBtn").on("click", () => openForm(null));
        $("#employeePhoto").on("change", onPhotoSelected);
        $("#confirmDeactivateBtn").on("click", confirmDeactivate);

        $("#employeeContractType").on("change", function () {
            const isEffective = $(this).val() === "efetivo_contratado";
            if (!isEffective) { $("#employeeSalary").val(""); }
            if ($("#employeeCommission").val() === "") {
                $("#employeeCommission").val(state.defaults[$(this).val()] ?? "");
            }
        });

        $(document).on("click", "[data-employee-edit]", function () {
            const employee = state.employees.find((item) => Number(item.id) === Number($(this).data("employee-edit")));
            openForm(employee || null);
        });

        $(document).on("click", "[data-employee-toggle]", function () {
            toggleEmployee($(this).data("employee-toggle"), $(this).data("employee-active"));
        });

        load();
    });

    return { state, load, openForm };
})();