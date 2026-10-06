const boGreenReceipts = (() => {
    const state = { configs: [], active: null, defaults: { reciboVerde: 70, efetivo: 0 } };

    function contractLabel(tipo) {
        return tipo === "recibo_verde" ? "Recibos verdes" : "Efetivo (contratado)";
    }

    function renderActive() {
        const active = state.active || {};
        const configs = active.configs || [];
        const recibo = configs.find(c => c.contractType === "recibo_verde");
        const efetivo = configs.find(c => c.contractType === "efetivo_contratado");

        const reciboPct = recibo?.commissionPercentage ?? state.defaults.reciboVerde;
        const efetivoPct = efetivo?.commissionPercentage ?? state.defaults.efetivo;

        $("#activeConfigBox").html(`
            <div class="row g-3">
                <div class="col-6">
                    <span class="text-muted small d-block">Recibos verdes</span>
                    <span class="fs-3 fw-bold text-success">${reciboPct}%</span>
                </div>
                <div class="col-6">
                    <span class="text-muted small d-block">Efetivo (contratado)</span>
                    <span class="fs-3 fw-bold">${efetivoPct}%</span>
                </div>
                <div class="col-12">
                    <span class="badge ${active.isDefault ? "bg-warning text-dark" : "bg-success"}">
                        ${active.isDefault ? "Valores por omissão" : "Configuração registada"}
                    </span>
                </div>
            </div>
        `);
    }

    function renderHistory() {
        const $container = $("#greenReceiptHistory");
        $container.empty();

        if (state.configs.length === 0) {
            $container.html(`<div class="text-center text-muted py-5">
                <i class="bi bi-clock-history fs-3 d-block mb-2"></i>Sem histórico de configurações.
            </div>`);
            return;
        }

        const rows = state.configs.map(config => `<tr>
            <td class="small">${generalUtils.formatDateTime(config.effectiveFrom)}</td>
            <td class="small">${contractLabel(config.contractType)}</td>
            <td class="text-end">${config.commissionPercentage}%</td>
            <td class="text-center">
                <span class="badge bg-light text-dark">#${config.id}</span>
            </td>
        </tr>`).join("");

        $container.html(`<div class="table-responsive">
            <table class="table table-hover align-middle mb-0 bo-table">
                <thead>
                    <tr>
                        <th>Vigência</th>
                        <th>Tipo de contrato</th>
                        <th class="text-end">% Funcionário</th>
                        <th class="text-center">ID</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>`);
    }

    function updatePreview() {
        const percentage = Number($("#grPercentage").val() || 0);
        const clamped = Math.max(0, Math.min(100, percentage));
        const platform = Math.round((100 - clamped) * 100) / 100;

        $("#grPreview")
            .toggleClass("text-danger", percentage !== clamped)
            .toggleClass("text-success", percentage === clamped)
            .text(percentage === clamped
                ? `Funcionário ${clamped}% · Empresa ${platform}% (num serviço de 100 €).`
                : "A percentagem tem de estar entre 0 e 100.");
    }

    async function load() {
        const promise = API.admin.greenReceipt.config();
        const preloader = $("#greenReceiptHistory").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            state.configs = response?.configs || [];
            state.active = response?.active || null;
            state.defaults = response?.defaults || state.defaults;

            renderActive();
            renderHistory();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar a configuração.");
        } finally {
            await preloader;
        }
    }

    function showError(message) { $("#greenReceiptError").removeClass("d-none").text(message); }
    function showSuccess(message) {
        $("#greenReceiptSuccess").removeClass("d-none").text(message);
        setTimeout(() => $("#greenReceiptSuccess").addClass("d-none"), 6000);
    }

    async function save() {
        const payload = {
            contractType: $("#grContractType").val(),
            commissionPercentage: $("#grPercentage").val(),
            effectiveFrom: $("#grEffectiveFrom").val()
        };

        $("#greenReceiptError, #greenReceiptSuccess").addClass("d-none");

        const promise = API.admin.greenReceipt.saveConfig(payload);
        const preloader = $("#greenReceiptHistory").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            const simulation = response?.simulation;
            showSuccess(
                (response?.message || "Configuração guardada.") +
                (simulation ? ` Exemplo para 100 €: ${generalUtils.formatCurrency(simulation.employeeValue)} / ${generalUtils.formatCurrency(simulation.platformValue)}.` : "")
            );

            await load();
        } catch (error) {
            const errors = error?.responseJSON?.errors;
            showError(errors ? Object.values(errors).join(" ") : (error?.responseJSON?.message || "Não foi possível guardar a configuração."));
        } finally {
            await preloader;
        }
    }

    function bindEvents() {
        $("#grPercentage").on("input change", updatePreview);
        $("#saveGreenReceiptConfigBtn").on("click", save);
    }

    $(() => {
        bindEvents();
        $("#grEffectiveFrom").val(new Date().toISOString().slice(0, 10));
        updatePreview();
        load();
    });

    return { state, load, save, updatePreview };
})();