<?php
// O nosso espaço — galeria do salão e da carrinha (conceito de mosaico da referência).
// As imagens são ESPAÇOS RESERVADOS: substituir os ficheiros mantendo os nomes.
$espaco = [
    ["image" => "about-images/about-store-1.png", "alt" => "Sala de atendimento do salão de Évora"],
    ["image" => "about-images/about-store-2.png", "alt" => "Zona de trabalho do salão"],
    ["image" => "about-images/about-store-3.png", "alt" => "Equipamento profissional do salão"],
    ["image" => "about-images/about-store-4.png", "alt" => "Receção do salão de Évora"],
    ["image" => "about-images/about-store-5.png", "alt" => "Carrinha ambulante da Secade Beauty"],
    ["image" => "about-images/about-store-6.png", "alt" => "Detalhe do espaço de trabalho"]
];
?>
<div class="container-fluid py-5">
    <div class="container">
        <div class="text-center wow fadeIn" data-wow-delay="0.1s">
            <h1 class="font-dancing-script text-primary">O nosso espaço</h1>
            <h1 class="mb-3">A loja de Évora e a carrinha</h1>
            <p class="text-muted mx-auto mb-5" style="max-width: 720px;">
                Um salão pensado para o atendimento por marcação, de terça a sábado, das 09:00 às 19:00 — e uma
                carrinha preparada para levar os mesmos serviços até à porta de casa.
            </p>
        </div>
        <div class="row g-4">
            <?php foreach ($espaco as $index => $item): ?>
                <div class="col-md-6 col-lg-4 wow fadeIn" data-wow-delay="<?= 0.2 + $index * 0.1 ?>s">
                    <div class="ratio ratio-4x3 bg-light border-bottom border-end img-container">
                        <img src="<?= BASE_URL ?>/modules/common/img/<?= htmlspecialchars($item["image"]) ?>"
                             alt="<?= htmlspecialchars($item["alt"]) ?>">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-5 wow fadeIn" data-wow-delay="0.8s">
            <a class="btn btn-primary extended-border text-uppercase px-5 py-3" href="<?= BASE_URL ?>/servicos">
                Ver o catálogo <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>