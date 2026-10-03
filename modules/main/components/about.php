<?php
register_script("components/about", "main");

// Indicadores do "Sobre nós": valor CONTADO na BD via `siteStats.utils.js` e, sem dados,
// o valor documental (fonte única: `SITE_STATS_FALLBACK` em `app/config/config.php`).
$statsFallback = SITE_STATS_FALLBACK;
?>

<div class="container-fluid py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-6 wow fadeIn d-flex flex-column" data-wow-delay="0.2s">
                <div class="img-container flex-grow-1 mb-3">
                    <img src="<?= BASE_URL ?>/modules/common/img/about.png" alt="Sobre nós">
                </div>
                <div class="d-flex align-items-center bg-light">
                    <div class="btn-square-100 btn-square flex-shrink-0 bg-primary">
                        <i class="fa fa-phone fa-2x text-dark"></i>
                    </div>
                    <div class="px-3">
                        <h3><?php echo htmlspecialchars(SITE_PHONE); ?></h3>
                        <span>Liga-nos para uma experiência personalizada...</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 wow fadeIn info-container" data-wow-delay="0.5s">
                <h1 class="font-dancing-script text-primary">Sobre nós</h1>
                <h1 class="mb-5">Beleza em Évora, no salão ou à sua porta</h1>
                <p class="mb-4">A <strong>Secade Beauty</strong> junta, no mesmo serviço, o <strong>salão</strong> e a
                    <strong>carrinha ambulante</strong>: marcamos cabeleireiro, barbearia e estética na loja de Évora e
                    vamos ao domicílio quando é mais cómodo para si.</p>
                <p class="mb-4">Trabalhamos com um catálogo de <strong><span data-site-stat="services"><?= (int)$statsFallback["services"] ?></span> serviços</strong>
                    em <strong><span data-site-stat="categories"><?= (int)$statsFallback["categories"] ?></span> áreas</strong>, com
                    <strong>preços desde <span data-site-stat="minPrice">5,01 €</span></strong> (IVA incluído) e a <strong>duração estimada</strong> à vista antes
                    de confirmar — sem surpresas. Na loja o horário é de <strong>terça a sábado, 09:00–19:00</strong>, com
                    marcações de 30 minutos; na carrinha escolhe a morada em
                    <strong><span data-site-stat="cities"><?= (int)$statsFallback["cities"] ?></span> cidades</strong> do
                    distrito de Évora e os serviços são organizados <strong>por pessoa</strong>, com confirmação por código.</p>
                <p class="mb-4">Cada serviço aceite pela nossa equipa fica registado, por isso sabe sempre em que ponto está
                    a sua marcação — e, no fim, pode avaliar o atendimento. É assim que garantimos que o resultado fica
                    à altura do que prometemos.</p>
                <div class="row g-3 mb-5">
                    <div class="col-sm-6">
                        <div class="bg-light text-center p-4">
                            <i class="fas fa-users fa-4x text-primary"></i>
                            <h1 class="display-5" id="aboutStatTeam" data-toggle="counter-up"><?= (int)$statsFallback["team"] ?></h1>
                            <p class="text-dark text-uppercase mb-0">Profissionais</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="bg-light text-center p-4">
                            <i class="fas fa-spa fa-4x text-primary"></i>
                            <h1 class="display-5" id="aboutStatServices" data-toggle="counter-up"><?= (int)$statsFallback["services"] ?></h1>
                            <p class="text-dark text-uppercase mb-0">Serviços disponíveis</p>
                        </div>
                    </div>
                </div>
                <?php
                if ($currentPage != "about.php" && $currentPage != "sobre") {
                    echo '<a class="btn btn-primary extended-border text-uppercase px-5 py-3" href="' . BASE_URL . '/sobre">Mais sobre nós</a>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
