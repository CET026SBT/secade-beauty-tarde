<?php
/**
 * referer.php — abre uma REFERENCIA do projeto no editor, no ficheiro e na linha
 * exatas, e permite voltar atras (pilha de posicoes) varias vezes seguidas.
 *
 * PARA QUE SERVE
 * A documentacao deste projeto referencia-se por identificadores: `§18.2`, `D-12`,
 * `RF-75`, `RN-30`. Seguir uma referencia era procurar o ficheiro e a linha a mao.
 * Esta ferramenta:
 *   - resolve a referencia -> (ficheiro, linha) a partir de um indice gerado do
 *     proprio repositorio (`index`);
 *   - abre-a no editor na linha certa, reaproveitando a janela aberta (`open`);
 *   - guarda um HISTORICO em pilha, para voltar a posicao exata de onde se veio
 *     (`back` / `forward`), tantas vezes quantas as referencias seguidas.
 *
 * TRES PECAS
 *   1. `_dev/tools/referer.php`      esta ferramenta (com copia de emergencia em
 *                                    `.git/ref-open/referer.php`, que sobrevive a
 *                                    qualquer troca de branch)
 *   2. `_dev/tools/refs.json`        indice resolvido (ref -> ficheiro:linha). Gerado,
 *                                    nao normativo, nunca versionado
 *   3. `.git/ref-open/history.json`  pilha de navegacao (local a este clone)
 *
 * REGRA DA UNICIDADE — uma referencia aponta para UM ficheiro:linha
 * O `index` deteta colisoes (a mesma chave em varios sitios) e resolve-as por uma
 * destas vias, todas explicitas:
 *   --id=CHAVE       fixa a chave (ex.: `--id=D-12` numa linha que a define)
 *   --anchor=§N      fixa a seccao do ficheiro onde a chave vale
 *   --file=CAMINHO   fixa o ficheiro
 * Sem nada fixado decide a regra do DONO: se a chave aparece no titulo da propria
 * seccao, essa seccao e a dona (ex.: `D-12` -> `### 3.12 · D-12 · ...`). Se continuar
 * empatado, a entrada fica marcada AMBIGUA — e o `index` diz quais, nunca em silencio.
 *
 * USO
 *   php _dev/tools/referer.php index [--write] [--drop-ambiguous] [--strict]
 *   php _dev/tools/referer.php list [filtro] [--verbose]
 *   php _dev/tools/referer.php open §18.2 [--dry-run]   |  open D-12
 *   php _dev/tools/referer.php back [n]                 |  forward [n]
 *   php _dev/tools/referer.php install [--dry-run]      (uma vez: .git/ + alias + exclusoes)
 *   php _dev/tools/referer.php status
 *
 * Depois do `install`, os comandos da consola sao:
 *   git ref-open §18.2      git ref-open D-12      git ref-back      git ref-forward
 *
 * Exit: 0 ok, 1 erro ou nada para fazer.
 */

/* O modulo comum vive em `_dev/tools/`. A copia de emergencia em `.git/ref-open/`
   fica sem ele — o `_dev/` desaparece do disco ao trocar para uma branch de produto —,
   por isso aqui define-se o minimo que esta ferramenta usa. Mesma solucao do
   `agent-files.php`; se `_common.php` existir, e ele que manda (fonte unica). */
$_common = __DIR__ . "/_common.php";
if (is_file($_common)) {
    require $_common;
} else {
    function out(string $s = ""): void { fwrite(STDOUT, $s . PHP_EOL); }
    function err(string $s): void { fwrite(STDERR, $s . PHP_EOL); }
    function fail(string $m, int $c = 1): void { fwrite(STDERR, "ERRO: {$m}" . PHP_EOL); exit($c); }
    function help(string $u, array $n = []): void
    {
        out("USO: {$u}");
        foreach ($n as $x) out("  - {$x}");
        exit(0);
    }
    function hasFlag(array $a, string $n): bool { return in_array($n, $a, true); }
    function positionals(array $a): array
    {
        return array_values(array_filter(array_slice($a, 1), fn($x) => !str_starts_with($x, "--")));
    }
    function posArg(array $p, int $i, ?string $d = null): ?string { return $p[$i] ?? $d; }
    function optValue(array $a, string $n): ?string
    {
        $pre = "--{$n}=";
        foreach ($a as $x) if (str_starts_with($x, $pre)) return substr($x, strlen($pre));
        return null;
    }
    function toLines(string $t): array { return preg_split('/\r\n|\n|\r/', rtrim($t, "\r\n")); }
    function relPath(string $root, string $p): string
    {
        $p = str_replace("\\", "/", $p);
        $r = rtrim(str_replace("\\", "/", $root), "/") . "/";
        return str_starts_with(strtolower($p), strtolower($r)) ? substr($p, strlen($r)) : $p;
    }
    function stripBom(string $t): string { return str_starts_with($t, "\xEF\xBB\xBF") ? substr($t, 3) : $t; }
    function readText(string $p): string
    {
        $t = @file_get_contents($p);
        if ($t === false) fail("nao foi possivel ler: {$p}");
        return stripBom($t);
    }
    /** Escreve UTF-8 SEM BOM com um unico fim de linha final (CRLF por omissao). */
    function writeText(string $p, string $t, string $eol = "CRLF"): void
    {
        $t = stripBom($t);
        $t = str_replace(["\r\n", "\r"], "\n", $t);
        $o = $eol === "LF" ? $t : str_replace("\n", "\r\n", $t);
        $o = rtrim($o, "\r\n") . ($eol === "LF" ? "\n" : "\r\n");
        @mkdir(dirname($p), 0777, true);
        if (file_put_contents($p, $o) === false) fail("falha ao escrever: {$p}");
    }
}
/* ============================================================== constantes */

const REFS_REL    = "_dev/tools/refs.json";    // indice resolvido (gerado, nao versionado)
const CACHE_DIR   = "ref-open";                // dentro de `.git/` — local a este clone
const TOOL_DEST   = "ref-open/referer.php";    // copia de emergencia da ferramenta
const DEFAULT_COL = 1;

/** Chaves de identificador aceites: padrao -> formato canonico (2 digitos, como o projeto). */
const KEY_PATTERNS = [
    '/\bD-0*(\d+)\b/u'  => "D-%02d",
    '/\bRF-0*(\d+)\b/u' => "RF-%02d",
    '/\bRN-0*(\d+)\b/u' => "RN-%02d",
];

/** Linhas que podem carregar um identificador (titulo, item, tabela, citacao). */
const KEY_BEARING_RE = '/^\s*(#{1,6}\s|\*\*|[-*+]\s|\d+[.)]\s|\||>)/u';
/** Cabecalho numerado = ancora `§N` / `§N.N`. */
const HEADING_RE = '/^(#{1,6})\s+(\d+(?:\.\d+)*)\.?\s+(\S.*)$/u';

/* =============================================================== argumentos */

$argvAll = $argv;
array_shift($argvAll);
$pos        = positionals($argv);
$cmd        = posArg($pos, 0);
$dryRun     = hasFlag($argvAll, "--dry-run");
$write      = hasFlag($argvAll, "--write");
$dropAmb    = hasFlag($argvAll, "--drop-ambiguous");
$strict     = hasFlag($argvAll, "--strict");
$verbose    = hasFlag($argvAll, "--verbose") || hasFlag($argvAll, "--all");
$newWindow  = hasFlag($argvAll, "--new-window");
$rootOpt    = optValue($argv, "root");
$indexOpt   = optValue($argv, "index");
$editorOpt  = optValue($argv, "editor");
$editorArgs = optValue($argv, "editor-args");
$idOpt      = optValue($argv, "id");
$anchorOpt  = optValue($argv, "anchor");
$fileOpt    = optValue($argv, "file");
$onlyFile   = $fileOpt !== null ? ltrim(str_replace("\\", "/", $fileOpt), "/") : null;

/* Raiz do projeto: derivada da localizacao do script, valida nos dois sitios onde ele
   vive — `_dev/tools/` e a copia em `.git/ref-open/`; as duas sobem dois niveis. */
$root = $rootOpt;
if (!$root) {
    $up   = dirname(__DIR__);
    $root = is_dir($up . "/.git") ? $up : dirname($up);
}
$root = rtrim(str_replace("\\", "/", $root), "/");
if (!is_dir($root)) fail("raiz inexistente: {$root}");

$refsFile = $indexOpt !== null ? $indexOpt : $root . "/" . REFS_REL;
$gitDir   = gitDir($root);
$store    = $gitDir . "/" . CACHE_DIR . "/history.json";

/* =============================================================== utilidades */

/** Pasta git (`.git`), absoluta, com barras normais. */
function gitDir(string $root): string
{
    $out = [];
    exec("git -C " . escapeshellarg($root) . " rev-parse --git-common-dir 2>&1", $out, $code);
    $gd = $code === 0 ? trim(implode("", $out)) : "";
    if ($gd === "") return $root . "/.git";
    if (!preg_match("#^([A-Za-z]:|/)#", $gd)) $gd = $root . "/" . $gd;
    return rtrim(str_replace("\\", "/", $gd), "/");
}

function isWindows(): bool { return stripos(PHP_OS_FAMILY, "Windows") !== false; }

/** Localiza um executavel no PATH (sem recorrer a shell). */
function whichExe(string $name, array $exts): ?string
{
    foreach (explode(PATH_SEPARATOR, (string) getenv("PATH")) as $dir) {
        $dir = rtrim($dir, "/\\");
        if ($dir === "") continue;
        foreach ($exts as $e) {
            $f = $dir . DIRECTORY_SEPARATOR . $name . $e;
            if (is_file($f)) return str_replace("\\", "/", $f);
        }
    }
    return null;
}

/** JSON -> array (ou null). */
function readJson(string $file): ?array
{
    if (!is_file($file)) return null;
    $j = json_decode((string) file_get_contents($file), true);
    return is_array($j) ? $j : null;
}

/** Array -> JSON legivel, UTF-8 sem BOM. */
function writeJson(string $file, array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) fail("nao consegui codificar JSON");
    writeText($file, $json . "\n");
}
/* ---------------------------------------------------------- documentos fonte */

/**
 * Ficheiros de onde se extraem referencias: a documentacao VIVA do projeto.
 * `_dev/docs/out/` fica de fora (saida descartavel — `_dev/docs/README.md`).
 */
function docFiles(string $root): array
{
    $files = [];
    foreach ([".clinerules", "especificacao_mvp.md", "README.md", "_dev/docs/README.md",
              "_dev/tools/README.md", "_dev/tests/README.md"] as $f) {
        if (is_file($root . "/" . $f)) $files[$f] = true;
    }
    foreach (["_dev/docs/spec", "_dev/docs/rules", "_dev/docs/templates"] as $d) {
        $abs = $root . "/" . $d;
        if (!is_dir($abs)) continue;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (!$f->isFile() || strtolower($f->getExtension()) !== "md") continue;
            $rel = relPath($root, str_replace("\\", "/", $f->getPathname()));
            if (str_contains($rel, "/out/")) continue;
            $files[$rel] = true;
        }
    }
    $out = array_keys($files);
    sort($out);
    return $out;
}

/**
 * Le um documento e devolve os cabecalhos numerados (ancoras) e os identificadores
 * mencionados, cada mencao ligada a seccao em que aparece.
 */
function parseDoc(string $abs): array
{
    $sections = [];
    $entries  = [];
    $lines    = toLines((string) file_get_contents($abs));
    $lastSec  = null;

    foreach ($lines as $i => $raw) {
        $n = $i + 1;
        if (preg_match(HEADING_RE, $raw, $m)) {
            $sections[$m[2]] = ["line" => $n, "text" => trim($raw), "title" => trim($m[3])];
            $lastSec = $m[2];
            /* A linha do cabecalho tambem transporta chaves: e onde vive a chave "dona" de uma
               seccao (`### 3.12 · D-12 · ...`). Sem isto o dono nunca era encontrado. */
        }
        if (!preg_match(KEY_BEARING_RE, $raw)) continue;
        foreach (KEY_PATTERNS as $re => $fmt) {
            if (!preg_match($re, $raw, $km)) continue;
            $key = sprintf($fmt, (int) $km[1]);
            $entries[] = [
                "key"  => $key,
                "line" => $n,
                "text" => trim($raw),
                "sec"  => $lastSec,
                "def"  => isDefinitionLine($raw, $key),
            ];
        }
    }
    return ["sections" => $sections, "entries" => $entries];
}

/** A chave aparece no titulo da seccao? Entao essa seccao e a DONA da chave. */
function secOwnsKey(?string $secText, string $key): bool
{
    if ($secText === null) return false;
    return (bool) preg_match('/\b' . preg_quote($key, '/') . '\b/u', $secText);
}

/**
 * A linha DEFINE a chave (em vez de a mencionar)? Neste projeto a definicao tem a chave
 * na primeira celula da tabela (`| RN-30 | ...`) ou a abrir um item de lista.
 */
function isDefinitionLine(string $raw, string $key): bool
{
    $k = preg_quote($key, "/");
    if (preg_match('/^\s*\|\s*' . $k . '\s*\|/u', $raw)) return true;
    if (preg_match('/^\s*[-*+]\s+\*{0,2}' . $k . '\*{0,2}\s/u', $raw)) return true;
    return false;
}

/** A seccao `§X.NN` corresponde ao numero da chave? No projeto, `D-07` vive em `§3.7`. */
function secNumberMatchesKey(?string $secNum, string $key): bool
{
    if ($secNum === null) return false;
    if (!preg_match('/(\d+)/', $key, $m)) return false;
    $parts = explode(".", $secNum);
    return (int) end($parts) === (int) $m[1];
}

/** Forma canonica de uma chave escrita de qualquer maneira (`D-1` -> `D-01`). Null se nao for chave. */
function canonKey(string $s): ?string
{
    $s = trim($s);
    foreach (KEY_PATTERNS as $re => $fmt) {
        if (preg_match($re, $s, $m) && preg_replace($re, "", $s) === "") return sprintf($fmt, (int) $m[1]);
    }
    return null;
}
/**
 * Constroi o indice: `§N` -> ficheiro:linha e chave (`D-12`, `RF-75`, …) -> ficheiro:linha.
 *
 * Unicidade: cada (ficheiro, seccao) conta uma vez (a mesma mencao repetida nao e
 * colisao). Com mais do que uma candidata decide a regra do DONO; se continuar
 * empatado a entrada fica AMBIGUA e e reportada — nunca resolvida em silencio.
 */
function buildIndex(string $root, bool $dropAmb, ?string $onlyFile, ?string $idOpt, ?string $anchorOpt): array
{
    $items   = [];
    $alias   = [];
    $amb     = [];
    $byKey   = [];
    $sources = docFiles($root);

    foreach ($sources as $rel) {
        if ($onlyFile !== null && $onlyFile !== $rel) continue;
        $p = parseDoc($root . "/" . $rel);
        foreach ($p["sections"] as $num => $s) {
            $items["§" . $num] = [
                "ref" => "§" . $num, "file" => $rel, "line" => $s["line"], "anchor" => "§" . $num,
                "title" => $s["title"], "kind" => "seccao", "sig" => $s["text"],
            ];
        }
        foreach ($p["entries"] as $e) {
            if ($anchorOpt !== null && $e["sec"] !== $anchorOpt) continue;
            $byKey[$e["key"]][] = [
                "file" => $rel, "line" => $e["line"], "text" => $e["text"], "sec" => $e["sec"],
                "secText" => $e["sec"] !== null ? ($p["sections"][$e["sec"]]["text"] ?? null) : null,
                "def" => !empty($e["def"]), "explicito" => ($idOpt !== null && $idOpt === $e["key"]),
            ];
        }
    }

    foreach ($byKey as $key => $hits) {
        $uniq = [];
        foreach ($hits as $h) {
            $k = $h["file"] . "#" . ($h["sec"] ?? "-");
            if (!isset($uniq[$k])) $uniq[$k] = $h;
        }
        $cands  = array_values($uniq);
        $chosen = null;
        $reason = "";

        if (count($cands) === 1) {
            $chosen = $cands[0];
            $reason = "unica mencao";
        } else {
            /* 1) a seccao cujo TITULO traz a chave e a dona (`### 3.12 · D-12 · ...`);
               2) senao, a linha que DEFINE a chave (`| RN-30 | ...`) — o caso das tabelas RF/RN. */
            $owners = array_values(array_filter($cands, fn($c) => secOwnsKey($c["secText"], $key)));
            if (count($owners) === 1) {
                $chosen = $owners[0];
                $reason = "seccao dona";
            } else {
                /* Vários donos: fica o que NOMEIA a chave mais cedo no titulo. `§3.3 — D-03 · ...`
                   nomeia-a; `§24.3 — ... (D-03)` apenas a cita entre parenteses. */
                $best = null;
                $bestPos = PHP_INT_MAX;
                $tie = false;
                foreach ($owners as $o) {
                    $pos = mb_strpos((string) ($o["secText"] ?? ""), $key);
                    if ($pos === false) continue;
                    if ($pos < $bestPos) { $bestPos = $pos; $best = $o; $tie = false; }
                    elseif ($pos === $bestPos) { $tie = true; }
                }
                if ($best !== null && !$tie) {
                    $chosen = $best;
                    $reason = "seccao dona (chave no inicio do titulo)";
                } else {
                    /* Ainda empatado: a seccao que respeita a NUMERACAO (`D-07` -> `§3.7`)
                       ou, em ultimo recurso, a linha que DEFINE a chave (`| RN-30 | ...`).
                       Se nada decidir, a entrada fica AMBIGUA — e o `index` di-lo. */
                    $numOwners = array_values(array_filter($owners, fn($c) => secNumberMatchesKey($c["sec"], $key)));
                    if (count($numOwners) === 1) {
                        $chosen = $numOwners[0];
                        $reason = "seccao dona (§ pela numeracao)";
                    } else {
                        $defs = array_values(array_filter($cands, fn($c) => !empty($c["def"])));
                        if (count($defs) === 1) { $chosen = $defs[0]; $reason = "linha de definicao"; }
                    }
                }
            }
        }

        if ($chosen === null) {
            $amb[] = ["key" => $key, "hits" => array_map(
                fn($c) => $c["file"] . ":" . $c["line"] . "  " . mb_substr($c["text"], 0, 58), $cands
            )];
            if ($dropAmb) continue;
            $chosen = $cands[0];
            $reason = "AMBIGUA — primeira mencao (fixar com --id/--anchor/--file)";
        }

        $secNum = $chosen["sec"];
        $items[$key] = [
            "ref" => $key, "file" => $chosen["file"], "line" => $chosen["line"],
            "anchor" => $secNum !== null ? "§" . $secNum : null,
            "title" => mb_substr($chosen["text"], 0, 90), "kind" => "chave",
            "sig" => $chosen["text"], "nota" => $reason,
        ];

        /* Alias: chave definida no TITULO de uma seccao aponta para essa seccao
           (ex.: `D-12` -> `§3.12`). Da a via curta: `git ref-open D-12`. */
        if ($secNum !== null && secOwnsKey($chosen["secText"], $key)) $alias[$key] = "§" . $secNum;
    }

    ksort($items, SORT_NATURAL);
    return [
        "version" => 1, "generated_at" => date("c"), "sources" => $sources,
        "alias" => $alias, "items" => $items, "ambiguous" => $amb,
    ];
}

/** Resolve a referencia escrita pelo utilizador numa entrada do indice. */
function resolveRef(array $idx, string $ref): ?array
{
    $items = $idx["items"] ?? [];
    $alias = $idx["alias"] ?? [];
    $r     = rtrim(trim($ref), ".");
    $canon = canonKey($r);          // `D-1` e `D-01` sao a mesma chave

    if (str_starts_with($r, "§")) {
        return $items["§" . ltrim(substr($r, strlen("§")), "§")] ?? null;
    }
    if ($canon !== null) {
        if (isset($alias[$canon]) && isset($items[$alias[$canon]])) return $items[$alias[$canon]];
        if (isset($items[$canon])) return $items[$canon];
    }
    if (isset($items["§" . $r])) return $items["§" . $r];
    return $items[$r] ?? null;
}
/* -------------------------------------------------- posicao e editor */

/**
 * Confirma a linha guardada contra o ficheiro: `ok`, `movida` (o cabecalho desceu/subiu
 * e foi reencontrado pela ancora) ou `perdida` (nada bate certo — usa-se a linha antiga).
 */
function verifyPosition(string $abs, array $item): array
{
    if (!is_file($abs)) return ["line" => (int) $item["line"], "status" => "sem-ficheiro"];
    $lines = toLines((string) file_get_contents($abs));
    $line  = (int) $item["line"];
    $sig   = (string) ($item["sig"] ?? "");

    if ($line >= 1 && $line <= count($lines) && ($sig === "" || trim($lines[$line - 1]) === $sig)) {
        return ["line" => $line, "status" => "ok"];
    }
    $anchor = $item["anchor"] ?? null;
    if ($anchor !== null) {
        $re = '/^#{1,6}\s+' . preg_quote(ltrim((string) $anchor, "§"), "/") . '\.?\s+\S/u';
        foreach ($lines as $i => $l) if (preg_match($re, $l)) return ["line" => $i + 1, "status" => "movida"];
    }
    if ($sig !== "") {
        foreach ($lines as $i => $l) if (trim($l) === $sig) return ["line" => $i + 1, "status" => "movida"];
    }
    return ["line" => max(1, min($line, count($lines))), "status" => "perdida"];
}

/**
 * Descobre o editor: `--editor=` > `REF_EDITOR` > VS Code/Cursor instalados no sistema.
 * `--editor=none` desliga o arranque (util com `--dry-run`, ou noutra maquina).
 */
function resolveEditor(?string $opt, ?string $argsOpt, bool $newWin): array
{
    if ($opt !== null && strtolower($opt) === "none") {
        return ["exe" => "", "args" => [], "origem" => "--editor=none"];
    }

    $exe    = ($opt !== null && $opt !== "") ? $opt : (string) getenv("REF_EDITOR");
    $origem = ($opt !== null && $opt !== "") ? "--editor" : ($exe !== "" ? "REF_EDITOR" : "");
    if ($exe === "") {
        $lad = (string) getenv("LOCALAPPDATA");
        $pf  = (string) getenv("ProgramFiles");
        $cands = isWindows()
            ? [$lad . "/Programs/Microsoft VS Code/Code.exe", $lad . "/Programs/Microsoft VS Code/bin/code.cmd",
               $pf . "/Microsoft VS Code/Code.exe", $lad . "/Programs/Cursor/Cursor.exe",
               whichExe("code", [".cmd"]), whichExe("code", [".exe"])]
            : [whichExe("code", [""]), whichExe("cursor", [""])];
        foreach ($cands as $c) {
            if ($c && is_file($c)) { $exe = str_replace("\\", "/", $c); $origem = "descoberto"; break; }
        }
    }
    if ($exe === "") return ["exe" => "", "args" => [], "origem" => "nao encontrado"];

    $win = $newWin ? "-n" : "-r";
    $argStr = $argsOpt ?? (string) getenv("REF_EDITOR_ARGS");
    if ($argStr === null || $argStr === "") {
        $base  = strtolower(basename($exe));
        $args  = in_array($base, ["xdg-open", "open"], true)
            ? ["{file}"]                                    // abridores genericos: sem linha
            : [$win, "-g", "{file}:{line}:{col}"];           // VS Code / Cursor
    } else {
        $args = preg_split('/\s+/', trim($argStr));
    }
    return ["exe" => $exe, "args" => $args ?: [], "origem" => $origem];
}

/** Arranca o editor na posicao pedida. Sem shell (proc_open com array). */
function launchEditor(array $editor, string $abs, int $line, int $col): array
{
    $args = [];
    foreach ($editor["args"] as $a) {
        $args[] = str_replace(["{file}", "{line}", "{col}"], [$abs, (string) $line, (string) $col], $a);
    }
    $exe = (string) $editor["exe"];
    if ($exe === "") return [true, "(sem editor: so a posicao foi calculada)"];

    /* `.cmd`/`.bat` no Windows nao sao executaveis por CreateProcess: precisa do cmd.exe */
    if (preg_match('/\.(cmd|bat)$/i', $exe)) {
        $full = array_merge([whichExe("cmd", [".exe"]) ?? "cmd.exe", "/c", $exe], $args);
    } else {
        $full = array_merge([$exe], $args);
    }
    $show = implode(" ", array_map(fn($a) => str_contains($a, " ") ? '"' . $a . '"' : $a, $full));

    $p = @proc_open($full, [1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes);
    if (!is_resource($p)) return [false, $show . "   <-- o editor nao arrancou"];
    $err  = trim((string) stream_get_contents($pipes[2]));
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($p);
    return [$code === 0, $show . ($err !== "" ? "   " . $err : "")];
}
/* ------------------------------------------------ historico (pilha) */

/** Le a pilha de navegacao (`.git/ref-open/history.json`). */
function storeRead(string $store): array
{
    $st = readJson($store) ?? [];
    return [
        "current" => is_array($st["current"] ?? null) ? $st["current"] : null,
        "stack"   => is_array($st["stack"] ?? null) ? $st["stack"] : [],
        "forward" => is_array($st["forward"] ?? null) ? $st["forward"] : [],
        "opened"  => (int) ($st["opened"] ?? 0),
    ];
}

/** Grava a pilha de navegacao, criando `.git/ref-open/` se ainda nao existir. */
function storeWrite(string $store, array $st): void
{
    $st["version"]    = 1;
    $st["updated_at"] = date("c");
    $dir = dirname($store);
    if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) fail("nao criei {$dir}");
    writeJson($store, $st);
}

/** Empilha: a posicao atual passa para a pilha e a nova fica a ser a atual. */
function historyPush(array &$st, array $entry): void
{
    $sameRef  = is_array($st["current"]) && ($st["current"]["ref"] ?? null) === ($entry["ref"] ?? null);
    $sameFile = is_array($st["current"]) && ($st["current"]["file"] ?? null) === ($entry["file"] ?? null);
    if ($sameRef && $sameFile) { $st["current"] = $entry; return; }   // mesma referencia: so a posicao

    if (is_array($st["current"])) $st["stack"][] = $st["current"];
    $st["current"] = $entry;
    $st["forward"] = [];                       // abrir de novo corta o "avancar" (como no browser)
    $st["opened"]  = (int) $st["opened"] + 1;
}

/** Move `n` posicoes entre as pilhas (`stack` <-> `forward`). Null = nao ha para onde ir. */
function historyMove(array &$st, string $from, string $to, int $n): ?array
{
    if (!is_array($st[$from]) || !$st[$from]) return null;
    for ($i = 0; $i < $n && $st[$from]; $i++) {
        $target = array_pop($st[$from]);
        if (is_array($st["current"])) $st[$to][] = $st["current"];
        $st["current"] = $target;
    }
    return $st["current"];
}

/** Apresenta `1 posicao: 2 anteriores | avancar: 0`. */
function histLine(array $st): string
{
    return count($st["stack"]) . " anterior(es) | avancar: " . count($st["forward"]);
}

/**
 * Verifica a posicao e abre-a no editor. Devolve 0 se tudo bem, 1 se o ficheiro
 * nao existe no disco (caso tipico: branch de produto sem `git agent-restore`).
 */
function openPosition(string $root, array $editor, array $entry, bool $dryRun, array $st, string $motivo): int
{
    $rel = (string) $entry["file"];
    $abs = $root . "/" . $rel;
    $ver = verifyPosition($abs, $entry);
    $line = (int) $ver["line"];

    out("  " . $motivo);
    out("  referencia : " . ($entry["ref"] ?? "?") . ($entry["anchor"] ? "   (ancora " . $entry["anchor"] . ")" : ""));
    out("  ficheiro   : " . $rel);
    out("  linha      : " . $line . "   [" . $ver["status"] . "]");
    if (!empty($entry["title"])) out("  titulo     : " . mb_substr((string) $entry["title"], 0, 78));
    out("  historico  : " . histLine($st));

    if ($ver["status"] === "sem-ficheiro") {
        err("ERRO: {$rel} nao existe no disco.");
        err("      Numa branch de produto os ficheiros do agente so existem depois de:");
        err("        git agent-restore      (ver .clinerules §4)");
        return 1;
    }

    if ($dryRun) { out("  editor     : (--dry-run: nao arrancou)"); return 0; }

    [$ok, $show] = launchEditor($editor, $abs, $line, DEFAULT_COL);
    out("  editor     : " . $show);
    return $ok ? 0 : 1;
}

/** Monta a entrada de historico a partir de um item do indice. */
function entryFrom(string $ref, array $item): array
{
    return [
        "ref"    => $ref,
        "file"   => (string) $item["file"],
        "line"   => (int) $item["line"],
        "anchor" => $item["anchor"] ?? null,
        "sig"    => (string) ($item["sig"] ?? ""),
        "title"  => (string) ($item["title"] ?? ""),
    ];
}
/* -------------------------------------------------- install (uma vez) */

/** Regras locais em `.git/info/exclude` — nao versionado, logo nao vaza para o remoto. */
function installExcludes(string $gitDir, bool $dry): array
{
    $abs = $gitDir . "/info/exclude";
    if (!is_dir(dirname($abs))) fail("nao encontrei " . dirname($abs) . " — isto e um clone Git?");
    $rules = ["/" . REFS_REL, "/.vscode/"];
    $have  = is_file($abs) ? (string) file_get_contents($abs) : "";
    $add   = [];
    foreach ($rules as $r) if (!str_contains($have, $r)) $add[] = $r;
    if (!$add || $dry) return $add;

    $block = "\n# --- referencias (referer.php) — local, nao versionado ---\n"
           . "# O indice gerado e a pasta de apoio do VS Code nunca saem deste clone;\n"
           . "# por isso nao aparecem no `git status` em branch nenhuma.\n"
           . implode("\n", $add) . "\n";
    file_put_contents($abs, $have . $block);
    return $add;
}

/** Alias git -> `git ref-open`. Vive em `.git/config`, logo sobrevive a troca de branch. */
function runArgv(array $args, ?string &$captured = null): int
{
    $p = @proc_open($args, [1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes);
    if (!is_resource($p)) return 1;
    $captured = (string) stream_get_contents($pipes[1]) . (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return (int) proc_close($p);
}

if ($cmd === "install") {
    $dest    = $gitDir . "/" . TOOL_DEST;
    $destWin = str_replace("/", DIRECTORY_SEPARATOR, $dest);

    /* 1) Copia de emergencia: `.git/` e o unico sitio que um `git checkout` nunca apaga.
          Sem ela o `back` morria exatamente quando e preciso — com o `_dev/` ja fora do disco. */
    if ($dryRun) { out("[dry] copia de emergencia: {$destWin}"); }
    elseif (!@mkdir(dirname($destWin), 0777, true) && !is_dir(dirname($destWin))) { fail("nao criei " . dirname($destWin)); }
    elseif (!@copy(__FILE__, $destWin)) { fail("nao consegui copiar para {$destWin}"); }
    else { out("copia de emergencia: {$destWin}"); }

    /* 2) Exclusoes locais, para o indice e o apoio do VS Code nao sujarem o `git status`. */
    $add = installExcludes($gitDir, $dryRun);
    out("exclusoes locais: " . ($add ? implode(", ", $add) : "ja instaladas"));

    /* 3) Alias `git ref-open`. Nada de escapeshellarg aqui: no Windows ele troca `!` e `"`
          por espacos e destruiria o prefixo de shell. Os caminhos nao tem espacos.
          Se houver espacos (ex.: outro projeto), cai no `.cmd` que nao precisa do prefixo `!`. */
    $phpAbs = str_replace("\\", "/", PHP_BINARY);
    $toolAbs = str_replace("\\", "/", $dest);
    $needsShell = str_contains($root, " ") || str_contains($phpAbs, " ");
    $cmdLine = str_replace("/", DIRECTORY_SEPARATOR, $toolAbs);
    $alias   = '!' . $phpAbs . ' "' . $toolAbs . '"';
    if ($needsShell) {
        $cmdPath = $gitDir . "/" . CACHE_DIR . "/ref-open.cmd";
        $body = "@echo off\r\n\"" . str_replace("/", "\\", $phpAbs) . "\" \"" . $cmdLine . "\" %*\r\n";
        if ($dryRun) { out("[dry] wrapper: {$cmdPath}"); }
        else { writeText($cmdPath, $body); out("wrapper (caminhos com espacos): {$cmdPath}"); }
        $alias = '!' . str_replace("/", DIRECTORY_SEPARATOR, $cmdPath);
    }

    if ($dryRun) { out("[dry] alias ref-open -> {$alias}"); out("[dry] alias ref-back / ref-forward / ref-index"); exit(0); }

    /* `git ref-open §18.2` tem de chegar como `open §18.2`: o alias injecta o subcomando. */
    foreach ([["ref-open", $alias . " open"],
              ["ref-back", $alias . " back"],
              ["ref-forward", $alias . " forward"],
              ["ref-index", $alias . " index"]] as [$name, $val]) {
        $c = runArgv(["git", "-C", $root, "config", "alias." . $name, $val], $cap);
        if ($c !== 0) fail("nao consegui criar o alias {$name}: " . trim((string) $cap));
        out("alias: git " . $name);
    }

    out("");
    out("Em QUALQUER branch (mesmo depois de o _dev/ sair do disco):");
    out("  git ref-open §18.2      git ref-open D-12      git ref-open RF-75");
    out("  git ref-back            git ref-back 2         git ref-forward");
    out("  git ref-index --write   (refaz o indice depois de editar a documentacao)");
    exit(0);
}

/* ---------------------------------------------------------------- status */

if ($cmd === "status") {
    $idx     = readJson($refsFile);
    $st      = storeRead($store);
    $editor  = resolveEditor($editorOpt, $editorArgs, $newWindow);
    $exclude = installExcludes($gitDir, true);

    out("raiz        : {$root}");
    out("indice      : " . ($idx ? RELS($idx, $refsFile) : "AUSENTE — correr `index --write`"));
    out("historico   : " . (is_array($st["current"])
        ? $st["current"]["ref"] . "  (" . $st["current"]["file"] . ":" . $st["current"]["line"] . ")  " . histLine($st)
        : "(vazio)"));
    out("editor      : " . ($editor["exe"] !== "" ? $editor["exe"] . "   [" . $editor["origem"] . "]"
                                                   : "NAO ENCONTRADO — usar --editor=CAMINHO"));
    out("exclusoes   : " . ($exclude ? "a instalar: " . implode(", ", $exclude) : "instaladas"));
    out("copia .git/ : " . (is_file($gitDir . "/" . TOOL_DEST) ? "presente" : "AUSENTE — correr `install`"));
    out("alias git   : " . (trim((string) shell_exec("git -C " . escapeshellarg($root) . " config alias.ref-open 2>&1")) !== ""
        ? "instalados" : "AUSENTES — correr `install`"));
    exit(0);
}

/** Linha de resumo do indice. */
function RELS(?array $idx, string $file): string
{
    $n     = count($idx["items"] ?? []);
    $secs  = count(array_filter(array_keys($idx["items"] ?? []), fn($k) => str_starts_with($k, "§")));
    $alias = count($idx["alias"] ?? []);
    $amb   = count($idx["ambiguous"] ?? []);
    return "{$file}   {$n} refs ({$secs} seccoes, " . ($n - $secs) . " chaves, {$alias} alias)"
         . ($amb ? "   AMBIGUAS: {$amb}" : "   sem ambiguas");
}
/* ---------------------------------------------------------------- comandos */

if (!$cmd || hasFlag($argvAll, "--help") || hasFlag($argvAll, "-h")) {
    help("php _dev/tools/referer.php <index|list|open|back|forward|install|status> [args]", [
        "index [--write] [--strict] [--drop-ambiguous]   constroi o indice a partir da documentacao",
        "      --file=CAMINHO --id=CHAVE --anchor=§N       fixa uma referencia (chave unica)",
        "list  [filtro] [--verbose]                      lista as referencias resolvidas",
        "open  <ref> [--dry-run]                         abre a referencia no editor (ex.: §18.2 · D-12)",
        "back  [n]  |  forward [n]                       volta/avanca n posicoes na pilha",
        "install [--dry-run]                             copia em .git/ + exclusoes locais + alias git",
        "status                                          estado do indice, do historico e do editor",
        "",
        "--editor=CAMINHO   editor a usar (ou REF_EDITOR); --editor=none so calcula a posicao",
        "--new-window       abre numa janela nova em vez de reaproveitar a atual",
    ]);
}

$editor = resolveEditor($editorOpt, $editorArgs, $newWindow);

/* ------------------------------------------------------------- index */
if ($cmd === "index") {
    $idx = buildIndex($root, $dropAmb, $onlyFile, $idOpt, $anchorOpt);
    $n   = count($idx["items"]);

    out("fontes     : " . count($idx["sources"]) . " ficheiro(s) de documentacao");
    out("referencias: {$n}  (" . count($idx["alias"]) . " alias de chave->seccao)");
    out("ambiguas   : " . count($idx["ambiguous"]));

    foreach (array_slice($idx["ambiguous"], 0, 12) as $a) {
        out("");
        out("  [" . $a["key"] . "] " . count($a["hits"]) . " candidatas — fixar com --id/--anchor/--file:");
        foreach ($a["hits"] as $h) out("      " . $h);
    }
    if (count($idx["ambiguous"]) > 12) out("  ... e mais " . (count($idx["ambiguous"]) - 12));

    if ($write) { writeJson($refsFile, $idx); out(""); out("GRAVADO: " . relPath($root, $refsFile)); }
    else { out(""); out("dry-run — use --write para gravar o indice"); }

    exit($strict && $idx["ambiguous"] ? 1 : 0);
}

/* -------------------------------------------------------------- list */
if ($cmd === "list") {
    $idx = readJson($refsFile) ?? fail("indice ausente — correr: php _dev/tools/referer.php index --write");
    $filter = posArg($pos, 1);
    $shown  = 0;

    out(sprintf("  %-10s %-34s %-6s %s", "REF", "FICHEIRO", "LINHA", "TITULO"));
    out("  " . str_repeat("-", 100));
    foreach ($idx["items"] as $ref => $it) {
        if ($filter !== null && !str_contains($ref, $filter)) continue;
        $shown++;
        printf("  %-10s %-34s %-6d %s\n", $ref, $it["file"], $it["line"], mb_substr((string) $it["title"], 0, 44));
        if ($verbose) {
            if (!empty($it["anchor"])) out("             ancora: " . $it["anchor"]);
            if (!empty($it["nota"]))   out("             nota  : " . $it["nota"]);
        }
    }
    out("");
    out("Mostradas: {$shown}" . ($filter !== null ? " (filtro: {$filter})" : "")
        . "   |   ambiguas no indice: " . count($idx["ambiguous"] ?? []));
    exit(0);
}

/* -------------------------------------------------------------- open */
if ($cmd === "open") {
    $ref = posArg($pos, 1);
    if ($ref === null) fail("falta a referencia. Ex.: php _dev/tools/referer.php open §18.2");

    $idx  = readJson($refsFile);
    $novo = false;
    if (!$idx) { $idx = buildIndex($root, false, $onlyFile, $idOpt, $anchorOpt); $novo = true; }

    $item = resolveRef($idx, $ref);
    if (!$item) {
        /* Sugestoes: comparar ignorando pontuacao ("D12" encontra "D-12", "18.2" encontra "§18.2"). */
        $norm = preg_replace('/[^a-z0-9]/i', '', mb_strtolower($ref));
        $sug  = [];
        foreach ($idx["items"] as $k => $it) {
            if (preg_replace('/[^a-z0-9]/i', '', mb_strtolower($k)) === $norm) $sug[] = $k . "  -> " . $it["file"] . ":" . $it["line"];
        }
        err("ERRO: referencia '{$ref}' nao existe no indice.");
        foreach ($sug as $s) err("      quis dizer: {$s}");
        err("      procura com: php _dev/tools/referer.php list " . $ref);
        exit(1);
    }
    if ($novo) { writeJson($refsFile, $idx); out("  (indice reconstruido: " . relPath($root, $refsFile) . ")"); }

    /* A entrada guarda o que o utilizador escreveu (`D-07`) e a ancora resolvida (§3.7):
       o `back` volta a mostrar as duas, e `D-07` fica legivel no historico. */
    $entry = entryFrom($ref, $item);
    $st    = storeRead($store);
    historyPush($st, $entry);

    $code = openPosition($root, $editor, $entry, $dryRun, $st, "ABRIR  " . $item["ref"]);
    if (!$dryRun && $code === 0) storeWrite($store, $st);
    exit($code);
}

/* -------------------------------------------------------- back / forward */
if ($cmd === "back" || $cmd === "forward") {
    $n    = max(1, (int) (posArg($pos, 1) ?? 1));
    $st   = storeRead($store);
    $from = $cmd === "back" ? "stack" : "forward";
    $to   = $cmd === "back" ? "forward" : "stack";

    $before = count($st[$from]);
    $entry  = historyMove($st, $from, $to, $n);
    if ($entry === null) {
        out("nada para " . $cmd . "  (" . histLine($st) . ")");
        exit(1);
    }
    $moved = $before - count($st[$from]);

    $code = openPosition($root, $editor, $entry, $dryRun, $st,
        strtoupper($cmd) . "  " . $moved . ($moved < $n ? " de {$n}" : "") . " posicao(oes)");
    if (!$dryRun && $code === 0) storeWrite($store, $st);
    exit($code);
}

err("comando desconhecido: {$cmd}   (index | list | open | back | forward | install | status)");
exit(1);