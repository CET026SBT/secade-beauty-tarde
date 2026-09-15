<?php
$adminPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Secade Beauty Admin</title>
    <link href="assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="assets/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-icon"><i class="fas fa-spa"></i></div>
                <div class="sidebar-brand-text mx-3">Secade</div>
            </a>
            <hr class="sidebar-divider my-0">
            <li class="nav-item <?= $adminPage === 'index.php' ? 'active' : '' ?>">
                <a class="nav-link" href="index.php"><i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span></a>
            </li>
            <li class="nav-item <?= $adminPage === 'servicos.php' ? 'active' : '' ?>">
                <a class="nav-link" href="servicos.php"><i class="fas fa-fw fa-cut"></i><span>Servicos</span></a>
            </li>
            <li class="nav-item <?= $adminPage === 'agendamentos.php' ? 'active' : '' ?>">
                <a class="nav-link" href="agendamentos.php"><i class="fas fa-fw fa-calendar-check"></i><span>Agendamentos</span></a>
            </li>
            <li class="nav-item <?= $adminPage === 'utilizadores.php' ? 'active' : '' ?>">
                <a class="nav-link" href="utilizadores.php"><i class="fas fa-fw fa-users"></i><span>Utilizadores</span></a>
            </li>
            <li class="nav-item <?= $adminPage === 'deslocacoes.php' ? 'active' : '' ?>">
                <a class="nav-link" href="deslocacoes.php"><i class="fas fa-fw fa-route"></i><span>Deslocacoes</span></a>
            </li>
            <hr class="sidebar-divider">
            <li class="nav-item">
                <a class="nav-link" href="../index.php"><i class="fas fa-fw fa-home"></i><span>Site publico</span></a>
            </li>
        </ul>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <span class="nav-link">Base de dados: <strong>secade_beauty</strong></span>
                        </li>
                    </ul>
                </nav>
                <div class="container-fluid">
