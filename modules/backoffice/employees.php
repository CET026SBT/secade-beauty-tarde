<?php
require_once APP_PATH . "/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Backoffice do GESTOR
Session::requireProfile(["gestor"]);

register_script("validators/user.validator", "common");
register_script("validators/employee.validator", "common");
register_script("components/employees", "backoffice");

$boPageTitle = "Recursos Humanos";
$boCurrentPage = "employees";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-people text-primary me-2"></i>Recursos Humanos</h2>
            <p class="text-muted small mb-0">
                Funcionários, tipo de contrato, salário base e percentagem por serviço.
                Desativar é um <strong>soft delete</strong>: o histórico preserva-se sempre.
            </p>
        </div>
        <button type="button" class="btn btn-primary extended-border" id="newEmployeeBtn">
            <i class="bi bi-person-plus me-1"></i> Novo funcionário
        </button>
    </div>

    <div class="alert alert-danger d-none" id="employeesError" role="alert"></div>
    <div class="alert alert-success d-none" id="employeesSuccess" role="alert"></div>

    <div class="row g-3 mb-4" id="employeesKpis"></div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">Funcionários</span>
            <span class="badge bg-light text-dark" id="employeesCount">0</span>
        </div>
        <div class="card-body p-0" id="employeesTableContainer" preloader-defer></div>
    </div>
</main>

<!-- Modal do formulário (molde: suppliers.php #supplierFormModal) -->
<div class="modal fade" id="employeeFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employeeModalTitle">Novo funcionário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="employeeForm">
                    <input type="hidden" name="employeeId" id="employeeId">

                    <div class="row g-3">
                        <div class="col-md-8" id="employeeIdentityBlock">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1" for="employeeName">Nome completo</label>
                                    <input type="text" name="name" id="employeeName" class="form-control form-control-sm" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1" for="employeeEmail">E-mail</label>
                                    <input type="email" name="email" id="employeeEmail" class="form-control form-control-sm" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1" for="employeePhone">Telemóvel</label>
                                    <input type="text" name="phone" id="employeePhone" class="form-control form-control-sm" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6" id="passwordBlock">
                                    <label class="form-label small text-muted mb-1" for="employeePassword">Palavra-passe</label>
                                    <input type="password" name="password" id="employeePassword" class="form-control form-control-sm">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1" for="employeeNif">NIF</label>
                                    <input type="text" name="nif" id="employeeNif" class="form-control form-control-sm">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1" for="employeeCc">Cartão de Cidadão</label>
                                    <input type="text" name="cc" id="employeeCc" class="form-control form-control-sm" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <div class="text-center mb-2">
                                    <img id="employeePhotoPreview" class="bo-employee-photo rounded-circle"
                                         src="<?= BASE_URL ?>/modules/common/img/sb-logo.png" alt="">
                                </div>
                                <input type="file" id="employeePhotoFile" class="form-control form-control-sm mb-2"
                                       accept="image/jpeg,image/png,image/webp">
                                <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-2" id="employeePhotoUploadBtn" disabled>
                                    <i class="bi bi-upload me-1"></i>Carregar foto
                                </button>
                                <p class="small text-muted mb-0">JPG, PNG ou WEBP, até 2 MB.</p>
                            </div>
                        </div>
                    </div>

                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1" for="employeeContractType">Tipo de contrato</label>
                            <select name="contractType" id="employeeContractType" class="form-select form-select-sm" required>
                                <option value="recibo_verde">Recibos verdes</option>
                                <option value="efetivo_contratado">Efetivo (contratado)</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1" for="employeeCommission">% por serviço</label>
                            <input type="number" step="0.01" min="0" max="100" name="commissionPercentage" id="employeeCommission"
                                   class="form-control form-control-sm">
                            <div class="invalid-feedback"></div>
                            <p class="small text-muted mb-0" id="employeeCommissionHint">Sugerida pelo tipo de contrato.</p>
                        </div>
                        <div class="col-md-4" id="salaryBlock">
                            <label class="form-label small text-muted mb-1" for="employeeSalary">Salário base (€)</label>
                            <input type="number" step="0.01" min="0" name="salary" id="employeeSalary"
                                   class="form-control form-control-sm" value="0">
                            <div class="invalid-feedback"></div>
                            <p class="small text-muted mb-0">Só os efetivos têm salário base (RV = 0).</p>
                        </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary extended-border" id="saveEmployeeBtn">
                    <i class="bi bi-check2 me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>