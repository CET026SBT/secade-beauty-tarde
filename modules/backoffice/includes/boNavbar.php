<?php
register_script("components/menuUserBo", "backoffice");

$boCurrentPage = $boCurrentPage ?? "dashboard";
$boIsEmployee = Session::isEmployee();

// D-14 (§3.14): `/gestao` é o painel do gestor; o funcionário entra pela sua agenda.
$boHomeUrl = $boIsEmployee ? BASE_URL . "/gestao/agenda" : BASE_URL . "/gestao/painel";
?>
<nav class="bo-navbar navbar navbar-expand-lg navbar-dark py-3">
    <a href="<?= $boHomeUrl ?>/" class="navbar-brand px-3 px-md-5 me-0">
        <img src="<?= $boHomeUrl ?>/modules/common/img/sb-logo-primary.svg" alt="Secade Beauty" class="site-logo user-select-none" draggable="false">
        <span class="fw-bold">Backoffice</span>
    </a>

    <?php include ROOT_PATH . "/modules/backoffice/components/menuUserBo.php" ?>

    <button type="button" class="navbar-toggler mx-2" data-bs-toggle="collapse" data-bs-target="#boSidebar">
        <span class="navbar-toggler-icon"></span>
    </button>
</nav>

<?php include ROOT_PATH . "/modules/backoffice/includes/boSidebar.php" ?>