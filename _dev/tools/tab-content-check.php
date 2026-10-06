<?php
/**
 * Diagnóstico: diz se as panes de tab da Área Cliente estão DENTRO de `.tab-content`.
 *
 * Uso: php _dev/tools/tab-content-check.php <ficheiro-ou-fonte-HTML>
 *
 * O Bootstrap 5 esconde com `.tab-content > .tab-pane` (filho **direto**): uma pane
 * que fique fora do contentor aparece **sempre**. Este utilitário isola o bloco
 * `.tab-content` (por balanceamento de `<div>`) e verifica as três secções.
 */

$file = $argv[1] ?? null;

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Uso: php tab-content-check.php <ficheiro>\n");
    exit(1);
}

$html = file_get_contents($file);
$start = strpos($html, '<div class="tab-content">');

if ($start === false) {
    echo "SEM `.tab-content`\n";
    exit(1);
}

preg_match_all('/<div\b[^>]*>|<\/div>/', substr($html, $start), $tags, PREG_OFFSET_CAPTURE);

$depth = 0;
$end = null;

foreach ($tags[0] as [$tag, $offset]) {
    $depth += str_starts_with($tag, "</") ? -1 : 1;

    if ($depth === 0) {
        $end = $offset + strlen($tag);
        break;
    }
}

$slice = $end === null ? substr($html, $start) : substr($html, $start, $end);

$inside = 0;

foreach (["sectionPerfil", "sectionAgendamentos", "sectionLembretes"] as $id) {
    $found = str_contains($slice, 'id="' . $id . '"');
    $inside += $found ? 1 : 0;

    printf("%-22s %s\n", $id, $found ? "DENTRO" : "*** FORA ***");
}

echo "panes dentro do contentor: {$inside}/3\n";
echo $inside === 3 ? "OK\n" : "DEFEITO\n";

exit($inside === 3 ? 0 : 1);