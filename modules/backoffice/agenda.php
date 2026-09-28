<?php
require_once __DIR__ . "/../../app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Fase 6.5 — agenda do FUNCIONÁRIO (RF-78 · RN-33): a aceitação continua em
// listagem e a agenda mostra só os agendamentos de ROTAS CONFIRMADAS.
Session::requireProfile(["funcionario"]);

register_script("components/agenda", "backoffice");

$boPageTitle = "A minha agenda";
$boCurrentPage = "agenda";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-calendar3 text-primary me-2"></i>A minha agenda</h2>
            <p class="text-muted small mb-0">
                Agendamentos de <strong>rotas confirmadas</strong> (RN-33). O que ainda está por aceitar
                ou em rota por decidir aparece na listagem de
                <a href="<?= BASE_URL ?>/gestao/servicos">Serviços</a>.
            </p>
        </div>
        <div class="d-flex gap-2 align-items-end">
            <div>
                <label class="form-label small text-muted mb-1" for="agendaMonth">Mês</label>
                <input type="month" id="agendaMonth" class="form-control form-control-sm">
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="agendaTodayBtn">
                <i class="bi bi-calendar-day me-1"></i> Mês atual
            </button>
        </div>
    </div>

    <div class="alert alert-danger d-none" id="agendaError" role="alert"></div>

    <div class="row g-3 mb-4" id="agendaKpis" preloader-defer></div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-bold" id="agendaMonthLabel">A carregar...</span>
                    <span class="text-muted small">Dias com marcação ficam assinalados</span>
                </div>
                <div class="card-body" preloader-defer>
                    <div class="bo-calendar" id="agendaCalendar"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold" id="agendaDayLabel">Escolha um dia</div>
                <div class="card-body" id="agendaDayServices" preloader-defer>
                    <p class="text-muted small mb-0">
                        Clique num dia do calendário para ver os serviços marcados.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>