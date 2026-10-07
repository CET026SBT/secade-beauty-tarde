<?php
require_once APP_PATH . "/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Backoffice do GESTOR — Recursos Humanos (F7 · §7 · §25)
Session::requireProfile(["gestor"]);

register_script("validators/employee.validator", "common");
register_script("components/rh", "backoffice");

$boPageTitle = "Recursos Humanos";
$boCurrentPage = "rh";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>

<main class="p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-people text-primary me-2"></i>Recursos Humanos</h2>
            <p class="text-muted small mb-0">
                Equipa do espaço. «Remover» faz <strong>desativação</strong> (mantém o histórico) — se o funcionário
                tiver serviços por executar, esses voltam a ficar por alocar.
            </p>
        </div>
        <button type="button" class="btn btn-primary extended-border" id="newEmployeeBtn">
            <i class="bi bi-plus-lg me-1"></i> Novo funcionário
        </button>
    </div>

    <div class="row g-3 mb-4" id="rhKpis" preloader-defer></div>

    <div class="alert alert-danger d-none" id="rhError" role="alert"></div>
    <div class="alert alert-success d-none" id="rhResult" role="alert"></div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-bold">Equipa</span>
            <span class="text-muted small" id="rhCount">A carregar...</span>
        </div>
        <div class="card-body p-0" id="rhTableContainer" preloader-defer>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bo-table">
                    <thead>
                        <tr>
                            <th>Funcionário</th>
                            <th>Contacto</th>
                            <th>Contrato</th>
                            <th class="text-end">Salário base</th>
                            <th class="text-end">% comissão</th>
                            <th class="text-end">IRS</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="rhTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal criar/editar funcionário -->
<div class="modal fade" id="employeeFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeFormTitle">Novo funcionário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="employeeForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="employeeId" id="employeeId">
                    <div class="row g-3">
                        <div class="col-md-4 text-center">
                            <div class="bo-avatar-wrap mb-2">
                                <img id="employeePhotoPreview" class="bo-avatar-preview"
                                     src="<?= BASE_URL ?>/modules/common/img/favicon.ico" alt="Fotografia">
                                <input type="file" class="form-control form-control-sm" id="employeePhoto" accept="image/png,image/jpeg,image/webp">
                            </div>
                            <div class="form-text">JPG/PNG/WEBP, até 2 MB.</div>
                        </div>
                        <div class="col-md-8">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="employeeName">Nome completo <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="employeeName" name="name" maxlength="150">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="employeeEmail">E-mail <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="employeeEmail" name="email" maxlength="150">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="employeePhone">Telemóvel <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="employeePhone" name="phone" maxlength="20">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="employeeNif">NIF</label>
                                    <input type="text" class="form-control" id="employeeNif" name="nif" maxlength="20">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="employeeCc">Cartão de Cidadão <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="employeeCc" name="cc" maxlength="20">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-sm-6" id="employeePasswordWrap">
                                    <label class="form-label" for="employeePassword">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="employeePassword" name="password">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-sm-3">
                            <label class="form-label" for="employeeContractType">Tipo de contrato <span class="text-danger">*</span></label>
                            <select class="form-select" id="employeeContractType" name="contractType">
                                <option value="efetivo_contratado">Efetivo (contratado)</option>
                                <option value="recibo_verde">Recibo verde</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label" for="employeeSalary">Salário base (€)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="employeeSalary" name="salary">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label" for="employeeCommission">% comissão</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" id="employeeCommission" name="commissionPercentage">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label" for="employeeIrs">IRS (%)</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control" id="employeeIrs" name="irsRate">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <p class="text-muted small mb-0 mt-3">
                        A % de comissão nasce com o valor por omissão do contrato (efetivo 0%, recibo verde 70%) e pode
                        ser ajustada. O salário só se aplica aos efetivos.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary extended-border" id="saveEmployeeBtn">
                        <i class="bi bi-check2 me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmação de desativação (fluxo de impacto §7.5) -->
<div class="modal fade" id="employeeDeactivateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Desativar funcionário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="employeeDeactivateBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeactivateBtn">
                    <i class="bi bi-person-dash me-1"></i> Desativar
                </button>
            </div>
        </div>
    </div>
</div>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>