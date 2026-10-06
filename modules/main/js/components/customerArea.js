const customerArea = (() => {
    const state = { profile: null, cropper: null, pendingFile: null, cities: [] };

    function showError(message) {
        $("#customerAreaError").removeClass("d-none").text(message);
    }

    function showSuccess(message) {
        $("#customerAreaSuccess").removeClass("d-none").text(message);
        setTimeout(() => $("#customerAreaSuccess").addClass("d-none"), 6000);
    }

    function clearMessages() { $("#customerAreaError, #customerAreaSuccess").addClass("d-none"); }

    function photoUrl(profile) {
        if (profile?.photo) {
            return `${BASE_URL ?? ""}/${String(profile.photo).replace(/^\//, "")}`;
        }
        return `${BASE_URL ?? ""}/modules/common/img/testimonial-1.jpg`;
    }

    /** Activa a secção pedida no URL (`/area-cliente?seccao=agendamentos` ou `#id`). */
    function activateSection() {
        const requested = ($("#customerArea").data("section") || "").toString() || (window.location.hash || "").replace("#", "");

        const map = { perfil: "#sectionPerfil", agendamentos: "#sectionAgendamentos", lembretes: "#sectionLembretes" };
        const target = map[requested];

        if (!target || !document.querySelector(target)) return;

        const trigger = document.querySelector(`#customerAreaTabs [data-bs-target="${target}"]`);
        if (!trigger) return;

        // Bootstrap 5.0.0 não tem API de "mostrar por seletor": usa-se o próprio botão.
        if (typeof bootstrap !== "undefined" && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(trigger).show();
        }
    }

    // ------------------------------------------------------------------
    // Perfil
    // ------------------------------------------------------------------
    function renderProfile(profile) {
        state.profile = profile;

        $("#customerName").text(profile?.name || "");
        $("#customerEmail").text(profile?.email || "");
        $("#customerPhoto").attr("src", photoUrl(profile));

        $("#customerFieldName").text(profile?.name || "-");
        $("#customerFieldEmail").text(profile?.email || "-");
        $("#customerFieldPhone").text(profile?.phone || "-");
        $("#customerFieldNif").text(profile?.nif || "-");
    }

    async function loadProfile() {
        const promise = API.customer.profile();

        try {
            const response = await promise;
            renderProfile(response?.profile || null);
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar o perfil.");
        }
    }

    function openProfileModal() {
        clearMessages();
        $("#customerInputName").val(state.profile?.name || "");
        $("#customerInputPhone").val(state.profile?.phone || "");
        $("#customerInputNif").val(state.profile?.nif || "");

        generalUtils.bsModalGetOrCreateInstance($("#customerProfileModal")[0]).show();
    }

    async function saveProfile() {
        clearMessages();

        const promise = API.customer.update({
            name: $("#customerInputName").val(),
            phone: $("#customerInputPhone").val(),
            nif: $("#customerInputNif").val()
        });

        try {
            const response = await promise;
            showSuccess(response?.message || "Dados atualizados.");
            bootstrap.Modal.getInstance($("#customerProfileModal")[0])?.hide();
            await loadProfile();
        } catch (error) {
            const errors = error?.responseJSON?.errors;
            showError(errors ? Object.values(errors).join(" ") : (error?.responseJSON?.message || "Não foi possível guardar."));
        }
    }

    // ------------------------------------------------------------------
    // Foto (crop quadrado · §4.6)
    // ------------------------------------------------------------------
    function openCropModal(file) {
        if (!file) { showError("Escolha uma fotografia."); return; }

        state.pendingFile = true;

        const reader = new FileReader();
        reader.onload = (event) => {
            const image = document.getElementById("customerCropImage");
            image.src = event.target.result;

            generalUtils.bsModalGetOrCreateInstance($("#customerCropModal")[0]).show();
        };
        reader.readAsDataURL(file);
    }

    function onCropShown() {
        const image = document.getElementById("customerCropImage");

        if (state.cropper) { state.cropper.destroy(); }

        if (typeof Cropper === "undefined") return;

        state.cropper = new Cropper(image, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 1,
            movable: false,
            zoomable: true,
            background: false
        });
    }

    function cropToBlob() {
        return new Promise((resolve) => {
            if (!state.cropper) { resolve(state.pendingFile); return; }

            state.cropper.getCroppedCanvas({ width: 600, height: 600, imageSmoothingQuality: "high" })
                .toBlob((blob) => resolve(blob), "image/jpeg", 0.9);
        });
    }

    async function uploadPhoto() {
        clearMessages();

        const blob = await cropToBlob();
        if (!blob) { showError("Não foi possível preparar a imagem."); return; }

        const formData = new FormData();
        formData.append("photo", blob, "avatar.jpg");

        const promise = API.user.uploadPhoto(formData);
        const preloader = $("#customerArea").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            showSuccess(response?.message || "Fotografia atualizada.");
            bootstrap.Modal.getInstance($("#customerCropModal")[0])?.hide();
            await loadProfile();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar a fotografia.");
        } finally {
            await preloader;
        }
    }

    async function removePhoto() {
        if (!await generalUtils.confirmDialog({ icon: "warning", text: "Remover a sua fotografia?" })) return;

        clearMessages();

        try {
            const response = await API.user.removePhoto();
            showSuccess(response?.message || "Fotografia removida.");
            await loadProfile();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível remover a fotografia.");
        }
    }

    // ------------------------------------------------------------------
    // Moradas
    // ------------------------------------------------------------------
    function addressCard(address) {
        const main = address.isMain
            ? `<span class="badge bg-primary ms-1">Principal</span>`
            : "";

        return `<div class="border rounded p-3 mb-2 d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <span class="fw-bold">${generalUtils.escapeHtml(address.label || "Morada")}</span>${main}
                <p class="text-muted small mb-0">
                    ${generalUtils.escapeHtml(address.address || "")}<br>
                    ${generalUtils.escapeHtml(address.zipCode || "")} ${generalUtils.escapeHtml(address.cityName || "")}
                </p>
            </div>
            <div class="text-nowrap">
                ${address.isMain ? "" : `<button type="button" class="btn btn-sm btn-outline-primary" data-address-main="${address.id}">
                    <i class="bi bi-star"></i></button>`}
                <button type="button" class="btn btn-sm btn-outline-danger" data-address-delete="${address.id}">
                    <i class="bi bi-trash"></i></button>
            </div>
        </div>`;
    }

    async function loadAddresses() {
        try {
            const response = await API.customer.addresses();
            const addresses = response?.addresses || [];

            $("#customerAddressList").html(
                addresses.length === 0
                    ? `<p class="text-muted mb-0">Ainda não tem moradas registadas.</p>`
                    : addresses.map(addressCard).join("")
            );
        } catch (error) {
            $("#customerAddressList").html(`<p class="text-danger mb-0">Não foi possível carregar as moradas.</p>`);
        }
    }

    /** Cidades suportadas — as mesmas do wizard de criação (fonte única). */
    async function loadCityOptions() {
        try {
            const response = await API.cities.getSupported();
            const cities = response?.cities || [];

            state.cities = cities;

            $("#addressCityInput").html(
                `<option value="">Escolher...</option>` +
                cities.map((city) => `<option value="${city.id}">${generalUtils.escapeHtml(city.name)}</option>`).join("")
            );
        } catch (error) {
            showError("Não foi possível carregar as cidades.");
        }
    }

    function openAddressModal() {
        clearMessages();
        $("#customerAddressForm").trigger("reset");

        generalUtils.bsModalGetOrCreateInstance($("#customerAddressModal")[0]).show();
    }

    async function saveAddress() {
        clearMessages();

        const promise = API.customer.createAddress({
            label: $("#addressLabelInput").val(),
            address: $("#addressStreetInput").val(),
            cityId: $("#addressCityInput").val(),
            zipCode: $("#addressZipInput").val(),
            isMain: $("#addressMainInput").is(":checked") ? 1 : 0
        });

        try {
            const response = await promise;
            showSuccess(response?.message || "Morada adicionada.");
            bootstrap.Modal.getInstance($("#customerAddressModal")[0])?.hide();
            await loadAddresses();
            await loadProfile();
        } catch (error) {
            const errors = error?.responseJSON?.errors;
            showError(errors ? Object.values(errors).join(" ") : (error?.responseJSON?.message || "Não foi possível guardar a morada."));
        }
    }

    // ------------------------------------------------------------------
    // Lembretes (avisos do cliente — C-07 · §3.5)
    // ------------------------------------------------------------------
    function renderAlerts(response) {
        const groups = response?.groups || [];
        const count = Number(response?.count || 0);

        $("#customerAlertsBadge").toggleClass("d-none", count === 0).text(count);

        if (groups.length === 0) {
            $("#customerAlertsList").html(`<p class="text-muted mb-0">Sem lembretes. Tudo em ordem.</p>`);
            return;
        }

        $("#customerAlertsList").html(groups.map((group) => `
            <div class="mb-3">
                <h6 class="fw-bold mb-2">${generalUtils.escapeHtml(group.label)}
                    <span class="badge bg-secondary ms-1">${Number(group.count || 0)}</span></h6>
                ${(group.items || []).length === 0
                    ? `<p class="text-muted small mb-0">Nada por aqui.</p>`
                    : `<ul class="list-unstyled mb-0">${group.items.map((item) => `
                        <li class="border rounded p-2 mb-2">
                            <span class="fw-bold small">${generalUtils.escapeHtml(item.title || "")}</span>
                            <span class="d-block small text-muted">${generalUtils.escapeHtml(item.detail || "")}</span>
                        </li>`).join("")}</ul>`}
                ${group.hasMore ? `<p class="small text-muted mb-0">E mais ${Number(group.hidden || 0)}…</p>` : ""}
            </div>`).join(""));
    }

    async function loadAlerts() {
        const promise = API.customer.alerts();

        try {
            renderAlerts(await promise);
        } catch (error) {
            $("#customerAlertsList").html(`<p class="text-danger mb-0">Não foi possível carregar os lembretes.</p>`);
        }
    }

    async function markAlertsRead() {
        clearMessages();

        try {
            const response = await API.customer.markAlertsRead();
            showSuccess(response?.message || "Avisos marcados como lidos.");
            await loadAlerts();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível marcar como lidos.");
        }
    }

    function bindEvents() {
        $("#customerEditBtn").on("click", openProfileModal);
        $("#customerProfileSaveBtn").on("click", saveProfile);

        $("#customerPhotoFile").on("change", function () {
            openCropModal(this.files?.[0] || null);
        });

        $("#customerCropModal").on("shown.bs.modal", onCropShown);
        $("#customerCropSaveBtn").on("click", uploadPhoto);
        $("#customerPhotoRemoveBtn").on("click", removePhoto);

        $("#customerAlertsReadBtn").on("click", markAlertsRead);

        $("#customerAddressNewBtn").on("click", openAddressModal);
        $("#customerAddressSaveBtn").on("click", saveAddress);

        $(document).on("click", "[data-address-main]", async function () {
            try {
                await API.customer.setPrincipalAddress(Number($(this).data("address-main")));
                showSuccess("Morada principal atualizada.");
                await loadAddresses();
            } catch (error) {
                showError(error?.responseJSON?.message || "Não foi possível alterar a morada principal.");
            }
        });

        $(document).on("click", "[data-address-delete]", async function () {
            if (!await generalUtils.confirmDialog({ icon: "warning", text: "Remover esta morada?" })) return;

            try {
                await API.customer.deleteAddress(Number($(this).data("address-delete")));
                showSuccess("Morada removida.");
                await loadAddresses();
                await loadProfile();
            } catch (error) {
                showError(error?.responseJSON?.message || "Não foi possível remover a morada.");
            }
        });
    }

    $(() => {
        if (!$("#customerArea").length) return;

        bindEvents();
        activateSection();

        loadProfile();
        loadAddresses();
        loadCityOptions();
        loadAlerts();
    });

    return { state, loadProfile, renderProfile, photoUrl, showError, showSuccess, clearMessages, activateSection, openProfileModal, saveProfile, openCropModal, onCropShown, uploadPhoto, removePhoto, loadAddresses, loadCityOptions, openAddressModal, saveAddress, loadAlerts, markAlertsRead };
})();