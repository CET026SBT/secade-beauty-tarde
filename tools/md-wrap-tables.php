<?php
/**
 * md-wrap-tables.php — quebra o texto das celulas das tabelas markdown para que
 * NENHUMA linha da tabela exceda um numero maximo de colunas (default 200).
 *
 * PORQUE EXISTE
 * -------------
 * Tabelas com muitas colunas e textos longos ficam com 300-560 colunas de largura.
 * Em ecras estreitos o editor faz wrap visual, quebrando a estrutura da tabela.
 * Este utilitario reduz a largura redistribuindo-a e quebrando o texto das celulas
 * em varias linhas fisicas, mantendo tudo nivelado.
 *
 * COMO (generico, sem assumir o numero de colunas)
 *  - O separador de colunas e o caracter `|` (pipes ESCAPADOS `\|` sao conteudo).
 *  - Mede a largura NATURAL de cada coluna (largura de ecra, emoji contam 2).
 *  - Calcula a largura da linha: `Soma(w) + 3*n + 1` (formato `| a | b |`).
 *  - Se exceder o maximo, encolhe sucessivamente a coluna MAIS LARGA ate caber
 *    (nunca abaixo de --min colunas), preservando as colunas curtas.
 *  - Quebra o texto de cada celula a largura da sua coluna e preenche as outras
 *    celulas com espacos (ou com as suas proprias continuacoes) -> tudo nivelado.
 *  - Uma linha cujas celulas sao TODAS `:?-+:?` e um DIVISOR DE LINHAS (o `| --- |`
 *    do markdown): e emitida uma unica vez, a largura certa, e nunca quebrada.
 *  - Tabelas que ja caibam no maximo ficam intactas.
 *
 * SEGURANCA: nunca altera texto. Concatena o texto de todas as celulas antes/depois
 * (espacos normalizados) e ABORTA se diferir. So muda espacos e quebras de linha.
 *
 * Uso:
 *   php tools/md-wrap-tables.php <ficheiro.md> [--write] [--max=200] [--min=8] [--verbose]
 * Sem --write corre em dry-run.
 *
 * Exit: 0 = ok (idempotente), 1 = erro/problema detetado.
 */

require __DIR__ . "/_common.php";

$argvAll = $argv;
array_shift($argvAll);
$write   = hasFlag($argvAll, "--write");
$verbose = hasFlag($argvAll, "--verbose");

$maxArg  = optValue($argvAll, "--max");
$maxCols = $maxArg !== null ? max(40, (int) $maxArg) : 200;
$minArg  = optValue($argvAll, "--min");
$minCol  = $minArg !== null ? max(4, (int) $minArg) : 8;

$file = posArg(positionals($argv), 0);
if (!$file) {
    help("php tools/md-wrap-tables.php <ficheiro.md> [--write] [--max=200] [--min=8] [--verbose]", [
        "Dry-run por omissao; use --write para gravar.",
        "Ex.: php tools/md-wrap-tables.php duvidas.md --write",
        "--max=N : largura maxima da LINHA da tabela, de ponta a ponta (default 200; aceita o pragma <!-- md-wrap-tables:max=N --> do ficheiro).",
        "--min=N : largura minima de cada coluna (default 8).",
        "Nunca altera texto -- so espacos e quebras de linha. Nunca parte palavras a meio.",
    ]);
}

// ------------------------------------------------------------------ helpers

/** Divide a linha nas celulas, removendo os pipes extremos.
 *  Pipes ESCAPADOS (`\|`) sao conteudo, nao separadores. */
function splitCells(string $line): array
{
    $t = trim($line);
    if ($t !== '' && $t[0] === '|') $t = substr($t, 1);
    $len = strlen($t);
    if ($len > 0 && $t[$len - 1] === '|' && ($len < 2 || $t[$len - 2] !== '\\')) {
        $t = substr($t, 0, $len - 1);
    }

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

/** Todas as celulas sao divisorias (`-`, `---`, `:--`, `--:`, `:-:`)? */
function isDivider(array $cells): bool
{
    if (!$cells) return false;
    foreach ($cells as $c) if (!preg_match('/^:?-+:?$/', $c)) return false;
    return true;
}

/** Largura da linha de uma tabela: `| a | b |` = Soma(w) + 3*n + 1. */
function fullWidth(array $w): int
{
    return array_sum($w) + 3 * count($w) + 1;
}

/**
 * Texto normalizado POR COLUNA (para comparar antes/depois).
 * Necessario porque a quebra cria linhas fisicas extra: concatenar as celulas
 * linha-a-linha mudaria a ordem. Por coluna, a ordem mantem-se.
 */
function colText(array $rows, array $isDiv): array
{
    $cols = [];
    foreach ($rows as $k => $r) {
        if ($isDiv[$k]) continue;
        foreach ($r as $c => $v) {
            $cols[$c] = ($cols[$c] ?? '') . ' ' . trim($v);
        }
    }
    $out = [];
    foreach ($cols as $c => $v) $out[$c] = trim(preg_replace('/\s+/u', ' ', $v));
    return $out;
}

/**
 * Divide em ATOMOS: palavras, mas mantendo juntos os spans de formatacao inline
 * (**negrito**, __negrito__, `codigo`, *italico*, _italico_, [texto](url)).
 * Sem isto, `**fonte ... persistente**` era partido entre linhas e o markdown
 * renderizava os `**` como literais.
 */
function atoms(string $text): array
{
    $re = '/(\*\*[^*]+\*\*|__[^_]+__|`[^`]+`|\*[^*\s][^*]*\*|_[^_\s][^_]*_'
        . '|\[[^\]]+\]\([^)]+\)|\S+)([ \t]*)/u';
    if (!preg_match_all($re, $text, $m, PREG_SET_ORDER)) return [];

    // Gera os atomos e COLA ao anterior os que NAO tinham espaco antes deles
    // (ex.: `**L.§2.1.3**:` e indivisivel -- nao pode ganhar um espaco pelo caminho).
    $out = [];
    $spacedBefore = true;                              // inicio do texto
    foreach ($m as $s) {
        if ($out && !$spacedBefore) $out[count($out) - 1] .= $s[1];
        else                        $out[] = $s[1];
        $spacedBefore = ($s[2] !== '');                // espaco DEPOIS deste atomo?
    }
    return $out;
}

/** Quebra um texto em linhas de no maximo $w colunas (por atomos; hard-break se preciso). */
function wrapText(string $text, int $w): array
{
    $text = trim($text);
    if ($text === '') return [''];
    if (strWidth($text) <= $w) return [$text];

    $lines = []; $cur = '';
    foreach (atoms($text) as $atom) {
        while (strWidth($atom) > $w) {                 // atomo maior que a coluna
            if ($cur !== '') { $lines[] = $cur; $cur = ''; }
            $chunk = ''; $cw = 0;
            foreach (preg_split('//u', $atom, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
                $c = strWidth($ch);
                if ($cw + $c > $w) break;
                $chunk .= $ch; $cw += $c;
            }
            if ($chunk === '') $chunk = mb_substr($atom, 0, 1, 'UTF-8');
            $lines[] = $chunk;
            $atom = mb_substr($atom, mb_strlen($chunk, 'UTF-8'), null, 'UTF-8');
        }
        if ($atom === '') continue;
        if ($cur === '') { $cur = $atom; continue; }
        if (strWidth($cur) + 1 + strWidth($atom) <= $w) $cur .= ' ' . $atom;
        else { $lines[] = $cur; $cur = $atom; }
    }
    if ($cur !== '') $lines[] = $cur;
    return $lines ?: [''];
}

/**
 * Encolhe as colunas MAIS LARGAS ate caber em $max.
 * $floor[c] = piso de cada coluna: nunca abaixo do ATOMO mais longo dessa coluna,
 * para nao partir spans de formatacao inline (`**negrito**`, `` `codigo` ``, ...).
 */
function shrink(array $natural, array $floor, int $max, int $minCol): array
{
    $n      = count($natural);
    $budget = $max - (3 * $n + 1);                 // Soma(w) <= budget
    if ($budget < 1) $budget = 1;

    $fl = [];
    foreach ($natural as $c => $v) $fl[$c] = max($minCol, min($v, (int) ($floor[$c] ?? 0)));

    // Pisos NAO sao relaxados. Se nao couberem no orcamento, a tabela fica mais larga
    // do que --max e o relatorio di-lo (seccao "acima do limite mesmo com palavras
    // intactas"). Relaxar um piso abaixo do ATOMO mais longo da coluna obrigava o
    // wrapText a partir uma palavra ao meio ("edi"/"taveis"); o teste de integridade
    // de texto detetava-o e a tabela ficava por formatar -- metade formatada, metade
    // nao, que era exatamente o defeito reportado.
    $w = $natural;
    $guard = 0;
    while (array_sum($w) > $budget && $guard++ < 200000) {
        $ix = -1; $mx = -1;
        foreach ($w as $c => $v) if ($v > $fl[$c] && $v > $mx) { $mx = $v; $ix = $c; }
        if ($ix < 0) break;                        // ninguem pode encolher mais
        $w[$ix] = max($fl[$ix], $mx - 1);
    }
    return $w;
}

/** Reconstroi a linha divisoria com a largura final de cada coluna. */
function renderDivider(array $cells, array $w): string
{
    $parts = [];
    foreach ($cells as $c => $v) {
        $left  = str_starts_with($v, ':');
        $right = str_ends_with($v, ':');
        $inner = max(1, $w[$c] - ($left ? 1 : 0) - ($right ? 1 : 0));
        $parts[] = ($left ? ':' : '') . str_repeat('-', $inner) . ($right ? ':' : '');
    }
    return '| ' . implode(' | ', $parts) . ' |';
}

/**
 * Processa um bloco de linhas de tabela.
 * Devolve [novasLinhas, info]. info['wrapped'] = true se quebrou.
 */
function wrapTable(array $block, int $max, int $minCol): array
{
    $rows = [];
    foreach ($block as $l) $rows[] = splitCells($l);

    $nCols = 0;
    foreach ($rows as $r) $nCols = max($nCols, count($r));
    if ($nCols < 1) return [$block, ['skip' => 'sem colunas']];
    foreach ($rows as $k => $r) {
        $rows[$k] = array_pad(array_slice($r, 0, $nCols), $nCols, '');
    }

    // Tabela markdown valida = divisor de linhas logo apos o cabecalho
    if (count($rows) < 2 || !isDivider($rows[1])) {
        return [$block, ['skip' => 'nao e tabela markdown (falta divisor apos o cabecalho)']];
    }

    $isDiv = [];
    foreach ($rows as $k => $r) $isDiv[$k] = isDivider($r);

    // largura NATURAL por coluna (emoji contam 2)
    $natural = array_fill(0, $nCols, 1);
    foreach ($rows as $k => $r) {
        if ($isDiv[$k]) continue;
        foreach ($r as $c => $v) $natural[$c] = max($natural[$c], strWidth($v));
    }

    $before = fullWidth($natural);
    if ($before <= $max) {
        return [$block, ['ok' => true, 'w' => $before, 'cols' => $nCols]];
    }

    // piso por coluna = atomo mais longo (para nao partir **negrito** / `codigo`)
    $minAtom = array_fill(0, $nCols, 0);
    foreach ($rows as $k => $r) {
        if ($isDiv[$k]) continue;
        foreach ($r as $c => $v) {
            foreach (atoms($v) as $a) $minAtom[$c] = max($minAtom[$c], strWidth($a));
        }
    }

    $w   = shrink($natural, $minAtom, $max, $minCol);

    // 1.a passagem: quebrar com o teto w[c]
    $allLines = [];
    foreach ($rows as $k => $r) {
        if ($isDiv[$k]) { $allLines[$k] = null; continue; }
        $cl = [];
        foreach ($r as $c => $v) $cl[$c] = wrapText($v, $w[$c]);
        $allLines[$k] = $cl;
    }

    // Largura FINAL por coluna = maior celula realmente produzida (min 3, como o
    // md-align-tables). Assim as duas ferramentas concordam e sao idempotentes:
    // se ficassemos pelo teto, o md-align encolheria a coluna a seguir.
    for ($c = 0; $c < $nCols; $c++) {
        $act = 3;
        foreach ($allLines as $cl) {
            if ($cl === null) continue;
            foreach ($cl[$c] as $line) $act = max($act, strWidth($line));
        }
        $w[$c] = $act;
    }

    $out = [];
    foreach ($rows as $k => $r) {
        if ($isDiv[$k]) { $out[] = renderDivider($r, $w); continue; }

        $cellLines = $allLines[$k];
        $h = 1;
        foreach ($cellLines as $cl) $h = max($h, count($cl));

        for ($ln = 0; $ln < $h; $ln++) {           // uma linha fisica por continuacao
            $parts = [];
            for ($c = 0; $c < $nCols; $c++) {
                $txt = $cellLines[$c][$ln] ?? '';
                $parts[] = $txt . str_repeat(' ', max(0, $w[$c] - strWidth($txt)));
            }
            $out[] = '| ' . implode(' | ', $parts) . ' |';
        }
    }

    return [$out, [
        'wrapped' => true,
        'before'  => $before,
        'after'   => fullWidth($w),
        'exceeds' => fullWidth($w) > $max,
        'cols'    => $nCols,
        'rows'    => count($rows),
    ]];
}

// ------------------------------------------------------------------ processar

$text  = readText($file);
$eol   = detectEol($text);
$lines = toLines($text);
$n     = count($lines);

// Limite declarado no proprio ficheiro (excecao documentada, no seguimento do
// pragma `encoding-check:ignore-mojibake`): <!-- md-wrap-tables:max=NNN -->
// Uma tabela densa (muitas colunas com spans de codigo longos) pode nao caber em
// 200 colunas SEM partir palavras; nesse caso declara-se aqui o limite real do
// ficheiro, que passa a ser o default (o --max da linha de comandos continua a
// sobrepor-se). O health-check respeita-o automaticamente.
$limitSrc = 'default';
if ($maxArg === null) {
    if (preg_match('/md-wrap-tables:max=(\d+)/', $text, $pm)) {
        $maxCols  = max(40, (int) $pm[1]);
        $limitSrc = 'pragma do ficheiro';
    }
} else {
    $limitSrc = '--max';
}

$out = [];
$inCode = false;
$i = 0;
$tables = 0; $over = 0; $done = 0; $skipped = 0;
$maxBefore = 0; $maxAfter = 0;
$problems = [];
$ignored  = [];
$dense    = [];

while ($i < $n) {
    if (preg_match('/^\s*```/', $lines[$i])) {
        $inCode = !$inCode; $out[] = $lines[$i]; $i++; continue;
    }
    if (!$inCode && preg_match('/^\s*\|/', $lines[$i])) {
        $j = $i;
        while ($j < $n && preg_match('/^\s*\|/', $lines[$j])) $j++;
        $block = array_slice($lines, $i, $j - $i);
        $tables++;

        [$newBlock, $info] = wrapTable($block, $maxCols, $minCol);

        if (!empty($info['skip'])) {
            $skipped++;
            $ignored[] = "L" . ($i + 1) . ": {$info['skip']}";
        } elseif (!empty($info['ok'])) {
            $maxBefore = max($maxBefore, $info['w']);
            $maxAfter  = max($maxAfter, $info['w']);
        } else {
            $over++;
            // ---- seguranca: o texto tem de ser o mesmo (so espacos/quebras mudam)
            $origRows = array_map('splitCells', $block);
            $newRows  = array_map('splitCells', $newBlock);
            $same = colText($origRows, array_map('isDivider', $origRows))
                 === colText($newRows,  array_map('isDivider', $newRows));
            if (!$same) {
                $problems[] = "L" . ($i + 1) . ": TEXTO ALTERADO — tabela mantida intacta";
                if ($verbose) {                    // diagnostico: onde diferiu
                    $co = colText($origRows, array_map('isDivider', $origRows));
                    $cn = colText($newRows,  array_map('isDivider', $newRows));
                    foreach ($co as $c => $v) {
                        $v2 = $cn[$c] ?? '';
                        if ($v === $v2) continue;
                        $n1 = mb_strlen($v, 'UTF-8'); $n2 = mb_strlen($v2, 'UTF-8');
                        $p = 0; $mn = min($n1, $n2);
                        while ($p < $mn && mb_substr($v, $p, 1, 'UTF-8') === mb_substr($v2, $p, 1, 'UTF-8')) $p++;
                        out("    col " . ($c + 1) . " difere na posicao {$p} (len {$n1} vs {$n2}):");
                        out("      antes : ..." . mb_substr($v,  max(0, $p - 30), 80, 'UTF-8'));
                        out("      depois: ..." . mb_substr($v2, max(0, $p - 30), 80, 'UTF-8'));
                    }
                }
                array_push($out, ...$block);
                $i = $j; continue;
            }
            $done++;
            $maxBefore = max($maxBefore, $info['before']);
            $maxAfter  = max($maxAfter, $info['after']);
            if (!empty($info['exceeds'])) {
                $dense[] = "L" . ($i + 1) . " ({$info['cols']} colunas, precisa de {$info['after']} colunas)";
            }
            if ($verbose) {
                out(sprintf("  L%-5d %d cols, %d linhas: largura %d -> %d",
                    $i + 1, $info['cols'], $info['rows'], $info['before'], $info['after']));
            }
        }

        array_push($out, ...$newBlock);
        $i = $j; continue;
    }
    $out[] = $lines[$i]; $i++;
}

// ------------------------------------------------------------------ relatorio

$fits = $tables - $over - $skipped;    // $over = tabelas acima do limite (inclui as quebradas)
out("Ficheiro        : {$file}");
out("Limite da linha : {$maxCols} colunas (min por coluna: {$minCol}) [{$limitSrc}]");
out("Tabelas         : {$tables}  (ja cabiam: {$fits} · acima do limite: {$over} · quebradas: {$done})");
out("Tabelas a quebrar: {$over}");            // usado pelo health-check.php (dry-run)
out("Largura maxima  : {$maxBefore} -> {$maxAfter}");

if ($ignored) {
    out("");
    out("BLOCO(S) IGNORADO(S) — nao sao tabelas markdown:");
    foreach ($ignored as $p) out("  - {$p}");
}
if ($dense) {
    out("");
    out("TABELA(S) ACIMA DO LIMITE MESMO COM PALAVRAS INTACTAS:");
    foreach ($dense as $d) out("  - {$d}");
    out("  (aumentar --max nesse ficheiro, ou declarar <!-- md-wrap-tables:max=NNN --> no topo)");
}
if ($problems) {
    out("");
    out("PROBLEMAS:");
    foreach ($problems as $p) out("  - {$p}");
}

if (!isUtf8(implode("\n", $out))) fail("abortado — resultado nao e UTF-8 valido", 1);

if ($out === $lines) {
    out("");
    out("RESULTADO: nada a fazer (idempotente).");
    exit(0);
}
if ($problems) {
    fail("abortado — houve tabelas com problema (ver acima); nada foi gravado", 1);
}
if (!$write) {
    out("");
    out("RESULTADO: dry-run — {$done} tabela(s) seriam quebradas. Use --write.");
    exit(0);
}
writeText($file, implode("\n", $out), $eol);
out("");
out("RESULTADO: GRAVADO ({$done} tabela(s) quebradas).");
exit(0);