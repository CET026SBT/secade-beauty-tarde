<?php
$pageTitle = "Página Não Encontrada";
$currentPage = "404.php";
$pageHeaderTitle = "Erro 404";
//$pageHeaderBreadcrumb = "404 Error";

include_once __DIR__ . '/includes/header.php';
include_once __DIR__ . '/includes/spinner.php';
include_once __DIR__ . '/includes/navbar.php';

include_once __DIR__ . '/components/page_header.php';
include_once __DIR__ . '/components/not_found.php';

include_once __DIR__ . '/includes/footer.php';
