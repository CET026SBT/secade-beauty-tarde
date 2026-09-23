<?php
/**
 * health-check.php — verifica a saude da documentacao e do encoding do projeto.
 *
 * Corre, por ordem:
 *   1. encoding-check  (BOM / mojibake / UTF-8 / fins de linha)
 *   2. md-verify       (code fences, referencias cruzadas, tabelas)
 *   3. md-align-tables em dry-run (diz se ha tabelas markdown desalinhadas)
 *   4. ascii-align     em dry-run (diz se ha tabelas ASCII/diagramas desalinhados)
 *   5. md-wrap-tables  em dry-run (diz se ha tabelas acima da largura maxima)
 *
 * Uso:
 *   php tools/health-check.php            (projeto inteiro)
 *   php tools/health-check.php --quiet    (so o resumo final)
 *
 * Exit: 0 = tudo ok, 1 = ha problemas.
 */

require __DIR__ . "/_common.php";

$quiet = hasFlag($argv, "--quiet");
$php = PHP_BINARY;
$tools = __DIR__;

/** Corre um utilitario e devolve [exitCode, saida]. */
function run(string $php, string $script, array $args, bool $capture): array
{
    $cmd = escapeshellarg($php) . " " . escapeshellarg($script);
    foreach ($args as $a) $cmd .= " " . escapeshellarg($a);
    $cmd .= " 2>&1";

    $output = [];
    $code = 0;
    exec($cmd, $output, $code);
    return [$code, implode(PHP_EOL, $output)];
}

$steps = [
    ["encoding-check", "encoding-check.php", [
        "--include-tools",
        // Excecoes conhecidas: ficheiros que por natureza nao sao UTF-8.
        // DataBase_backup_pre_v2.sql = dump legado do HeidiSQL em UTF-16 LE (arquivo,
        // ver especificacao_mvp.md §17.7) - NAO deve ser convertido sem decisao explicita.
        "--ignore=DataBase_backup_pre_v2.sql",
    ]],
    ["md-verify",      "md-verify.php",      []],
    ["md-align-tables", "md-align-tables.php", []],
    ["ascii-align",    "ascii-align.php",    []],
    ["md-wrap-tables", "md-wrap-tables.php", []],
];

$results = [];
$failures = 0;

foreach ($steps as [$label, $script, $args]) {
    $path = $tools . "/" . $script;

    if ($script === "md-align-tables.php" || $script === "ascii-align.php" || $script === "md-wrap-tables.php") {
        // dry-run por ficheiro .md
        $mdFiles = collectFiles(getcwd(), ["md"]);
        $desaligned = [];
        foreach ($mdFiles as $f) {
            [$code, $out] = run($php, $path, [$f], true);
            if (preg_match('/(?:Linhas alteradas|Tabelas a quebrar):\s*(\d+)/', $out, $m) && (int) $m[1] > 0) {
                $desaligned[] = relPath(getcwd(), $f) . " ({$m[1]})";
            }
        }
        $ok = !$desaligned;
        $results[] = [$label, $ok, $desaligned ? "por nivelar/quebrar: " . implode(", ", $desaligned)
                                             : "tudo nivelado"];
        if (!$ok) $failures++;
        continue;
    }

    [$code, $out] = run($php, $path, $args, true);
    $ok = $code === 0;

    // extrair a ultima linha de resumo do utilitario
    $summary = $ok ? "sem problemas" : "ver saida abaixo";
    foreach (array_reverse(explode("\n", $out)) as $ln) {
        $ln = trim($ln);
        if ($ln !== "" && (stripos($ln, "RESULTADO") !== false || stripos($ln, "Encontrados") !== false)) {
            $summary = $ln;
            break;
        }
    }
    $results[] = [$label, $ok, $summary];
    if (!$ok) $failures++;

    if (!$ok && !$quiet) {
        out("--------------------------------------------------------------");
        out("SAIDA DE {$label}:");
        out($out);
    }
}

out("");
out("==============================================================");
out(" HEALTH CHECK — SECADE BEAUTY");
out("==============================================================");
foreach ($results as [$label, $ok, $msg]) {
    printf("  [%s] %-18s %s\n", $ok ? "OK" : "!!", $label, $msg);
}
out("");
if ($failures === 0) {
    out("RESULTADO: TUDO OK");
    exit(0);
}
out("RESULTADO: {$failures} verificacao(oes) com problemas");
exit(1);