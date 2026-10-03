<?php
$pageTitle = "Página Não Encontrada";
$currentPage = "404";
$pageHeaderTitle = "Erro 404";

include_once ROOT_PATH . "/modules/main/includes/header.php";
include_once ROOT_PATH . "/modules/main/includes/navbar.php";
?>

<main>
    <?php
    include_once ROOT_PATH . "/modules/main/components/pageHeader.php";
    include_once ROOT_PATH . "/modules/main/components/notFound.php";
    ?>
</main>

<?php include_once ROOT_PATH . "/modules/main/includes/footer.php"; ?>
