<?php
/** Dumper local de .docx (temporario, Git-ignored). Uso: php .tmp-docx-text.php "f.docx" */
$file = $argv[1];
$z = new ZipArchive();
$z->open($file);
$xml = $z->getFromName('word/document.xml');
$d = new DOMDocument();
$d->loadXML($xml);
$xpath = new DOMXPath($d);
$xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
echo "FICHEIRO: " . basename($file) . "\n";
$n = 0;
foreach ($xpath->query('//w:p') as $p) {
    $txt = '';
    foreach ($xpath->query('.//w:t', $p) as $t) { $txt .= $t->textContent; }
    $txt = trim($txt);
    if ($txt === '') { continue; }
    $n++;
    echo $n . '| ' . $txt . "\n";
}
echo "\nTOTAL PARAGRAFOS COM TEXTO = $n\n";
$z->close();