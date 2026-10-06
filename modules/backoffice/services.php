<?php
require_once APP_PATH . "/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// F4 (C-08): gestor aloca (escolhe o funcionário) · funcionário RV aceita os seus.
Session::requireProfile(["funcionario", "gestor"]);

register_script("components/services", "backoffice");

$isManager = Session::isManager();

$boPageTitle = "Alocação de Serviços";
$boCurrentPage = "services";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="p-4">
    <script>window.BO_IS_MANAGER = <?= $isManager ? "true" : "false" ?>;</script>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-list-check text-primary me-2"></i>Serviços de Ambulatório</h2>
            <p class="text-muted small mb-0">
                <?php if ($isManager): ?>
                    <strong>Alocação</strong> individual por serviço: escolha o funcionário e aloque.
                <?php else: ?>
                    <strong>Aceitação</strong> individual por serviço. As categorias são apenas filtros visuais.
                <?php endif; ?>
                As categorias são apenas filtros visuais. Ao alocar o último serviço, o agendamento fica
                <strong>Totalmente Alocado</strong> e fica pronto para a rota.
            </p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-light text-dark align-self-center" id="greenReceiptInfo">-</span>
        </div>
    </div>

    <div class="alert alert-danger d-none" id="servicesError" role="alert"></div>
    <div class="alert alert-success d-none" id="servicesSuccess" role="alert"></div>

    <div class="row g-4">
        <div class="col-lg-12 col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-bold"><i class="bi bi-hourglass-split me-1 text-warning"></i>Por alocar</span>
                    <span class="text-muted small" id="pendingCount">A carregar...</span>
                </div>
                <div class="card-body">
                    <div class="row g-3 bo-filters sticky-top align-items-end mb-3 pt-2 bg-white">
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

        <div class="col-lg-12 col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-bold"><i class="bi bi-check2-circle me-1 text-success"></i><?= $isManager ? "Alocados" : "Alocados por mim" ?></span>
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