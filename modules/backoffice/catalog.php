<?php
require_once APP_PATH . "/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Backoffice do GESTOR
Session::requireProfile(["gestor"]);

register_script("components/catalog", "backoffice");

$boPageTitle = "Catálogo de Serviços";
$boCurrentPage = "catalog";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="p-4">
    <div class="mb-4">
        <h2 class="mb-1"><i class="bi bi-images text-primary me-2"></i>Catálogo de Serviços</h2>
        <p class="text-muted small mb-0">
            Fotografias do catálogo. A imagem <strong>principal</strong> é a que aparece no card;
            as restantes formam a galeria dos detalhes. JPG, PNG ou WEBP, até 2 MB.
        </p>
    </div>

    <div class="alert alert-danger d-none" id="catalogError" role="alert"></div>
    <div class="alert alert-success d-none" id="catalogSuccess" role="alert"></div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Serviços</span>
                    <span class="badge bg-light text-dark" id="catalogCount">0</span>
                </div>
                <div class="card-body p-0" id="catalogServicesList" preloader-defer></div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold" id="catalogPhotosTitle">Fotografias</div>
                <div class="card-body" id="catalogPhotosPanel">
                    <p class="text-muted small mb-0">Escolha um serviço para gerir as fotografias.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>