<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/site-content.php';

function handle_image_upload(array $file, string $prefix): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('O upload da imagem falhou.');
    }

    if (($file['size'] ?? 0) > 4 * 1024 * 1024) {
        throw new RuntimeException('A imagem deve ter no maximo 4 MB.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $mime = mime_content_type($tmpName);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Use uma imagem JPG, PNG, GIF ou WEBP.');
    }

    $uploadDir = __DIR__ . '/../../assets/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $filename = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extensions[$mime];
    $target = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($tmpName, $target)) {
        throw new RuntimeException('Nao foi possivel guardar a imagem.');
    }

    return 'assets/uploads/' . $filename;
}

function handle_featured_image_upload(array $file): string
{
    return handle_image_upload($file, 'destaque');
}

function save_service_photo(int $serviceId, string $imagePath): void
{
    if ($imagePath === '') {
        return;
    }

    $pdo = db();
    $stmt = $pdo->prepare('UPDATE servico_foto SET destaque = 0 WHERE servico_id = ?');
    $stmt->execute([$serviceId]);

    $stmt = $pdo->prepare(
        'INSERT INTO servico_foto (servico_id, url_foto, destaque, ordem_exibicao)
         VALUES (?, ?, 1, 0)'
    );
    $stmt->execute([$serviceId, $imagePath]);
}

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
