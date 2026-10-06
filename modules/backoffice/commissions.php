<?php
require_once APP_PATH . "/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Fase 6.4 (RF-84): comissões por funcionário. A abrangência sai da sessão —
// o gestor vê todos, o funcionário só as suas.
Session::requireProfile(["gestor", "funcionario"]);

register_script("components/commissions", "backoffice");

$boPageTitle = "Comissões";
$boCurrentPage = "commissions";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-cash-stack text-primary me-2"></i><?= Session::isEmployee() ? "As minhas comissões" : "Comissões" ?></h2>
            <p class="text-muted small mb-0">
                Folha de <strong>serviços prestados</strong> — o que conta é o serviço ter sido executado,
                não apenas alocado. Os valores são o <strong>snapshot da aceitação</strong>: a percentagem
                pode mudar, o histórico não.
            </p>
        </div>
        <div class="d-flex gap-2 align-items-end">
            <div>
                <label class="form-label small text-muted mb-1" for="commissionMonth">Mês</label>
                <input type="month" id="commissionMonth" class="form-control form-control-sm">
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="commissionReloadBtn">
                <i class="bi bi-arrow-clockwise me-1"></i> Atualizar
            </button>
        </div>
    </div>

    <div class="alert alert-danger d-none" id="commissionsError" role="alert"></div>

    <div class="row g-3 mb-4" id="commissionKpis" preloader-defer></div>

    <!-- D-07.1/D-07.2: o fixo (salário base) nunca se mistura com o variável (comissões). -->
    <div class="card border-0 shadow-sm mb-4 d-none" id="commissionSalaryCard">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="d-block text-muted small">Salário base (fixo)</span>
                <span class="fs-4 fw-bold" id="commissionSalaryValue">—</span>
            </div>
            <p class="small text-muted mb-0 flex-grow-1">
                Valor fixo do contrato, apresentado separado: <strong>não</strong> é comissão e
                <strong>não</strong> entra no total variável.
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 d-none" id="commissionEmployeesCard">
        <div class="card-header bg-white fw-bold">Totais por funcionário</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bo-table">
                    <thead>
                        <tr>
                            <th>Funcionário</th>
                            <th class="text-center">Serviços prestados</th>
                            <th class="text-end">Valor dos serviços</th>
                            <th class="text-center">% média</th>
                            <th class="text-end">Comissão (funcionário)</th>
                            <th class="text-end">Parte da Empresa</th>
                        </tr>
                    </thead>
                    <tbody id="commissionEmployeesBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-bold">Serviços prestados no mês</span>
            <span class="text-muted small" id="commissionsCount">A carregar...</span>
        </div>
        <div class="card-body p-0" id="commissionsTableContainer" preloader-defer>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bo-table">
                    <thead>
                        <tr>
                            <th>Agendamento</th>
                            <th>Serviço</th>
                            <th>Funcionário</th>
                            <th>Data</th>
                            <th class="text-end">Valor do serviço</th>
                            <th class="text-center">%</th>
                            <th class="text-end">Comissão</th>
                        </tr>
                    </thead>
                    <tbody id="commissionsTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>