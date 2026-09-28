<?php
/**
 * Despeja o conteudo do .ods "Secade Duracao Servicos 1.ods" em texto local (uso unico).
 */
$src = __DIR__ . "/Secade Duração Serviços 1.ods";
if (!file_exists($src)) { echo "ERRO: nao encontrei {$src}\n"; exit(1); }

$zip = new ZipArchive();
if ($zip->open($src) !== true) { echo "ERRO: nao abri o .ods\n"; exit(1); }

$xml = $zip->getFromName("content.xml");
$zip->close();

$xml = preg_replace("~</table:table-cell>~", "\t", $xml);
$xml = preg_replace("~</table:table-row>~", "\n", $xml);
$xml = preg_replace("~</table:table>~", "\n=== FIM DA FOLHA ===\n", $xml);
$text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, "UTF-8");

$lines = preg_split("~\n~", $text);
$out = "";
$i = 0;
foreach ($lines as $line) {
    $line = trim(preg_replace("~\s+~u", " ", $line));
    if ($line === "") continue;
    $i++;
    $out .= str_pad((string)$i, 3, " ", STR_PAD_LEFT) . "| " . $line . "\n";
}

$dst = __DIR__ . "/.tmp-xlsx/duracoes-servicos.txt";
file_put_contents($dst, $out);
echo "linhas: {$i}\nficheiro: {$dst}\n";