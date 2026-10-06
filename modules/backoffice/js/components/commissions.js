const boCommissions = (() => {
    const state = { data: null };

    function renderKpis(data) {
        const isOwn = data?.scope === "proprio";

        const cards = [
            { label: "Serviços prestados", value: String(Number(data?.totals?.services || 0)), icon: "bi-list-check" },
            { label: isOwn ? "As minhas comissões" : "Comissões do mês", value: generalUtils.formatCurrencyWithVat(data?.totals?.employeeValue || 0), icon: "bi-cash-stack" },
            { label: isOwn ? "Valor que gerei" : "Parte da Empresa", value: generalUtils.formatCurrencyWithVat(isOwn ? (data?.totals?.platformValue || 0) + (data?.totals?.employeeValue || 0) : (data?.totals?.platformValue || 0)), icon: "bi-graph-up-arrow" }
        ];

        if (!isOwn) {
            cards.push({ label: "Funcionários com prestações", value: String((data?.employees || []).length), icon: "bi-people" });
        }

        $("#commissionKpis").html(cards.map((card) => `
            <div class="col-sm-6 col-xl-3">
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

        // D-07.1/D-07.2: o salário base só aparece (e só ao próprio) num cartão separado.
        if (data?.fixedSalary?.applicable) {
            $("#commissionSalaryValue").text(generalUtils.formatCurrencyWithVat(data.fixedSalary.value));
            $("#commissionSalaryCard").removeClass("d-none");
        } else {
            $("#commissionSalaryCard").addClass("d-none");
        }
    }

    function renderEmployees(employees) {
        // O funcionário não vê a tabela de totais por colega (só as suas linhas).
        if (!employees || employees.length === 0 || state.data?.scope === "proprio") {
            $("#commissionEmployeesCard").addClass("d-none");
            return;
        }

        $("#commissionEmployeesCard").removeClass("d-none");
        $("#commissionEmployeesBody").html(employees.map((employee) => `
            <tr>
                <td class="fw-bold">${generalUtils.escapeHtml(employee.employeeName)}</td>
                <td class="text-center">${Number(employee.services)}</td>
                <td class="text-end">${generalUtils.formatCurrencyWithVat(employee.servicesValue)}</td>
                <td class="text-center">${Number(employee.averagePercentage).toFixed(2).replace(".", ",")} %</td>
                <td class="text-end fw-bold">${generalUtils.formatCurrencyWithVat(employee.employeeValue)}</td>
                <td class="text-end text-muted">${generalUtils.formatCurrencyWithVat(employee.platformValue)}</td>
            </tr>`).join(""));
    }

    function renderCommissions(commissions) {
        $("#commissionsCount").text(`${commissions.length} serviço(s) prestado(s)`);

        $("#commissionsTableBody").html(
            commissions.length === 0
                ? `<tr><td colspan="7" class="text-center text-muted py-5">
                       <i class="bi bi-cash-stack fs-3 d-block mb-2"></i>Sem serviços prestados neste mês.
                   </td></tr>`
                : commissions.map((commission) => `
                    <tr>
                        <td class="fw-bold">#${commission.bookingId}</td>
                        <td class="small">${generalUtils.escapeHtml(commission.serviceName)}</td>
                        <td class="small">${generalUtils.escapeHtml(commission.employeeName)}</td>
                        <td class="small">${generalUtils.formatDateTime(commission.dateTime)}</td>
                        <td class="text-end">${generalUtils.formatCurrencyWithVat(commission.price)}</td>
                        <td class="text-center">${Number(commission.percentage).toFixed(2).replace(".", ",")} %</td>
                        <td class="text-end fw-bold">${generalUtils.formatCurrencyWithVat(commission.employeeValue)}</td>
                    </tr>`).join("")
        );
    }

    async function load() {
        const month = $("#commissionMonth").val() || new Date().toISOString().substring(0, 7);

        const promise = API.admin.commissions.list(month);
        const preloader = $("main").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            state.data = response;

            $("#commissionMonth").val(response?.month || month);

            renderKpis(response);
            renderEmployees(response?.employees || []);
            renderCommissions(response?.commissions || []);
        } catch (error) {
            $("#commissionsError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível carregar as comissões.");
        } finally {
            await preloader;
        }
    }

    $(() => {
        if (!$("#commissionMonth").length) return;

        $("#commissionMonth").val(new Date().toISOString().substring(0, 7));

        $("#commissionMonth").on("change", load);
        $("#commissionReloadBtn").on("click", load);

        load();
    });

    return { state, load };
})();