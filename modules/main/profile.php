<?php
require_once __DIR__ . "/../../app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Require login to access this page
Session::requireLogin();

register_script("components/profile", "main");

$user = Session::user();
$currentPage = "profile";

include_once ROOT_PATH . "/modules/main/includes/header.php";
include_once ROOT_PATH . "/modules/main/includes/navbar.php";
?>

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
    <div class="container py-5" id="profilePage" preloader-defer>
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <img src="<?= BASE_URL ?>/modules/common/img/testimonial-1.jpg" 
                             alt="<?= htmlspecialchars($user['name']) ?>"
                             class="wh-150 rounded-circle object-fit-cover mb-3 border border-3 border-primary">
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
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1">Nome Completo</label>
                                <p class="fw-bold mb-0" id="profileFieldName">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1">E-mail</label>
                                <p class="fw-bold mb-0" id="profileFieldEmail">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1">Telemóvel</label>
                                <p class="fw-bold mb-0" id="profileFieldPhone">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1">NIF</label>
                                <p class="fw-bold mb-0" id="profileFieldNif">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small mb-1">Telemóvel validado por OTP</label>
                                <p class="fw-bold mb-0" id="profileFieldVerified">-</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Minhas Moradas</h5>
                        <button type="button" class="btn btn-sm btn-light" id="toggleAddressFormBtn">
                            <i class="bi bi-plus-lg me-1"></i> Adicionar
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger d-none" id="addressError" role="alert"></div>

                        <div class="border rounded p-3 mb-4 d-none" id="profileAddressForm">
                            <h6 class="fw-bold small text-uppercase mb-3">Nova morada</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <select class="form-select form-select-sm" id="profileAddressCity">
                                        <option value="">Cidade...</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control form-control-sm" id="profileAddressZip" placeholder="Código postal (0000-000)">
                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control form-control-sm" id="profileAddressStreet" placeholder="Rua / Avenida">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control form-control-sm" id="profileAddressDoor" placeholder="Nº da porta">
                                </div>
                                <div class="col-12 d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-primary" id="saveAddressBtn">
                                        <i class="bi bi-check-lg me-1"></i> Guardar morada
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="cancelAddressBtn">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="profileAddressesList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div preloader-overlay class="jq-overlay-process-lg"></div>
</div>

<?php include_once ROOT_PATH . "/modules/main/includes/footer.php"; ?>
