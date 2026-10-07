<?php
require_once APP_PATH . "/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// F4 · C-08/G-03: o GESTOR aloca, o FUNCIONÁRIO (RV) aceita. O funcionário
// EFETIVO não usa este ecrã — é encaminhado para a sua Agenda.
Session::requireProfile(["gestor", "funcionario"]);

if (Session::isEmployee() && Session::employeeContractType() === "efetivo_contratado") {
    header("Location: " . BASE_URL . "/gestao/agenda");
    exit;
}

register_script("components/services", "backoffice");

$isManager = Session::isManager();

$boPageTitle = "Serviços de Ambulatório";
$boCurrentPage = "services";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="p-4">
    <script>window.BO_IS_MANAGER = <?= $isManager ? "true" : "false" ?>;</script>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-truck text-primary me-2"></i>Serviços de Ambulatório</h2>
            <p class="text-muted small mb-0">
                <?php if ($isManager): ?>
                    Alocação <strong>individual</strong> por serviço — escolha o funcionário efetivo que o executa.
                    Ao alocar o último serviço, o agendamento fica <strong>totalmente alocado</strong>; o aviso ao
                    cliente acontece na confirmação da rota.
                <?php else: ?>
                    Aceitação <strong>individual</strong> por serviço. O valor que recebe segue a sua percentagem
                    configurada; o aviso ao cliente acontece na confirmação da rota.
                <?php endif; ?>
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
                    <span class="fw-bold">
                        <i class="bi bi-hourglass-split me-1 text-warning"></i><?= $isManager ? "Por alocar" : "Por aceitar" ?>
                    </span>
                    <span class="text-muted small" id="pendingCount">A carregar...</span>
                </div>
                <div class="card-body">
                    <div class="bo-filters sticky-filters mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-6 col-md-5">
                                <label class="form-label small text-muted mb-1" for="pendingDate">Data</label>
                                <input type="date" id="pendingDate" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-5">
                                <label class="form-label small text-muted mb-1" for="pendingCity">Cidade</label>
                                <select id="pendingCity" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-2 text-md-end">
                                <button type="button" class="btn btn-sm btn-outline-primary w-100" id="pendingMoreFiltersBtn"
                                        data-bs-toggle="collapse" data-bs-target="#pendingMoreFilters" title="Mais filtros">
                                    <i class="bi bi-funnel"></i>
                                </button>
                            </div>
                        </div>
                        <div class="collapse mt-2" id="pendingMoreFilters">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1" for="pendingCategory">Categoria</label>
                                    <select id="pendingCategory" class="form-select form-select-sm">
                                        <option value="">Todas</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1" for="pendingBooking">Agendamento</label>
                                    <input type="number" min="1" id="pendingBooking" class="form-control form-control-sm" placeholder="#">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="pendingList" preloader-defer></div>
                </div>
            </div>
        </div>

        <div class="col-lg-12 col-xl-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-bold">
                        <i class="bi bi-check2-circle me-1 text-success"></i><?= $isManager ? "Alocados" : "Aceites por mim" ?>
                    </span>
                    <span class="text-muted small" id="acceptedTotals">-</span>
                </div>
                <div class="card-body">
                    <div class="bo-filters sticky-filters mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-6 col-md-5">
                                <label class="form-label small text-muted mb-1" for="acceptedDate">Data</label>
                                <input type="date" id="acceptedDate" class="form-control form-control-sm">
                            </div>
                            <div class="col-6 col-md-5">
                                <label class="form-label small text-muted mb-1" for="acceptedCity">Cidade</label>
                                <select id="acceptedCity" class="form-select form-select-sm">
                                    <option value="">Todas</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-2 text-md-end">
                                <button type="button" class="btn btn-sm btn-outline-primary w-100" id="acceptedMoreFiltersBtn"
                                        data-bs-toggle="collapse" data-bs-target="#acceptedMoreFilters" title="Mais filtros">
                                    <i class="bi bi-funnel"></i>
                                </button>
                            </div>
                        </div>
                        <div class="collapse mt-2" id="acceptedMoreFilters">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1" for="acceptedEmployee">Funcionário</label>
                                    <select id="acceptedEmployee" class="form-select form-select-sm">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1" for="acceptedBooking">Agendamento</label>
                                    <input type="number" min="1" id="acceptedBooking" class="form-control form-control-sm" placeholder="#">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="acceptedList" preloader-defer></div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>
