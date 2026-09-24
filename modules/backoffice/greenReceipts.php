<?php
require_once __DIR__ . "/../../app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Backoffice do GESTOR
Session::requireLogin();
if (!Session::isManager()) {
    header("Location: " . BASE_URL . "/");
    exit;
}

register_script("components/greenReceipts", "backoffice");

$boPageTitle = "Simulador de Recibos Verdes";
$boCurrentPage = "greenReceipts";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="mb-1"><i class="bi bi-cash-stack text-primary me-2"></i>Simulador de Recibos Verdes</h2>
        <p class="text-muted small mb-0">
            Percentagens configuráveis, com vigência por data. Na aceitação de um serviço é aplicada
            a configuração em vigor. <strong>Simulação académica</strong> — não há emissão real na Segurança Social.
        </p>
    </div>

    <div class="alert alert-danger d-none" id="greenReceiptError" role="alert"></div>
    <div class="alert alert-success d-none" id="greenReceiptSuccess" role="alert"></div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Configuração em vigor</div>
                <div class="card-body" id="activeConfigBox">A carregar...</div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Nova configuração</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1" for="grEmployee">% Funcionário</label>
                            <input type="number" step="0.01" min="0" max="100" id="grEmployee" class="form-control form-control-sm" value="70">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1" for="grPlatform">% Plataforma</label>
                            <input type="number" step="0.01" min="0" max="100" id="grPlatform" class="form-control form-control-sm" value="30">
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted mb-1" for="grEffectiveFrom">Data de vigência</label>
                            <input type="date" id="grEffectiveFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <div class="alert alert-light border small mb-0" id="grPreview">Soma atual: 100%</div>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-sm btn-primary" id="saveGreenReceiptConfigBtn">
                                <i class="bi bi-check2 me-1"></i> Guardar configuração
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Histórico de configurações</div>
                <div class="card-body p-0" id="greenReceiptHistory" preloader-defer></div>
            </div>
        </div>
    </div>
</main>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>