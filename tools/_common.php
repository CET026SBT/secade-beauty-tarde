<?php
/**
 * SECADE BEAUTY — Utilitarios de manutencao (dev-only)
 * Modulo comum: I/O UTF-8 seguro em Windows + helpers de CLI e largura de texto.
 *
 * PORQUE EXISTE ESTE MODULO
 * -------------------------
 * O Windows PowerShell 5.1 (a shell deste ambiente) corrompe ficheiros UTF-8:
 *   1) Get-Content SEM -Encoding decodifica os bytes UTF-8 como CP1252 (ANSI)
 *      -> acentos/travessoes/euro entram em memoria ja em mojibake;
 *   2) Set-Content -Encoding UTF8 grava UTF-8 **COM BOM** (no PS 5.1 a opcao
 *      "utf8NoBOM" NAO existe; os encodings validos sao: Unknown, String,
 *      Unicode, Byte, BigEndianUnicode, UTF8, UTF7, UTF32, Ascii, Default, Oem,
 *      BigEndianUTF32 — o UTF8 e com BOM e o Default e ANSI).
 * Resultado: ficheiro maior, com BOM e com mojibake.
 *
 * SOLUCAO: nunca reescrever ficheiros com Get-Content/Set-Content.
 * Usar estes utilitarios (PHP, que escreve UTF-8 sem BOM por omissao) ou,
 * em PowerShell, a via .NET explicitamente:
 *   $enc = New-Object System.Text.UTF8Encoding($false)
 *   $txt = [System.IO.File]::ReadAllText($path, $enc)
 *   [System.IO.File]::WriteAllText($path, $txt, $enc)
 */

declare(strict_types=1);

const BOM_UTF8 = "\xEF\xBB\xBF";

/** Assinatura de mojibake (UTF-8 lido como CP1252 e regravado como UTF-8). */
const MOJIBAKE_RE =
    '/[ÃÂâ][\x{0080}-\x{009F}\x{20AC}\x{201A}\x{0192}\x{201E}\x{2026}\x{2020}\x{2021}'
  . '\x{02C6}\x{2030}\x{0160}\x{2039}\x{0152}\x{017D}\x{2018}\x{2019}\x{201C}\x{201D}'
  . '\x{2022}\x{2013}\x{2014}\x{02DC}\x{2122}\x{0161}\x{203A}\x{0153}\x{017E}\x{0178}]/u';

// ---------------------------------------------------------------- CLI helpers

function out(string $s = ""): void { fwrite(STDOUT, $s . PHP_EOL); }
function err(string $s): void    { fwrite(STDERR, $s . PHP_EOL); }

/** Le um argumento posicional (1-based) ou o valor por omissao. */
function arg(array $argv, int $pos, ?string $default = null): ?string
{
    return $argv[$pos] ?? $default;
}

function hasFlag(array $argv, string $name): bool
{
    return in_array($name, $argv, true);
}

/** Mostra ajuda e sai. */
function help(string $usage, array $notes = []): void
{
    out("USO: {$usage}");
    foreach ($notes as $n) out("  - {$n}");
    exit(0);
}

/** Termina com erro. */
function fail(string $msg, int $code = 1): void
{
    err("ERRO: {$msg}");
    exit($code);
}
// ------------------------------------------------------------ encoding seguro

function hasBom(string $raw): bool { return str_starts_with($raw, BOM_UTF8); }
function stripBom(string $raw): string { return hasBom($raw) ? substr($raw, 3) : $raw; }
function isUtf8(string $raw): bool { return mb_check_encoding($raw, "UTF-8"); }
function countMojibake(string $raw): int { return (int) preg_match_all(MOJIBAKE_RE, $raw); }

/** Le um ficheiro como texto UTF-8 (remove BOM). Morre se nao for UTF-8 valido. */
function readText(string $path, bool $allowNonUtf8 = false): string
{
    if (!is_file($path)) fail("ficheiro inexistente: {$path}");
    $raw = file_get_contents($path);
    if ($raw === false) fail("nao foi possivel ler: {$path}");
    if (!isUtf8($raw) && !$allowNonUtf8) {
        fail("{$path} nao esta em UTF-8 (talvez UTF-16/ANSI; use encoding-fix.php)");
    }
    return stripBom($raw);
}

/**
 * Escreve texto em UTF-8 SEM BOM, normalizando fins de linha e garantindo um
 * unico fim de linha final.
 */
function writeText(string $path, string $text, string $eol = "CRLF"): void
{
    $text = stripBom($text);
    $eolS = $eol === "LF" ? "\n" : "\r\n";
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $out  = $eol === "LF" ? $text : str_replace("\n", "\r\n", $text);
    $out  = rtrim($out, "\r\n") . $eolS;

    if (!isUtf8($out)) fail("recusado escrever: resultado nao e UTF-8 valido");
    if (file_put_contents($path, $out) === false) fail("falha ao escrever: {$path}");
}

/** Deteta o estilo de fim de linha dominante. */
function detectEol(string $text): string
{
    $crlf = substr_count($text, "\r\n");
    $lf   = substr_count($text, "\n");
    if ($crlf === 0) return "LF";
    return $crlf === $lf ? "CRLF" : "MISTOS";
}

/** Ficheiros com uma extensao, recursivo, excluindo pastas irrelevantes. */
function collectFiles(string $root, array $exts, array $exclude = []): array
{
    $exclude = $exclude ?: ["/\\.git/", "/vendor/", "/node_modules/", "/admin/"];
    $found = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        if (!$f->isFile()) continue;
        $p = str_replace("\\", "/", $f->getPathname());
        $skip = false;
        foreach ($exclude as $re) if (preg_match($re, $p)) { $skip = true; break; }
        if ($skip) continue;
        if ($exts && !in_array(strtolower($f->getExtension()), $exts, true)) continue;
        $found[] = $p;
    }
    sort($found);
    return $found;
}

/** Caminho relativo a uma raiz, para apresentacao. */
function relPath(string $root, string $path): string
{
    $p = str_replace("\\", "/", $path);
    $r = rtrim(str_replace("\\", "/", $root), "/") . "/";
    return str_starts_with(mb_strtolower($p), mb_strtolower($r))
        ? substr($p, mb_strlen($r)) : $p;
}
// --------------------------------------------------------- largura de texto/md

/** Largura de ecra (colunas) de um code-point. Emoji/simbolos contam 2. */
function cpWidth(int $cp): int
{
    if ($cp === 0xFE0F || $cp === 0xFE0E || $cp === 0x200D) return 0;       // selectors / ZWJ
    if (($cp >= 0x0300 && $cp <= 0x036F) || ($cp >= 0x20D0 && $cp <= 0x20FF)) return 0;
    if ($cp === 0x26A0) return 2;                                           // aviso: sempre 2
    if ($cp >= 0x1F300 && $cp <= 0x1FAFF) return 2;                         // emoji
    if ($cp >= 0x2600 && $cp <= 0x27BF) return 2;                           // dingbats (checks/estrelas)
    if ($cp === 0x2B1C || $cp === 0x2B50 || $cp === 0x2B06) return 2;       // quadrado/estrela/seta
    if (($cp >= 0x1100 && $cp <= 0x115F) || ($cp >= 0x2E80 && $cp <= 0xA4CF)
        || ($cp >= 0xAC00 && $cp <= 0xD7A3) || ($cp >= 0xF900 && $cp <= 0xFAFF)
        || ($cp >= 0xFE30 && $cp <= 0xFE6F) || ($cp >= 0xFF00 && $cp <= 0xFF60)
        || ($cp >= 0xFFE0 && $cp <= 0xFFE6)) return 2;                      // CJK/fullwidth
    return 1;
}

function strWidth(string $s): int
{
    $w = 0;
    foreach (preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
        $w += cpWidth(mb_ord($ch, "UTF-8"));
    }
    return $w;
}

/** Normaliza para comparacao: sem espacos, com runs de '-' colapsados. */
function normForCompare(string $s): string
{
    return preg_replace('/-+/u', '-', preg_replace('/\s+/u', '', $s));
}

/** Divide em linhas aceitando CRLF/LF/CR. */
function toLines(string $text): array
{
    return preg_split('/\r\n|\n|\r/', rtrim($text, "\r\n"));
}

/** Caminho da pasta tools (para mensagens). */
function toolsDir(): string { return __DIR__; }
/**
 * Argumentos posicionais: exclui o nome do script (argv[0]) e todas as flags.
 * Devolve um array reindexado a partir de 0 -> primeiro argumento real.
 * "0" e um argumento valido (nao e confundido com flag).
 */
function positionals(array $argv): array
{
    return array_values(array_filter(
        array_slice($argv, 1),
        fn($a) => !str_starts_with($a, "--")
    ));
}

/** Valor de uma flag "--nome=valor" (ou null). */
function optValue(array $argv, string $name): ?string
{
    $prefix = "--{$name}=";
    foreach ($argv as $a) {
        if (str_starts_with($a, $prefix)) return substr($a, strlen($prefix));
    }
    return null;
}

/**
 * Acesso seguro a um argumento posicional (0-based) devolvido por positionals().
 * Ex.: posArg($p, 0) -> primeiro argumento depois do nome do script.
 */
function posArg(array $p, int $i, ?string $default = null): ?string
{
    return $p[$i] ?? $default;
}

/** Deteta a codificacao de um ficheiro: UTF-8, UTF-16 LE/BE, ou "nao-UTF8". */
function detectEncoding(string $raw): string
{
    if (str_starts_with($raw, "\xFF\xFE")) return "UTF-16 LE";
    if (str_starts_with($raw, "\xFE\xFF")) return "UTF-16 BE";
    if (str_starts_with($raw, BOM_UTF8))   return "UTF-8 (com BOM)";
    if (isUtf8($raw))                      return "UTF-8";
    // heuristica: muitos bytes NUL -> UTF-16 sem BOM
    $nul = substr_count(substr($raw, 0, 2000), "\x00");
    if ($nul > 100) return "UTF-16 (sem BOM?)";
    return "nao-UTF8 (ANSI/outro)";
}

/**
 * Pragma para suprimir a deteccao de mojibake em ficheiros que a documentam
 * como exemplo. Basta incluir o texto abaixo em qualquer linha do ficheiro:
 *   encoding-check:ignore-mojibake
 */
function hasMojibakePragma(string $raw): bool
{
    return str_contains($raw, "encoding-check:ignore-mojibake");
}