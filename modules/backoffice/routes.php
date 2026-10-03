<?php
require_once APP_PATH . "/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Backoffice: acesso restrito a gestores
Session::requireProfile(["gestor"]);

register_script("components/routes", "backoffice");

$boPageTitle = "Gestão de Rotas";
$boCurrentPage = "routes";
$boDefaultRouteDate = date("Y-m-d", strtotime("+1 day"));

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-signpost-split text-primary me-2"></i>Gestão de Rotas</h2>
            <p class="text-muted small mb-0">
                A decisão é <strong>manual e livre</strong>: aprovar ou recusar é sempre do gestor.
                Os <strong>50 €</strong> junto da rentabilidade são apenas um <strong>indicador visual</strong> de
                referência — nunca bloqueiam nem decidem nada.
            </p>
        </div>
        <a href="<?= BASE_URL ?>/gestao/agendamentos" class="btn btn-outline-primary">
            <i class="bi bi-calendar-check me-1"></i> Ir para Agendamentos
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3 bo-filters align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1" for="routeDate">Data das rotas</label>
                    <input type="date" id="routeDate" class="form-control form-control-sm" value="<?= htmlspecialchars($boDefaultRouteDate) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1" for="routeCity">Cidade</label>
                    <select id="routeCity" class="form-select form-select-sm">
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1" for="routeStatus">Estado da rota</label>
                    <select id="routeStatus" class="form-select form-select-sm">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-sm btn-outline-primary w-100" id="reloadRoutesBtn">
                        <i class="bi bi-arrow-clockwise me-1"></i> Atualizar
                    </button>
                </div>
            </div>
            <div class="alert alert-light border small mb-0 mt-3">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Decisão manual:</strong> o gestor aprova ou recusa livremente. A rentabilidade é um
                indicador de apoio — a referência de <strong><?= number_format(50, 0) ?> €</strong> é apenas visual
                e <strong>não</strong> decide por si.
            </div>
            <div class="alert alert-warning border small mb-0 mt-3">
                <i class="bi bi-shield-exclamation me-1"></i>
                <strong>Regra da rota:</strong> uma rota só agrega agendamentos com <strong>todos os
                serviços aceites</strong>. Grupos com serviços por aceitar aparecem aqui como aviso, mas
                <strong>não</strong> podem ser decididos enquanto tiverem serviços pendentes.
            </div>
        </div>
    </div>

    <div class="alert alert-danger d-none" id="routesError" role="alert"></div>
    <div class="alert alert-success d-none" id="routesResult" role="alert"></div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="fw-bold">Rotas do dia</span>
            <span class="text-muted small" id="routesCount">A carregar...</span>
        </div>
        <div class="card-body p-0" id="routesTableContainer" preloader-defer>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bo-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Cidade</th>
                            <th class="text-center">Agendamentos</th>
                            <th class="text-end">Receita</th>
                            <th class="text-end">Combustível</th>
                            <th class="text-end">Rentabilidade</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Decisão</th>
                        </tr>
                    </thead>
                    <tbody id="routesTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal: detalhes da rota (RN-34 · §24.7 item 7)
     O gestor inclui/exclui agendamentos ANTES de decidir: a decisão aplica-se ao
     conjunto incluído e o que ficar de fora continua qualificado (nunca cancelado). -->
<div class="modal fade" id="routeDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da rota</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="routeDetailsSummary" class="mb-3"></div>
                <div id="routeDetailsBody" preloader-defer></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-success" id="modalApproveRouteBtn">
                    <i class="bi bi-check2 me-1"></i> Aprovar incluídos
                </button>
                <button type="button" class="btn btn-danger" id="modalRefuseRouteBtn">
                    <i class="bi bi-x me-1"></i> Recusar incluídos
                </button>
            </div>
        </div>
    </div>
</div>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>