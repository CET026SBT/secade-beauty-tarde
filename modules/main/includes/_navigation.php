<?php
$isAuthPage = isset($isAuthPage) ? $isAuthPage : in_array($currentPage, ["login", "registar"]);

$navigationLinks = [
    ["page" => "home",         "url" => "/",         "icon" => "bi-house-door",     "label" => "Home"]
];

$navigationLinks = array_merge($navigationLinks, $isAuthPage ? [] : [
    ["page" => "sobre",        "url" => "/sobre",    "icon" => "bi-info-circle",    "label" => "Acerca"],
    ["page" => "servicos",     "url" => "/servicos", "icon" => "bi-scissors",       "label" => "Serviços"],
    ["page" => "appointments", "url" => "/agendar",  "icon" => "bi-calendar-check", "label" => "Agendar",   "profile" => ["customer"]],
    ["page" => "contacto",     "url" => "/contacto", "icon" => "bi-envelope",       "label" => "Contactos", "profile" => ["customer", "guest"]]
]);
?>