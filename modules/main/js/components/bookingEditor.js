/**
 * Editor de agendamento do cliente — F9b (C-06 · §4.5).
 *
 * Âmbito do editor (decisão do cliente): pode mudar-se **serviços/pessoas**,
 * **morada** (carrinha) e **data/hora** — com re-avaliação dos slots ao mudar os
 * serviços. Não se muda o **canal** (imutável) nem o OTP (D-04/D-07.7).
 *
 * A avaliação de §4.5 (modularizar o wizard vs. escrever um editor próprio) resolveu-se
 * pelo **editor próprio**: o wizard tem stepper, OTP e resumo que aqui não fazem
 * sentido; partilham a API e os utilitários comuns (`API.booking.availability`,
 * `generalUtils`), que é o que efetivamente é reutilizável.
 */
const bookingEditor = (() => {
    const state = { booking: null, services: [], addresses: [], slots: [], time: null, people: [] };

    function showError(message) {
        $("#bookingEditError").removeClass("d-none").text(message);
    }

    function clearError() { $("#bookingEditError").addClass("d-none").text(""); }

    function isAmbulatory() {
        return state.booking?.local === "carrinha_ambulante";
    }

    function serviceById(id) {
        return state.services.find(service => Number(service.id) === Number(id));
    }

    function durationOf(serviceIds) {
        const ids = [...new Set(serviceIds.map(Number))];
        return ids.reduce((total, id) => total + Number(serviceById(id)?.estimatedDurationMinutes || 0), 0);
    }

    /** Duração total do que está selecionado agora (loja: lista; carrinha: por pessoa). */
    function currentDuration() {
        if (isAmbulatory()) {
            return state.people.reduce((total, person) => Math.max(total, durationOf(person.serviceIds || [])), 0);
        }

        return durationOf(selectedStoreServices());
    }

    function selectedStoreServices() {
        return $("#bookingEditStoreServicesList .booking-edit-service:checked").map(function () { return $(this).val(); }).get();
    }

    // ------------------------------------------------------------------
    // Serviços (loja)
    // ------------------------------------------------------------------
    function serviceCheckbox(service, checked) {
        return $(`<div class="col-md-6">
            <div class="form-check border rounded p-2">
                <input class="form-check-input booking-edit-service" type="checkbox" value="${service.id}"
                       id="be-svc-${service.id}" ${checked ? "checked" : ""}>
                <label class="form-check-label small" for="be-svc-${service.id}">
                    <span class="fw-bold d-block">${generalUtils.escapeHtml(service.name)}</span>
                    <span class="text-muted">${generalUtils.formatDuration(service.estimatedDurationMinutes)} ·
                        ${generalUtils.formatCurrencyWithVat(service.basePrice)}</span>
                </label>
            </div>
        </div>`);
    }

    function renderStoreServices(selectedIds) {
        const $list = $("#bookingEditStoreServicesList").empty();
        const selected = new Set(selectedIds.map(Number));

        for (const service of state.services) {
            $list.append(serviceCheckbox(service, selected.has(Number(service.id))));
        }
    }

    // ------------------------------------------------------------------
    // Pessoas + serviços (carrinha)
    // ------------------------------------------------------------------
    function personTemplate(person, index) {
        const services = state.services.map(service => {
            const checked = (person.serviceIds || []).map(Number).includes(Number(service.id));
            return `<div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input booking-edit-person-service" type="checkbox"
                           data-person="${index}" value="${service.id}" id="be-p${index}-s${service.id}" ${checked ? "checked" : ""}>
                    <label class="form-check-label small" for="be-p${index}-s${service.id}">
                        ${generalUtils.escapeHtml(service.name)}
                        <span class="text-muted">· ${generalUtils.formatCurrencyWithVat(service.basePrice)}</span>
                    </label>
                </div>
            </div>`;
        }).join("");

        return `<div class="border rounded p-3 mb-2 booking-edit-person" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <input type="text" class="form-control form-control-sm booking-edit-person-name me-2"
                       value="${generalUtils.escapeHtml(person.name || "")}" placeholder="Nome da pessoa">
                <button type="button" class="btn btn-sm btn-outline-danger booking-edit-person-remove" data-index="${index}">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="row g-2">${services}</div>
        </div>`;
    }

    function renderPeople() {
        const $container = $("#bookingEditPeople").empty();

        state.people.forEach((person, index) => {
            $container.append(personTemplate(person, index));
        });

        if (state.people.length === 0) {
            $container.html('<p class="text-muted small mb-0">Adicione pelo menos uma pessoa com serviços.</p>');
        }
    }

    /** Lê do DOM as pessoas atuais (nomes + serviços), preservando observações. */
    function syncPeopleFromDom() {
        $(".booking-edit-person").each(function () {
            const index = Number($(this).data("index"));
            if (!state.people[index]) return;

            state.people[index].name = $(this).find(".booking-edit-person-name").val();

            const services = [];
            $(this).find(".booking-edit-person-service:checked").each(function () {
                services.push(Number($(this).val()));
            });
            state.people[index].serviceIds = services;
        });
    }

    function addPerson() {
        syncPeopleFromDom();
        state.people.push({ name: "", serviceIds: [] });
        renderPeople();
    }

    // ------------------------------------------------------------------
    // Slots (re-avaliação dinâmica — §9.2)
    // ------------------------------------------------------------------
    function renderSlots() {
        const $container = $("#bookingEditSlots").empty();

        if (state.slots.length === 0) {
            $container.html('<p class="text-muted small mb-0">Sem horários disponíveis para esta data.</p>');
            return;
        }

        $container.html(state.slots.map(slot => {
            const disabled = slot.available ? "" : "disabled";
            const active = String(slot.time) === String(state.time) ? "active" : "";
            return `<button type="button" class="btn btn-sm btn-outline-primary booking-edit-slot ${active}"
                            data-time="${slot.time}" ${disabled}>${slot.time}</button>`;
        }).join(""));
    }

    async function loadSlots() {
        const date = $("#bookingEditDate").val();
        if (!date) return;

        // A duração muda com os serviços: os slots são reavaliados por isso (§9.2).
        const duration = Math.max(currentDuration(), 30);
        const local = state.booking?.local || "loja_fisica";

        const promise = API.booking.availability({ date, duration, local });
        const preloader = $("#bookingEditSlots").preloader(".jq-overlay-process", promise);

        try {
            state.slots = (await promise)?.slots || [];

            // Mantém o horário original quando a data não mudou e o slot continua livre.
            const originalDate = String(state.booking?.dateTime || "").substring(0, 10);
            const originalTime = String(state.booking?.dateTime || "").substring(11, 16);
            const keepOriginal = date === originalDate && state.slots.some(slot => slot.time === originalTime && slot.available);

            state.time = keepOriginal ? originalTime : null;
            $("#bookingEditTime").val(state.time || "");
            renderSlots();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar os horários.");
        } finally {
            await preloader;
        }
    }

    // ------------------------------------------------------------------
    // Morada
    // ------------------------------------------------------------------
    function renderAddresses(selectedId) {
        const $select = $("#bookingEditAddress").empty();

        $select.append('<option value="">Selecione...</option>');
        state.addresses.forEach(address => {
            const label = [address.street, address.doorNumber && `Nº ${address.doorNumber}`, address.zipCode, address.cityName]
                .filter(Boolean).map(value => generalUtils.escapeHtml(String(value))).join(", ");
            $select.append(`<option value="${address.id}">${label}</option>`);
        });

        if (selectedId) $select.val(String(selectedId));
    }

    // ------------------------------------------------------------------
    // Abrir / guardar
    // ------------------------------------------------------------------
    async function open(booking) {
        clearError();

        state.booking = booking;
        state.time = null;
        state.people = [];

        $("#bookingEditRef").text("#" + booking.id);
        $("#bookingEditChannel").text(
            booking.local === "carrinha_ambulante"
                ? "Prestação em carrinha ambulante — pode mudar serviços, pessoas, morada e data/hora."
                : "Prestação em loja — pode mudar serviços e data/hora. (O canal não se altera.)"
        );

        // Serviços e moradas: uma só leitura, em paralelo.
        try {
            const [serviceResponse, addressResponse] = await Promise.all([
                API.booking.services(),
                API.customer.addresses()
            ]);

            state.services  = serviceResponse?.services || [];
            state.addresses = addressResponse?.addresses || [];
        } catch (error) {
            showError("Não foi possível carregar os serviços.");
            return;
        }

        $("#bookingEditDate").val(String(booking.dateTime || "").substring(0, 10));

        if (booking.local === "carrinha_ambulante") {
            $("#bookingEditAddressBlock, #bookingEditPeopleBlock").removeClass("d-none");
            $("#bookingEditStoreServices").addClass("d-none");

            const services = booking.services || [];
            const people = (booking.people || []).map(person => ({
                name: person.personName || "",
                serviceIds: services
                    .filter(service => Number(service.personId) === Number(person.id))
                    .map(service => Number(service.serviceId))
            }));

            state.people = people.length > 0
                ? people
                : [{ name: "", serviceIds: services.map(service => Number(service.serviceId)) }];

            renderPeople();
            renderAddresses(booking.addressId);
        } else {
            $("#bookingEditStoreServices").removeClass("d-none");
            $("#bookingEditAddressBlock, #bookingEditPeopleBlock").addClass("d-none");

            renderStoreServices((booking.services || []).map(service => Number(service.serviceId)));
        }

        bootstrap.Modal.getOrCreateInstance($("#bookingEditModal")[0]).show();

        await loadSlots();
    }

    function buildPayload() {
        const payload = {
            bookingId: Number(state.booking.id),
            date: $("#bookingEditDate").val(),
            time: $("#bookingEditTime").val() || state.time
        };

        if (isAmbulatory()) {
            syncPeopleFromDom();

            payload.addressId = Number($("#bookingEditAddress").val() || 0);
            payload.people = state.people
                .filter(person => (person.serviceIds || []).length > 0)
                .map(person => ({ name: person.name || "Pessoa", serviceIds: person.serviceIds }));
        } else {
            payload.serviceIds = selectedStoreServices().map(Number);
        }

        return payload;
    }

    async function save() {
        clearError();

        const payload = buildPayload();

        if (!payload.time) { showError("Escolha um horário."); return; }

        if (isAmbulatory() && payload.people.length === 0) {
            showError("Indique pelo menos uma pessoa com serviços.");
            return;
        }

        if (!isAmbulatory() && payload.serviceIds.length === 0) {
            showError("Escolha pelo menos um serviço.");
            return;
        }

        const promise = API.booking.updateBooking(payload);
        const preloader = $("#bookingEditModal").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;

            bootstrap.Modal.getInstance($("#bookingEditModal")[0])?.hide();

            generalUtils.alertDialog({ icon: "success", text: response?.message || "Agendamento alterado." });

            // A lista (agendamentos) recarrega-se para refletir a alteração.
            appointments?.load?.();
        } catch (error) {
            const errors = error?.responseJSON?.errors;
            showError(errors ? Object.values(errors).join(" ") : (error?.responseJSON?.message || "Não foi possível guardar."));
        } finally {
            await preloader;
        }
    }

    function bindEvents() {
        $("#bookingEditDate").on("change", loadSlots);

        // Re-avalia os slots quando a composição de serviços muda (§9.2).
        $(document).on("change", ".booking-edit-service", loadSlots);
        $(document).on("change", ".booking-edit-person-service", function () {
            syncPeopleFromDom();
            loadSlots();
        });

        $(document).on("click", ".booking-edit-slot", function () {
            state.time = $(this).data("time");
            $("#bookingEditTime").val(state.time);
            $(".booking-edit-slot").removeClass("active");
            $(this).addClass("active");
        });

        $("#bookingEditAddPerson").on("click", addPerson);

        $(document).on("click", ".booking-edit-person-remove", function () {
            const index = Number($(this).data("index"));
            syncPeopleFromDom();
            state.people.splice(index, 1);
            renderPeople();
        });

        $("#bookingEditSaveBtn").on("click", save);
    }

    $(() => {
        if (!$("#bookingEditModal").length) return;
        bindEvents();
    });

    return { state, open, save, buildPayload, loadSlots, renderSlots, renderStoreServices, renderPeople, addPerson, loadAddresses: renderAddresses };
})();