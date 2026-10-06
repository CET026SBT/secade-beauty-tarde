<?php
/**
 * Substitui um intervalo de linhas de um ficheiro pelo conteudo de outro (UTF-8, CRLF, sem BOM).
 * Uso: php .tmp-cut-lines.php <alvo> <inicio> <fim> <ficheiro-do-conteudo>
 */
[$script, $alvo, $inicio, $fim, $fonte] = array_pad($argv, 5, null);
if (!$alvo || !$inicio || !$fim || !$fonte) {
    fwrite(STDERR, "uso: php .tmp-cut-lines.php <alvo> <inicio> <fim> <ficheiro-do-conteudo>\n");
    exit(1);
}

$texto = file_get_contents($alvo);
$novo  = file_get_contents($fonte);

$texto = str_replace("\r\n", "\n", $texto);
$novo  = rtrim(str_replace("\r\n", "\n", $novo), "\n");

$linhas = explode("\n", $texto);
$inicio = (int) $inicio - 1;
$fim    = (int) $fim;

$antes  = array_slice($linhas, 0, $inicio);
$depois = array_slice($linhas, $fim);

$resultado = implode("\n", $antes) . ($novo === "" ? "" : $novo . "\n") . implode("\n", $depois);

if (substr($resultado, -1) !== "\n") {
    $resultado .= "\n";
}

file_put_contents($alvo, str_replace("\n", "\r\n", $resultado));

echo "substituidas as linhas {$inicio}-{$fim} de {$alvo}\n";
echo "linhas antes: " . count($linhas) . " | depois: " . (count(explode("\n", rtrim($resultado, "\n")))) . "\n";