<?php
/**
 * Despeja o TEXTO COMPLETO do .odt de referencia num .txt local (uso unico).
 * O .odt e de OUTRO projeto: serve apenas para ideias de apresentacao (graficos/KPI).
 */
$src = __DIR__ . "/trabalho-lavandaria-info.odt";
$dst = __DIR__ . "/.tmp-odt-dump.txt";

$zip = new ZipArchive();
if ($zip->open($src) !== true) { echo "ERRO: nao abri o .odt\n"; exit(1); }

$xml = $zip->getFromName("content.xml");
$zip->close();

$xml = preg_replace("~</text:p>~", "\n", $xml);
$xml = preg_replace("~</text:h>~", "\n", $xml);
$xml = preg_replace("~</table:table-cell>~", "\t", $xml);
$xml = preg_replace("~</table:table-row>~", "\n", $xml);
$xml = strip_tags($xml);
$xml = html_entity_decode($xml, ENT_QUOTES | ENT_XML1, "UTF-8");

$lines = preg_split("~\\n+~", $xml);
$out = "";
$i = 0;
foreach ($lines as $line) {
    $line = trim(preg_replace("~\\s+~u", " ", $line));
    if ($line === "") continue;
    $i++;
    $out .= str_pad((string)$i, 4, " ", STR_PAD_LEFT) . "| " . $line . "\n";
}
file_put_contents($dst, $out);
echo "linhas: {$i}\nficheiro: {$dst}\n";