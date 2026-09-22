<?php
/**
 * md-verify.php — verificacao estrutural de ficheiros markdown.
 *
 * Valida:
 *   1. encoding (UTF-8 valido, sem BOM, sem mojibake, fim de linha consistente)
 *   2. code fences emparelhadas
 *   3. referencias cruzadas §NN e §NN.N a secoes existentes
 *   4. tabelas com numero de colunas consistente em cada bloco
 *   5. marcadores de substituicao residuais (@@XXX@@)
 *
 * Uso:
 *   php tools/md-verify.php [ficheiro.md ...]
 *   php tools/md-verify.php                 (todos os .md do projeto, inclui mapaMentalMVP/)
 *
 * Exit: 0 = tudo ok, 1 = problemas encontrados.
 */

require __DIR__ . "/_common.php";

$argvAll = $argv;
array_shift($argvAll);
$targets = array_values(array_filter($argvAll, fn($a) => !str_starts_with($a, "--")));

$root = getcwd();
$files = [];
if ($targets) {
    foreach ($targets as $t) {
        if (is_dir($t))      $files = array_merge($files, collectFiles($t, ["md"]));
        elseif (is_file($t)) $files[] = str_replace("\\", "/", $t);
        else                 fail("alvo inexistente: {$t}");
    }
} else {
    $files = collectFiles($root, ["md"]);
}
$files = array_values(array_unique($files));
if (!$files) fail("nenhum ficheiro .md encontrado");

// Documento-mestre: serve para resolver referencias cruzadas §NN feitas por outros
// ficheiros (ex.: README.md -> §29.2 do documento-mestre). Sem isto, qualquer
// referencia externa seria reportada como invalida.
$masterSections = [];
$masterName = "especificacao_mvp.md";
$masterPath = getcwd() . "/" . $masterName;
if (is_file($masterPath)) {
    foreach (toLines(file_get_contents($masterPath)) as $ln) {
        if (preg_match('/^## (\d+)\./', $ln, $m)) $masterSections[(int) $m[1]] = true;
    }
}

$failures = 0;

foreach ($files as $path) {
    $raw   = file_get_contents($path);
    $lines = toLines($raw);
    $name  = relPath($root, $path);

    $problems = [];

    // ---- 1. encoding
    $bom  = hasBom($raw);
    $utf8 = isUtf8($raw);
    $pragma = hasMojibakePragma($raw);
    $moji = $pragma ? 0 : countMojibake($raw);
    $crlf = substr_count($raw, "\r\n");
    $lf   = substr_count($raw, "\n");
    $eol  = $crlf === $lf ? "CRLF" : ($crlf === 0 ? "LF" : "MISTOS");

    if ($bom)           $problems[] = "tem BOM UTF-8";
    if (!$utf8)         $problems[] = "codificacao: " . detectEncoding($raw);
    if ($moji > 0)      $problems[] = "mojibake x{$moji}";
    if ($eol === "MISTOS") $problems[] = "fins de linha mistos";

    // ---- 2. code fences
    $fences = (int) preg_match_all('/^```/m', $raw);
    if ($fences % 2 !== 0) $problems[] = "code fences impares ({$fences})";

    // ---- 5. marcadores residuais
    $ph = (int) preg_match_all('/@@[A-Z_]+@@/', $raw);
    if ($ph > 0) $problems[] = "marcadores residuais x{$ph}";

    // ---- 3. referencias cruzadas
    $sections = [];
    foreach ($lines as $ln) {
        if (preg_match('/^## (\d+)\./', $ln, $m)) $sections[(int) $m[1]] = true;
    }
    preg_match_all('/§(\d+)(?:\.(\d+))?/', $raw, $ms, PREG_SET_ORDER);
    $badRefs = [];
    $externalRefs = 0;
    $seen = [];
    foreach ($ms as $m) {
        $key = $m[1] . (isset($m[2]) ? "." . $m[2] : "");
        if (isset($seen[$key])) continue;
        $seen[$key] = true;

        $num = (int) $m[1];
        if (isset($sections[$num])) continue;          // resolve no proprio ficheiro
        if (isset($masterSections[$num])) {           // resolve no documento-mestre
            $externalRefs++;
            continue;
        }
        $badRefs[] = $key;                            // nao resolve em lado nenhum
    }
    if ($badRefs) $problems[] = "referencias invalidas: " . implode(",", $badRefs);

    // ---- 4. tabelas
    $inCode = false;
    $cur = [];
    $tables = 0;
    $badTables = 0;
    $flush = function () use (&$cur, &$tables, &$badTables) {
        if (count($cur) < 2) { $cur = []; return; }
        $tables++;
        $counts = array_map(fn($l) => substr_count($l, "|"), $cur);
        if (count(array_unique($counts)) > 1) $badTables++;
        $cur = [];
    };
    foreach ($lines as $ln) {
        if (preg_match('/^\s*```/', $ln)) { $flush(); $inCode = !$inCode; continue; }
        if (!$inCode && preg_match('/^\s*\|/', $ln)) $cur[] = $ln; else $flush();
    }
    $flush();
    if ($badTables > 0) $problems[] = "{$badTables} tabela(s) com colunas inconsistentes";

    // ---- relatorio
    printf("%-34s linhas=%-5d fences=%-3d tabelas=%-3d refs=%-3d ext=%-2d  %s\n",
        $name, count($lines), $fences, $tables, count($seen), $externalRefs,
        $problems ? "PROBLEMA" : "OK");

    if ($problems) {
        $failures++;
        foreach ($problems as $p) out("      - {$p}");
    }
}

out("");
if ($failures === 0) {
    out("RESULTADO: TUDO OK (" . count($files) . " ficheiro(s))");
    exit(0);
}
out("RESULTADO: {$failures} ficheiro(s) com problemas");
exit(1);