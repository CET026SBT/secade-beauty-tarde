const boSuppliers = (() => {
    const state = {
        suppliers: [],
        summary: null
    };

    const form = new Form('#supplierForm', {
        validators: { ...supplierValidators },
        submit(formData) {
            // Os campos chegam como texto: o contrato da API é booleano/número (§18.5).
            const payload = Object.fromEntries(formData.entries());
            payload.supplierId = Number(payload.supplierId || 0);
            payload.active = $('#supplierActiveCheck').is(':checked') ? 1 : 0;

            return payload.supplierId > 0
                ? saveSupplier(() => API.admin.suppliers.update(payload))
                : saveSupplier(() => API.admin.suppliers.store(payload));
        }
    });

    function summaryCards(summary) {
        const cards = [
            { label: "Fornecedores", value: String(Number(summary?.total || 0)), icon: "bi-truck" },
            { label: "Ativos", value: String(Number(summary?.active || 0)), icon: "bi-check2-circle" },
            { label: "Sem NIF na fonte", value: String(Number(summary?.withoutNif || 0)), icon: "bi-question-circle" }
        ];

        $("#supplierKpis").html(cards.map((card) => `
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

    function row(supplier) {
        const contact = [supplier.email, supplier.phone].filter(Boolean).map(generalUtils.escapeHtml).join("<br>");

        return `<tr>
            <td class="fw-bold">#${supplier.id}</td>
            <td>
                <span class="d-block">${generalUtils.escapeHtml(supplier.name || "-")}</span>
                ${supplier.notes ? `<span class="d-block small text-muted">${generalUtils.escapeHtml(supplier.notes)}</span>` : ""}
            </td>
            <td class="small">${supplier.nif ? generalUtils.escapeHtml(supplier.nif) : '<span class="text-muted">em branco</span>'}</td>
            <td class="small">${contact || '<span class="text-muted">em branco</span>'}</td>
            <td class="text-center">
                <span class="badge bo-status-badge ${supplier.active ? "bg-success" : "bg-secondary"}">
                    ${supplier.active ? "Ativo" : "Inativo"}
                </span>
            </td>
            <td class="text-end text-nowrap">
                ${boUtils.actionButton("edit", "Editar o fornecedor", `data-supplier-edit="${supplier.id}"`)}
                ${supplier.active
                    ? boUtils.actionButton("remove", "Desativar (mantém o registo)", `data-supplier-toggle="${supplier.id}" data-supplier-active="0"`)
                    : boUtils.actionButton("approve", "Reativar o fornecedor", `data-supplier-toggle="${supplier.id}" data-supplier-active="1"`)}
            </td>
        </tr>`;
    }

    function render(suppliers) {
        $("#suppliersTableBody").html(
            suppliers.length === 0
                ? `<tr><td colspan="6" class="text-center text-muted py-5">
                       <i class="bi bi-truck fs-3 d-block mb-2"></i>Nenhum fornecedor corresponde aos filtros.
                   </td></tr>`
                : suppliers.map(row).join("")
        );

        $("#suppliersCount").text(`${suppliers.length} fornecedor(es)`);
    }

    async function load() {
        const promise = API.admin.suppliers.list({
            term: $("#supplierTerm").val(),
            active: $("#supplierActive").val()
        });

        const preloader = $("#suppliersTableContainer").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            state.suppliers = response?.suppliers || [];
            state.summary = response?.summary || null;

            summaryCards(state.summary);
            render(state.suppliers);
        } catch (error) {
            $("#suppliersError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível carregar os fornecedores.");
        } finally {
            await preloader;
        }
    }

    /**
     * Abre o formulário. Todos os campos são escritos (mesmo vazios), para que o
     * payload inclua sempre o registo completo — nunca só o que foi tocado.
     */
    function openForm(supplier = null) {
        const record = supplier || { id: 0, name: "", nif: "", email: "", phone: "", notes: "", active: true };

        $("#supplierFormTitle").text(record.id > 0 ? `Editar fornecedor #${record.id}` : "Novo fornecedor");

        const fields = {
            supplierId: record.id || "",
            name: record.name || "",
            nif: record.nif || "",
            phone: record.phone || "",
            email: record.email || "",
            notes: record.notes || ""
        };

        for (const [fieldName, value] of Object.entries(fields)) {
            $(`#supplierForm [name="${fieldName}"]`).val(value).trigger("change");
        }

        $("#supplierActiveCheck").prop("checked", record.active !== false).trigger("change");

        $("#supplierForm .is-invalid").removeClass("is-invalid");
        $("#supplierForm .invalid-feedback").text("");

        generalUtils.bsModalGetOrCreateInstance(document.getElementById("supplierFormModal")).show();
    }

    async function saveSupplier(request) {
        $("#suppliersError, #suppliersResult").addClass("d-none");

        const promise = request();
        const preloader = $("#supplierFormModal").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            bootstrap.Modal.getInstance(document.getElementById("supplierFormModal"))?.hide();

            $("#suppliersResult").removeClass("d-none")
                .text(response?.message || "Fornecedor guardado.");

            await load();
        } catch (error) {
            const response = error?.responseJSON || {};

            if (response.errors) {
                form.setErrors(response.errors);
            }

            $("#suppliersError").removeClass("d-none")
                .text(response.message || "Não foi possível guardar o fornecedor.");
        } finally {
            await preloader;
        }
    }

    async function toggleSupplier(supplierId, active) {
        const supplier = state.suppliers.find((item) => Number(item.id) === Number(supplierId));
        const label = active ? "reativar" : "desativar";

        if (!(await generalUtils.confirmDialog({ title: `Confirma ${label} ${supplier?.name || "o fornecedor"}?`, icon: "warning" }))) return;

        $("#suppliersError, #suppliersResult").addClass("d-none");

        const promise = API.admin.suppliers.setActive(Number(supplierId), Number(active));
        const preloader = $("#suppliersTableContainer").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            $("#suppliersResult").removeClass("d-none")
                .text(response?.message || "Fornecedor atualizado.");

            await load();
        } catch (error) {
            $("#suppliersError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível atualizar o fornecedor.");
        } finally {
            await preloader;
        }
    }

    $(() => {
        if (!$("#suppliersTableBody").length) return;

        $("#newSupplierBtn").on("click", function () {
            openForm(null);
        });

        $("#searchSuppliersBtn").on("click", load);

        $("#clearSuppliersBtn").on("click", function () {
            $("#supplierTerm").val("");
            $("#supplierActive").val("");
            load();
        });

        $("#supplierTerm").on("keydown", function (event) {
            if (event.key === "Enter") {
                event.preventDefault();
                load();
            }
        });

        $(document).on("click", "[data-supplier-edit]", function () {
            const supplier = state.suppliers.find((item) => Number(item.id) === Number($(this).data("supplier-edit")));
            openForm(supplier || null);
        });

        $(document).on("click", "[data-supplier-toggle]", function () {
            toggleSupplier($(this).data("supplier-toggle"), $(this).data("supplier-active"));
        });

        load();
    });

    return { state, load, openForm };
})();
