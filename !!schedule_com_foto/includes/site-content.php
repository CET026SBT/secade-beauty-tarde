<?php
declare(strict_types=1);

function site_content_file(): string
{
    return __DIR__ . '/../config/site-content.json';
}

function default_site_content(): array
{
    return [
        'featured_image' => '',
        'featured_title' => 'Destaque Secade Beauty',
        'featured_text' => 'Adicione uma imagem no painel administrativo para a apresentar aqui.',
    ];
}

function site_content(): array
{
    $file = site_content_file();
    if (!is_file($file)) {
        return default_site_content();
    }

    $content = json_decode((string) file_get_contents($file), true);
    if (!is_array($content)) {
        return default_site_content();
    }

    return array_merge(default_site_content(), $content);
}

function save_site_content(array $content): void
{
    $file = site_content_file();
    file_put_contents(
        $file,
        json_encode(array_merge(default_site_content(), $content), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}
