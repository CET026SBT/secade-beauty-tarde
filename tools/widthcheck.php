<?php
/**
 * widthcheck.php (instrumento de verificacao, so leitura)
 * Para cada bloco de tabela markdown, mostra o conjunto de LARGURAS DE ECRA das
 * suas linhas. Uniforme (1 valor) = pipes alinhados. Usa a metrica das tools.
 *
 * Uso: php widthcheck.php <ficheiro.md> [limite]
 */
require 'C:/laragon/www/secade-beauty-tarde/tools/_common.php';

$file  = $argv[1] ?? 'especificacao_mvp.md';
$lines = toLines(readText($file));
$limit = (int) ($argv[2] ?? 0);
if ($limit <= 0) {
    // Pragmas suportados no TOPO do ficheiro (primeiras 10 linhas, mesma convencao
    // do md-wrap-tables e do encoding-check):
    //   <!-- md-widths:max=NNN -->   ou   <!-- md-wrap-tables:max=NNN -->
    $limit = 200;
    $head  = implode("\n", array_slice($lines, 0, 10));
    if (preg_match('/md-(?:widths|wrap-tables):max=(\d+)/', $head, $pm)) $limit = max(40, (int) $pm[1]);
}
$inCode = false;
$blk = []; $blkStart = 0; $nTables = 0; $bad = 0;

$report = function (array $blk, int $start) use ($limit, &$nTables, &$bad): void {
    if (count($blk) < 2) return;
    $nTables++;

    // Um bloco cuja 2.a linha NAO e um divisor nao e uma tabela valida: e sinal de
    // tabela PARTIDA (normalmente por uma linha em branco pelo meio) ou de linhas de
    // tabela escritas a mao sem cabecalho. Sem divisor, os restantes utilitarios
    // ignoram o bloco -- e este e o unico sitio onde isso se torna visivel.
    $divider = function (string $l): bool {
        $c = trim($l);
        $c = preg_replace('/^\|/', '', $c);
        $len = strlen($c);
        if ($len > 0 && $c[$len - 1] === '|') $c = substr($c, 0, $len - 1);
        foreach (explode('|', $c) as $cell) {
            if (!preg_match('/^:?-+:?$/', trim($cell))) return false;
        }
        return true;
    };
    $ok = $divider($blk[1][1]);
    if (!$ok) {
        $bad++;
        printf("  L%-5d %2d linhas  SEM DIVISOR na 2.a linha -> bloco nao e tabela valida "
             . "(tabela partida? ver tools/md-join-tables.php)\n", $start, count($blk));
        return;
    }

    $ws = [];
    $max = 0;
    foreach ($blk as [$ln, $t]) { $w = strWidth($t); $ws[$w] = true; if ($w > $max) $max = $w; }
    $distinct = count($ws);
    $over = $max > $limit;
    if ($distinct > 1 || $over) {
        $bad++;
        printf("  L%-5d %2d linhas  larguras=%s  %s%s\n",
            $start, count($blk), implode(',', array_keys($ws)),
            $distinct > 1 ? 'PIPES DESALINHADOS ' : '',
            $over ? "(max {$max} > {$limit})" : '');
    }
};

foreach ($lines as $i => $l) {
    if (preg_match('/^\s*```/', $l)) { $inCode = !$inCode; if ($blk) { $report($blk, $blkStart); $blk = []; } continue; }
    if (!$inCode && preg_match('/^\s*\|/', $l)) {
        if (!$blk) $blkStart = $i + 1;
        $blk[] = [$i + 1, $l];
    } elseif ($blk) { $report($blk, $blkStart); $blk = []; }
}
if ($blk) $report($blk, $blkStart);

printf("RESULTADO: %d tabela(s), %d com problema%s\n", $nTables, $bad, $bad ? '' : ' (tudo uniforme e dentro do limite)');
out("Limite          : {$limit} colunas" . ($limit !== 200 ? ' (pragma do ficheiro)' : ''));
out("Tabelas desalinhadas: {$bad}");     // linha usada pelo health-check.php (dry-run)
exit($bad === 0 ? 0 : 1);