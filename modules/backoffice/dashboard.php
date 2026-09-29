<?php
require_once __DIR__ . "/../../app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// D-14 (§3.14 · §24.7): `/gestao` é o PAINEL do gestor. O funcionário não tem
// painel — é encaminhado para a sua agenda.
if (Session::isEmployee()) {
    header("Location: " . BASE_URL . "/gestao/agenda");
    exit;
}

// Backoffice: acesso restrito a gestores
Session::requireProfile(["gestor"]);

register_script("components/dashboard", "backoffice");

$boPageTitle = "Painel";
$boCurrentPage = "dashboard";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-speedometer2 text-primary me-2"></i>Painel</h2>
            <p class="text-muted small mb-0">
                Indicadores do dia e dos próximos 7 dias, com os gráficos por baixo.
                Nenhum destes números decide ou bloqueia nada — a decisão é sempre <strong>manual</strong>.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/gestao/agendamentos" class="btn btn-outline-primary">
                <i class="bi bi-calendar-check me-1"></i> Agendamentos
            </a>
            <a href="<?= BASE_URL ?>/gestao/rotas" class="btn btn-outline-primary">
                <i class="bi bi-signpost-split me-1"></i> Rotas
            </a>
        </div>
    </div>

    <div class="alert alert-danger d-none" id="dashboardError" role="alert"></div>

    <div class="row g-3 mb-4" id="dashboardKpis" preloader-defer></div>

    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Agendamentos nos próximos 7 dias</div>
                <div class="card-body" preloader-defer>
                    <canvas id="chartBookingsPerDay" height="150"></canvas>
                    <p class="text-muted small mb-0 d-none" id="chartBookingsPerDayEmpty">
                        Sem agendamentos no período.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Agendamentos por estado</div>
                <div class="card-body" preloader-defer>
                    <canvas id="chartBookingsByState" height="150"></canvas>
                    <p class="text-muted small mb-0 d-none" id="chartBookingsByStateEmpty">
                        Ainda não existem agendamentos.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Serviços por estado de aceitação</div>
                <div class="card-body" preloader-defer>
                    <canvas id="chartServicesByAcceptance" height="150"></canvas>
                    <p class="text-muted small mb-0 d-none" id="chartServicesByAcceptanceEmpty">
                        Sem serviços ativos para aceitar.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold">Obrigações fiscais pendentes (por tipo)</div>
                <div class="card-body" preloader-defer>
                    <canvas id="chartFiscalByType" height="150"></canvas>
                    <p class="text-muted small mb-0 d-none" id="chartFiscalByTypeEmpty">
                        Sem obrigações fiscais pendentes.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold">Contabilidade</div>
        <div class="card-body">
            <div class="alert alert-info border small mb-0" id="accountingState">
                A carregar...
            </div>
        </div>
    </div>
</main>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>