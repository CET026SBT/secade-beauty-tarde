<?php
require_once __DIR__ . "/../../app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

Session::requireLogin(BASE_URL . "/login");

$bookingId = (int)($_GET["id"] ?? 0);
$local     = $_GET["local"] ?? "loja_fisica";
$isAmbulatory = $local === "carrinha_ambulante";
$user = Session::user();

$pageTitle = "Agendamento Submetido";
$currentPage = "agendar";

include_once ROOT_PATH . "/modules/main/includes/header.php";
include_once ROOT_PATH . "/modules/main/includes/navbar.php";
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-4 p-lg-5">
                    <div class="display-4 text-success mb-3">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>

                    <h2 class="mb-3">Agendamento submetido com sucesso!</h2>

                    <?php if ($bookingId > 0): ?>
                        <p class="text-muted mb-4">Referência da marcação: <strong>#<?= $bookingId ?></strong></p>
                    <?php endif; ?>

                    <?php if ($isAmbulatory): ?>
                        <div class="alert alert-warning text-start">
                            <i class="bi bi-hourglass-split me-1"></i>
                            O seu agendamento na <strong>carrinha ambulante</strong> está <strong>pendente</strong>.
                            A rota será validada pela equipa e receberá confirmação (notificação simulada).
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-start">
                            <i class="bi bi-info-circle me-1"></i>
                            O seu agendamento na <strong>loja física</strong> foi registado.
                            Os serviços foram aceites automaticamente e aguarda validação logística da loja.
                        </div>
                    <?php endif; ?>

                    <p class="text-muted small">
                        Olá <strong><?= htmlspecialchars($user["name"] ?? "") ?></strong>,
                        pode consultar o estado de todas as suas marcações na área de agendamentos.
                    </p>

                    <div class="d-flex flex-wrap gap-3 justify-content-center mt-4">
                        <a href="<?= BASE_URL ?>/agendamentos" class="btn btn-primary px-4">
                            <i class="bi bi-calendar-check me-1"></i> Ver os meus agendamentos
                        </a>
                        <a href="<?= BASE_URL ?>/agendar" class="btn btn-outline-primary px-4">
                            <i class="bi bi-plus me-1"></i> Nova marcação
                        </a>
                        <a href="<?= BASE_URL ?>/" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-house me-1"></i> Início
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once ROOT_PATH . "/modules/main/includes/footer.php"; ?>