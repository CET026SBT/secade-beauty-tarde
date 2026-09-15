<?php
require_once __DIR__ . "/../../app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Require login to access this page
Session::requireLogin();

$user = Session::user();
$currentPage = "appointments";
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <?php include __DIR__ . "/includes/header.php"; ?>
    <title>Meus Agendamentos - <?= SITE_NAME ?></title>
</head>
<body>
    <?php include __DIR__ . "/includes/navbar.php"; ?>

    <!-- Page Header -->
    <div class="container-fluid page-header py-5 mb-5">
        <div class="container text-center py-5">
            <h1 class="display-4 text-white animated slideInDown mb-3">Meus Agendamentos</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb justify-content-center mb-0">
                    <li class="breadcrumb-item"><a class="text-white" href="<?= BASE_URL ?>/">Home</a></li>
                    <li class="breadcrumb-item text-white active" aria-current="page">Agendamentos</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Appointments Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa fa-calendar me-2"></i>Histórico de Agendamentos</h5>
                        <button class="btn btn-sm btn-light" disabled>
                            <i class="fa fa-plus me-2"></i>Novo Agendamento (Em breve)
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>
                            <strong>Página em desenvolvimento.</strong> 
                            Em breve poderá visualizar e gerir todos os seus agendamentos aqui.
                        </div>

                        <div class="text-center py-5">
                            <i class="fa fa-calendar-times fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Ainda não tem agendamentos registados.</p>
                            <button class="btn btn-primary mt-3" disabled>
                                Fazer Primeiro Agendamento (Em breve)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . "/includes/footer.php"; ?>
</body>
</html>
