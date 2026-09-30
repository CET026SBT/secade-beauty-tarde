<?php
$pageTitle = "Iniciar Sessão";
$currentPage = "login";
$showMainFooter = false;

include_once ROOT_PATH . "/modules/main/includes/header.php";
include_once ROOT_PATH . "/modules/main/includes/navbar.php";
?>

<main>
    <?php include_once ROOT_PATH . "/modules/main/components/login.php"; ?>
</main>

<?php include_once ROOT_PATH . "/modules/main/includes/footer.php";
