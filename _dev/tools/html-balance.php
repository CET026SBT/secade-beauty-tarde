<?php
/**
 * Verificador de balanceamento de <div> numa página PHP (diagnóstico rápido).
 *
 * Uso: php _dev/tools/html-balance.php <ficheiro>
 *
 * Lê **linha a linha** e ignora blocos PHP (`<?php … ?>`, `<?= … ?>`) e comentários
 * HTML — assim o número de linha reportado corresponde ao do ficheiro.
 */

$file = $argv[1] ?? null;

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Uso: php html-balance.php <ficheiro>\n");
    exit(1);
}

$lines = file($file, FILE_IGNORE_NEW_LINES);
$depth = 0;
$max = 0;
$problems = 0;
$inPhp = false;
$inComment = false;

foreach ($lines as $index => $line) {
    $number = $index + 1;
    $text = $line;

    // Remove comentários HTML (podem abranger várias linhas).
    if ($inComment) {
        $end = strpos($text, "-->");
        if ($end === false) { continue; }
        $text = substr($text, $end + 3);
        $inComment = false;
    }

    while (($start = strpos($text, "<!--")) !== false) {
        $end = strpos($text, "-->", $start);
        if ($end === false) {
            $text = substr($text, 0, $start);
            $inComment = true;
            break;
        }
        $text = substr($text, 0, $start) . substr($text, $end + 3);
    }

    // Remove blocos PHP.
    if ($inPhp) {
        $end = strpos($text, "?>");
        if ($end === false) { continue; }
        $text = substr($text, $end + 2);
        $inPhp = false;
    }

    while (($start = strpos($text, "<?php")) !== false || ($start = strpos($text, "<?=")) !== false) {
        $end = strpos($text, "?>", $start);
        if ($end === false) {
            $text = substr($text, 0, $start);
            $inPhp = true;
            break;
        }
        $text = substr($text, 0, $start) . substr($text, $end + 2);
    }

    // Conta as <div> da linha.
    if (preg_match_all('/<div\b[^>]*>|<\/div\s*>/i', $text, $matches)) {
        foreach ($matches[0] as $tag) {
            if (str_starts_with($tag, "</")) {
                $depth--;
                if ($depth < 0) {
                    echo "linha {$number}: </div> a mais\n";
                    $depth = 0;
                    $problems++;
                }
            } else {
                $depth++;
                $max = max($max, $depth);
            }
        }
    }
}

echo "profundidade máxima: {$max}\n";
echo "divs por fechar no fim: {$depth}\n";
echo $problems === 0 && $depth === 0 ? "BALANCEADO\n" : "DESBALANCEADO\n";

exit($problems === 0 && $depth === 0 ? 0 : 1);