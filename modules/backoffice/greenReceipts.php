<?php
require_once APP_PATH . "/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Backoffice do GESTOR
Session::requireProfile(["gestor"]);

register_script("components/greenReceipts", "backoffice");

$boPageTitle = "Configurações de Percentagens";
$boCurrentPage = "greenReceipts";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="p-4">
    <div class="mb-4">
        <h2 class="mb-1"><i class="bi bi-cash-stack text-primary me-2"></i>Configurações de Percentagens</h2>
        <p class="text-muted small mb-0">
            Percentagens <strong>padrão por tipo de contrato</strong>, com vigência por data. São aplicadas como
            valor inicial ao criar um funcionário (ou para pré-preencher o formulário de RH) — cada funcionário
            pode depois ter a sua própria percentagem. <strong>Simulação académica</strong> — não há emissão real
            na Segurança Social.
        </p>
    </div>

    <div class="alert alert-danger d-none" id="greenReceiptError" role="alert"></div>
    <div class="alert alert-success d-none" id="greenReceiptSuccess" role="alert"></div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Padrões em vigor</div>
                <div class="card-body" id="activeConfigBox">A carregar...</div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Nova configuração</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small text-muted mb-1" for="grContractType">Tipo de contrato</label>
                            <select id="grContractType" class="form-select form-select-sm">
                                <option value="recibo_verde">Recibos verdes</option>
                                <option value="efetivo_contratado">Efetivo (contratado)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1" for="grPercentage">% Funcionário</label>
                            <input type="number" step="0.01" min="0" max="100" id="grPercentage" class="form-control form-control-sm" value="70">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted mb-1" for="grEffectiveFrom">Data de vigência</label>
                            <input type="date" id="grEffectiveFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <div class="alert alert-light border small mb-0" id="grPreview">A restante percentagem (100 − x) fica para a Empresa.</div>
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-sm btn-primary extended-border" id="saveGreenReceiptConfigBtn">
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