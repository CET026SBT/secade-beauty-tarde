<?php
/**
 * md-align-tables.php — alinha as tabelas markdown de um ficheiro.
 *
 * - Normaliza o padding de cada coluna (largura de ecra, nao numero de bytes).
 * - Emoji/simbolos contam 2 colunas; variation selectors contam 0.
 * - Preserva os marcadores de alinhamento (:---, ---:, :---:).
 * - Ignora o interior de code fences (``` ... ```) — diagramas e mermaid intactos.
 * - Deteta a linha separadora com 1+ hifens por celula (GFM): `| - | --- |` e valida.
 * - Pipes escapados (`\|`) sao tratados como conteudo, nao como separadores.
 * - NAO altera texto: apenas espacos e o comprimento dos separadores.
 *
 * Uso:
 *   php tools/md-align-tables.php <ficheiro.md> [--write]
 * Sem --write corre em dry-run. Aborta se detetar alteracao de conteudo.
 *
 * Exit: 0 = ok (idempotente), 1 = erro/alteracao detetada.
 */

require __DIR__ . "/_common.php";

$argvAll = $argv;
array_shift($argvAll);
$write = hasFlag($argvAll, "--write");
$file  = posArg(positionals($argv), 0);

if (!$file) {
    help("php tools/md-align-tables.php <ficheiro.md> [--write]", [
        "Dry-run por omissao; use --write para gravar.",
        "Ex.: php tools/md-align-tables.php especificacao_mvp.md --write",
    ]);
}

$text  = readText($file);
$eol   = detectEol($text);
$lines = toLines($text);

/** Divide a linha de tabela nas suas celulas (sem os pipes extremos).
 *  Pipes ESQUIPADOS (`\|`) sao conteudo, nao separadores -- sem isto, uma celula
 *  com `\|` gerava celulas-fantasma, a linha separadora deixava de validar e a
 *  tabela era silenciosamente ignorada. */
function splitCells(string $line): array
{
    $t = trim($line);
    $t = preg_replace('/^\|/', '', $t);
    $len = strlen($t);
    if ($len > 0 && $t[$len - 1] === '|' && ($len < 2 || $t[$len - 2] !== '\\')) {
        $t = substr($t, 0, $len - 1);
    }

    $cells = [];
    $cur   = '';
    $len   = strlen($t);
    for ($i = 0; $i < $len; $i++) {
        $ch = $t[$i];
        if ($ch === '\\' && $i + 1 < $len) {          // preserva o escape tal como esta
            $cur .= $ch . $t[$i + 1];
            $i++;
            continue;
        }
        if ($ch === '|') { $cells[] = $cur; $cur = ''; continue; }
        $cur .= $ch;
    }
    $cells[] = $cur;

    return array_map('trim', $cells);
}

$out = [];
$inCode = false;
$blocks = 0;
$cells = 0;
$changed = 0;

$i = 0;
$n = count($lines);

while ($i < $n) {
    $line = $lines[$i];

    // alternar estado de code fence
    if (preg_match('/^\s*```/', $line)) {
        $inCode = !$inCode;
        $out[] = $line;
        $i++;
        continue;
    }

    if (!$inCode && preg_match('/^\s*\|/', $line)) {
        // recolher o bloco contiguo de linhas de tabela
        $j = $i;
        while ($j < $n && preg_match('/^\s*\|/', $lines[$j])) $j++;

        $rows = [];
        for ($k = $i; $k < $j; $k++) $rows[] = splitCells($lines[$k]);

        // numero de colunas = maximo; normalizar todas as linhas
        $cols = max(array_map('count', $rows));
        foreach ($rows as $idx => $r) {
            $rows[$idx] = array_pad(array_slice($r, 0, $cols), $cols, '');
        }

        // a 2.a linha tem de ser separadora; caso contrario nao e uma tabela valida.
        // GFM aceita 1+ hifens por celula (`| - | --- |`), forma comum em cabecalhos
        // escritos a mao -- com `-{2,}` essas tabelas eram silenciosamente ignoradas.
        $isTable = count($rows) >= 2;
        if ($isTable) {
            foreach ($rows[1] as $c) {
                if (!preg_match('/^:?-+:?$/', $c)) { $isTable = false; break; }
            }
        }
        if (!$isTable) { $out[] = $line; $i++; continue; }

        // larguras por coluna (ignorando a linha separadora)
        $w = array_fill(0, $cols, 3);
        foreach ($rows as $idx => $r) {
            if ($idx === 1) continue;
            foreach ($r as $c => $v) $w[$c] = max($w[$c], strWidth($v));
        }

        // reconstruir
        $newRows = [];
        foreach ($rows as $idx => $r) {
            $cs = [];
            if ($idx === 1) {
                foreach ($r as $c => $v) {
                    $left  = str_starts_with($v, ':');
                    $right = str_ends_with($v, ':');
                    $dash  = str_repeat('-', max(3, $w[$c] - ($left ? 1 : 0) - ($right ? 1 : 0)));
                    $cs[]  = ($left ? ':' : '') . $dash . ($right ? ':' : '');
                }
            } else {
                foreach ($r as $c => $v) {
                    $cs[] = $v . str_repeat(' ', max(0, $w[$c] - strWidth($v)));
                }
            }
            $newRows[] = '| ' . implode(' | ', $cs) . ' |';
        }

        $blocks++;
        $cells += count($rows) * $cols;
        foreach ($newRows as $idx => $nr) {
            $out[] = $nr;
            if ($nr !== $lines[$i + $idx]) $changed++;
        }
        $i = $j;
        continue;
    }

    $out[] = $line;
    $i++;
}

// ------------------------------------------------------------------ seguranca
$before = normForCompare(implode("\n", $lines));
$after  = normForCompare(implode("\n", $out));
$sameText = ($before === $after);

out("Ficheiro        : {$file}");
out("Blocos de tabela: {$blocks}");
out("Celulas         : {$cells}");
out("Linhas alteradas: {$changed}");
out("Texto identico  : " . ($sameText ? "SIM (apenas espacos/separadores)" : "NAO"));

if (!$sameText) {
    fail("abortado — teria alterado conteudo (nao apenas espacos)", 1);
}
if (!isUtf8(implode("\n", $out))) {
    fail("abortado — resultado nao e UTF-8 valido", 1);
}

if ($changed === 0) {
    out("");
    out("RESULTADO: ja estava alinhado (idempotente).");
    exit(0);
}

if (!$write) {
    out("");
    out("RESULTADO: dry-run — {$changed} linha(s) seriam alinhadas. Use --write.");
    exit(0);
}

writeText($file, implode("\n", $out), $eol);
out("");
out("RESULTADO: GRAVADO ({$changed} linha(s) alinhadas).");
exit(0);