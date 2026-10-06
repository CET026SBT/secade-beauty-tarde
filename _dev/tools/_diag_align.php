<?php
/**
 * Diagnostico: porque e que a tabela do ponto 3 nao esta alinhada?
 * Imprime as posicoes dos pipes (nao escapados) de cada linha e corre o md-align-tables.
 */
require __DIR__ . '/../tools/_common.php';

$file = '_dev/mapaMentalMVP/analise_backoffice_gestor.md';
$text = readText($file);
$lines = toLines($text);

function pipes(string $line): array
{
    $pos = [];
    $len = strlen($line);
    for ($i = 0; $i < $len; $i++) {
        if ($line[$i] === '\\') { $i++; continue; }
        if ($line[$i] === '|') $pos[] = $i;
    }
    return $pos;
}

echo "=== Tabela do ponto 3 (linhas 425..472) ===\n";
$inCode = false;
$blocks = 0; $starts = [];
$n = count($lines); $i = 0;
while ($i < $n) {
    if (preg_match('/^\s*```/', $lines[$i])) { $inCode = !$inCode; $i++; continue; }
    if (!$inCode && preg_match('/^\s*\|/', $lines[$i])) {
        $j = $i;
        while ($j < $n && preg_match('/^\s*\|/', $lines[$j])) $j++;
        $blocks++;
        $rows = [];
        for ($k = $i; $k < $j; $k++) $rows[] = splitCellsDiag($lines[$k]);
        $cols = max(array_map('count', $rows));
        $sep = preg_match('/^:?-+:?$/', $rows[1][0] ?? '') ? 'SIM' : 'NAO';
        $starts[] = sprintf('  bloco #%-2d  L%-4d..L%-4d  linhas=%-3d  colunas=%-2d  separadora=%s',
            $blocks, $i + 1, $j, $j - $i, $cols, $sep);
        // guarda o bloco do ponto 3
        if ($i + 1 <= 425 && 425 <= $j) {
            echo "  -> este e o bloco do ponto 3\n";
            foreach ($rows as $idx => $r) {
                echo sprintf("  L%-4d ncell=%-2d larguras=[%s]\n", $i + 1 + $idx, count($r),
                    implode(',', array_map('strWidth', $r)));
            }
        }
        $i = $j;
        continue;
    }
    $i++;
}
echo "=== Todos os blocos de tabela detectados: {$blocks} ===\n";
echo implode("\n", $starts), "\n";

function splitCellsDiag(string $line): array
{
    $t = trim($line);
    $t = preg_replace('/^\|/', '', $t);
    $len = strlen($t);
    if ($len > 0 && $t[$len - 1] === '|' && ($len < 2 || $t[$len - 2] !== '\\')) $t = substr($t, 0, $len - 1);
    $cells = []; $cur = ''; $len = strlen($t);
    for ($i = 0; $i < $len; $i++) {
        $ch = $t[$i];
        if ($ch === '\\' && $i + 1 < $len) { $cur .= $ch . $t[$i + 1]; $i++; continue; }
        if ($ch === '|') { $cells[] = $cur; $cur = ''; continue; }
        $cur .= $ch;
    }
    $cells[] = $cur;
    return array_map('trim', $cells);
}