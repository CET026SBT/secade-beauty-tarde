<?php
/**
 * ascii-align.php — nivela as TABELAS ASCII (boxes "+---+" / "|") dentro de code
 * fences de um ficheiro markdown.
 *
 * PORQUE EXISTE
 * -------------
 * O `md-align-tables.php` so trata tabelas markdown ("| a | b |"). As tabelas ASCII
 * desenhadas a mao (dentro de ```text```) ficam como foram escritas: e comum a ultima
 * coluna "|" nao fechar na mesma coluna e as continuacoes de etiquetas ("Estado do" /
 * "servico") ficarem desencontradas. Este utilitario nivela-as.
 *
 * O QUE FAZ (apenas espacos e a largura dos tracos; nunca texto)
 *  - Le a geometria (posicoes dos "+") da primeira linha-regua do bloco.
 *  - Re-emite as reguas com os "+" nas colunas corretas e tracos da largura certa.
 *  - Re-emite cada linha com os separadores nas colunas corretas e cada celula
 *    ajustada a largura da coluna.
 *  - Mantem o caracter separador de cada linha ("|" ou "+"): uma linha que fecha uma
 *    sub-caixa ("+-----+") continua a fechar uma sub-caixa.
 *  - Nivela continuacoes: uma celula com UM bloco de texto, precedida na mesma coluna
 *    por uma celula com DOIS ou mais blocos, alinha o seu texto com o ULTIMO bloco da
 *    anterior (ex.: "servico" por baixo de "Estado do").
 *  - Ignora tudo o que nao seja um box ASCII (diagramas de arvore, texto normal).
 *
 * Uso:
 *   php _dev/tools/ascii-align.php <ficheiro.md> [--write] [--verbose] [--boxes-only]
 * Sem --write corre em dry-run. Recusa alterar texto (so espacos/tracos).
 * Por omissao nivela tambem a coluna de referencia "│" dos diagramas de fluxo;
 * com --boxes-only trata apenas os boxes "+---+".
 *
 * Exit: 0 = ok (idempotente), 1 = erro/problema detetado.
 */

require __DIR__ . "/_common.php";

$argvAll = $argv;
array_shift($argvAll);
$write    = hasFlag($argvAll, "--write");
$diagrams = !hasFlag($argvAll, "--boxes-only");   // diagramas: por omissao sim
$verbose  = hasFlag($argvAll, "--verbose");
$file     = posArg(positionals($argv), 0);

if (!$file) {
    help("php _dev/tools/ascii-align.php <ficheiro.md> [--write] [--verbose] [--boxes-only]", [
        "Dry-run por omissao; use --write para gravar.",
        "Ex.: php _dev/tools/ascii-align.php levantamento-requisitos.md --write",
        "Nivela os boxes '+---+' E a coluna de referencia '|' dos diagramas de fluxo.",
        "--boxes-only: nivela apenas os boxes '+---+' (nao toca nos diagramas).",
        "--verbose: mostra, por linha, as colunas de cada bloco de texto.",
    ]);
}

$text  = readText($file);
$eol   = detectEol($text);
$lines = toLines($text);
$n     = count($lines);

/** Uma linha de regua: "+---+", "+===+", ou combinacoes destas. */
function isRuler(string $l): bool
{
    return (bool) preg_match('/^\+(?:[-=]+\+)+$/', rtrim($l));
}

/** Linha de box: comeca por "+" ou "|". */
function isBoxLine(string $l): bool
{
    return (bool) preg_match('/^[+|]/', $l);
}

/** Posicoes (byte/coluna) dos separadores na regua. */
function rulerBounds(string $l): array
{
    $b = [];
    $len = strlen(rtrim($l));
    for ($i = 0; $i < $len; $i++) if ($l[$i] === '+') $b[] = $i;
    return $b;
}

/**
 * Blocos de texto (runs) de uma linha, com a coluna (0-based) onde comecam.
 * Usado para diagnosticar e para nivelar continuacoes. Retorna [[col, texto], ...].
 */
function runs(string $l): array
{
    $res = [];
    if (preg_match_all('/\S+(?: \S+)*/u', $l, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as [$txt, $byteOff]) {
            $col = strWidth(substr($l, 0, $byteOff));
            $res[] = [$col, $txt];
        }
    }
    return $res;
}

// ---------------------------------------------------------------- render box
/**
 * Preenche uma celula para a largura $w, nivelando continuacoes.
 * Se a celula tem UM bloco de texto e a anterior (mesma coluna) tinha DOIS ou mais,
 * alinha com o ULTIMO bloco da anterior (ex.: "servico" sob "Estado do").
 */
function padCell(string $content, int $w, ?array $prevRuns): string
{
    $r = runs($content);
    if (!$r) return str_repeat(' ', $w);

    if (count($r) === 1 && $prevRuns !== null && count($prevRuns) >= 2) {
        $target = $prevRuns[count($prevRuns) - 1][0];
        $txt = $r[0][1];
        if ($target >= 0 && $target + strWidth($txt) <= $w) {
            return str_repeat(' ', $target) . $txt
                 . str_repeat(' ', $w - $target - strWidth($txt));
        }
    }
    $t  = rtrim($content);
    $wt = strWidth($t);
    if ($wt > $w) return $t;                 // overflow: devolvido tal como esta
    return $t . str_repeat(' ', $w - $wt);
}

/** Reconstroi um bloco ASCII nivelado. Devolve [linhas, problemas]. */
function renderBox(array $blk, array $lines): array
{
    $b     = $blk['bounds'];
    $nc    = count($b) - 1;
    $res   = [];
    $probs = [];
    $prevRuns = null;

    for ($k = $blk['start']; $k < $blk['end']; $k++) {
        $ln = rtrim($lines[$k]);

        if (isRuler($ln)) {                       // regua: recompor do zero
            $ch = str_contains($ln, '=') ? '=' : '-';
            $s = '';
            for ($i = 0; $i < $nc; $i++) $s .= '+' . str_repeat($ch, $b[$i + 1] - $b[$i] - 1);
            $res[] = $s . '+';
            $prevRuns = null;
            continue;
        }

        $seps = [];                               // preserva '+' das sub-caixas
        foreach ($b as $pos) $seps[] = (($ln[$pos] ?? '|') === '+') ? '+' : '|';

        $cells = [];
        $cellRuns = [];
        for ($i = 0; $i < $nc; $i++) {
            $w   = $b[$i + 1] - $b[$i] - 1;
            $raw = substr($ln, $b[$i] + 1, max(0, $w));
            $tr  = trim($raw);
            if (($tr === '' || preg_match('/^-+$/', $tr)) && $seps[$i] === '+' && $seps[$i + 1] === '+') {
                $cells[$i] = str_repeat('-', $w);   // segmento de regua dentro da linha
                $cellRuns[$i] = [];
                continue;
            }
            $cells[$i] = padCell($raw, $w, $prevRuns[$i] ?? null);
            if (strWidth($cells[$i]) > $w) {
                $probs[] = "linha " . ($k + 1) . ": celula " . ($i + 1) . " excede {$w} colunas";
            }
            $cellRuns[$i] = runs($raw);
        }

        $s = $seps[0];
        for ($i = 0; $i < $nc; $i++) $s .= $cells[$i] . $seps[$i + 1];
        $res[] = $s;
        $prevRuns = $cellRuns;
    }
    return [$res, $probs];
}

// ------------------------------------------------------- coluna "|" (fluxos)
/**
 * Linha com coluna de referencia ("... │ L.§..."). Devolve
 * ['col'=>coluna do │, 'before'=>texto antes, 'after'=>texto cru depois] ou null.
 */
function refLine(string $l): ?array
{
    if (!str_contains($l, '│')) return null;
    if (!preg_match('/^(.*\S)(\s+)│(.*)$/u', $l, $m)) return null;   // │ tem de ter texto antes
    $tail = trim($m[3]);
    if ($tail !== '' &&
        !preg_match('/§|RN-|DI-|\bDOCX\b|image\d|[FED]-?\d|✅|🧠|⚠️|🟡|🔵|🎨|📊|⚙️|🔐|🧩/u', $tail)) {
        return null;                                                 // nao e referencia
    }
    return ['col' => strWidth($m[1]) + strWidth($m[2]), 'before' => $m[1], 'after' => $m[3]];
}

// ------------------------------------------------- nivelar guias verticais
/**
 * Linha com UM unico conector de guia, ou null.
 *  - guide : a linha e so o conector ("│", "▼", "┬", ...)
 *  - border: canto com um unico ┬/┴ ("└───┬───┘")
 *  - stub  : termina num conector, com conteudo antes ("... upload de X │")
 * Linhas com 2+ conectores (diagramas em diamante/arvore) sao descartadas.
 */
function connInfo(string $l): ?array
{
    $t = rtrim($l);
    if ($t === '') return null;
    if (preg_match_all('/[│▼┬┴┼]/u', $t) !== 1) return null;

    if (preg_match('/^(\s*)([│▼┬┴┼])\s*$/u', $t, $m)) {
        return ['kind' => 'guide', 'col' => strWidth($m[1]), 'ch' => $m[2], 'min' => 0];
    }
    if (preg_match('/^(\s*)([┌└])([─-]+)([┬┴])([─-]+)([┐┘])$/u', $t, $m)) {
        return ['kind' => 'border',
                'col'  => strWidth($m[1]) + strWidth($m[2]) + strWidth($m[3]),
                'head' => $m[2], 'left' => $m[3], 'ch' => $m[4], 'right' => $m[5],
                'tail' => $m[6], 'indent' => $m[1], 'dash' => mb_substr($m[3], 0, 1), 'min' => 1];
    }
    if (preg_match('/^(\s*)([│▼])(\s+)(\S.*)$/u', $t, $m)) {
        return ['kind' => 'lead', 'col' => strWidth($m[1]), 'ch' => $m[2], 'min' => 0,
                'gap' => $m[3], 'text' => $m[4]];
    }
    if (preg_match('/^(.*\S)(\s+)([│▼])$/u', $t, $m)) {
        return ['kind' => 'stub', 'col' => strWidth($m[1]) + strWidth($m[2]),
                'before' => $m[1], 'ch' => $m[3], 'min' => strWidth($m[1]) + 1];
    }
    return null;
}

/** Aplica o alvo $target a uma linha de guia. */
function connRender(array $ci, int $target): string
{
    switch ($ci['kind']) {
        case 'guide':
            return str_repeat(' ', $target) . $ci['ch'];

        case 'lead':
            return str_repeat(' ', $target) . $ci['ch'] . $ci['gap'] . $ci['text'];

        case 'stub':
            return $ci['before'] . str_repeat(' ', $target - strWidth($ci['before'])) . $ci['ch'];

        case 'border':
            $delta = $target - $ci['col'];
            $l = mb_strlen($ci['left'], 'UTF-8') + $delta;
            $r = mb_strlen($ci['right'], 'UTF-8') - $delta;
            if ($l < 1 || $r < 1) return '';          // nao cabe: nao mexe
            return $ci['indent'] . $ci['head'] . str_repeat($ci['dash'], $l) . $ci['ch']
                 . str_repeat($ci['dash'], $r) . $ci['tail'];
    }
    return '';
}

// ------------------------------------------------------------------ recolha
$inCode = false;
$blocks = [];   // [startIdx, endIdxExclusive, bounds]
$i = 0;
while ($i < $n) {
    if (preg_match('/^\s*```/', $lines[$i])) { $inCode = !$inCode; $i++; continue; }
    if ($inCode && isBoxLine($lines[$i])) {
        $j = $i;
        while ($j < $n && isBoxLine($lines[$j])) $j++;
        // precisa de pelo menos uma regua com 2+ colunas para ser tabela
        $ruler = null;
        for ($k = $i; $k < $j; $k++) if (isRuler($lines[$k])) { $ruler = $k; break; }
        if ($ruler !== null) {
            $blocks[] = ['start' => $i, 'end' => $j, 'ruler' => $ruler, 'bounds' => rulerBounds($lines[$ruler])];
        }
        $i = $j;
        continue;
    }
    $i++;
}

// ------------------------------------------------------------------ report
out("Ficheiro        : {$file}");
out("Blocos ASCII    : " . count($blocks));
out("");

$allProblems   = [];
$outLines      = $lines;
$changedBoxes  = 0;
$changedDiag   = 0;
$groupsChanged = 0;

foreach ($blocks as $bi => $blk) {
    $b     = $blk['bounds'];
    $cols  = count($b) - 1;
    $width = $b[count($b) - 1] + 1;
    out(sprintf("Bloco #%d (linhas %d-%d): %d coluna(s), largura %d  [%s]",
        $bi + 1, $blk['start'] + 1, $blk['end'], $cols, $width, implode(',', $b)));

    [$newLines, $probs] = renderBox($blk, $outLines);
    $allProblems = array_merge($allProblems, $probs);

    foreach ($newLines as $idx => $nl) {
        $k = $blk['start'] + $idx;
        if ($nl !== rtrim($outLines[$k])) {
            $changedBoxes++;
            if ($verbose) out("   L" . ($k + 1) . " nivelada: " . $nl);
        }
        $outLines[$k] = $nl;
    }

    if ($verbose) {   // diagnostico de colunas
        for ($k = $blk['start']; $k < $blk['end']; $k++) {
            $w = strWidth($outLines[$k]);
            $seps = '';
            foreach ($b as $pos) $seps .= (($outLines[$k][$pos] ?? '') === '+') ? '+' : '|';
            out(sprintf("   L%-5d largura=%-4d esperado=%-4d seps=%s", $k + 1, $w, $width, $seps));
            foreach (runs($outLines[$k]) as [$col, $txt]) out(sprintf("            col %-4d %s", $col, $txt));
        }
    }
    out("");
}

if ($diagrams) {
    // agrupa linhas de referencia consecutivas dentro de code fences
    $inCode = false;
    $cur = [];
    $groups = [];
    for ($k = 0; $k < $n; $k++) {
        if (preg_match('/^\s*```/', $lines[$k])) {
            if ($cur) { $groups[] = $cur; $cur = []; }
            $inCode = !$inCode;
            continue;
        }
        if (!$inCode) { if ($cur) { $groups[] = $cur; $cur = []; } continue; }
        $ri = refLine($outLines[$k]);
        if ($ri) $cur[] = ['i' => $k, 'ri' => $ri];
        elseif ($cur) { $groups[] = $cur; $cur = []; }
    }
    if ($cur) $groups[] = $cur;

    out("Grupos de referencia (coluna |): " . count($groups));
    foreach ($groups as $gi => $g) {
        $target = 0;
        foreach ($g as $e) $target = max($target, $e['ri']['col']);
        $moved = 0;
        foreach ($g as $e) {
            $k  = $e['i'];
            $nl = $e['ri']['before'] . str_repeat(' ', $target - strWidth($e['ri']['before']))
                . '│' . $e['ri']['after'];
            if ($nl !== rtrim($outLines[$k])) {
                $moved++;
                $changedDiag++;
                if ($verbose) out(sprintf("   L%-5d | %d -> %d", $k + 1, $e['ri']['col'], $target));
            }
            $outLines[$k] = $nl;
        }
        if ($moved) {
            $groupsChanged++;
            out(sprintf("Grupo #%d (L%d-%d, %d linhas): coluna %d, %d linha(s) por nivelar",
                $gi + 1, $g[0]['i'] + 1, $g[count($g) - 1]['i'] + 1, count($g), $target, $moved));
        }
    }
    // ---- guias verticais: grupos com um unico conector, sem coluna de referencia
    $inCode = false;
    $cur = [];
    $ggroups = [];
    for ($k = 0; $k < $n; $k++) {
        if (preg_match('/^\s*```/', $lines[$k])) {
            if ($cur) { $ggroups[] = $cur; $cur = []; }
            $inCode = !$inCode;
            continue;
        }
        if (!$inCode) { if ($cur) { $ggroups[] = $cur; $cur = []; } continue; }
        $ci = connInfo($outLines[$k]);
        $rl = refLine($outLines[$k]);
        // so exclui referencias GENUINAS (com texto depois do │); um "stub" sem
        // referencia (so "') nao colide com a nivelacao da coluna de referencia.
        if ($ci !== null && $rl !== null && trim($rl['after']) !== '') $ci = null;
        if ($ci !== null) $cur[] = ['i' => $k, 'ci' => $ci];
        elseif ($cur) { $ggroups[] = $cur; $cur = []; }
    }
    if ($cur) $ggroups[] = $cur;

    foreach ($ggroups as $gi => $g) {
        if (count($g) < 2) continue;                  // uma linha so nao se nivela
        // exige uma ancora (guia ou borda) -- evita alinhar ramos de arvore
        $hasAnchor = false;
        foreach ($g as $e) if ($e['ci']['kind'] === 'guide' || $e['ci']['kind'] === 'border') $hasAnchor = true;
        if (!$hasAnchor) continue;
        $target = 0;
        foreach ($g as $e) $target = max($target, $e['ci']['col'], $e['ci']['min']);
        $moved = 0;
        foreach ($g as $e) {
            $nl = connRender($e['ci'], $target);
            if ($nl === '') continue;                 // alvo nao cabe: nao mexe
            if ($nl !== rtrim($outLines[$e['i']])) {
                $moved++;
                $changedDiag++;
                if ($verbose) out(sprintf("   L%-5d guia %d -> %d", $e['i'] + 1, $e['ci']['col'], $target));
            }
            $outLines[$e['i']] = $nl;
        }
        if ($moved) {
            $groupsChanged++;
            $membros = implode(' ', array_map(fn($e) => ($e['i'] + 1) . ':' . $e['ci']['kind'] . '@' . $e['ci']['col'], $g));
            out(sprintf("Guia #%d (L%d-%d, %d linhas): coluna %d, %d linha(s) por nivelar",
                $gi + 1, $g[0]['i'] + 1, $g[count($g) - 1]['i'] + 1, count($g), $target, $moved));
            if ($verbose) out("   membros: {$membros}");
        }
    }
    out("");
}

if ($allProblems) {
    out("PROBLEMAS:");
    foreach ($allProblems as $p) out("  - {$p}");
    out("");
}

// ------------------------------------------------------------------ gravacao
$changedTotal = $changedBoxes + $changedDiag;
out("Linhas alteradas: {$changedTotal} (boxes={$changedBoxes} diagramas={$changedDiag}, grupos={$groupsChanged})");

if (!isUtf8(implode("\n", $outLines))) fail("abortado — resultado nao e UTF-8 valido", 1);

if ($changedTotal === 0) {
    out("");
    out("RESULTADO: ja estava nivelado (idempotente).");
    exit(0);
}
if (!$write) {
    out("");
    out("RESULTADO: dry-run — {$changedTotal} linha(s) seriam niveladas. Use --write.");
    exit(0);
}
writeText($file, implode("\n", $outLines), $eol);
out("");
out("RESULTADO: GRAVADO ({$changedTotal} linha(s) niveladas).");
exit(0);