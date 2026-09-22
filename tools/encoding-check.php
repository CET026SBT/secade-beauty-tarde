<?php
/**
 * encoding-check.php — verifica BOM, UTF-8 invalido, mojibake e fins de linha.
 *
 * Uso:
 *   php tools/encoding-check.php [ficheiro|pasta ...] [--ext=php,sql,js,md,css,html]
 *   php tools/encoding-check.php                       (analisa o projeto inteiro)
 *
 * Saida: tabela + resumo. Exit 0 = limpo, 1 = problemas encontrados.
 *
 * Nota: a partir da raiz do projeto; caminhos relativos ao diretorio atual.
 */

require __DIR__ . "/_common.php";

$argvAll = $argv;
array_shift($argvAll);

// --ext=php,sql,...   (por omissao: conjunto usado no projeto)
$extArg = null;
foreach ($argvAll as $a) {
    if (str_starts_with($a, "--ext=")) $extArg = substr($a, 6);
}
$exts = $extArg !== null
    ? array_filter(array_map("trim", explode(",", $extArg)))
    : ["php", "sql", "js", "md", "css", "html", "json"];

$exclude = ["/tools/"];   // os utilitarios nao precisam de audit; incluir com --include-tools se quiser
if (hasFlag($argvAll, "--include-tools")) $exclude = [];

// --ignore=sufixo1,sufixo2 : caminhos aceites como excecao conhecida (nao contam como problema)
$ignore = [];
foreach ($argvAll as $a) {
    if (str_starts_with($a, "--ignore=")) {
        $ignore = array_filter(array_map("trim", explode(",", substr($a, 9))));
    }
}

// alvos = argumentos que nao sejam flags
$targets = array_values(array_filter(
    $argvAll,
    fn($a) => !str_starts_with($a, "--")
));

$root = getcwd();
$files = [];
if ($targets) {
    foreach ($targets as $t) {
        if (is_dir($t)) {
            $files = array_merge($files, collectFiles($t, $exts, $exclude));
        } elseif (is_file($t)) {
            $files[] = str_replace("\\", "/", $t);
        } else {
            fail("alvo inexistente: {$t}");
        }
    }
    $files = array_values(array_unique($files));
} else {
    $files = collectFiles($root, $exts, $exclude);
}

if (!$files) fail("nenhum ficheiro a analisar (extensoes: " . implode(",", $exts) . ")");

$bad = [];
$exceptions = [];
$checked = 0;
$ignoredPragma = 0;

foreach ($files as $path) {
    $raw = file_get_contents($path);
    if ($raw === false) continue;
    $checked++;

    $enc    = detectEncoding($raw);
    $pragma = hasMojibakePragma($raw);
    $mojiRaw = countMojibake($raw);

    $crlf = substr_count($raw, "\r\n");
    $lf   = substr_count($raw, "\n");
    $eol  = $crlf === $lf ? "CRLF" : ($crlf === 0 ? "LF" : "MISTOS");

    $problems = [];
    if ($enc === "UTF-8 (com BOM)")             $problems[] = "BOM UTF-8";
    if (!in_array($enc, ["UTF-8", "UTF-8 (com BOM)"], true)) {
        $problems[] = "encoding: {$enc}";
    }
    if ($pragma) {
        if ($mojiRaw > 0) $ignoredPragma++;
    } elseif ($mojiRaw > 0) {
        $problems[] = "mojibake x{$mojiRaw}";
    }
    if ($eol === "MISTOS") $problems[] = "fins de linha mistos";

    if ($problems) {
        $rel = relPath($root, $path);
        $known = false;
        foreach ($ignore as $ig) {
            if ($ig !== "" && str_contains($rel, $ig)) { $known = true; break; }
        }
        if ($known) {
            $exceptions[] = ["path" => $rel, "issues" => $problems];
        } else {
            $bad[] = ["path" => $rel, "issues" => $problems];
        }
    }
}

out("Ficheiros analisados : {$checked}");
out("Extensoes            : " . implode(", ", $exts));
out("Meta                 : UTF-8 sem BOM, sem mojibake, fim de linha consistente");
if ($ignoredPragma > 0) {
    out("Pragma               : {$ignoredPragma} ficheiro(s) com 'encoding-check:ignore-mojibake'");
}
out("");
if ($exceptions) {
    out("EXCECOES CONHECIDAS (" . count($exceptions) . ") — aceites, nao contam como problema:");
    foreach ($exceptions as $e) out("  " . $e["path"] . "  " . implode(", ", $e["issues"]));
    out("");
}

if (!$bad) {
    out("RESULTADO: LIMPO — nenhum problema de encoding encontrado.");
    exit(0);
}

out("PROBLEMAS ENCONTRADOS (" . count($bad) . "):");
$w = 0;
foreach ($bad as $b) $w = max($w, mb_strlen($b["path"]));
foreach ($bad as $b) {
    printf("  %-{$w}s  %s\n", $b["path"], implode(", ", $b["issues"]));
}
out("");
out("Corrigir com: php tools/encoding-fix.php <ficheiro...> --write");
exit(1);