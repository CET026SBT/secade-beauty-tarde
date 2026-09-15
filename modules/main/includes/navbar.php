<?php
$currentPage = isset($currentPage) ? $currentPage : basename($_SERVER["PHP_SELF"]);
$isAuthPage = in_array($currentPage, ["login.php", "login", "customerRegister.php", "registo"]);
?>

<div class="container-fluid bg-dark sticky-top p-0">
    <nav class="navbar navbar-expand-lg navbar-dark p-0">
        <a href="<?= BASE_URL ?>/" class="navbar-brand px-3 px-md-5 me-0">
            <img src="<?= BASE_URL ?>/modules/common/img/sb-logo.png" class="site-logo user-select-none" draggable="false">
            <img src="<?= BASE_URL ?>/modules/common/img/sb-title.png" class="site-title user-select-none" draggable="false">
        </a>
        <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse p-3" id="navbarCollapse">
            <div class="navbar-nav me-auto">
                <a href="<?= BASE_URL ?>/" class="nav-item nav-link <?php echo in_array($currentPage, ["home.php", "home", ""]) ? "active" : ""; ?>">Home</a>
                <?php if (!$isAuthPage): ?>
                    <a href="<?= BASE_URL ?>/sobre" class="nav-item nav-link <?php echo in_array($currentPage, ["about.php", "about", "sobre"]) ? "active" : ""; ?>">Acerca</a>
                    <a href="<?= BASE_URL ?>/servicos" class="nav-item nav-link <?php echo in_array($currentPage, ["serviceCategories.php", "service", "servicos"]) ? "active" : ""; ?>">Servicos</a>
                    <a href="<?= BASE_URL ?>/contacto" class="nav-item nav-link <?php echo in_array($currentPage, ["contact.php", "contact", "contacto"]) ? "active" : ""; ?>">Contactos</a>
                <?php endif; ?>
            </div>
            <?php if (!$isAuthPage) include ROOT_PATH . "/modules/main/components/menuUser.php" ?>
        </div>
    </nav>
</div>