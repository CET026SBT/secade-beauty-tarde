<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function public_stats(): array
{
    $pdo = db();

    return [
        'servicos' => (int) $pdo->query('SELECT COUNT(*) FROM servico')->fetchColumn(),
        'categorias' => (int) $pdo->query('SELECT COUNT(*) FROM categoria_profissional')->fetchColumn(),
        'cidades' => (int) $pdo->query('SELECT COUNT(*) FROM cidade')->fetchColumn(),
    ];
}

function featured_services(int $limit = 6): array
{
    $stmt = db()->prepare(
        'SELECT s.*, c.nome AS categoria
         FROM servico s
         JOIN categoria_profissional c ON c.id = s.categoria_id
         ORDER BY c.nome, s.nome
         LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function all_services(): array
{
    return db()->query(
        'SELECT s.*, c.nome AS categoria
         FROM servico s
         JOIN categoria_profissional c ON c.id = s.categoria_id
         ORDER BY c.nome, s.nome'
    )->fetchAll();
}

function all_cities(): array
{
    return db()->query('SELECT id, nome FROM cidade ORDER BY nome')->fetchAll();
}
