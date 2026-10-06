const boCatalog = (() => {
    const state = { services: [], selectedId: null, photos: [] };

    const PLACEHOLDER_BASE = `${BASE_URL ?? ''}/modules/common/img/service-images/skeletons/`;
    const KNOWN_CATEGORIES = ['cabeleireiro', 'barbearia', 'estetica'];

    function imageUrl(service) {
        if (service.photoUrl) {
            return `${BASE_URL ?? ''}/${String(service.photoUrl).replace(/^\//, '')}`;
        }

        const slug = generalUtils.slugify(service.categoryName || '').toLowerCase();
        const file = KNOWN_CATEGORIES.includes(slug) ? `servico-${slug}.svg` : 'servico-generico.svg';
        return `${PLACEHOLDER_BASE}${file}`;
    }

    function showError(message) { $("#catalogError").removeClass("d-none").text(message); }
    function showSuccess(message) {
        $("#catalogSuccess").removeClass("d-none").text(message);
        setTimeout(() => $("#catalogSuccess").addClass("d-none"), 6000);
    }
    function clearMessages() { $("#catalogError, #catalogSuccess").addClass("d-none"); }

    function serviceRow(service) {
        const isActive = Number(state.selectedId) === Number(service.id);
        return `<button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 ${isActive ? "active" : ""}"
                        data-service-select="${service.id}">
            <img src="${imageUrl(service)}" alt="" class="bo-catalog-thumb rounded">
            <span class="flex-grow-1 text-start">
                <span class="d-block fw-bold small">${generalUtils.escapeHtml(service.name)}</span>
                <span class="d-block text-muted small">${generalUtils.escapeHtml(service.categoryName || '')}</span>
            </span>
            ${service.photoUrl ? '<i class="bi bi-image text-success" title="Tem fotografia"></i>' : '<i class="bi bi-image text-muted" title="Sem fotografia"></i>'}
        </button>`;
    }

    function renderServices() {
        const $list = $("#catalogServicesList");
        $("#catalogCount").text(state.services.length);

        if (state.services.length === 0) {
            $list.html(`<div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>Sem serviços no catálogo.
            </div>`);
            return;
        }

        $list.html(`<div class="list-group list-group-flush">${state.services.map(serviceRow).join("")}</div>`);
    }

    async function loadServices() {
        const promise = API.admin.catalog.list();
        const preloader = $("#catalogServicesList").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            state.services = response?.services || [];
            renderServices();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar o catálogo.");
        } finally {
            await preloader;
        }
    }

    function photoCard(photo) {
        const featured = Boolean(photo.featured);
        return `<div class="col-6 mb-3">
            <div class="card h-100 border ${featured ? "border-success" : ""}">
                <img src="${BASE_URL ?? ''}/${String(photo.url).replace(/^\//, '')}" class="card-img-top bo-catalog-photo" alt="">
                <div class="card-body p-2 text-center">
                    ${featured
                        ? '<span class="badge bg-success mb-2">Principal</span>'
                        : `<button type="button" class="btn btn-sm btn-outline-success w-100 mb-2" data-photo-featured="${photo.id}">
                               <i class="bi bi-star me-1"></i>Definir principal</button>`}
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" data-photo-remove="${photo.id}">
                        <i class="bi bi-trash me-1"></i>Remover</button>
                </div>
            </div>
        </div>`;
    }

    function renderPhotos() {
        const service = state.services.find(item => Number(item.id) === Number(state.selectedId));
        const title = service ? `Fotografias — ${generalUtils.escapeHtml(service.name)}` : "Fotografias";

        $("#catalogPhotosTitle").text(title);

        const gallery = state.photos.length === 0
            ? `<p class="text-muted small">Ainda não há fotografias. O card usa o marcador da categoria.</p>`
            : `<div class="row">${state.photos.map(photoCard).join("")}</div>`;

        $("#catalogPhotosPanel").html(`
            ${gallery}
            <hr>
            <form id="catalogUploadForm" class="row g-2 align-items-end">
                <div class="col-12">
                    <label class="form-label small text-muted mb-1" for="catalogPhotoFile">Nova fotografia</label>
                    <input type="file" id="catalogPhotoFile" name="photo" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm" required>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" id="catalogPhotoFeatured" class="form-check-input" checked>
                        <label class="form-check-label small" for="catalogPhotoFeatured">Definir como principal (imagem do card)</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-sm btn-primary extended-border w-100">
                        <i class="bi bi-upload me-1"></i>Carregar
                    </button>
                </div>
            </form>
        `);
    }

    async function selectService(serviceId) {
        state.selectedId = Number(serviceId);
        renderServices();
        await loadPhotos();
    }

    async function loadPhotos() {
        if (!state.selectedId) return;

        const promise = API.admin.catalog.photos(state.selectedId);
        const preloader = $("#catalogPhotosPanel").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            state.photos = response?.photos || [];
            renderPhotos();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível carregar as fotografias.");
        } finally {
            await preloader;
        }
    }

    async function uploadPhoto(formElement) {
        clearMessages();

        const fileInput = formElement.querySelector("#catalogPhotoFile");
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            showError("Escolha uma fotografia para carregar.");
            return;
        }

        const formData = new FormData();
        formData.append("serviceId", state.selectedId);
        formData.append("photo", fileInput.files[0]);
        formData.append("featured", formElement.querySelector("#catalogPhotoFeatured")?.checked ? "1" : "0");

        const promise = API.admin.catalog.uploadPhoto(formData);
        const preloader = $("#catalogPhotosPanel").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            showSuccess(response?.message || "Fotografia carregada.");
            await loadServices();
            renderServices();
            await loadPhotos();
        } catch (error) {
            const errors = error?.responseJSON?.errors;
            showError(errors ? Object.values(errors).join(" ") : (error?.responseJSON?.message || "Não foi possível carregar a fotografia."));
        } finally {
            await preloader;
        }
    }

    async function setFeatured(photoId) {
        clearMessages();

        const promise = API.admin.catalog.setPhotoFeatured(Number(photoId));
        const preloader = $("#catalogPhotosPanel").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            showSuccess(response?.message || "Fotografia principal atualizada.");
            await loadServices();
            renderServices();
            await loadPhotos();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível definir a principal.");
        } finally {
            await preloader;
        }
    }

    async function removePhoto(photoId) {
        const confirmed = await generalUtils.confirmDialog({ icon: "warning", text: "Remover esta fotografia?" });
        if (!confirmed) return;

        clearMessages();

        const promise = API.admin.catalog.removePhoto(Number(photoId));
        const preloader = $("#catalogPhotosPanel").preloader(".jq-overlay-process", promise);

        try {
            const response = await promise;
            showSuccess(response?.message || "Fotografia removida.");
            await loadServices();
            renderServices();
            await loadPhotos();
        } catch (error) {
            showError(error?.responseJSON?.message || "Não foi possível remover a fotografia.");
        } finally {
            await preloader;
        }
    }

    function bindEvents() {
        $(document).on("click", "[data-service-select]", function () {
            selectService($(this).data("service-select"));
        });

        $(document).on("submit", "#catalogUploadForm", function (event) {
            event.preventDefault();
            uploadPhoto(this);
        });

        $(document).on("click", "[data-photo-featured]", function () {
            setFeatured($(this).data("photo-featured"));
        });

        $(document).on("click", "[data-photo-remove]", function () {
            removePhoto($(this).data("photo-remove"));
        });
    }

    $(() => {
        if (!$("#catalogServicesList").length) return;

        bindEvents();
        loadServices();
    });

    return { state, loadServices, loadPhotos, renderServices, imageUrl, showError, showSuccess, clearMessages };
})();