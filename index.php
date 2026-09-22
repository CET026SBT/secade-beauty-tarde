<?php

require_once __DIR__ . "/app/config/config.php";
require_once APP_PATH . "/utils/Session.php";

$path = resolve_request_path(BASE_URL);

if (is_api_request($path)) {
    require_once APP_PATH . "/config/api.php";
    exit;
}

$routes = [
    ""                      => ROOT_PATH . "/modules/main/home.php",
    "home"                  => ROOT_PATH . "/modules/main/home.php",
    "sobre"                 => ROOT_PATH . "/modules/main/about.php",
    "contacto"              => ROOT_PATH . "/modules/main/contact.php",
    "servicos"              => ROOT_PATH . "/modules/main/serviceCategories.php",
    "servicos/:category"    => ROOT_PATH . "/modules/main/services.php",

    "login"                 => ROOT_PATH . "/modules/main/login.php",
    "registo"               => ROOT_PATH . "/modules/main/customerRegister.php",
    "recuperar-passe"       => ROOT_PATH . "/modules/main/recoverPassword.php",

    "perfil"                => ROOT_PATH . "/modules/main/profile.php",
    "agendamentos"          => ROOT_PATH . "/modules/main/appointments.php",
    "agendar"               => ROOT_PATH . "/modules/main/booking.php",
    "agendamento-sucesso"   => ROOT_PATH . "/modules/main/bookingSuccess.php",

    "gestao"                => ROOT_PATH . "/modules/backoffice/appointments.php",
    "gestao/agendamentos"   => ROOT_PATH . "/modules/backoffice/appointments.php",
    "gestao/rotas"          => ROOT_PATH . "/modules/backoffice/routes.php",
    "gestao/servicos"       => ROOT_PATH . "/modules/backoffice/services.php",
    "gestao/fiscal"         => ROOT_PATH . "/modules/backoffice/fiscal.php",
    "gestao/recibos-verdes" => ROOT_PATH . "/modules/backoffice/greenReceipts.php"
];

$matchedFile = match_route_and_extract_params($path, $routes);

if ($matchedFile && file_exists($matchedFile)) {
    require_once $matchedFile;
} else {
    render_404_page();
}

// --------------------------------------------------------------------------------

function resolve_request_path(string $baseUrl): string {
    $requestUri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $baseUrlParsed = parse_url($baseUrl, PHP_URL_PATH);
    $basePath = $baseUrlParsed ? rtrim($baseUrlParsed, "/") : "";

    $path = str_replace($basePath, "", $requestUri);
    return trim($path, "/");
}

function is_api_request(string $path): bool {
    return $path === "api" || isset($_GET["action"]);
}

function match_route_and_extract_params(string $path, array $routes): ?string {
    foreach ($routes as $routePattern => $file) {
        if ($routePattern === "") {
            if ($path === "") return $file;
            continue;
        }

        $routeRegex = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_-]*)/', '([^/]+)', $routePattern);
        $pattern = "#^{$routeRegex}$#i";

        if (preg_match($pattern, $path, $matches)) {
            preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_-]*)/', $routePattern, $paramNames);
            
            array_shift($matches);
            foreach ($paramNames[1] as $index => $paramName) {
                if (isset($matches[$index])) {
                    $_GET[$paramName] = urldecode($matches[$index]);
                }
            }

            return $file;
        }
    }

    return null;
}

function render_404_page(): void {
    http_response_code(404);
    $file404 = ROOT_PATH . "/modules/main/404.php";
    
    if (file_exists($file404)) {
        require_once $file404;
    } else {
        echo "<h1>404 - Página não encontrada</h1>";
    }
    exit;
}
