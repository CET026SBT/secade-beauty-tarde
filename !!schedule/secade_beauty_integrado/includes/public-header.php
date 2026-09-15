<?php
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="utf-8">
    <title>Secade Beauty</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="assets/img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/lib/animate/animate.min.css" rel="stylesheet">
    <link href="assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/secade.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid bg-light d-none d-lg-block">
        <div class="row py-2 px-lg-5">
            <div class="col-lg-6 text-left mb-2 mb-lg-0">
                <small>Beleza em loja e carrinha ambulante no distrito de Evora</small>
            </div>
            <div class="col-lg-6 text-right">
                <small>Painel: <a href="admin/">Admin Secade Beauty</a></small>
            </div>
        </div>
    </div>

    <div class="container-fluid p-0">
        <nav class="navbar navbar-expand-lg bg-white navbar-light py-3 py-lg-0 px-lg-5">
            <a href="index.php" class="navbar-brand ml-lg-3">
                <h1 class="m-0 text-primary"><span class="text-dark">Secade</span> Beauty</h1>
            </a>
            <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-between px-lg-3" id="navbarCollapse">
                <div class="navbar-nav m-auto py-0">
                    <a href="index.php" class="nav-item nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">Inicio</a>
                    <a href="servicos.php" class="nav-item nav-link <?= $currentPage === 'servicos.php' ? 'active' : '' ?>">Servicos</a>
                    <a href="agendar.php" class="nav-item nav-link <?= $currentPage === 'agendar.php' ? 'active' : '' ?>">Agendar</a>
                </div>
                <a href="agendar.php" class="btn btn-primary d-none d-lg-block">Marcar agora</a>
            </div>
        </nav>
    </div>
