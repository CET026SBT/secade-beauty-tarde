const bookingWizard = (() => {
    const supportedCities = [];

    const state = {
        services: [],
        categories: [],
        selectedServiceIds: new Set(),
        filterCategoryId: null,
        channel: null,
        addresses: [],
        cities: [],
        people: [],
        generatedOtp: null,
        otpValidated: false,
        date: null,
        time: null,
        slots: []
    };

    const FLOWS = {
        loja_fisica: ["services", "channel", "datetime", "professional", "summary"],
        carrinha_ambulante: ["services", "channel", "address", "otp", "datetime", "policy", "summary"]
    };

    const STEP_LABELS = {
        services: "Serviços",
        channel: "Canal",
        address: "Morada",
        otp: "OTP",
        datetime: "Data/Hora",
        professional: "Profissional",
        policy: "Sinal",
        summary: "Resumo"
    };

    const form = new Form("#bookingWizardForm", {
        validators: bookingValidators({ supportedCities })
    });

    const $form = $("#bookingWizardForm");
    const clientName = $form.data("client-name") || "";

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    function serviceById(id) {
        return state.services.find(s => s.id === Number(id));
    }

    function selectedServices() {
        return [...state.selectedServiceIds].map(serviceById).filter(Boolean);
    }

    function uniqueDurations(ids) {
        return [...new Set(ids.map(Number))]
            .map(serviceById)
            .filter(Boolean)
            .reduce((total, service) => total + Number(service.estimatedDurationMinutes), 0);
    }

    function uniqueAmount(ids) {
        return [...new Set(ids.map(Number))]
            .map(serviceById)
            .filter(Boolean)
            .reduce((total, service) => total + Number(service.basePrice), 0);
    }

    function bookingDuration() {
        if (state.channel === "carrinha_ambulante") {
            return state.people.reduce((total, person) => total + uniqueDurations(person.serviceIds), 0);
        }
        return uniqueDurations([...state.selectedServiceIds]);
    }

    function bookingAmount() {
        if (state.channel === "carrinha_ambulante") {
            return state.people.reduce((total, person) => {
                return total + person.serviceIds.reduce((sum, id) => sum + Number(serviceById(id)?.basePrice || 0), 0);
            }, 0);
        }
        return uniqueAmount([...state.selectedServiceIds]);
    }

    function hasPhysicalSpaceService() {
        return selectedServices().some(service => service.requiresPhysicalSpace);
    }

    function updateTotals() {
        $("#totalDuration").text(generalUtils.formatDuration(bookingDuration()));
        $("#totalAmount").text(generalUtils.formatCurrency(bookingAmount()));
    }

    // ------------------------------------------------------------------
    // Passo 1 — Serviços
    // ------------------------------------------------------------------

    function serviceOption(service) {
        const badge = service.requiresPhysicalSpace
            ? '<span class="badge bg-warning text-dark ms-1">Apenas Loja</span>'
            : '<span class="badge bg-success ms-1">Loja e Carrinha</span>';

        return $(`<div class="col-md-6 col-lg-4 service-option" data-category-id="${service.categoryId}">
            <div class="card h-100 border service-option-card">
                <div class="card-body py-3">
                    <div class="form-check">
                        <input class="form-check-input booking-service-checkbox" type="checkbox"
                               value="${service.id}" id="svc-${service.id}">
                        <label class="form-check-label w-100" for="svc-${service.id}">
                            <span class="d-block fw-bold">${generalUtils.escapeHtml(service.name)}</span>
                            <span class="d-block small text-muted">${generalUtils.escapeHtml(service.categoryName || '')} ${badge}</span>
                            <span class="d-block small mt-1">
                                <i class="bi bi-clock me-1"></i>${generalUtils.formatDuration(service.estimatedDurationMinutes)}
                                <i class="bi bi-tag ms-2 me-1"></i>${generalUtils.formatCurrency(service.basePrice)}
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>`);
    }

    function renderServicePicker() {
        const $list = $("#bookingServicePicker .services-list");
        $list.empty();

        for (const service of state.services) {
            $list.append(serviceOption(service));
        }

        applyServiceFilter();
        syncServiceCheckboxes();
    }

    function renderServiceFilters() {
        const $filters = $("#bookingServicePicker .filters");
        const buttons = ['<button type="button" class="btn btn-sm btn-outline-primary service-cat-filter active" data-category-id="">Todos</button>'];

        for (const category of state.categories) {
            buttons.push(`<button type="button" class="btn btn-sm btn-outline-primary service-cat-filter" data-category-id="${category.id}">${generalUtils.escapeHtml(category.name)}</button>`);
        }

        $filters.html(buttons.join(""));
    }

    function applyServiceFilter() {
        $("#bookingServicePicker .service-option").each(function () {
            const $option = $(this);
            const visible = state.filterCategoryId === null || Number($option.data("category-id")) === state.filterCategoryId;
            $option.toggleClass("d-none", !visible);
        });
    }

    function syncServiceCheckboxes() {
        $("#bookingServicePicker .booking-service-checkbox").each(function () {
            $(this).prop("checked", state.selectedServiceIds.has(Number($(this).val())));
        });
    }

    function syncChannelCards() {
        const blocked = hasPhysicalSpaceService();
        $("#ambChannelCard").toggleClass("channel-card-disabled opacity-50", blocked);
        $("#ambDisabledNote").toggleClass("d-none", !blocked);

        $(".channel-card").each(function () {
            const $card = $(this);
            const $input = $card.find('input[name="channel"]');
            $card.find(".channel-card-body").toggleClass("border-primary shadow-sm", $input.is(":checked"));
        });
    }

    // ------------------------------------------------------------------
    // Passo 2B — Morada e Pessoas
    // ------------------------------------------------------------------

    function toggleNewAddressForm() {
        const isNew = $("#addressChoice").val() === "new";
        $("#newAddressForm").toggleClass("d-none", !isNew);
    }

    function renderAddressOptions() {
        const $select = $("#addressChoice");
        const options = ['<option value="">Selecione...</option>'];

        for (const address of state.addresses) {
            const label = [address.street, address.doorNumber && `Nº ${address.doorNumber}`, address.zipCode, address.cityName]
                .filter(Boolean)
                .join(", ");
            const suffix = address.isMain ? " (principal)" : "";
            options.push(`<option value="${address.id}">${generalUtils.escapeHtml(label + suffix)}</option>`);
        }

        options.push('<option value="new">+ Introduzir nova morada</option>');
        $select.html(options.join(""));

        const mainAddress = state.addresses.find(address => address.isMain);
        if (mainAddress) {
            $select.val(String(mainAddress.id));
        } else if (state.addresses.length === 0) {
            $select.val("new");
        }

        toggleNewAddressForm();
    }

    function renderCityOptions() {
        const $select = $("#cityName");
        const options = ['<option value="">Selecione...</option>'];

        for (const city of state.cities) {
            options.push(`<option value="${generalUtils.escapeHtml(city.name)}">${generalUtils.escapeHtml(city.name)}</option>`);
        }

        $select.html(options.join(""));
    }

    function renderPeople() {
        const $container = $("#peopleContainer");
        $container.empty();

        state.people.forEach((person, index) => {
            $container.append(personCard(person, index));
        });
    }

    function personCard(person, index) {
        const services = state.services.map(service => {
            const checked = person.serviceIds.includes(service.id) ? "checked" : "";
            const inputId = `person-${index}-svc-${service.id}`;

            return `<div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input person-service-checkbox" type="checkbox" value="${service.id}"
                           id="${inputId}" data-person-index="${index}" ${checked}>
                    <label class="form-check-label small" for="${inputId}">
                        ${generalUtils.escapeHtml(service.name)}
                        <span class="text-muted">· ${generalUtils.formatCurrency(service.basePrice)}</span>
                    </label>
                </div>
            </div>`;
        }).join("");

        const removeButton = state.people.length > 1
            ? `<button type="button" class="btn btn-sm btn-link text-danger p-0 person-remove" data-person-index="${index}">Remover</button>`
            : "";

        return $(`<div class="border rounded p-3 mb-3" data-person-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Pessoa ${index + 1}</h6>
                ${removeButton}
            </div>
            <div class="form-floating mb-3">
                <input type="text" class="form-control person-name" data-person-index="${index}"
                       value="${generalUtils.escapeHtml(person.name)}" placeholder="Nome da pessoa">
                <label>Nome da pessoa*</label>
            </div>
            <div class="row g-2">${services}</div>
        </div>`);
    }

    function addPerson() {
        state.people.push({
            name: state.people.length === 0 ? clientName : "",
            serviceIds: []
        });
        renderPeople();
    }

    function ensurePeople() {
        if (state.people.length === 0) {
            addPerson();
        } else {
            renderPeople();
        }
    }

    async function loadAddressData() {
        const promise = Promise.all([API.customer.addresses(), API.cities.getSupported()]);
        const preloader = $('.form-step[data-step="address"]').preloader(".jq-overlay-process", promise);

        const [addressResponse, cityResponse] = await promise;

        state.addresses = addressResponse?.addresses || [];
        state.cities = cityResponse?.cities || [];

        supportedCities.length = 0;
        supportedCities.push(...state.cities.map(city => city.name));

        renderCityOptions();
        renderAddressOptions();
        ensurePeople();

        await preloader;
    }

    // ------------------------------------------------------------------
    // Passo 2C — OTP (simulação académica)
    // ------------------------------------------------------------------

    async function requestOtp() {
        $("#otpStatus").text("A enviar código...");

        try {
            const response = await API.booking.requestOtp();

            state.generatedOtp = response?.otpCode || null;
            state.otpValidated = false;
            window.bookingOtpCode = state.generatedOtp;

            $("#otpCodeDisplay").text(state.generatedOtp || "------");
            $("#otpSimulationBox").removeClass("d-none");
            $("#otpOkBox").addClass("d-none");
            $("#otpStatus").text("Código enviado (simulação SMS).");
        } catch (xhr) {
            const response = xhr?.responseJSON;
            $("#otpStatus").text(response?.message || "Não foi possível enviar o código. Volte a iniciar sessão.");
        }
    }

    function checkOtp() {
        const isValid = form.validators.otpCode();

        state.otpValidated = isValid;
        $("#otpOkBox").toggleClass("d-none", !isValid);

        return isValid;
    }

    // ------------------------------------------------------------------
    // Passo 3 — Data e Hora
    // ------------------------------------------------------------------

    function highlightSelectedSlot() {
        $(".slot-btn").each(function () {
            const $button = $(this);
            $button.toggleClass("active", $button.data("time") === state.time);
        });
    }

    function renderSlots() {
        const $container = $("#slotsContainer");
        $container.empty();

        if (state.slots.length === 0) {
            $container.html('<p class="text-muted small mb-0">Sem horários disponíveis para esta data.</p>');
            return;
        }

        const buttons = state.slots.map(slot => {
            const disabled = slot.available ? "" : "disabled";
            const title = slot.available ? "" : ' title="Horário indisponível"';
            return `<button type="button" class="btn btn-sm btn-outline-primary slot-btn" data-time="${slot.time}" ${disabled}${title}>${slot.time}</button>`;
        });

        $container.html(`<div class="d-flex flex-wrap gap-2">${buttons.join("")}</div>`);
        highlightSelectedSlot();
    }

    async function loadSlots() {
        if (!state.date) return;

        const duration = Math.max(bookingDuration(), 30);
        const local = state.channel || "loja_fisica";

        state.time = null;
        $("#selectedTime").val("");
        $("#slotError").addClass("d-none");

        const promise = API.booking.availability({ date: state.date, duration, local });
        const preloader = $("#slotsContainer").preloader(".jq-overlay-process", promise);

        const response = await promise;
        state.slots = response?.slots || [];
        renderSlots();

        await preloader;
    }

    // ------------------------------------------------------------------
    // Passo 5 — Resumo
    // ------------------------------------------------------------------

    function summaryRow(label, value) {
        return `<div class="d-flex justify-content-between border-bottom py-2">
            <span class="text-muted">${label}</span>
            <span class="fw-bold text-end">${value}</span>
        </div>`;
    }

    function addressLabel() {
        const choice = $("#addressChoice").val();

        if (choice === "new") {
            return [$("#street").val(), $("#doorNumber").val() && `Nº ${$("#doorNumber").val()}`, $("#zipCode").val(), $("#cityName").val()]
                .filter(Boolean)
                .map(value => generalUtils.escapeHtml(value))
                .join(", ");
        }

        const address = state.addresses.find(item => String(item.id) === String(choice));
        if (!address) return "-";

        return [address.street, address.doorNumber && `Nº ${address.doorNumber}`, address.zipCode, address.cityName]
            .filter(Boolean)
            .map(value => generalUtils.escapeHtml(value))
            .join(", ");
    }

    function personSummary() {
        return state.people.map((person, index) => {
            const names = person.serviceIds
                .map(id => generalUtils.escapeHtml(serviceById(id)?.name || ""))
                .join(", ");

            return `<li><strong>Pessoa ${index + 1} (${generalUtils.escapeHtml(person.name)})</strong>: ${names}</li>`;
        }).join("");
    }

    function renderSummary() {
        const isAmb = state.channel === "carrinha_ambulante";
        const amount = bookingAmount();
        const deposit = isAmb ? 0 : amount * 0.10;

        let html = summaryRow("Canal", isAmb ? "Carrinha Ambulante" : "Loja Física (Évora)");

        if (isAmb) {
            html += summaryRow("Morada", addressLabel());
            html += `<div class="py-2 border-bottom">
                        <span class="text-muted d-block mb-1">Pessoas e serviços</span>
                        <ul class="mb-0 small ps-3">${personSummary()}</ul>
                     </div>`;
        } else {
            html += summaryRow("Serviços", selectedServices().map(service => generalUtils.escapeHtml(service.name)).join(", "));
        }

        html += summaryRow("Data e hora", generalUtils.formatDateTime(`${state.date} ${state.time}`));
        html += summaryRow("Duração estimada", generalUtils.formatDuration(bookingDuration()));
        html += summaryRow("Valor total", generalUtils.formatCurrency(amount));

        if (isAmb) {
            html += summaryRow("Sinal", "Dispensado (1ª marcação em ambulatório)");
        } else {
            html += summaryRow("Sinal 10% (simulado)", generalUtils.formatCurrency(deposit));
            html += summaryRow("Restante a pagar no dia", generalUtils.formatCurrency(amount - deposit));
        }

        html += `<div class="alert alert-info mt-3 mb-0 small">
            <i class="bi bi-info-circle me-1"></i>
            ${isAmb
                ? "O agendamento ficará <strong>pendente</strong> até validação da rota pela equipa."
                : "Os serviços são <strong>aceites automaticamente</strong> e o agendamento fica pendente de validação logística da loja."}
        </div>`;

        $("#summaryBody").html(html);
    }

    function showSubmitError(message) {
        $("#submitError").removeClass("d-none").text(message);
    }

    async function resolveAddressId() {
        const choice = $("#addressChoice").val();
        if (choice !== "new") return Number(choice);

        const response = await API.customer.createAddress({
            cityName: $("#cityName").val(),
            street: $("#street").val(),
            doorNumber: $("#doorNumber").val(),
            zipCode: $("#zipCode").val()
        });

        return Number(response?.addressId);
    }

    async function confirmBooking() {
        $("#submitError").addClass("d-none");

        const isAmb = state.channel === "carrinha_ambulante";

        const request = (async () => {
            const payload = { date: state.date, time: state.time };

            if (!isAmb) {
                payload.serviceIds = [...state.selectedServiceIds];
                return API.booking.createStore(payload);
            }

            payload.addressId = await resolveAddressId();
            payload.otpCode = $("#otpCode").val();
            payload.people = state.people.map(person => ({
                name: person.name || clientName,
                serviceIds: person.serviceIds
            }));

            return API.booking.createAmbulatory(payload);
        })();

        const preloader = $('.form-step[data-step="summary"]').preloader(".jq-overlay-process", request);

        try {
            const response = await request;
            const local = isAmb ? "carrinha_ambulante" : "loja_fisica";
            location.href = `${BASE_URL ?? ''}/agendamento-sucesso?id=${response.bookingId}&local=${local}`;
        } catch (error) {
            showSubmitError(error?.responseJSON?.message || "Não foi possível concluir o agendamento. Tente novamente.");
        } finally {
            await preloader;
        }
    }

    // ------------------------------------------------------------------
    // Navegação e validação por passo
    // ------------------------------------------------------------------

    function currentFlow() {
        if (!state.channel) return ["services", "channel"];
        return FLOWS[state.channel];
    }

    function activeStepName() {
        return $form.find(".form-step.active").data("step");
    }

    function renderStepper() {
        const flow = currentFlow();
        const current = activeStepName();
        const currentIndex = flow.indexOf(current);

        const html = flow.map((step, index) => {
            const isActive = step === current;
            const isDone = currentIndex > index;

            return `<div class="booking-step${isActive ? " active" : ""}${isDone ? " done" : ""}">
                <span class="booking-step-index">${isDone ? '<i class="bi bi-check-lg"></i>' : index + 1}</span>
                <span class="booking-step-label">${STEP_LABELS[step] || step}</span>
            </div>`;
        }).join("");

        $("#bookingStepsIndicator").html(html);
    }

    function showStep(name) {
        $form.find(".form-step").removeClass("active");
        $form.find(`.form-step[data-step="${name}"]`).addClass("active");
        renderStepper();
        generalUtils.scrollToElement("#bookingStepsIndicator", 120);
    }

    function validateServicesStep() {
        const hasServices = state.selectedServiceIds.size > 0;
        $("#servicesError")
            .toggleClass("d-none", hasServices)
            .text(hasServices ? "" : "Selecione pelo menos um serviço para continuar.");

        return hasServices;
    }

    function validateChannelStep() {
        const hasChannel = form.validators.channel();
        let message = "";

        if (!hasChannel) {
            message = "Escolha onde prefere ser atendido.";
        } else if (state.channel === "carrinha_ambulante" && hasPhysicalSpaceService()) {
            message = "Os serviços selecionados exigem espaço físico e não podem ser realizados na carrinha. Escolha a loja física ou remova esses serviços.";
        }

        $("#channelError").toggleClass("d-none", message === "").text(message);

        return message === "";
    }

    function validatePeople() {
        let message = "";

        if (state.people.length === 0) {
            message = "Adicione pelo menos uma pessoa.";
        } else {
            for (let index = 0; index < state.people.length; index++) {
                const person = state.people[index];

                if (!String(person.name).trim()) {
                    message = `Indique o nome da Pessoa ${index + 1}.`;
                    break;
                }

                if (person.serviceIds.length === 0) {
                    message = `Selecione pelo menos um serviço para a Pessoa ${index + 1}.`;
                    break;
                }
            }
        }

        $("#peopleError").toggleClass("d-none", message === "").text(message);

        return message === "";
    }

    function validateAddressStep() {
        const checks = [form.validators.addressChoice()];

        if ($("#addressChoice").val() === "new") {
            checks.push(
                form.validators.cityName(),
                form.validators.zipCode(),
                form.validators.street(),
                form.validators.doorNumber()
            );
        }

        checks.push(validatePeople());

        return checks.every(Boolean);
    }

    function validateDateTimeStep() {
        state.date = $("#bookingDate").val() || state.date;

        const dateValid = form.validators.bookingDate();
        const timeValid = !!state.time;

        $("#slotError")
            .toggleClass("d-none", timeValid)
            .text(timeValid ? "" : "Selecione um horário disponível.");

        return dateValid && timeValid;
    }

    function validateStep(name) {
        switch (name) {
            case "services": return validateServicesStep();
            case "channel":  return validateChannelStep();
            case "address":  return validateAddressStep();
            case "otp":      return checkOtp();
            case "datetime": return validateDateTimeStep();
            case "policy":   return form.validators.termsAccepted();
            default:         return true;
        }
    }

    function onEnterStep(name) {
        if (name === "address") loadAddressData();
        if (name === "summary") renderSummary();
    }

    function goNext() {
        const flow = currentFlow();
        const current = activeStepName();

        if (!validateStep(current)) return;

        const next = flow[flow.indexOf(current) + 1];
        if (!next) return;

        onEnterStep(next);
        showStep(next);
    }

    function goPrev() {
        const flow = currentFlow();
        const previous = flow[flow.indexOf(activeStepName()) - 1];

        if (previous) showStep(previous);
    }

    // ------------------------------------------------------------------
    // Eventos + arranque
    // ------------------------------------------------------------------

    function bindEvents() {
        $(document).on("click", "[data-step-next]", goNext);
        $(document).on("click", "[data-step-prev]", goPrev);
        $(document).on("click", "#confirmBookingBtn", confirmBooking);

        // Passo 1 — serviços
        $(document).on("change", ".booking-service-checkbox", function () {
            const id = Number($(this).val());

            if ($(this).is(":checked")) {
                state.selectedServiceIds.add(id);
            } else {
                state.selectedServiceIds.delete(id);
            }

            updateTotals();
            syncChannelCards();
            $("#servicesError").addClass("d-none");
        });

        $(document).on("click", ".service-cat-filter", function () {
            const value = $(this).data("category-id");

            state.filterCategoryId = value === "" ? null : Number(value);
            $(".service-cat-filter").removeClass("active");
            $(this).addClass("active");
            applyServiceFilter();
        });

        // Passo 2 — canal
        $(document).on("change", 'input[name="channel"]', function () {
            state.channel = $(this).val();
            syncChannelCards();
            updateTotals();
            renderStepper();
            $("#channelError").addClass("d-none");
        });

        // Passo 2B — morada e pessoas
        $(document).on("change", "#addressChoice", toggleNewAddressForm);
        $(document).on("click", "#addPersonBtn", addPerson);

        $(document).on("click", ".person-remove", function () {
            state.people.splice(Number($(this).data("person-index")), 1);
            renderPeople();
        });

        $(document).on("input", ".person-name", function () {
            const index = Number($(this).data("person-index"));
            if (state.people[index]) state.people[index].name = $(this).val();
        });

        $(document).on("change", ".person-service-checkbox", function () {
            const index = Number($(this).data("person-index"));
            const id = Number($(this).val());
            const person = state.people[index];

            if (!person) return;

            if ($(this).is(":checked")) {
                if (!person.serviceIds.includes(id)) person.serviceIds.push(id);
            } else {
                person.serviceIds = person.serviceIds.filter(serviceId => serviceId !== id);
            }

            $("#peopleError").addClass("d-none");
        });

        // Passo 2C — OTP
        $(document).on("click", "#otpRequestBtn", requestOtp);
        $(document).on("change blur", "#otpCode", checkOtp);

        // Passo 3 — data e hora
        $(document).on("change", "#bookingDate", function () {
            state.date = $(this).val();

            if (form.validators.bookingDate()) {
                loadSlots();
            }
        });

        $(document).on("click", ".slot-btn", function () {
            state.time = $(this).data("time");

            $("#selectedTime").val(state.time);
            $("#slotError").addClass("d-none");
            highlightSelectedSlot();
        });
    }

    async function init() {
        state.date = $("#bookingDate").val() || null;

        // Pré-seleção por query string (?services=1,2) — usado pelo catálogo
        String(window.APP_PARAMS?.services || "")
            .split(",")
            .map(Number)
            .filter(Boolean)
            .forEach(id => state.selectedServiceIds.add(id));

        const promise = Promise.all([API.categories.getAll(), API.booking.services()]);
        const preloader = $("#bookingServicePicker").preloader(".jq-skeleton-service-category-card", promise);

        const [categoriesResponse, servicesResponse] = await promise;

        state.categories = categoriesResponse?.categories || [];
        state.services = servicesResponse?.services || [];

        renderServiceFilters();
        renderServicePicker();
        updateTotals();
        syncChannelCards();
        renderStepper();

        await preloader;
    }

    $(() => {
        bindEvents();
        init();
    });

    return {
        form,
        state,
        goNext,
        goPrev,
        confirmBooking,
        reload: init
    };
})();