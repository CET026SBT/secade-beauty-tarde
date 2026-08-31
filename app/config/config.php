<?php

date_default_timezone_set('Europe/Lisbon');

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
}
if (!defined('APP_PATH')) {
    define('APP_PATH', ROOT_PATH . '/app');
}

if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']); // Retorna /CET026/secade-beauty-tarde
    $baseDir = ($scriptDir === '/' || $scriptDir === '\\') ? '' : rtrim($scriptDir, '/\\');
    
    define('BASE_URL', $protocol . '://' . $host . $baseDir);
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Secade Beauty');
}
if (!defined('SITE_PHONE')) {
    define('SITE_PHONE', '+351 939 357 313');
}
if (!defined('SITE_EMAIL')) {
    define('SITE_EMAIL', 'geral@secadebeauty.com.pt');
}
if (!defined('SITE_ADDRESS')) {
    define('SITE_ADDRESS', 'Rua do centro de formacao, 123');
}

$requiredScripts = [];

function register_script($scriptName, $module='common') {
    global $requiredScripts;
    
    $scriptFile = $scriptName . '.js';

    if ($module === 'common') {
        $diskPath = ROOT_PATH . '/common/js/' . $scriptFile;
        $urlPath = 'common/js/' . $scriptFile;
    } else {
        $diskPath = ROOT_PATH . '/modules/' . $module . '/js/' . $scriptFile;
        $urlPath = 'modules/' . $module . '/js/' . $scriptFile;
    }

    if (file_exists($diskPath) && !in_array($urlPath, $requiredScripts)) {
        $requiredScripts[] = $urlPath;
    }
}
