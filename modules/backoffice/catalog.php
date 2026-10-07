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
            Carregue fotografias por serviço, defina a imagem principal do card e remova as que já não usa.
            Sem fotografia, o card mostra o placeholder da categoria.
        </p>
    </div>

    <div class="alert alert-danger d-none" id="catalogError" role="alert"></div>
    <div class="alert alert-success d-none" id="catalogResult" role="alert"></div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Serviços</div>
                <div class="list-group list-group-flush" id="catalogServices" style="max-height: 70vh; overflow-y: auto;"></div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold" id="catalogPhotosHeader">Fotografias</div>
                <div class="card-body" id="catalogPhotos">
                    <p class="text-muted small mb-0">Escolha um serviço para gerir as fotografias.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>
