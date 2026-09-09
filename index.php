<?php

require_once __DIR__ . "/app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

$requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$baseUrlParsed = parse_url(BASE_URL, PHP_URL_PATH);
$basePath = $baseUrlParsed ? rtrim($baseUrlParsed, "/") : "";

$path = str_replace($basePath, "", $requestUri);
$path = trim($path, "/");

if ($path === "api" || isset($_GET["action"])) {
    require_once APP_PATH . "/config/api.php";
    exit;
}

$routes = [
    // Páginas Públicas / Institucionais
    ""                 => ROOT_PATH . "/modules/main/home.php",
    "home"             => ROOT_PATH . "/modules/main/home.php",
    "sobre"            => ROOT_PATH . "/modules/main/about.php",
    "contacto"         => ROOT_PATH . "/modules/main/contact.php",
    "servicos"         => ROOT_PATH . "/modules/main/serviceCategories.php",

    // Autenticação e Gestão de Conta
    "login"            => ROOT_PATH . "/modules/main/login.php",
    "registo"          => ROOT_PATH . "/modules/main/customerRegister.php",
    "recuperar-passe"  => ROOT_PATH . "/modules/main/recoverPassword.php"
];

if (array_key_exists($path, $routes)) {
    require_once $routes[$path];
} else {
    http_response_code(404);
    $file404 = ROOT_PATH . "/modules/main/404.php";
    if (file_exists($file404)) {
        require_once $file404;
    } else {
        echo "<h1>404 - Página não encontrada</h1>";
    }
    exit;
}
