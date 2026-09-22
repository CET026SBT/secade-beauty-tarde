const boGreenReceipts = (() => {
    const state = { configs: [], active: null, defaults: { employeePercentage: 70, platformPercentage: 30 } };

    function renderActive() {
        const active = state.active || {};
        const percentage = active.employeePercentage ?? state.defaults.employeePercentage;
        const platform = active.platformPercentage ?? state.defaults.platformPercentage;

        $("#activeConfigBox").html(`
            <div class="row g-3">
                <div class="col-6">
                    <span class="text-muted small d-block">Funcionário</span>
                    <span class="fs-3 fw-bold text-success">${percentage}%</span>
                </div>
                <div class="col-6">
                    <span class="text-muted small d-block">Plataforma</span>
                    <span class="fs-3 fw-bold">${platform}%</span>
                </div>
                <div class="col-12">
                    <span class="text-muted small d-block">Vigente desde</span>
                    <span class="fw-bold">${active.effectiveFrom ? generalUtils.formatDateTime(active.effectiveFrom) : "por omissão (sem configuração registada)"}</span>
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
            <td class="text-end">${config.employeePercentage}%</td>
            <td class="text-end">${config.platformPercentage}%</td>
            <td class="text-center">
                <span class="badge bg-light text-dark">#${config.id}</span>
            </td>
        </tr>`).join("");

        $container.html(`<div class="table-responsive">
            <table class="table table-hover align-middle mb-0 bo-table">
                <thead>
                    <tr>
                        <th>Vigência</th>
                        <th class="text-end">Funcionário</th>
                        <th class="text-end">Plataforma</th>
                        <th class="text-center">ID</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>`);
    }

    function updatePreview() {
        const employee = Number($("#grEmployee").val() || 0);
        const platform = Number($("#grPlatform").val() || 0);
        const sum = Math.round((employee + platform) * 100) / 100;
        const valid = sum === 100;

        $("#grPreview")
            .toggleClass("text-danger", !valid)
            .toggleClass("text-success", valid)
            .text(valid
                ? `Soma atual: ${sum}% — válida.`
                : `Soma atual: ${sum}% — tem de ser exatamente 100%.`);
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
            employeePercentage: $("#grEmployee").val(),
            platformPercentage: $("#grPlatform").val(),
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
        $("#grEmployee, #grPlatform").on("input change", updatePreview);
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