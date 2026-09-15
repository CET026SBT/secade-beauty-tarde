<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

function count_table(string $table): int
{
    $allowed = ['servico', 'agendamento', 'utilizador', 'cidade', 'rota_ambulante', 'transacao_financeira'];
    if (!in_array($table, $allowed, true)) {
        return 0;
    }

    return (int) db()->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
}

function admin_categories(): array
{
    return db()->query('SELECT id, nome FROM categoria_profissional ORDER BY nome')->fetchAll();
}

function redirect_to(string $path): never
{
    header('Location: ' . $path);
    exit;
}
