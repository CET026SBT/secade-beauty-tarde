<?php
/**
 * Mapeia as imagens do .odt a posicao no texto (para saber ONDE esta cada mockup).
 * Uso unico, local. O .odt e de OUTRO projeto: so serve para ideias de apresentacao.
 */
$src = __DIR__ . "/trabalho-lavandaria-info.odt";

$zip = new ZipArchive();
if ($zip->open($src) !== true) { echo "ERRO\n"; exit(1); }
$xml = $zip->getFromName("content.xml");
$zip->close();

// Cada imagem passa a marcador, mantendo a ORDEM do documento
$xml = preg_replace_callback(
    "~<draw:image[^>]*xlink:href=\"([^\"]+)\"[^>]*/?>~i",
    function ($m) { return "\n@@IMG:" . basename($m[1]) . "\n"; },
    $xml
);
$xml = preg_replace("~</text:p>~", "\n", $xml);
$xml = preg_replace("~</text:h>~", "\n", $xml);
$xml = preg_replace("~</table:table-cell>~", "\t", $xml);
$xml = preg_replace("~</table:table-row>~", "\n", $xml);
$xml = strip_tags($xml);
$xml = html_entity_decode($xml, ENT_QUOTES | ENT_XML1, "UTF-8");

$linhas = [];
foreach (preg_split("~\\n+~", $xml) as $line) {
    $line = trim(preg_replace("~[ \t]+~u", " ", $line));
    if ($line !== "") { $linhas[] = $line; }
}

// So o contexto ANTES de cada imagem (o titulo/caption que a identifica)
$total = count($linhas);
foreach ($linhas as $i => $line) {
    if (!str_starts_with($line, "@@IMG:")) { continue; }
    $antes = [];
    for ($k = max(0, $i - 3); $k < $i; $k++) {
        $antes[] = mb_substr($linhas[$k], 0, 90);
    }
    echo str_pad((string)($i + 1), 4, " ", STR_PAD_LEFT) . "| "
        . str_replace("@@IMG:", "", $line)
        . "  <<< " . implode(" / ", $antes) . "\n";
}
echo "\ntotal de linhas: {$total}\n";