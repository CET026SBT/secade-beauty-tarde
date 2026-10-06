<?php
/**
 * Divide o `backlog.md` na fronteira de §N (o ficheiro passou das 400 linhas):
 *   ≥ linha $corte  ->  novo ficheiro (trabalho futuro, §25)
 *   <  linha $corte  ->  fica no backlog.md (gap analysis, §24)
 * Uso: php .tmp-split-25.php
 */
$raiz    = dirname(__DIR__, 2);
$origem  = $raiz . "/_dev/docs/spec/backlog.md";
$destino = $raiz . "/_dev/docs/spec/backlog-future.md";
$corte   = 352; // 1-based: linha do "## 25. TRABALHO FUTURO PRIORIZADO"

$texto = str_replace("\r\n", "\n", file_get_contents($origem));
$linhas = explode("\n", $texto);

$gap    = rtrim(implode("\n", array_slice($linhas, 0, $corte - 1)), "\n");
$futuro = rtrim(implode("\n", array_slice($linhas, $corte - 1)), "\n");

$cabecalho = <<<MD
# SECADE BEAUTY — TRABALHO FUTURO PRIORIZADO (§25)
**Especificação — ficheiro de domínio** · branch **`agent-workspace`**
**Âmbito:** §25 — prioridades vinculativas, consolidações técnicas, nice-to-have e a **estrutura de menus**
do backoffice que toda a implementação futura tem de respeitar.
**Router:** `especificacao_mvp.md` · **Gap analysis (§24):** `backlog.md` (ficheiro irmão).

MD;

file_put_contents($origem, str_replace("\n", "\r\n", $gap . "\n"));
file_put_contents($destino, str_replace("\n", "\r\n", $cabecalho . $futuro . "\n"));

echo "backlog.md: " . (count(explode("\n", $gap))) . " linhas\n";
echo "backlog-future.md: " . (count(explode("\n", $cabecalho . $futuro))) . " linhas\n";