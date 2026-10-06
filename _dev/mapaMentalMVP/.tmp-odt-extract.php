<?php
/**
 * Extrator temporario das imagens (mockups) do .odt de referencia.
 * Uso unico, local: o ficheiro e o resultado ficam em _dev/ (ignorado pelo Git).
 */
$dir = __DIR__;
$src = $dir . "/trabalho-lavandaria-info.odt";

if (!file_exists($src)) {
    echo "ERRO: nao encontrei {$src}\n";
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($src) !== true) {
    echo "ERRO: nao consegui abrir o .odt\n";
    exit(1);
}

$out = $dir . "/.tmp-odt";
if (!is_dir($out)) {
    mkdir($out, 0777, true);
}

$total = 0;
for ($i = 0; $i < $zip->numFiles; $i++) {
    $stat = $zip->statIndex($i);
    $name = $stat["name"];

    if (str_starts_with($name, "Pictures/")) {
        file_put_contents($out . "/" . basename($name), $zip->getFromIndex($i));
        echo "IMAGEM {$name} -> " . $stat["size"] . " bytes\n";
        $total++;
    }
}

$zip->close();
echo "total de imagens: {$total}\n";
echo "destino: {$out}\n";