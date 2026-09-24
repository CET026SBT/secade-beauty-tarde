<?php
require_once __DIR__ . "/../../app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Backoffice de FUNCIONÁRIO (gestores também podem consultar)
Session::requireLogin();
if (!Session::isEmployee() && !Session::isManager()) {
    header("Location: " . BASE_URL . "/");
    exit;
}

register_script("components/services", "backoffice");

$boPageTitle = "Serviços de Ambulatório";
$boCurrentPage = "services";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-list-check text-primary me-2"></i>Serviços de Ambulatório</h2>
            <p class="text-muted small mb-0">
                Aceitação <strong>individual</strong> por serviço. As categorias são apenas filtros visuais.
                Ao aceitar o último serviço, o agendamento fica <strong>Totalmente Aceite</strong> e bloqueia a janela temporal.
            </p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-light text-dark align-self-center" id="greenReceiptInfo">-</span>
        </div>
    </div>

    <div class="alert alert-danger d-none" id="servicesError" role="alert"></div>
    <div class="alert alert-success d-none" id="servicesSuccess" role="alert"></div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-bold"><i class="bi bi-hourglass-split me-1 text-warning"></i>Por aceitar</span>
                    <span class="text-muted small" id="pendingCount">A carregar...</span>
                </div>
                <div class="card-body">
                    <div class="row g-3 bo-filters align-items-end mb-3">
                        <div class="col-md-5">
                            <label class="form-label small text-muted mb-1" for="pendingDate">Data</label>
                            <input type="date" id="pendingDate" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small text-muted mb-1" for="pendingCategory">Categoria (visual)</label>
                            <select id="pendingCategory" class="form-select form-select-sm">
                                <option value="">Todas</option>
                            </select>
                        </div>
                    </div>

                    <div id="pendingList" preloader-defer></div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-bold"><i class="bi bi-check2-circle me-1 text-success"></i>Aceites por mim</span>
                    <span class="text-muted small" id="acceptedTotals">-</span>
                </div>
                <div class="card-body">
                    <div id="acceptedList" preloader-defer></div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>