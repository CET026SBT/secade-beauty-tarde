<?php
$requiredScripts = [];

function register_script($scriptName) {
    global $requiredScripts;
    $path = "js/" . $scriptName . ".js";

    if (!in_array($path, $requiredScripts) && file_exists($path)) {
        $requiredScripts[] = $path;
    }
}

// Global Configuration
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
