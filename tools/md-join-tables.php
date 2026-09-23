<?php
/**
 * md-join-tables.php — junta blocos de tabela que ficaram PARTIDOS por uma linha
 * em branco (ou por outra linha nao-tabela).
 *
 * PORQUE EXISTE
 * -------------
 * Em markdown, uma linha em branco FECHA o bloco da tabela. Se o bloco seguinte nao
 * tiver o divisor logo apos o cabecalho, ele nao e uma tabela valida e os
 * formatadores (md-align-tables, md-wrap-tables, widthcheck) ignoram-no -- a tabela
 * fica "meio formatada". Aconteceu duas vezes neste projeto, sempre por edicao manual.
 *
 * REGRA (conservadora, para nao juntar tabelas legitimamente separadas)
 * --------------------------------------------------------------------
 * So junta quando TODAS as condicoes se verificam:
 *   1. o bloco ANTERIOR e uma tabela markdown valida (2.a linha = divisor);
 *   2. o bloco SEGUINTE e INVALIDO como tabela isolada (2.a linha != divisor);
 *   3. entre os dois ha exactamente UMA linha nao-tabela;
 *   4. os dois blocos tem o mesmo numero de colunas.
 * Se o bloco seguinte fosse uma tabela valida, ficaria intacto (sao duas tabelas).
 *
 * Uso:
 *   php tools/md-join-tables.php <ficheiro.md> [--write]
 * Sem --write corre em dry-run.
 *
 * Exit: 0 = ok (nada a fazer ou aplicado/previsto), 1 = erro.
 */

require __DIR__ . "/_common.php";

$argvAll = $argv;
array_shift($argvAll);
$write = hasFlag($argvAll, "--write");
$file  = posArg(positionals($argv), 0);

if (!$file) {
    help("php tools/md-join-tables.php <ficheiro.md> [--write]", [
        "Dry-run por omissao; use --write para gravar.",
        "Junta blocos de tabela partidos por uma linha em branco.",
    ]);
}

$text  = readText($file);
$eol   = detectEol($text);
$lines = toLines($text);
$n     = count($lines);

/** A linha e uma linha de tabela? */
$isRow = fn(string $l): bool => (bool) preg_match('/^\s*\|/', $l);

/** A segunda linha do bloco e um divisor (linha separadora valida)? */
$hasDivider = function (array $block): bool {
    if (count($block) < 2) return false;
    $cells = trim($block[1]);
    $cells = preg_replace('/^\|/', '', $cells);
    $len = strlen($cells);
    if ($len > 0 && $cells[$len - 1] === '|') $cells = substr($cells, 0, $len - 1);
    foreach (explode('|', $cells) as $c) {
        if (!preg_match('/^:?-+:?$/', trim($c))) return false;
    }
    return true;
};

/** Numero de separadores NAO escapados. */
$pipes = function (string $l): int {
    $c = 0; $len = strlen($l);
    for ($i = 0; $i < $len; $i++) { if ($l[$i] === '\\') { $i++; continue; } if ($l[$i] === '|') $c++; }
    return $c;
};

// ---- recolher blocos de linhas de tabela (com o intervalo que os separa)
$blocks = [];
$cur = [];
$start = 0;
for ($i = 0; $i <= $n; $i++) {
    $isEnd = ($i === $n);
    $row   = !$isEnd && $isRow($lines[$i]);
    if ($row) {
        if (!$cur) $start = $i;
        $cur[] = ['i' => $i, 't' => $lines[$i]];
        continue;
    }
    if ($cur) { $blocks[] = ['start' => $start, 'end' => $i - 1, 'rows' => $cur]; $cur = []; }
}

$joined = 0;
$report = [];
$drop = [];                      // indices de linhas a remover

for ($b = 1; $b < count($blocks); $b++) {
    $prev = $blocks[$b - 1];
    $next = $blocks[$b];

    $textsPrev = array_column($prev['rows'], 't');
    $textsNext = array_column($next['rows'], 't');

    if (!$hasDivider($textsPrev)) continue;                 // anterior nao e tabela valida
    if ($hasDivider($textsNext)) continue;                  // seguinte ja e valida: sao duas tabelas
    if ($next['start'] - $prev['end'] !== 2) continue;      // tem de haver exactamente 1 linha entre eles
    $gap = $next['start'] - 1;
    if (trim($lines[$gap]) !== '') continue;                // essa linha tem de estar em branco

    $colsPrev = $pipes($textsPrev[0]);
    $colsNext = $pipes($textsNext[0]);
    if ($colsPrev !== $colsNext) continue;                  // colunas diferentes: nao sao a mesma tabela

    $drop[] = $gap;
    $joined++;
    $report[] = sprintf("L%d-%d + L%d-%d: junta %d linha(s) a tabela anterior (%d colunas)",
        $prev['start'] + 1, $prev['end'] + 1, $next['start'] + 1, $next['end'] + 1,
        count($textsNext), $colsPrev);
}

out("Ficheiro      : {$file}");
out("Blocos de tabela: " . count($blocks));
out("Blocos partidos: {$joined}");

if ($report) {
    out("");
    out($write ? "JUNTADOS:" : "A JUNTAR (dry-run):");
    foreach ($report as $r) out("  - {$r}");
}

if ($joined === 0) {
    out("");
    out("RESULTADO: nada a fazer (nenhum bloco partido).");
    exit(0);
}

$outLines = [];
foreach ($lines as $i => $l) { if (!in_array($i, $drop, true)) $outLines[] = $l; }

if (!isUtf8(implode("\n", $outLines))) fail("abortado — resultado nao e UTF-8 valido", 1);

if (!$write) {
    out("");
    out("RESULTADO: dry-run — {$joined} bloco(s) seriam juntados. Use --write.");
    exit(0);
}

writeText($file, implode("\n", $outLines), $eol);
out("");
out("RESULTADO: GRAVADO ({$joined} bloco(s) juntados).");
exit(0);