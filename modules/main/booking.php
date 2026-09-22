<?php
require_once __DIR__ . "/../../app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

// Wizard de agendamento: requer sessão de cliente
Session::requireLogin(BASE_URL . "/login");

$pageTitle = "Agendar";
$currentPage = "agendar";

include_once ROOT_PATH . "/modules/main/includes/header.php";
include_once ROOT_PATH . "/modules/main/includes/navbar.php";
include_once ROOT_PATH . "/modules/main/components/bookingWizard.php";
include_once ROOT_PATH . "/modules/main/includes/footer.php";