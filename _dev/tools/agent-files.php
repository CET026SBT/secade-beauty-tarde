<?php
/**
 * agent-files.php — mantem os ficheiros do agente disponiveis na pasta de trabalho,
 * mesmo quando se esta noutra branch.
 *
 * PORQUE EXISTE
 * Os ficheiros do agente (regras, especificacao, docs, tools, apoio) estao versionados
 * APENAS na branch `agent-workspace`. Ao fazer `git checkout dev`, o Git REMOVE-OS da
 * pasta de trabalho, porque nao existem na tree de destino — e as regras do .gitignore
 * NAO impedem isso (ignorar nao protege ficheiros versionados).
 * Este utilitario:
 *   - `exclude` instala regras em `.git/info/exclude` (local, NAO versionado), para que os
 *     ficheiros restaurados fiquem invisiveis ao `git status` em qualquer branch;
 *   - `restore` devolve-os ao disco a partir de uma branch, SEM tocar no indice;
 *   - `status` diz o que esta presente e o que falta.
 *
 * USO
 *   php _dev/tools/agent-files.php status
 *   php _dev/tools/agent-files.php exclude
 *   php _dev/tools/agent-files.php restore                 (de agent-workspace)
 *   php _dev/tools/agent-files.php restore --ref=dev --with-tests --dry-run
 *
 * Exit: 0 ok, 1 problema.
 */

/* O modulo comum vive em `_dev/tools/`. A copia de emergencia em `.git/` (feita por `install`)
   fica sem ele, por isso define aqui o minimo que este utilitario usa — assim a copia
   funciona mesmo quando o `_dev/` ja nao existe no disco. */
$_common = __DIR__ . "/_common.php";
if (is_file($_common)) {
    require $_common;
} else {
    function out(string $s = ""): void { fwrite(STDOUT, $s . PHP_EOL); }
    function err(string $s): void { fwrite(STDERR, $s . PHP_EOL); }
    function fail(string $m, int $c = 1): void { fwrite(STDERR, $m . PHP_EOL); exit($c); }
    function arg(array $a, int $p, ?string $d = null): ?string { return $a[$p] ?? $d; }
    function hasFlag(array $a, string $n): bool { return in_array($n, $a, true); }
    function help(string $u, array $n = []): void
    {
        out("USO: {$u}");
        foreach ($n as $x) out("  - {$x}");
        exit(0);
    }
}

const AGENT_PATHS = [
    ".clinerules",
    "especificacao_mvp.md",
    "_dev/docs",
    "_dev/tools",
    "_dev/mapaMentalMVP",
    "_dev/.htaccess",
];
/** `_dev/tests` so entra por pedido: nas branches de produto os testes vivem em `tests/`. */
const AGENT_TESTS_PATH = "_dev/tests";

/** Corre git e devolve as linhas de stdout. */
function gitLines(string $root, array $args): array
{
    $cmd = "git -C " . escapeshellarg($root);
    foreach ($args as $a) $cmd .= " " . escapeshellarg($a);
    $out = [];
    exec($cmd . " 2>&1", $out, $code);
    return $code === 0 ? $out : [];
}

/* ---------------------------------------------------------------- argumentos */
$cmd  = arg($argv, 1);
$ref  = "agent-workspace";
$dry  = hasFlag($argv, "--dry-run");
$withTests = hasFlag($argv, "--with-tests");
$rootOpt = null;
foreach ($argv as $a) {
    if (str_starts_with($a, "--ref="))  $ref     = substr($a, 6);
    if (str_starts_with($a, "--root=")) $rootOpt = substr($a, 7);
}

/* Raiz do projeto. Deriva da localizacao do script, o que funciona nos dois sitios onde
   ele vive: `_dev/tools/` (a pasta subiu um nivel) e a copia local em `.git/` (ja e a raiz). */
$root = $rootOpt;
if (!$root) {
    $up = dirname(__DIR__);
    $root = is_dir($up . "/.git") ? $up : dirname($up);
}
$root = rtrim(str_replace("\\", "/", $root), "/");
if (!is_dir($root)) fail("raiz inexistente: {$root}");

/** Corre um comando SEM passar pela shell (array de argumentos). Evita o tratamento
 *  de `!` e `"` que o escapeshellarg do Windows destroi (substitui-os por espacos). */
function runArgv(array $args, ?string &$captured = null): int
{
    $spec = [1 => ["pipe", "w"], 2 => ["pipe", "w"]];
    $p = @proc_open($args, $spec, $pipes);
    if (!is_resource($p)) return 1;
    $captured = (string) stream_get_contents($pipes[1]) . (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    return (int) proc_close($p);
}

/** Pasta git comum (`.git`), absoluta e com barras normais. */
function gitDirAbs(string $root): string
{
    $gd = trim(implode("", gitLines($root, ["rev-parse", "--git-common-dir"])));
    if ($gd === "") $gd = ".git";
    if (!preg_match("#^([A-Za-z]:|/)#", $gd)) $gd = $root . "/" . $gd;
    return rtrim(str_replace("\\", "/", $gd), "/");
}

$paths = AGENT_PATHS;
if ($withTests) $paths[] = AGENT_TESTS_PATH;

if (!$cmd || hasFlag($argv, "--help")) {
    help("php _dev/tools/agent-files.php <status|install|exclude|restore> [opcoes]", [
        "--ref=<branch>   branch de origem (default: agent-workspace)",
        "--root=<pasta>   raiz do projeto (default: derivada da localizacao do script)",
        "--with-tests     inclui _dev/tests (nas branches de produto os testes vivem em tests/)",
        "--dry-run        mostra o que faria, sem escrever",
    ]);
}

/** Conta ficheiros de um caminho (ficheiro = 1). */
function countAt(string $abs): int
{
    if (is_file($abs)) return 1;
    if (!is_dir($abs)) return 0;
    $n = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) if ($f->isFile()) $n++;
    return $n;
}

/* ---------------------------------------------------------------- status */
if ($cmd === "status") {
    $branch = trim(implode("", gitLines($root, ["rev-parse", "--abbrev-ref", "HEAD"])));
    out("branch atual: {$branch}");
    out("");
    out(sprintf("  %-24s %8s %8s", "caminho", "git", "disco"));
    out("  " . str_repeat("-", 42));
    foreach ($paths as $p) {
        $nGit   = count(gitLines($root, ["ls-tree", "-r", "--name-only", "HEAD", "--", $p]));
        $nDisco = countAt($root . "/" . $p);
        $flag   = ($nGit > 0 && $nDisco === 0) ? "  <== FALTA NO DISCO" : "";
        out(sprintf("  %-24s %8d %8d%s", $p, $nGit, $nDisco, $flag));
    }
    out("");
    out("Se houver ficheiros so no Git: correr `restore` (nao versiona nada).");
    exit(0);
}

/* ---------------------------------------------------------------- exclusoes locais */
/** Instala as regras em `.git/info/exclude` (local ao clone, nao versionado). Devolve o que faltava. */
function installExcludes(string $root, bool $dry): array
{
    $abs = gitDirAbs($root) . "/info/exclude";
    if (!is_dir(dirname($abs))) fail("nao encontrei " . dirname($abs) . " — isto e um clone Git?");
    $rules = ["/.clinerules", "/especificacao_mvp.md", "/_dev/", "/dev/", "/mapaMentalMVP/", "/tools/"];
    $have  = is_file($abs) ? (string) file_get_contents($abs) : "";
    $add   = [];
    foreach ($rules as $r) if (!str_contains($have, $r)) $add[] = $r;
    if (!$add || $dry) return $add;
    $block = "\n# --- ficheiros do agente (ver .clinerules 4) — local, nao versionado ---\n"
           . "# Existem apenas em agent-workspace; mantidos no disco noutras branches\n"
           . "# por agent-files.php restore, sem sujar o git status.\n"
           . implode("\n", $add) . "\n";
    file_put_contents($abs, $have . $block);
    return $add;
}

if ($cmd === "exclude") {
    $add = installExcludes($root, $dry);
    if (!$add) { out("ja instalado — nada a fazer."); exit(0); }
    if ($dry)  { out("por instalar: " . implode(", ", $add)); exit(0); }
    out("instalado: " . implode(", ", $add));
    exit(0);
}

/* ---------------------------------------------------------------- install */
if ($cmd === "install") {
    $gd      = gitDirAbs($root);
    $dest    = $gd . "/agent-files.php";
    $destWin = str_replace("/", DIRECTORY_SEPARATOR, $dest);

    /* 1) Copia do proprio script dentro de `.git`: e' o unico sitio que um `git checkout`
          nunca apaga. Sem isto, o restauro seria impossivel — a ferramenta desaparecia
          com os ficheiros que devia restaurar. */
    if ($dry) { out("[dry] copia de emergencia: {$destWin}"); }
    elseif (!@copy(__FILE__, $destWin)) { fail("nao consegui copiar para {$destWin}"); }
    else { out("copia de emergencia: {$destWin}"); }

    /* 2) Exclusoes locais, para os ficheiros restaurados nao sujarem o `git status`. */
    $add = installExcludes($root, $dry);
    out("exclusoes locais: " . ($add ? implode(", ", $add) : "ja instaladas"));

    /* 3) Alias `git agent-restore` — vive em `.git/config`, logo sobrevive a qualquer
          troca de branch, mesmo quando `_dev/` ja nao existe no disco.
          Nota: NADA de escapeshellarg aqui — no Windows ele substitui `!` e `"` por
          espacos e destruiria o prefixo de shell. Os caminhos nao tem espacos. */
    $phpAbs = str_replace("\\", "/", PHP_BINARY);
    $alias  = '!' . $phpAbs . ' ' . $dest . ' restore --root=' . $root;
    if ($dry) { out("[dry] alias agent-restore -> {$alias}"); exit(0); }
    $c = runArgv(["git", "-C", $root, "config", "alias.agent-restore", $alias], $captured);
    if ($c !== 0) fail("nao consegui criar o alias: " . trim((string) $captured));

    out("alias: git agent-restore");
    out("");
    out("Em QUALQUER branch (incluindo depois de o _dev/ ter desaparecido):");
    out("  git agent-restore                    # devolve os ficheiros do agente ao disco");
    out("  php " . str_replace("\\", "/", $dest) . " status");
    exit(0);
}

/* ---------------------------------------------------------------- restore */
if ($cmd === "restore") {
    if (count(gitLines($root, ["rev-parse", "--verify", $ref])) === 0) {
        fail("a referencia '{$ref}' nao existe.");
    }

    $files = [];
    foreach ($paths as $p) {
        foreach (gitLines($root, ["ls-tree", "-r", "--name-only", $ref, "--", $p]) as $f) $files[$f] = true;
    }
    $files = array_keys($files);
    sort($files);
    if (!$files) fail("a referencia '{$ref}' nao tem nenhum dos caminhos do agente.");

    out("origem : {$ref}   (" . count($files) . " ficheiros)");
    if ($dry) {
        foreach ($files as $f) out("  + " . $f);
        out("--dry-run: nada foi escrito.");
        exit(0);
    }

    $tmp = rtrim(sys_get_temp_dir(), "/\\") . "/agent-files-" . getmypid() . ".tar";
    $cmdline = "git -C " . escapeshellarg($root)
             . " archive --format=tar --output=" . escapeshellarg($tmp)
             . " " . escapeshellarg($ref)
             . " -- " . implode(" ", array_map("escapeshellarg", $paths));
    exec($cmdline . " 2>&1", $o, $code);
    if ($code !== 0 || !is_file($tmp)) fail("git archive falhou: " . implode(" | ", $o));

    $ok = false;
    if (class_exists("PharData")) {
        try { (new PharData($tmp))->extractTo($root, null, true); $ok = true; }
        catch (Throwable $e) { err("PharData falhou (" . $e->getMessage() . "); a tentar tar..."); }
    }
    if (!$ok) {
        exec("tar -xf " . escapeshellarg($tmp) . " -C " . escapeshellarg($root) . " 2>&1", $o2, $c2);
        $ok = ($c2 === 0);
        if (!$ok) { @unlink($tmp); fail("extracao falhou: " . implode(" | ", $o2)); }
    }
    @unlink($tmp);

    $faltam = 0;
    foreach ($files as $f) if (!is_file($root . "/" . $f)) $faltam++;
    out("restaurados: " . (count($files) - $faltam) . " de " . count($files) . " ficheiros");
    if ($faltam) { err("FALTAM {$faltam} ficheiros"); exit(1); }
    out("");
    out("O indice NAO foi tocado. Se aparecerem como untracked, correr `exclude`.");
    exit(0);
}

err("comando desconhecido: {$cmd}  (use status | exclude | restore)");
exit(1);