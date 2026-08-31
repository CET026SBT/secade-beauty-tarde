<?php
$pageTitle = "Página Não Encontrada";
$currentPage = "404";
$pageHeaderTitle = "Erro 404";
//$pageHeaderBreadcrumb = "404 Error";

include_once ROOT_PATH . '/modules/main/includes/header.php';
include_once ROOT_PATH . '/modules/main/includes/spinner.php';
include_once ROOT_PATH . '/modules/main/includes/navbar.php';

include_once ROOT_PATH . '/modules/main/components/page_header.php';
include_once ROOT_PATH . '/modules/main/components/not_found.php';

include_once ROOT_PATH . '/modules/main/includes/footer.php';
