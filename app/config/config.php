<?php

date_default_timezone_set("Europe/Lisbon");

if (!defined("ROOT_PATH")) {
    define("ROOT_PATH", dirname(__DIR__, 2));
}
if (!defined("APP_PATH")) {
    define("APP_PATH", ROOT_PATH . "/app");
}

if (!defined("BASE_URL")) {
    $protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http";
    $host = $_SERVER["HTTP_HOST"];
    
    $scriptDir = dirname($_SERVER["SCRIPT_NAME"]); // Retorna /secade-beauty-tarde
    $baseDir = ($scriptDir === "/" || $scriptDir === "\\") ? "" : rtrim($scriptDir, "/\\");
    
    define("BASE_URL", $protocol . "://" . $host . $baseDir);
}

if (!defined("SITE_NAME")) {
    define("SITE_NAME", "Secade Beauty");
}
if (!defined("SITE_PHONE")) {
    define("SITE_PHONE", "+351 939 357 313");
}
if (!defined("SITE_EMAIL")) {
    define("SITE_EMAIL", "secadebeauty@gmail.com");
}
if (!defined("SITE_ADDRESS")) {
    define("SITE_ADDRESS", "Espaço comercial, Praça Joaquim António de Aguiar, 12 a 19, U-5-ag, Évora");
}
if (!defined("SITE_MAP_URL")) {
    define("SITE_MAP_URL", "https://maps.google.com/?q=" . rawurlencode(SITE_ADDRESS));
}
// Taxa de IVA aplicada na apresentação dos valores ao cliente (D-16 · RN-36).
// O catálogo guarda o preço base tributável (sem IVA); a loja mostra o valor com IVA.
if (!defined("IVA_RATE")) {
    define("IVA_RATE", 0.23);
}
// Indicadores públicos do site (contadores do "Sobre nós" e apresentação de serviços).
// Leitura: valor CONTADO na BD (`?action=site-stats`) e, quando ainda não há dados,
// o valor documental abaixo — nunca um número inventado.
// Fontes: catálogo `DataBase.sql` (35 serviços · 3 categorias ·
// 10 cidades do distrito de Évora) e a folha de salários oficial do 1.º trimestre de 2026
// (6 trabalhadores — `Contabilidade Secade Beauty.xlsx`, folha «Custos Funcionários»).
if (!defined("SITE_STATS_FALLBACK")) {
    define("SITE_STATS_FALLBACK", [
        "services"   => 35,
        "categories" => 3,
        "cities"     => 10,
        "team"       => 6,
        "customers"  => 0,
        "reviews"    => 0,
        // Preço mais baixo do catálogo *com IVA* (base 4,07 € → 5,01 €) — D-16 · RN-36.
        "minPrice"   => 5.01
    ]);
}
// Chaves em que a CONTAGEM da BD ainda não é completa: publica-se o valor documental
// enquanto a BD não tiver os registos todos. Hoje só `team`: a folha de salários prova
// 6 trabalhadores e a BD tem 1 registo em `funcionario` (as identidades dos 6 não constam
// de nenhum ficheiro entregue — §24.9). Sem esta lista, a página publicaria "1 profissional".
if (!defined("SITE_STATS_DOCUMENTAL")) {
    define("SITE_STATS_DOCUMENTAL", ["team"]);
}

$requiredScripts = [];

function register_script($scriptName, $module="common") {
    global $requiredScripts;
    
    $scriptFile = $scriptName . ".js";

    $diskPath = ROOT_PATH . "/modules/" . $module . "/js/" . $scriptFile;
    $urlPath = "modules/" . $module . "/js/" . $scriptFile;

    if (file_exists($diskPath) && !in_array($urlPath, $requiredScripts)) {
        $requiredScripts[] = $urlPath;
    }
}
