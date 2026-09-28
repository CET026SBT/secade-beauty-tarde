<?php
/**
 * Dumper local de .xlsx (temporario, Git-ignored).
 * Uso: php .tmp-xlsx-dump.php "ficheiro.xlsx" [nomeFolha]
 * Le xl/workbook.xml (nomes+ordem), xl/sharedStrings.xml e xl/worksheets/sheetN.xml.
 * Sem dependencias: ZipArchive + DOM.
 */
$file = $argv[1] ?? null;
if (!$file || !is_file($file)) { fwrite(STDERR, "sem ficheiro\n"); exit(1); }

$zip = new ZipArchive();
if ($zip->open($file) !== true) { fwrite(STDERR, "nao abriu zip\n"); exit(1); }

// 1) sharedStrings
$shared = [];
$ss = $zip->getFromName('xl/sharedStrings.xml');
if ($ss !== false && $ss !== '') {
    $d = new DOMDocument();
    $d->loadXML($ss);
    foreach ($d->getElementsByTagName('si') as $si) {
        $txt = '';
        foreach ($si->getElementsByTagName('t') as $t) { $txt .= $t->textContent; }
        $shared[] = $txt;
    }
}

// 2) mapa relId -> target das folhas
$rels = [];
$r = $zip->getFromName('xl/_rels/workbook.xml.rels');
if ($r !== false) {
    $d = new DOMDocument(); $d->loadXML($r);
    foreach ($d->getElementsByTagName('Relationship') as $rel) {
        $rels[$rel->getAttribute('Id')] = $rel->getAttribute('Target');
    }
}
$sheets = [];
$wb = $zip->getFromName('xl/workbook.xml');
$d = new DOMDocument(); $d->loadXML($wb);
foreach ($d->getElementsByTagName('sheet') as $sh) {
    $rid = $sh->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
    $tgt = $rels[$rid] ?? '';
    $tgt = ltrim(str_replace('xl/', '', $tgt), '/');
    $sheets[] = [$sh->getAttribute('name'), 'xl/' . $tgt];
}

$only = $argv[2] ?? null;
echo "FICHEIRO: " . basename($file) . "\n";
echo "FOLHAS (" . count($sheets) . "): ";
echo implode(' | ', array_map(fn($s) => $s[0], $sheets)) . "\n\n";

// 3) formatos numericos: detetar datas (numFmtId 14..22, 45..47, ou format com 'd'/'m'/'y')
$dateFmt = [];
$st = $zip->getFromName('xl/styles.xml');
if ($st !== false) {
    $d = new DOMDocument(); $d->loadXML($st);
    $custom = [];
    foreach ($d->getElementsByTagName('numFmt') as $nf) {
        $custom[(int)$nf->getAttribute('numFmtId')] = $nf->getAttribute('formatCode');
    }
    $xfs = $d->getElementsByTagName('cellXfs')->item(0);
    if ($xfs) {
        $i = 0;
        foreach ($xfs->getElementsByTagName('xf') as $xf) {
            $id = (int)$xf->getAttribute('numFmtId');
            $isDate = ($id >= 14 && $id <= 22) || ($id >= 45 && $id <= 47);
            if (!$isDate && isset($custom[$id])) {
                $c = $custom[$id];
                $isDate = (strpos($c, 'y') !== false && strpos($c, 'd') !== false) || strpos($c, 'mmm') !== false;
            }
            $dateFmt[$i] = $isDate;
            $i++;
        }
    }
}

function colLetter(string $ref): string { return preg_replace('/\d+/', '', $ref); }

foreach ($sheets as [$name, $path]) {
    if ($only !== null && $name !== $only) { continue; }
    echo str_repeat('=', 100) . "\nFOLHA: $name   ($path)\n" . str_repeat('=', 100) . "\n";
    $xml = $zip->getFromName($path);
    if ($xml === false || $xml === '') { echo "(vazia)\n\n"; continue; }
    $d = new DOMDocument(); $d->loadXML($xml);
    $rows = [];
    $maxCol = 0;
    foreach ($d->getElementsByTagName('row') as $row) {
        $rn = (int)$row->getAttribute('r');
        $cells = [];
        foreach ($row->getElementsByTagName('c') as $c) {
            $ref = $c->getAttribute('r');
            $col = colLetter($ref);
            $idx = 0;
            $len = strlen($col);
            for ($i = 0; $i < $len; $i++) { $idx = $idx * 26 + (ord($col[$i]) - 64); }
            $idx--;
            if ($idx > $maxCol) { $maxCol = $idx; }
            $t = $c->getAttribute('t');
            $s = (int)$c->getAttribute('s');
            $vNode = $c->getElementsByTagName('v')->item(0);
            $isNode = $c->getElementsByTagName('is')->item(0);
            $val = '';
            $isDate = false;
            if ($t === 's' && $vNode) {
                $val = $shared[(int)$vNode->textContent] ?? '';
            } elseif ($t === 'inlineStr' && $isNode) {
                foreach ($isNode->getElementsByTagName('t') as $tt) { $val .= $tt->textContent; }
            } elseif ($vNode) {
                $raw = $vNode->textContent;
                if ($t === 'str') {
                    // Celula de FORMULA com resultado textual: o <v> ja e o texto final.
                    $val = $raw;
                } elseif (($dateFmt[$s] ?? false) && is_numeric($raw)) {
                    $isDate = true;
                    $ts = ((float)$raw - 25569) * 86400;
                    $val = gmdate('Y-m-d', (int)round($ts));
                } elseif (!is_numeric($raw)) {
                    // Texto simples sem tipo declarado: nunca converter para numero (dava "0").
                    $val = $raw;
                } else {
                    $num = (float)$raw;
                    $val = ($num == (int)$num && abs($num) < 1e15)
                        ? number_format($num, 0, ',', '')
                        : rtrim(rtrim(number_format($num, 6, ',', ''), '0'), ',');
                }
            }
            $cells[$idx] = $val;
        }
        if ($cells) {
            ksort($cells);
            $rows[$rn] = $cells;
        }
    }
    ksort($rows);
    foreach ($rows as $rn => $cells) {
        $out = [];
        for ($i = 0; $i <= $maxCol; $i++) {
            $o = $cells[$i] ?? '';
            $out[] = $o;
        }
        // so imprime linhas com conteudo
        if (implode('', $out) === '') { continue; }
        echo str_pad((string)$rn, 4) . ': ';
        $parts = [];
        foreach ($out as $i => $o) { if ($o !== '') { $parts[] = chr(65 + ($i % 26)) . ($i >= 26 ? '#' : '') . '=' . $o; } }
        echo implode('  |  ', $parts) . "\n";
    }
    echo "\n";
}
$zip->close();