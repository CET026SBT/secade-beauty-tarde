const boCatalog = (() => {
    const state = { services: [], selectedId: null };

    function renderServices() {
        const $container = $("#catalogServices");
        $container.empty();

        if (state.services.length === 0) {
            $container.html('<div class="text-muted small p-3">Sem serviços.</div>');
            return;
        }

        state.services.forEach(service => {
            const active = Number(service.id) === Number(state.selectedId) ? "active" : "";
            const thumb = service.featuredUrl
                ? `<img src="${BASE_URL ?? ''}/${service.featuredUrl}" class="rounded object-fit-cover me-2" style="width:36px;height:36px" alt="">`
                : `<span class="d-inline-flex align-items-center justify-content-center rounded bg-light text-muted me-2" style="width:36px;height:36px"><i class="bi bi-image"></i></span>`;

            $container.append(`<button type="button" class="list-group-item list-group-item-action d-flex align-items-center ${active}" data-service-id="${service.id}">
                ${thumb}
                <span class="flex-grow-1 text-start">
                    <span class="d-block fw-bold small">${generalUtils.escapeHtml(service.name)}</span>
                    <span class="d-block text-muted small">${generalUtils.escapeHtml(service.categoryName || "")}</span>
                </span>
                <span class="badge bg-light text-dark">${(service.photos || []).length}</span>
            </button>`);
        });
    }

    function renderPhotos() {
        const $container = $("#catalogPhotos");
        const service = state.services.find(s => Number(s.id) === Number(state.selectedId));
        $("#catalogPhotosHeader").text(service ? `Fotografias — ${service.name}` : "Fotografias");

        if (!service) {
            $container.html('<p class="text-muted small mb-0">Escolha um serviço para gerir as fotografias.</p>');
            return;
        }

        const cards = (service.photos || []).map(photo => `
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <img src="${BASE_URL ?? ''}/${photo.url}" class="card-img-top object-fit-cover" style="height:120px" alt="">
                    <div class="card-body p-2 text-center">
                        ${photo.featured
                            ? '<span class="badge bg-success mb-2">Principal</span>'
                            : `<button type="button" class="btn btn-sm btn-outline-primary w-100 mb-2" data-featured="${photo.id}">Definir principal</button>`}
                        <button type="button" class="btn btn-sm btn-outline-danger w-100" data-remove="${photo.id}">Remover</button>
                    </div>
                </div>
            </div>`).join("");

        $container.html(`
            <div class="mb-3">
                <label class="form-label small text-muted mb-1" for="catalogPhotoFile">Adicionar fotografia (JPG/PNG/WEBP, máx. 2 MB)</label>
                <div class="input-group input-group-sm">
                    <input type="file" class="form-control" id="catalogPhotoFile" accept="image/jpeg,image/png,image/webp">
                    <button class="btn btn-primary" type="button" id="catalogUploadBtn">Carregar</button>
                </div>
            </div>
            <div class="row g-3">
                ${cards || '<div class="col-12"><p class="text-muted small mb-0">Sem fotografias — o card mostra o placeholder da categoria.</p></div>'}
            </div>
        `);
    }

    async function load() {
        try {
            const response = await API.admin.catalog.list();
            state.services = response?.services || [];

            if (state.selectedId === null && state.services.length > 0) {
                state.selectedId = state.services[0].id;
            }

            renderServices();
            renderPhotos();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar o catálogo.");
        }
    }

    function showError(message) {
        $("#catalogResult").addClass("d-none");
        $("#catalogError").removeClass("d-none").text(message);
    }

    function showSuccess(message) {
        $("#catalogError").addClass("d-none");
        $("#catalogResult").removeClass("d-none").text(message);
        setTimeout(() => $("#catalogResult").addClass("d-none"), 6000);
    }

    async function upload() {
        const file = $("#catalogPhotoFile")[0]?.files?.[0];
        if (!file) { showError("Escolha um ficheiro."); return; }

        const formData = new FormData();
        formData.append("serviceId", state.selectedId);
        formData.append("photo", file);

        try {
            const response = await API.admin.catalog.uploadPhoto(formData);
            showSuccess(response?.message || "Fotografia carregada.");
            await load();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar a fotografia.");
        }
    }

    async function setFeatured(photoId) {
        try {
            const response = await API.admin.catalog.setFeatured(state.selectedId, photoId);
            showSuccess(response?.message || "Fotografia principal atualizada.");
            await load();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível definir a principal.");
        }
    }

    async function removePhoto(photoId) {
        if (!(await generalUtils.confirmDialog({ title: "Remover esta fotografia?", icon: "warning" }))) return;

        try {
            const response = await API.admin.catalog.deletePhoto(state.selectedId, photoId);
            showSuccess(response?.message || "Fotografia removida.");
            await load();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível remover a fotografia.");
        }
    }

    function bindEvents() {
        $(document).on("click", "[data-service-id]", function () {
            state.selectedId = Number($(this).data("service-id"));
            renderServices();
            renderPhotos();
        });

        $(document).on("click", "#catalogUploadBtn", upload);
        $(document).on("click", "[data-featured]", function () { setFeatured(Number($(this).data("featured"))); });
        $(document).on("click", "[data-remove]", function () { removePhoto(Number($(this).data("remove"))); });
    }

    $(() => {
        if (!$("#catalogServices").length) return;
        bindEvents();
        load();
    });

    return { state, load, renderServices, renderPhotos, showError, showSuccess };
})();
