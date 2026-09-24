<?php
/**
 * file-edit.php — leitura/escrita/substituicao em UTF-8 SEGURO.
 *
 * Substituto dos comandos Get-Content/Set-Content do PowerShell 5.1, que
 * adicionam BOM e convertem acentos em mojibake (ver _dev/tools/_common.php).
 * Escreve sempre UTF-8 sem BOM e preserva o fim de linha do ficheiro.
 *
 * SUBCOMANDOS
 *   show    <ficheiro> [inicio] [n] [--plain]
 *           Mostra um intervalo de linhas (1-based). Sem args: ficheiro todo.
 *
 *   write   <ficheiro> <ficheiro-conteudo|->   [--write]
 *           Substitui o ficheiro inteiro pelo conteudo de outro (ou stdin com "-").
 *
 *   replace <ficheiro> <spec.json>             [--write]
 *           Substituicao literal/por regex. spec.json = [{"search":"...","replace":"..."}, ...]
 *
 *   lines   <ficheiro> <inicio> <fim> <ficheiro-conteudo|->  [--write]
 *           Substitui o intervalo [inicio..fim] (inclusive, 1-based).
 *
 *   grep    <padrao> [pasta|ficheiro] [--ext=php,js] [--ignore-case]
 *           Procura em ficheiros (localizar referencias).
 *
 * Sem --write, os subcomandos de escrita correm em dry-run.
 *
 * Exemplos:
 *   php _dev/tools/file-edit.php show especificacao_mvp.md 1105 40
 *   php _dev/tools/file-edit.php replace notas.md spec.json --write
 *   php _dev/tools/file-edit.php lines doc.md 12 18 novo.txt --write
 */

require __DIR__ . "/_common.php";

$argvAll = $argv;
array_shift($argvAll);
$p = positionals($argv);
$cmd = posArg($p, 0);
if (!$cmd) {
    help("php _dev/tools/file-edit.php <show|write|replace|lines|grep> ...", [
        "show    <ficheiro> [inicio] [n] [--plain]",
        "write   <ficheiro> <conteudo|-> [--write]",
        "replace <ficheiro> <spec.json> [--write]",
        "lines   <ficheiro> <inicio> <fim> <conteudo|-> [--write]",
        "grep    <padrao> [pasta|ficheiro] [--ext=php,js] [--ignore-case]",
        "",
        "Sem --write, os subcomandos de escrita correm em dry-run.",
    ]);
}

$write = hasFlag($argvAll, "--write");

switch ($cmd) {

    // ------------------------------------------------------------------ show
    case "show": {
        $file = posArg($p, 1) ?? fail("falta <ficheiro>");
        $text = readText($file);
        $lines = toLines($text);
        $start = posArg($p, 2) !== null ? max(1, (int) posArg($p, 2)) : 1;
        $count = posArg($p, 3) !== null ? max(1, (int) posArg($p, 3)) : count($lines);
        $plain = hasFlag($argvAll, "--plain");

        $last = min($start + $count - 1, count($lines));
        out("--- {$file}  (total " . count($lines) . " linhas | mostrar {$start}..{$last}) ---");
        for ($i = $start; $i <= $last; $i++) {
            if ($plain) out($lines[$i - 1]);
            else printf("%5d | %s\n", $i, $lines[$i - 1]);
        }
        exit(0);
    }

    // ----------------------------------------------------------------- write
    case "write": {
        $file    = posArg($p, 1) ?? fail("falta <ficheiro>");
        $content = posArg($p, 2) ?? fail("falta <ficheiro-conteudo|->");

        $text = $content === "-" ? stream_get_contents(STDIN) : readText($content);
        if ($text === false || $text === "") fail("conteudo vazio");

        $old = is_file($file) ? readText($file) : "";
        $eol = $old !== "" ? detectEol($old) : "CRLF";

        out("destino        : {$file}");
        out("origem         : " . ($content === "-" ? "stdin" : $content));
        out("bytes antes    : " . strlen($old));
        out("bytes depois   : " . strlen($text));
        out("eol            : {$eol}");

        if (!$write) { out(""); out("dry-run — use --write para gravar"); exit(0); }
        writeText($file, $text, $eol === "LF" ? "LF" : "CRLF");
        out("GRAVADO");
        exit(0);
    }

    // --------------------------------------------------------------- replace
    case "replace": {
        $file = posArg($p, 1) ?? fail("falta <ficheiro>");
        $spec = posArg($p, 2) ?? fail("falta <spec.json>");

        $jobs = json_decode(readText($spec), true);
        if (!is_array($jobs)) fail("spec.json invalido (esperado array de {search,replace})");
        // aceitar tambem o formato com metadados: {"comment":"...", "jobs":[...]}
        if (isset($jobs["jobs"]) && is_array($jobs["jobs"])) $jobs = $jobs["jobs"];
        if (!$jobs) fail("spec.json sem substituicoes");

        $text = readText($file);
        $eol  = detectEol($text);
        $applied = 0;
        $missing = [];

        foreach ($jobs as $k => $job) {
            $search  = $job["search"]  ?? fail("job #{$k}: falta \"search\"");
            $replace = $job["replace"] ?? "";
            $limit   = (int) ($job["count"] ?? 0);

            if (!empty($job["regex"])) {
                $pattern = '/' . str_replace('/', '\/', $search) . '/u';
                $n = 0;
                $new = $limit > 0
                    ? preg_replace($pattern, $replace, $text, $limit, $n)
                    : preg_replace($pattern, $replace, $text, -1, $n);
                if ($new === null) fail("job #{$k}: regex invalida ({$search})");
            } elseif ($limit > 0) {
                // substituicao limitada, da esquerda para a direita
                $new = $text; $n = 0; $offset = 0;
                while ($n < $limit) {
                    $pos = mb_strpos($new, $search, $offset);
                    if ($pos === false) break;
                    $new = mb_substr($new, 0, $pos) . $replace
                         . mb_substr($new, $pos + mb_strlen($search));
                    $offset = $pos + mb_strlen($replace);
                    $n++;
                }
            } else {
                $n = substr_count($text, $search);
                $new = str_replace($search, $replace, $text);
            }

            if ($n === 0) $missing[] = mb_substr($search, 0, 60);
            $applied += $n;
            $text = $new;
        }

        out("ficheiro       : {$file}");
        out("substituicoes  : {$applied}");
        if ($missing) {
            out("nao encontrados: " . count($missing));
            foreach ($missing as $m) out("   - {$m}");
        }
        if (!isUtf8($text)) fail("resultado nao e UTF-8 valido");

        if (!$write) { out(""); out("dry-run — use --write para gravar"); exit(0); }
        writeText($file, $text, $eol === "LF" ? "LF" : "CRLF");
        out("GRAVADO");
        exit(0);
    }

    // ----------------------------------------------------------------- lines
    case "lines": {
        $file = posArg($p, 1) ?? fail("falta <ficheiro>");
        $ini  = (int) (posArg($p, 2) ?? fail("falta <inicio>"));
        $fim  = (int) (posArg($p, 3) ?? fail("falta <fim>"));
        $src  = posArg($p, 4) ?? fail("falta <ficheiro-conteudo|->");
        if ($ini < 1 || $fim < $ini) fail("intervalo invalido ({$ini}..{$fim})");

        $text  = readText($file);
        $eol   = detectEol($text);
        $lines = toLines($text);
        $total = count($lines);
        if ($ini > $total) fail("inicio {$ini} alem do fim do ficheiro ({$total} linhas)");
        $fimReal = min($fim, $total);

        $new = $src === "-" ? stream_get_contents(STDIN) : readText($src);
        if ($new === false) fail("nao foi possivel ler o conteudo");
        $newLines = toLines($new);

        $result = array_merge(
            array_slice($lines, 0, $ini - 1),
            $newLines,
            array_slice($lines, $fimReal)
        );

        out("ficheiro       : {$file}");
        out("linhas antes   : {$total}");
        out("substituir     : {$ini}..{$fimReal} (" . ($fimReal - $ini + 1) . " linhas)");
        out("por            : " . count($newLines) . " linhas");
        out("linhas depois  : " . count($result));
        out("eol            : {$eol}");

        $textOut = implode("\n", $result);
        if (!isUtf8($textOut)) fail("resultado nao e UTF-8 valido");

        if (!$write) { out(""); out("dry-run — use --write para gravar"); exit(0); }
        writeText($file, $textOut, $eol === "LF" ? "LF" : "CRLF");
        out("GRAVADO");
        exit(0);
    }

    // ------------------------------------------------------------------ grep
    case "grep": {
        $pattern = posArg($p, 1) ?? fail("falta <padrao>");
        $target  = posArg($p, 2) ?? ".";
        $extArg = null;
        foreach ($argvAll as $a) if (str_starts_with($a, "--ext=")) $extArg = substr($a, 6);
        $exts = $extArg !== null
            ? array_filter(array_map("trim", explode(",", $extArg)))
            : ["md", "php", "js", "sql", "css", "html"];

        $root = getcwd();
        $files = is_dir($target)
            ? collectFiles($target, $exts)
            : [str_replace("\\", "/", $target)];

        $re = '/' . str_replace('/', '\/', $pattern) . '/'
            . (hasFlag($argvAll, "--ignore-case") ? 'iu' : 'u');
        $hits = 0;
        foreach ($files as $path) {
            $text = file_get_contents($path);
            if ($text === false) continue;
            foreach (toLines($text) as $idx => $ln) {
                if (preg_match($re, $ln)) {
                    $hits++;
                    printf("%s:%d: %s\n", relPath($root, $path), $idx + 1, trim($ln));
                }
            }
        }
        out("");
        out("Encontrados: {$hits}");
        exit($hits > 0 ? 0 : 1);
    }

    default:
        fail("subcomando desconhecido: {$cmd}");
}