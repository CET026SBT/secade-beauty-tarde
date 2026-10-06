<?php
/**
 * Regenera as secções de DADOS de `DataBase.sql` e `DataBase_clean.sql` a partir
 * da base de dados viva (F11 · dumps sincronizados sem depender de ferramentas).
 *
 * Uso: php _dev/tools/refresh-dump-data.php
 *
 * Regras (`_dev/docs/spec/data-api.md` §2):
 *   - `DataBase.sql`        → dump completo (tabelas + dados), estilo da ferramenta
 *   - `DataBase_clean.sql`  → esquema legível; só a linha de INSERT por tabela
 * Nada mais é tocado: os CREATE TABLE ficam exatamente como estão.
 */

$root = dirname(__DIR__, 2);
$tables = [
    "agendamento", "agendamento_pessoa", "agendamento_servico", "alerta_fiscal",
    "base_partida", "categoria_servico", "cidade", "cliente", "cliente_morada",
    "config_percentagem_padrao", "execucao_agendamento", "fecho_caixa_diario",
    "feedback_cliente", "fornecedor", "funcionario", "gorjeta", "manutencao_execucao",
    "matriz_deslocacao", "notificacao", "obrigacao_fiscal", "rota_ambulante",
    "rota_funcionario", "servico", "servico_foto", "servico_local",
    "transacao_financeira", "utilizador"
];

$pdo = new PDO("mysql:host=localhost;dbname=secade_beauty;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/** Tipos que se escrevem sem aspas. */
function isNumericType(string $type): bool {
    return (bool)preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|numeric|float|double|real)/i', $type);
}

function quoteValue($value, bool $numeric): string {
    if ($value === null) return "NULL";
    if ($numeric) return (string)$value;

    return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], (string)$value) . "'";
}

/** Linhas INSERT de uma tabela, no formato do dump. */
function tableInsertLines(PDO $pdo, string $table, string $indent): array {
    $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);

    $types = [];
    $names = [];
    foreach ($columns as $column) {
        $names[] = $column["Field"];
        $types[$column["Field"]] = isNumericType((string)$column["Type"]);
    }

    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        return [];
    }

    $lines = [];
    $lines[] = "INSERT INTO `{$table}` (`" . implode("`, `", $names) . "`) VALUES";

    $last = count($rows) - 1;
    foreach (array_values($rows) as $index => $row) {
        $values = [];
        foreach ($names as $name) {
            $values[] = quoteValue($row[$name], $types[$name]);
        }

        $lines[] = $indent . "(" . implode(", ", $values) . ")" . ($index === $last ? ";" : ",");
    }

    return $lines;
}
/**
 * Substitui o bloco de dados de cada tabela, preservando o CREATE TABLE e o resto.
 */
function replaceBlocks(string $path, array $tables, PDO $pdo): void {
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        fwrite(STDERR, "Não foi possível ler {$path}\n");
        exit(1);
    }

    $isFullDump = str_contains(basename($path), "DataBase.sql");
    $indent = $isFullDump ? "\t" : "  ";

    foreach ($tables as $table) {
        $lines = replaceTableBlock($lines, $table, $isFullDump, $indent, $pdo);
    }

    file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
    echo "Atualizado: " . basename($path) . " (" . count($lines) . " linhas)\n";
}

function replaceTableBlock(array $lines, string $table, bool $isFullDump, string $indent, PDO $pdo): array {
    $pattern = $isFullDump
        ? "/^-- Dumping structure for table secade_beauty\." . preg_quote($table, "/") . "$/"
        : "/^CREATE TABLE `" . preg_quote($table, "/") . "` \($/";

    $start = null;
    foreach ($lines as $index => $line) {
        if (preg_match($pattern, $line)) { $start = $index; break; }
    }

    if ($start === null) {
        fwrite(STDERR, "Tabela {$table} não encontrada\n");
        return $lines;
    }

    // Fim do CREATE TABLE
    $createEnd = $start;
    for ($i = $start; $i < count($lines); $i++) {
        if (str_starts_with(trim($lines[$i]), ") ENGINE=")) { $createEnd = $i; break; }
    }

    // Bloco de dados antigo
    $oldStart = null;
    $oldEnd   = null;

    if ($isFullDump) {
        for ($i = $createEnd + 1; $i < count($lines); $i++) {
            if (str_starts_with($lines[$i], "-- Dumping structure for table ")) break;
            $oldStart = $oldStart ?? ($i + 1);
            $oldEnd = $i;
        }
    } else {
        for ($i = $createEnd + 1; $i < count($lines); $i++) {
            if (str_starts_with($lines[$i], "INSERT INTO `{$table}`")) {
                $oldStart = $i;
                $oldEnd = $i;
                for ($j = $i + 1; $j < count($lines); $j++) {
                    if (!preg_match('/^\s*\(/', $lines[$j])) break;
                    $oldEnd = $j;
                }
                break;
            }
            if (str_starts_with($lines[$i], "CREATE TABLE ")) break;
        }
    }

    $inserts = tableInsertLines($pdo, $table, $indent);
    $count = $inserts === [] ? 0 : count($inserts) - 1;

    $replacement = [];
    if ($isFullDump) {
        $replacement[] = "-- Dumping data for table secade_beauty.{$table}: ~{$count} rows (approximately)";
        $replacement[] = "DELETE FROM `{$table}`;";
    }
    foreach ($inserts as $insertLine) {
        $replacement[] = $insertLine;
    }

    if ($oldStart === null) {
        // Tabela sem bloco prévio: acrescenta-se a seguir ao CREATE TABLE.
        if ($replacement === []) {
            return $lines;
        }
        array_splice($lines, $createEnd + 1, 0, array_merge([""], $replacement));
        return $lines;
    }

    array_splice($lines, $oldStart, $oldEnd - $oldStart + 1, $replacement);

    return $lines;
}
replaceBlocks($root . "/DataBase.sql", $tables, $pdo);
replaceBlocks($root . "/DataBase_clean.sql", $tables, $pdo);