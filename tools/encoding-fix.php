<?php
/**
 * encoding-fix.php — repara ficheiros corrompidos pelo PowerShell 5.1:
 *   - remove o BOM UTF-8 (que o Set-Content -Encoding UTF8 adiciona)
 *   - reverte a dupla codificacao UTF-8 -> CP1252 -> UTF-8 (mojibake)
 *   - normaliza os fins de linha
 *
 * Uso:
 *   php tools/encoding-fix.php <ficheiro|pasta ...> [--write] [--eol=CRLF|LF] [--bom-only]
 * Sem --write, corre em dry-run (nao altera nada).
 *
 * SEGURANCA (mapa CP1252 explicito + verificacao round-trip):
 * reverte caractere a caractere (U+0080..U+00FF -> byte; especiais CP1252 -> byte;
 * restantes mantem-se) e RE-CODIFICA o resultado; se nao corresponder byte a byte
 * ao original, ABORTA sem escrever. Isto evita a perda de caracteres (—, €, "..."
 * nao existem em ISO-8859-1 e seriam substituidos por "?").
 */

require __DIR__ . "/_common.php";

/** CP1252 -> Unicode (posicoes 0x80-0x9F; as restantes sao Latin-1 direto). */
const CP1252_MAP = [
    0x80 => 0x20AC, 0x82 => 0x201A, 0x83 => 0x0192, 0x84 => 0x201E, 0x85 => 0x2026,
    0x86 => 0x2020, 0x87 => 0x2021, 0x88 => 0x02C6, 0x89 => 0x2030, 0x8A => 0x0160,
    0x8B => 0x2039, 0x8C => 0x0152, 0x8E => 0x017D, 0x91 => 0x2018, 0x92 => 0x2019,
    0x93 => 0x201C, 0x94 => 0x201D, 0x95 => 0x2022, 0x96 => 0x2013, 0x97 => 0x2014,
    0x98 => 0x02DC, 0x99 => 0x2122, 0x9A => 0x0161, 0x9B => 0x203A, 0x9C => 0x0153,
    0x9E => 0x017E, 0x9F => 0x0178,
];

/**
 * Reverte a dupla codificacao. Devolve [texto, nForaDoMapa].
 * Chars fora do mapa CP1252 nao foram produzidos por este bug -> preservam-se.
 */
function undoMojibake(string $raw): array
{
    $toByte = [];
    foreach (CP1252_MAP as $byte => $cp) $toByte[$cp] = chr($byte);

    $out = "";
    $outside = 0;
    foreach (preg_split('//u', $raw, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
        $cp = mb_ord($ch, "UTF-8");
        if ($cp <= 0xFF) {
            $out .= chr($cp);
        } elseif (isset($toByte[$cp])) {
            $out .= $toByte[$cp];
        } else {
            $out .= $ch;      // nao veio deste bug: manter como UTF-8
            $outside++;
        }
    }
    return [$out, $outside];
}

/** Re-aplica o bug para comparar com o original (round-trip). */
function reapplyMojibake(string $bytes): string
{
    $rt = "";
    foreach (str_split($bytes) as $b) {
        $cp = CP1252_MAP[ord($b)] ?? ord($b);
        $rt .= mb_chr($cp, "UTF-8");
    }
    return $rt;
}

$argvAll = $argv;
array_shift($argvAll);
$write   = hasFlag($argvAll, "--write");
$bomOnly = hasFlag($argvAll, "--bom-only");

$eolArg = "CRLF";
foreach ($argvAll as $a) if (str_starts_with($a, "--eol=")) $eolArg = strtoupper(substr($a, 6));
if (!in_array($eolArg, ["CRLF", "LF"], true)) fail("--eol aceita apenas CRLF ou LF");

$targets = array_values(array_filter($argvAll, fn($a) => !str_starts_with($a, "--")));
if (!$targets) {
    help("php tools/encoding-fix.php <ficheiro|pasta ...> [--write] [--eol=CRLF|LF] [--bom-only]", [
        "Sem --write corre em dry-run.",
        "Ex.: php tools/encoding-fix.php especificacao_mvp.md --write",
        "Ex.: php tools/encoding-fix.php . --write --include-tools",
    ]);
}

$root = getcwd();
$files = [];
foreach ($targets as $t) {
    if (is_dir($t)) {
        $files = array_merge($files, collectFiles($t, ["md", "php", "sql", "js", "css", "html", "json"]));
    } elseif (is_file($t)) {
        $files[] = str_replace("\\", "/", $t);
    } else {
        fail("alvo inexistente: {$t}");
    }
}
$files = array_values(array_unique($files));

out(($write ? "MODO: GRAVAR" : "MODO: DRY-RUN (use --write para alterar)"));
out("Ficheiros: " . count($files));
out("");

$totalFixed = 0;
$totalSkipped = 0;
$totalFailed = 0;
$totalConverted = 0;

foreach ($files as $path) {
    $raw = file_get_contents($path);
    if ($raw === false) { $totalFailed++; err("  [ERRO] nao foi possivel ler {$path}"); continue; }

    $encKind = detectEncoding($raw);

    // ---- CASO A: UTF-16 (tipico de dumps legados do HeidiSQL) -> converter para UTF-8
    if (str_starts_with($encKind, "UTF-16")) {
        $from  = $encKind === "UTF-16 BE" ? "UTF-16BE" : "UTF-16LE";
        $fixed = mb_convert_encoding(stripBom($raw), "UTF-8", $from);
        if (!isUtf8($fixed)) {
            $totalFailed++;
            err("  [ABORTADO] " . relPath($root, $path) . " — conversao de {$encKind} falhou");
            continue;
        }
        printf("  %-46s %s -> UTF-8   bytes=%d->%d   eol=%s\n",
            relPath($root, $path), $encKind, strlen($raw), strlen($fixed), detectEol($fixed));
        if ($write) writeText($path, $fixed, $eolArg === "LF" ? "LF" : "CRLF");
        $totalConverted++;
        continue;
    }

    $hadBom = hasBom($raw);
    $rawNoBom = stripBom($raw);
    $mojiBefore = countMojibake($rawNoBom);

    if (!$hadBom && $mojiBefore === 0) { $totalSkipped++; continue; }

    // 1) reverter mojibake (salvo se --bom-only)
    if ($bomOnly) {
        $fixed = $rawNoBom;
        $outside = 0;
    } else {
        [$fixed, $outside] = undoMojibake($rawNoBom);
    }

    if (!isUtf8($fixed)) {
        $totalFailed++;
        err("  [ABORTADO] " . relPath($root, $path) . " — resultado nao e UTF-8 valido");
        continue;
    }

    // 2) verificacao round-trip (so quando houve reversao)
    $rtOk = true;
    if (!$bomOnly) {
        $rtOk = (reapplyMojibake($fixed) === $rawNoBom);
        if (!$rtOk) {
            $totalFailed++;
            err("  [ABORTADO] " . relPath($root, $path) . " — round-trip falhou (nao corresponde)");
            continue;
        }
    }

    $mojiAfter = countMojibake($fixed);
    $eol = detectEol($fixed);

    printf("  %-46s bom=%-4s mojibake=%d->%d  fora-do-mapa=%d  round-trip=%-4s eol=%s\n",
        relPath($root, $path),
        $hadBom ? "SIM" : "nao",
        $mojiBefore, $mojiAfter, $outside, $rtOk ? "OK" : "FALHA", $eol);

    if ($write) {
        writeText($path, $fixed, $eolArg === "LF" ? "LF" : "CRLF");
    }
    $totalFixed++;
}

out("");
out("Corrigidos           : {$totalFixed}");
out("Convertidos UTF-16   : {$totalConverted}");
out("Intactos             : {$totalSkipped}");
out("Falhados             : {$totalFailed}");
if (!$write && ($totalFixed || $totalConverted)) out("(dry-run — nada foi gravado)");

exit($totalFailed > 0 ? 1 : 0);