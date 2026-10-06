<?php
/**
 * Extrai o TEXTO do .odt (content.xml) para identificar as mockups por ordem.
 * Uso unico, local (o resultado fica em _dev/, ignorado pelo Git).
 */
$src = __DIR__ . "/trabalho-lavandaria-info.odt";
$zip = new ZipArchive();
if ($zip->open($src) !== true) { echo "ERRO\n"; exit(1); }

$xml = $zip->getFromName("content.xml");
$zip->close();

// Paragrafos: cada </text:p> vira mudanca de linha
$xml = preg_replace("~</text:p>~", "\n", $xml);
$xml = preg_replace("~</text:h>~", "\n\n", $xml);
$xml = strip_tags($xml);
$xml = html_entity_decode($xml, ENT_QUOTES | ENT_XML1, "UTF-8");
$lines = preg_split("~\n+~", $xml);

$i = 0;
foreach ($lines as $line) {
    $line = trim(preg_replace("~\s+~u", " ", $line));
    if ($line === "") continue;
    $i++;
    if ($i > 220) { echo "... (truncado)\n"; break; }
    echo str_pad((string)$i, 4, " ", STR_PAD_LEFT) . "| " . $line . "\n";
}