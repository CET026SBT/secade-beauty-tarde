<?php
// A nossa missão — conceito de duas colunas (imagem + texto), na estética do projeto.
// A imagem é um ESPAÇO RESERVADO: substituir o ficheiro mantendo o nome (ou ajustar aqui).
?>
<div class="container-fluid py-5">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s sb-img-container">
                <img class="img-fluid w-100" src="<?= BASE_URL ?>/modules/common/img/about-images/about-mission.png"
                     alt="Missão da Secade Beauty">
            </div>
            <div class="col-lg-6 wow fadeIn" data-wow-delay="0.3s">
                <h1 class="font-dancing-script text-primary">A nossa missão</h1>
                <h1 class="mb-4">Cuidar de si, no salão ou à sua porta</h1>
                <p class="mb-4">A missão da <strong>Secade Beauty</strong> é tirar o esforço da marcação: reunir
                    <strong>cabeleireiro, barbearia e estética</strong> num só sítio, com o <strong>preço com IVA</strong>
                    e a <strong>duração estimada</strong> à vista antes de confirmar, para que ninguém tenha de
                    telefonar nem de esperar por resposta.</p>
                <p class="mb-4">Na loja de <strong>Évora</strong> recebemos de <strong>terça a sábado, das 09:00 às
                    19:00</strong>, em marcações de 30 minutos. Quando ir ao salão não é possível, é a
                    <strong>carrinha</strong> que se desloca — com os serviços organizados <strong>por pessoa</strong>
                    e confirmados por código.</p>
                <p class="mb-0">Trabalhamos com uma equipa de profissionais e um catálogo de
                    <strong><span data-site-stat="services"><?= (int)SITE_STATS_FALLBACK["services"] ?></span> serviços</strong>
                    em <strong><span data-site-stat="categories"><?= (int)SITE_STATS_FALLBACK["categories"] ?></span> áreas</strong>,
                    em <strong><span data-site-stat="cities"><?= (int)SITE_STATS_FALLBACK["cities"] ?></span> cidades</strong>
                    do distrito de Évora.</p>
            </div>
        </div>
    </div>
</div>