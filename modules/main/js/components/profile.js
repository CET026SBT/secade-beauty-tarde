const profile = (() => {
    const state = {
        profile: null,
        addresses: [],
        cities: []
    };

    function fillProfile() {
        const data = state.profile;
        if (!data) return;

        // `#profileFieldName`/`#profileFieldEmail` já vêm preenchidos do servidor
        // (sessão); os restantes dependem do perfil de cliente.
        $("#profileFieldName").text(data.name || "-");
        $("#profileFieldEmail").text(data.email || "-");
        $("#profileFieldPhone").text(data.phone || "-");
        $("#profileFieldNif").text(data.nif || "-");
        $("#profileFieldVerified").html(data.phoneVerified
            ? '<span class="badge bg-success">Sim</span>'
            : '<span class="badge bg-secondary">Não</span>');
    }

    function addressCard(address) {
        const label = [address.street, address.doorNumber && `Nº ${address.doorNumber}`, address.zipCode, address.cityName]
            .filter(Boolean)
            .map(value => generalUtils.escapeHtml(value))
            .join(", ");

        const mainBadge = address.isMain
            ? '<span class="badge bg-primary ms-2">Principal</span>'
            : "";

        const mainButton = address.isMain
            ? ""
            : `<button type="button" class="btn btn-sm btn-outline-primary" data-set-principal="${address.id}">
                   <i class="bi bi-star"></i> Tornar principal
               </button>`;

        return `<div class="border rounded p-3 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <span class="fw-bold">${label}</span>${mainBadge}
            </div>
            <div class="d-flex gap-2">
                ${mainButton}
                <button type="button" class="btn btn-sm btn-outline-danger" data-delete-address="${address.id}">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>`;
    }

    function renderAddresses() {
        const $list = $("#profileAddressesList");
        $list.empty();

        if (state.addresses.length === 0) {
            $list.html(`<div class="text-center text-muted py-4">
                <i class="bi bi-geo fs-3 d-block mb-2"></i>Ainda não guardou nenhuma morada.
            </div>`);
            return;
        }

        for (const address of state.addresses) {
            $list.append(addressCard(address));
        }
    }

    function fillCitySelect() {
        const $select = $("#profileAddressCity");
        const options = ['<option value="">Cidade...</option>'];

        for (const city of state.cities) {
            options.push(`<option value="${generalUtils.escapeHtml(city.name)}">${generalUtils.escapeHtml(city.name)}</option>`);
        }

        $select.html(options.join(""));
    }

    // A página é partilhada por todos os perfis, mas as APIs de cliente
    // (`customer-profile`, `customer-address-*`) respondem 403 a gestores e
    // funcionários — e a página ficava vazia, sem qualquer conteúdo.
    function isCustomerPage() {
        return Number($("#profilePage").data("customer")) === 1;
    }

    async function load() {
        if (!isCustomerPage()) return;

        const promise = Promise.all([API.customer.profile(), API.customer.addresses(), API.cities.getSupported()]);
        const preloader = $("#profilePage").preloader(".jq-overlay-process", promise);

        try {
            const [profileResponse, addressResponse, cityResponse] = await promise;

            state.profile = profileResponse?.profile || null;
            state.addresses = addressResponse?.addresses || [];
            state.cities = cityResponse?.cities || [];

            fillProfile();
            fillCitySelect();
            renderAddresses();
        } catch (error) {
            $("#addressError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível carregar o perfil.");
        } finally {
            await preloader;
        }
    }

    async function saveAddress() {
        const payload = {
            cityName: $("#profileAddressCity").val(),
            zipCode: $("#profileAddressZip").val(),
            street: $("#profileAddressStreet").val(),
            doorNumber: $("#profileAddressDoor").val()
        };

        $("#addressError").addClass("d-none");

        const promise = API.customer.createAddress(payload);
        const preloader = $("#profileAddressForm").preloader(".jq-overlay-process", promise);

        try {
            await promise;

            $("#profileAddressForm").addClass("d-none");
            $("#profileAddressCity, #profileAddressZip, #profileAddressStreet, #profileAddressDoor").val("");

            const response = await API.customer.addresses();
            state.addresses = response?.addresses || [];
            renderAddresses();
        } catch (error) {
            const errors = error?.responseJSON?.errors;
            $("#addressError").removeClass("d-none").text(
                errors ? Object.values(errors).join(" ") : (error?.responseJSON?.message || "Não foi possível guardar a morada.")
            );
        } finally {
            await preloader;
        }
    }

    async function setPrincipal(addressId) {
        const promise = API.customer.setPrincipalAddress(Number(addressId));
        const preloader = $("#profileAddressesList").preloader(".jq-overlay-process", promise);

        try {
            await promise;

            const response = await API.customer.addresses();
            state.addresses = response?.addresses || [];
            renderAddresses();
        } catch (error) {
            $("#addressError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível atualizar a morada principal.");
        } finally {
            await preloader;
        }
    }

    async function removeAddress(addressId) {
        if (!confirm("Remover esta morada?")) return;

        const promise = API.customer.deleteAddress(Number(addressId));
        const preloader = $("#profileAddressesList").preloader(".jq-overlay-process", promise);

        try {
            await promise;

            const response = await API.customer.addresses();
            state.addresses = response?.addresses || [];
            renderAddresses();
        } catch (error) {
            $("#addressError").removeClass("d-none")
                .text(error?.responseJSON?.message || "Não foi possível remover a morada.");
        } finally {
            await preloader;
        }
    }

    function bindEvents() {
        $("#toggleAddressFormBtn").on("click", function () {
            $("#profileAddressForm").toggleClass("d-none");
            $("#addressError").addClass("d-none");
        });

        $("#cancelAddressBtn").on("click", function () {
            $("#profileAddressForm").addClass("d-none");
        });

        $("#saveAddressBtn").on("click", saveAddress);

        $(document).on("click", "[data-set-principal]", function () {
            setPrincipal($(this).data("set-principal"));
        });

        $(document).on("click", "[data-delete-address]", function () {
            removeAddress($(this).data("delete-address"));
        });
    }

    $(() => {
        bindEvents();
        load();
    });

    return { state, load, saveAddress };
})();