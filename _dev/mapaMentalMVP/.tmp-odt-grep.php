<?php
/**
 * Garimpa SO os termos de APRESENTACAO (graficos/KPI) em dois ficheiros locais.
 * O .odt e de OUTRO projeto: aqui interessa apenas como se apresentam dados.
 */
$alvos = [
    __DIR__ . "/.tmp-odt-dump.txt",
    __DIR__ . "/analise_backoffice_gestor.md",
];
$re = "/gr\p{L}ficos?|KPI|circular|barras|pizza|percentagem|evolu\p{L}*|ranking|donut|"
    . "comparativ|Chart\.js|indicador/iu";
$limite = 240;

foreach ($alvos as $alvo) {
    echo "=== " . basename($alvo) . " ===\n";
    $linhas = file($alvo, FILE_IGNORE_NEW_LINES);
    foreach ($linhas as $n => $linha) {
        if (!preg_match($re, $linha)) { continue; }
        $txt = trim(preg_replace("~\s+~u", " ", $linha));
        echo str_pad((string)($n + 1), 5, " ", STR_PAD_LEFT) . "| "
            . mb_substr($txt, 0, $limite) . "\n";
    }
    echo "\n";
}