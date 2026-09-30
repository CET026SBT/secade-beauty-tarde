<?php
$pageTitle = "Sobre Nós";
$currentPage = "sobre";
$pageHeaderTitle = "Sobre nós";
//$pageHeaderBreadcrumb = "Sobre nós";

include_once ROOT_PATH . "/modules/main/includes/header.php";
include_once ROOT_PATH . "/modules/main/includes/navbar.php";
?>

<main>
<?php 
    include_once ROOT_PATH . "/modules/main/components/pageHeader.php";
    include_once ROOT_PATH . "/modules/main/components/about.php";
    include_once ROOT_PATH . "/modules/main/components/aboutMission.php";
    include_once ROOT_PATH . "/modules/main/components/aboutStory.php";
    include_once ROOT_PATH . "/modules/main/components/aboutValues.php";
    include_once ROOT_PATH . "/modules/main/components/aboutTeam.php";
    include_once ROOT_PATH . "/modules/main/components/aboutHow.php";
    include_once ROOT_PATH . "/modules/main/components/aboutStore.php";
    include_once ROOT_PATH . "/modules/main/components/aboutCommitment.php";
    include_once ROOT_PATH . "/modules/main/components/servicesOverview.php";
    include_once ROOT_PATH . "/modules/main/components/testimonial.php";
?>
</main>

<?php include_once ROOT_PATH . "/modules/main/includes/footer.php"; ?>
