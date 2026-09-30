<?php
$isAuthPage = isset($isAuthPage) ? $isAuthPage : in_array($currentPage, ["login", "registar"]);

require_once ROOT_PATH . "/modules/main/includes/_navigation.php";
?>

<nav class="navbar navbar-dark bg-dark justify-content-center py-1 px-3 fixed-bottom d-lg-none">
    <ul class="navbar-nav flex-row justify-content-around w-100 mb-0">
        <?php foreach ($navigationLinks as $link): ?>
            <?php if (!isset($link["profile"]) || in_array(Session::getUserProfile(), $link["profile"], true)): ?>
                <li class="nav-item">
                    <a class="nav-link text-center d-flex flex-column align-items-center p-1 mx-0 <?= $currentPage === $link["page"] ? "active" : ""; ?>"
                       href="<?= BASE_URL . $link["url"] ?>">
                        <i class="<?= htmlspecialchars($link["icon"]) ?> fs-5"></i>
                        <span class="smallest"><?= htmlspecialchars($link["label"]) ?></span>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</nav>