<?php
require_once __DIR__ . "/../../app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Backoffice: acesso restrito a gestores
Session::requireProfile(["gestor"]);

register_script("validators/supplier.validator", "common");
register_script("components/suppliers", "backoffice");

$boPageTitle = "Fornecedores";
$boCurrentPage = "suppliers";

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-truck text-primary me-2"></i>Fornecedores</h2>
            <p class="text-muted small mb-0">
                Registo dos fornecedores do espaço. Os <strong>43 fornecedores reais</strong> entregues pelo
                cliente já cá estão (migração <code>database_migration_v4.sql</code>). Não há eliminação:
                «remover» <strong>desativa</strong> o fornecedor, porque pode estar citado em despesas.
            </p>
        </div>
        <button type="button" class="btn btn-primary extended-border" id="newSupplierBtn">
            <i class="bi bi-plus-lg me-1"></i> Novo fornecedor
        </button>
    </div>

    <div class="row g-3 mb-4" id="supplierKpis" preloader-defer></div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 bo-filters align-items-end">
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1" for="supplierTerm">Pesquisar</label>
                    <input type="search" id="supplierTerm" class="form-control form-control-sm" placeholder="Nome, NIF ou e-mail">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1" for="supplierActive">Estado</label>
                    <select id="supplierActive" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="1">Ativos</option>
                        <option value="0">Inativos</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary flex-fill" id="searchSuppliersBtn">
                            <i class="bi bi-search me-1"></i> Pesquisar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary flex-fill" id="clearSuppliersBtn">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Limpar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-danger d-none" id="suppliersError" role="alert"></div>
    <div class="alert alert-success d-none" id="suppliersResult" role="alert"></div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-bold">Lista de fornecedores</span>
            <span class="text-muted small" id="suppliersCount">A carregar...</span>
        </div>
        <div class="card-body p-0" id="suppliersTableContainer" preloader-defer>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bo-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fornecedor</th>
                            <th>NIF</th>
                            <th>Contacto</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="suppliersTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal: criar/editar fornecedor -->
<div class="modal fade" id="supplierFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supplierFormTitle">Novo fornecedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="supplierForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="supplierId" id="supplierId">

                    <div class="mb-3">
                        <label class="form-label" for="supplierName">Nome <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="supplierName" name="name" maxlength="150">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="supplierNif">NIF</label>
                            <input type="text" class="form-control" id="supplierNif" name="nif" maxlength="20">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="supplierPhone">Telemóvel</label>
                            <input type="text" class="form-control" id="supplierPhone" name="phone" maxlength="20">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="supplierEmail">E-mail</label>
                            <input type="email" class="form-control" id="supplierEmail" name="email" maxlength="150">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="supplierNotes">Observações</label>
                            <textarea class="form-control" id="supplierNotes" name="notes" rows="3"
                                      placeholder="Ex.: o ficheiro do cliente trazia aqui o rótulo de canal/observação."></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="supplierActiveCheck" name="active" checked>
                                <label class="form-check-label" for="supplierActiveCheck">Fornecedor ativo</label>
                            </div>
                        </div>
                    </div>

                    <p class="text-muted small mb-0 mt-3">
                        Campos que a fonte não trazia ficam <strong>em branco</strong> — nunca se inventa um valor.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary extended-border" id="saveSupplierBtn">
                        <i class="bi bi-check2 me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>