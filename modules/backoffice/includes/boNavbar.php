<?php
register_script("components/menuUserBo", "backoffice");

$boCurrentPage = $boCurrentPage ?? "dashboard";
$boIsEmployee = Session::isEmployee();

// D-14 (§3.14): `/gestao` é o painel do gestor; o funcionário entra pela sua agenda.
$boHomeUrl = $boIsEmployee ? BASE_URL . "/gestao/agenda" : BASE_URL . "/gestao/painel";
?>
<nav class="navbar navbar-expand-lg navbar-dark bo-navbar py-3">
    <div class="container-fluid">
        <!-- No desktop a marca vive na sidebar; no topo fica só nos ecrãs pequenos. -->
        <a class="navbar-brand d-flex d-lg-none align-items-center gap-2" href="<?= $boHomeUrl ?>">
            <img src="<?= BASE_URL ?>/modules/common/img/sb-logo-primary.svg" alt="Secade Beauty" height="32" class="user-select-none" draggable="false">
            <span class="fw-bold">Backoffice</span>
        </a>

        <button type="button" class="btn btn-outline-light btn-sm d-lg-none ms-auto me-2" id="boSidebarToggle"
                data-bs-toggle="collapse" data-bs-target="#boSidebar" aria-expanded="false" aria-controls="boSidebar">
            <i class="bi bi-list"></i><span class="visually-hidden">Abrir o menu de gestão</span>
        </button>

        <?php include ROOT_PATH . "/modules/backoffice/components/menuUserBo.php" ?>
    </div>
</nav>

<?php include ROOT_PATH . "/modules/backoffice/includes/boSidebar.php" ?>