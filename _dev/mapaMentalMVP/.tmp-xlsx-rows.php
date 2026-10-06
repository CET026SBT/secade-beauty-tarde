<?php
/** Dump cru das linhas de uma folha .xlsx (temporario). Uso: php .tmp-xlsx-rows.php file.xlsx sheet2.xml */
$file = $argv[1]; $part = $argv[2];
$z = new ZipArchive(); $z->open($file);
$xml = $z->getFromName('xl/worksheets/' . $part);
echo "PARTE: $part  bytes=" . strlen($xml) . "\n";
$d = new DOMDocument(); $d->loadXML($xml);
$n = 0;
foreach ($d->getElementsByTagName('row') as $r) {
    $n++;
    $s = $d->saveXML($r);
    echo 'ROW r=' . $r->getAttribute('r') . ' cells=' . $r->getElementsByTagName('c')->length . "\n";
    echo '   ' . substr($s, 0, 600) . "\n";
}
echo "TOTAL ROWS = $n\n";
$z->close();