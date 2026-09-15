<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/site-content.php';

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
        'SELECT s.*, c.nome AS categoria,
                sf.url_foto AS foto
         FROM servico s
         JOIN categoria_profissional c ON c.id = s.categoria_id
         LEFT JOIN servico_foto sf ON sf.id = (
             SELECT sf2.id
             FROM servico_foto sf2
             WHERE sf2.servico_id = s.id
             ORDER BY sf2.destaque DESC, sf2.ordem_exibicao ASC, sf2.id DESC
             LIMIT 1
         )
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
        'SELECT s.*, c.nome AS categoria,
                sf.url_foto AS foto
         FROM servico s
         JOIN categoria_profissional c ON c.id = s.categoria_id
         LEFT JOIN servico_foto sf ON sf.id = (
             SELECT sf2.id
             FROM servico_foto sf2
             WHERE sf2.servico_id = s.id
             ORDER BY sf2.destaque DESC, sf2.ordem_exibicao ASC, sf2.id DESC
             LIMIT 1
         )
         ORDER BY c.nome, s.nome'
    )->fetchAll();
}

function service_image(array $service): string
{
    if (!empty($service['foto'])) {
        return (string) $service['foto'];
    }

    $fallbacks = [
        'Cabelereiro' => 'assets/img/haircut.png',
        'Barbearia' => 'assets/img/haircut.png',
        'Estética' => 'assets/img/makeup.png',
    ];

    return $fallbacks[$service['categoria'] ?? ''] ?? 'assets/img/skin-care.png';
}

function all_cities(): array
{
    return db()->query('SELECT id, nome FROM cidade ORDER BY nome')->fetchAll();
}
