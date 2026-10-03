<?php
$isAuthPage = isset($isAuthPage) ? $isAuthPage : in_array($currentPage, ["login", "registar"]);

register_script("components/navbar", "main");

require_once ROOT_PATH . "/modules/main/includes/_navigation.php";
?>

<div class="container-fluid bg-dark sticky-top p-0">
    <nav class="navbar navbar-expand-xl navbar-dark p-0 gap-2 mx-3 mx-md-5 me-lg-2 me-xl-5">
        <a href="<?= BASE_URL ?>/" class="navbar-brand m-0">
            <img src="<?= BASE_URL ?>/modules/common/img/sb-logo.png" class="site-logo user-select-none" draggable="false">
            <img src="<?= BASE_URL ?>/modules/common/img/sb-title.png" class="site-title user-select-none" draggable="false">
        </a>

        <?php if (!$isAuthPage) include ROOT_PATH . "/modules/main/components/menuUser.php" ?>

        <button type="button" class="navbar-toggler d-none d-lg-block d-xl-none" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav">
                <?php foreach ($navigationLinks as $link): ?>
                    <?php if (!isset($link["profile"]) || in_array(Session::getUserProfile(), $link["profile"], true)): ?>
                        <a class="nav-item nav-link py-sm-1 <?= $currentPage === $link["page"] ? "active" : ""; ?>"
                           href="<?= BASE_URL . $link["url"] ?>">
                            <span><?= htmlspecialchars($link["label"]) ?></span>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>
</div>