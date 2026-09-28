<?php
require_once __DIR__ . "/../../app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Backoffice do GESTOR
Session::requireProfile(["gestor"]);

register_script("components/fiscal", "backoffice");

$boPageTitle = "Calendário Fiscal";
$boCurrentPage = "fiscal";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Calendário Fiscal</h2>
            <p class="text-muted small mb-0">
                IVA, IRC, Segurança Social e Seguros, com alertas progressivos
                <strong>30 / 15 / 7 / 3 / 1 dia</strong> e alertas <strong>diários em atraso</strong>.
            </p>
        </div>
        <button type="button" class="btn btn-primary" id="toggleObligationFormBtn">
            <i class="bi bi-plus me-1"></i> Nova obrigação
        </button>
    </div>

    <div class="row g-3 mb-4" id="fiscalSummary">
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body">
            <span class="text-muted small d-block">Total</span><span class="fs-3 fw-bold" id="sumTotal">-</span>
        </div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body">
            <span class="text-muted small d-block">Pendentes</span><span class="fs-3 fw-bold text-warning" id="sumPending">-</span>
        </div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body">
            <span class="text-muted small d-block">Vencem em 7 dias</span><span class="fs-3 fw-bold text-info" id="sumDueSoon">-</span>
        </div></div></div>
        <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body">
            <span class="text-muted small d-block">Em atraso</span><span class="fs-3 fw-bold text-danger" id="sumOverdue">-</span>
        </div></div></div>
    </div>

    <div class="alert alert-danger d-none" id="fiscalError" role="alert"></div>

    <div class="card border-0 shadow-sm mb-4 d-none" id="fiscalAlertPanel">
        <div class="card-header bg-warning bg-opacity-25 d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="bi bi-bell me-1"></i>Alertas fiscais</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="markAlertsReadBtn">
                Marcar como visualizados
            </button>
        </div>
        <div class="card-body" id="fiscalAlertsList"></div>
    </div>

    <div class="card border-0 shadow-sm mb-4 d-none" id="obligationFormCard">
        <div class="card-header bg-white fw-bold">Nova obrigação fiscal</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1" for="obType">Tipo</label>
                    <select id="obType" class="form-select form-select-sm">
                        <option value="iva">IVA</option>
                        <option value="irc">IRC</option>
                        <option value="seguranca_social">Segurança Social</option>
                        <option value="seguros">Seguros</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1" for="obName">Designação</label>
                    <input type="text" id="obName" class="form-control form-control-sm" placeholder="Ex.: IVA 3.º trimestre">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1" for="obPeriodicity">Periodicidade</label>
                    <select id="obPeriodicity" class="form-select form-select-sm">
                        <option value="mensal">Mensal</option>
                        <option value="trimestral">Trimestral</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1" for="obValue">Valor estimado (€)</label>
                    <input type="number" step="0.01" min="0" id="obValue" class="form-control form-control-sm" placeholder="0.00">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1" for="obDueDate">Prazo</label>
                    <input type="date" id="obDueDate" class="form-control form-control-sm">
                </div>
                <div class="col-12">
                    <button type="button" class="btn btn-sm btn-primary" id="saveObligationBtn">
                        <i class="bi bi-check2 me-1"></i> Guardar obrigação
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="cancelObligationBtn">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-bold">Obrigações fiscais</span>
            <div class="d-flex gap-2 bo-filters">
                <select id="fiscalTypeFilter" class="form-select form-select-sm">
                    <option value="">Todos os tipos</option>
                    <option value="iva">IVA</option>
                    <option value="irc">IRC</option>
                    <option value="seguranca_social">Segurança Social</option>
                    <option value="seguros">Seguros</option>
                </select>
                <select id="fiscalStatusFilter" class="form-select form-select-sm">
                    <option value="">Todos os estados</option>
                    <option value="pendente">Pendente</option>
                    <option value="pago">Pago</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0" id="fiscalTableContainer" preloader-defer>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bo-table">
                    <thead>
                        <tr>
                            <th>Tipo</th><th>Designação</th><th>Periodicidade</th>
                            <th>Prazo</th><th class="text-center">Dias</th>
                            <th class="text-end">Valor</th><th class="text-center">Estado</th>
                            <th class="text-center">Alerta</th><th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="fiscalTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>