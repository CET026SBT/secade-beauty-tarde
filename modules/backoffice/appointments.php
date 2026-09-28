<?php
require_once __DIR__ . "/../../app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Backoffice: acesso restrito a gestores
Session::requireProfile(["gestor"]);

register_script("components/appointments", "backoffice");

$boPageTitle = "Gestão de Agendamentos";
$boCurrentPage = "appointments";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-calendar-check text-primary me-2"></i>Gestão de Agendamentos</h2>
            <p class="text-muted small mb-0">Consultar, filtrar e cancelar agendamentos de loja e ambulatório.</p>
        </div>
        <a href="<?= BASE_URL ?>/gestao/rotas" class="btn btn-outline-primary">
            <i class="bi bi-signpost-split me-1"></i> Ir para Rotas
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 bo-filters align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1" for="filterDate">Data</label>
                    <input type="date" id="filterDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1" for="filterLocal">Local</label>
                    <select id="filterLocal" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="loja_fisica">Loja Física</option>
                        <option value="carrinha_ambulante">Carrinha Ambulante</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1" for="filterStatus">Estado</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary flex-fill" id="applyFiltersBtn">
                            <i class="bi bi-funnel me-1"></i> Filtrar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary flex-fill" id="clearFiltersBtn">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Limpar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-bold">Agendamentos</span>
            <span class="text-muted small" id="appointmentsCount">A carregar...</span>
        </div>
        <div class="card-body p-0" id="appointmentsTableContainer" preloader-defer>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bo-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Data / Hora</th>
                            <th>Local</th>
                            <th>Cidade</th>
                            <th>Estado</th>
                            <th class="text-end">Valor</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="appointmentsTableBody"></tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="text-muted small" id="paginationInfo"></span>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="appointmentsPagination"></ul>
            </nav>
        </div>
    </div>
</main>

<!-- Modal: detalhes do agendamento -->
<div class="modal fade" id="appointmentDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do agendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="appointmentDetailsBody" preloader-defer></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-danger" id="modalCancelAppointmentBtn">
                    <i class="bi bi-x-circle me-1"></i> Cancelar agendamento
                </button>
            </div>
        </div>
    </div>
</div>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>