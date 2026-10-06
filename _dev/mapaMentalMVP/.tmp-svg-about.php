<?php
/**
 * Gera os ESPACOS RESERVADOS de imagem dos componentes `about*` (uso unico, local).
 * Mesmo molde dos `overview-*.svg` ja existentes: 800x600, moldura tracejada e
 * a instrucao de substituicao escrita na propria imagem.
 */
$destino = dirname(__DIR__, 2) . "/modules/common/img";
if (!is_dir($destino)) { fwrite(STDERR, "sem pasta de imagens\n"); exit(1); }

$mapa = [
    "about-mission.svg"      => "Imagem da missao da Secade Beauty",
    "about-story.svg"        => "Imagem da historia da Secade Beauty",
    "about-team.svg"         => "Fotografia da equipa",
    "about-commitment.svg"   => "Imagem do compromisso com o cliente",
    "about-store-1.svg"      => "Fotografia do espaco 1 (salao)",
    "about-store-2.svg"      => "Fotografia do espaco 2 (zona de trabalho)",
    "about-store-3.svg"      => "Fotografia do espaco 3 (equipamento)",
    "about-store-4.svg"      => "Fotografia do espaco 4 (rececao)",
    "about-store-5.svg"      => "Fotografia do espaco 5 (carrinha)",
    "about-store-6.svg"      => "Fotografia do espaco 6 (detalhe)",
];

$modelo = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600" width="800" height="600" role="img"
     aria-label="{aria}">
    <rect width="800" height="600" fill="#f8f5f2"/>
    <rect x="12" y="12" width="776" height="576" fill="none" stroke="#c9a227" stroke-width="3"
          stroke-dasharray="16 12"/>
    <text x="400" y="270" font-family="Inter, Arial, sans-serif" font-size="34" fill="#8a6d1f"
          text-anchor="middle" font-weight="700">{titulo}</text>
    <text x="400" y="318" font-family="Inter, Arial, sans-serif" font-size="22" fill="#8a6d1f"
          text-anchor="middle">Substituir por fotografia real (800 &#215; 600)</text>
    <text x="400" y="356" font-family="Inter, Arial, sans-serif" font-size="18" fill="#b09444"
          text-anchor="middle">{ficheiro}</text>
</svg>

SVG;

foreach ($mapa as $ficheiro => $titulo) {
    $svg = str_replace(
        ["{aria}", "{titulo}", "{ficheiro}"],
        [$titulo, $titulo, $ficheiro],
        $modelo
    );
    file_put_contents($destino . "/" . $ficheiro, $svg);
    echo "criado: modules/common/img/{$ficheiro}\n";
}
echo "total: " . count($mapa) . "\n";