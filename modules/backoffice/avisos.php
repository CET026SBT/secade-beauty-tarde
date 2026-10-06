<?php
require_once APP_PATH . "/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Avisos por perfil (RF-81 · D-15): gestor e funcionário têm a mesma página,
// com origens diferentes — os grupos são decididos no servidor.
Session::requireProfile(["gestor", "funcionario"]);

register_script("components/alerts", "backoffice");

$boPageTitle = "Avisos";
$boCurrentPage = "alerts";

$isManager = Session::isManager();

include_once ROOT_PATH . "/modules/backoffice/includes/boHeader.php";
include_once ROOT_PATH . "/modules/backoffice/includes/boNavbar.php";
?>
<main class="p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-bell text-primary me-2"></i>Avisos</h2>
            <p class="text-muted small mb-0">
                Tudo o que está por tratar, numa só página. Os avisos são <strong>derivados dos dados</strong>
                que já existem no sistema — nenhum é inventado e nenhum decide nada por si.
            </p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-secondary align-self-center" id="alertsCountBadge">0</span>
            <?php if ($isManager): ?>
                <button type="button" class="btn btn-outline-primary" id="markAlertsReadBtn">
                    <i class="bi bi-check2-all me-1"></i> Marcar fiscais como lidos
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="alert alert-danger d-none" id="alertsError" role="alert"></div>
    <div class="alert alert-success d-none" id="alertsResult" role="alert"></div>

    <div class="row g-3" id="alertsGroups" preloader-defer></div>
</main>

<?php include_once ROOT_PATH . "/modules/backoffice/includes/boFooter.php"; ?>