<?php
require_once __DIR__ . '/config.php';

$currentPage = isset($currentPage) ? $currentPage : basename($_SERVER['PHP_SELF']);
?>

<div class="container-fluid bg-dark sticky-top p-0">
    <nav class="navbar navbar-expand-lg navbar-dark p-0">
        <a href="index.php" class="navbar-brand px-3 px-md-5 me-0">
            <img src="img/sb-logo.png" class="site-logo">
            <img src="img/sb-title.png" class="site-title">
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse"
            data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse p-3" id="navbarCollapse">
            <div class="navbar-nav mx-auto">
                <a href="index.php" class="nav-item nav-link <?php echo ($currentPage == 'index.php' || $currentPage == 'home') ? 'active' : ''; ?>">Home</a>
                <a href="about.php" class="nav-item nav-link <?php echo ($currentPage == 'about.php' || $currentPage == 'about') ? 'active' : ''; ?>">Acerca</a>
                <a href="service.php" class="nav-item nav-link <?php echo ($currentPage == 'service.php' || $currentPage == 'service') ? 'active' : ''; ?>">Servicos</a>
                <a href="404.php" class="nav-item nav-link <?php echo ($currentPage == '404.php' || $currentPage == '404') ? 'active' : ''; ?>">404 Page</a>
                <a href="contact.php" class="nav-item nav-link <?php echo ($currentPage == 'contact.php' || $currentPage == 'contact') ? 'active' : ''; ?>">Contactos</a>
            </div>
            <a class="btn btn-sm btn-primary no-bg" href="admin/index.html"><i class="fa fa-user-cog me-2"></i>Admin</a>
        </div>
    </nav>
</div>
