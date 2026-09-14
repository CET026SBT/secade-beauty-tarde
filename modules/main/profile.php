<?php
require_once __DIR__ . "/../../app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Require login to access this page
Session::requireLogin();

$user = Session::user();
$currentPage = "profile";
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <?php include __DIR__ . "/includes/header.php"; ?>
    <title>Meu Perfil - <?= SITE_NAME ?></title>
</head>
<body>
    <?php include __DIR__ . "/includes/spinner.php"; ?>
    <?php include __DIR__ . "/includes/navbar.php"; ?>

    <!-- Page Header -->
    <div class="container-fluid page-header py-5 mb-5">
        <div class="container text-center py-5">
            <h1 class="display-4 text-white animated slideInDown mb-3">Meu Perfil</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb justify-content-center mb-0">
                    <li class="breadcrumb-item"><a class="text-white" href="<?= BASE_URL ?>/">Home</a></li>
                    <li class="breadcrumb-item text-white active" aria-current="page">Perfil</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <img src="<?= BASE_URL ?>/modules/common/img/testimonial-1.jpg" 
                             alt="<?= htmlspecialchars($user['name']) ?>"
                             class="rounded-circle mb-3"
                             style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #d4a574;">
                        <h4 class="mb-1"><?= htmlspecialchars($user['name']) ?></h4>
                        <p class="text-muted mb-3"><?= htmlspecialchars($user['email']) ?></p>
                        <span class="badge bg-primary"><?= ucfirst($user['profile']) ?></span>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fa fa-user me-2"></i>Informações Pessoais</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>
                            <strong>Página em desenvolvimento.</strong> 
                            A funcionalidade de edição de perfil e gestão de moradas será implementada em breve.
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Nome Completo</label>
                                <p class="fw-bold"><?= htmlspecialchars($user['name']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">E-mail</label>
                                <p class="fw-bold"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                        </div>

                        <div class="text-end">
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="fa fa-edit me-2"></i>Editar Perfil (Em breve)
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fa fa-map-marker-alt me-2"></i>Minhas Moradas</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>
                            <strong>Em desenvolvimento.</strong> 
                            Aqui poderá gerir as suas moradas guardadas.
                        </div>
                        
                        <div class="text-end">
                            <button class="btn btn-outline-primary" disabled>
                                <i class="fa fa-plus me-2"></i>Adicionar Morada (Em breve)
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
