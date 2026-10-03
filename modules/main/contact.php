<?php
require_once APP_PATH . "/config/config.php";
require_once APP_PATH . "/utils/Session.php";

Session::requireProfile(["cliente", "guest"]);

$pageTitle = "Contactos";
$currentPage = "contacto";
$pageHeaderTitle = "Contactos";

include_once ROOT_PATH . "/modules/main/includes/header.php";
include_once ROOT_PATH . "/modules/main/includes/navbar.php";
?>

<main>
    <?php
    include_once ROOT_PATH . "/modules/main/components/pageHeader.php";
    include_once ROOT_PATH . "/modules/main/components/contact.php";
    ?>
</main>

<?php include_once ROOT_PATH . "/modules/main/includes/footer.php";
