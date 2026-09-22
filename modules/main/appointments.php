<?php
require_once __DIR__ . "/../../app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Require login to access this page
Session::requireLogin();

register_script("components/appointments", "main");

$user = Session::user();
$currentPage = "appointments";

include_once ROOT_PATH . "/modules/main/includes/header.php";
include_once ROOT_PATH . "/modules/main/includes/navbar.php";
?>
<div class="container-fluid bg-light page-header py-5 mb-5">
    <div class="container text-center py-4">
        <h1 class="display-4 animated slideInDown mb-3">Meus Agendamentos</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Agendamentos</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Histórico de Agendamentos</h5>
                    <a href="<?= BASE_URL ?>/agendar" class="btn btn-sm btn-light">
                        <i class="bi bi-plus-lg me-1"></i> Nova marcação
                    </a>
                </div>
                <div class="card-body" id="appointmentsPage" preloader-defer>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <button type="button" class="btn btn-sm btn-outline-primary appt-filter active" data-status="">Todos</button>
                        <button type="button" class="btn btn-sm btn-outline-primary appt-filter" data-status="pendente_aceitacao_funcionarios">Pendentes</button>
                        <button type="button" class="btn btn-sm btn-outline-primary appt-filter" data-status="confirmado">Confirmados</button>
                        <button type="button" class="btn btn-sm btn-outline-primary appt-filter" data-status="cancelado">Cancelados</button>
                    </div>

                    <div class="alert alert-danger d-none" id="appointmentsError" role="alert"></div>
                    <div id="appointmentsList"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once ROOT_PATH . "/modules/main/includes/footer.php"; ?>
