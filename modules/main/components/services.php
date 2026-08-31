<?php
register_script('components/services', 'main');

$services = [
    [
        'title' => 'Cabelereiro',
        'desc' => 'Tranças, penteados, tendências num estilo único',
        'icon' => BASE_URL . '/common/img/cabelereiro.png',
        'delay' => '0.5s'
    ],
    [
        'title' => 'Barbearia',
        'desc' => 'Cortes únicos com todo o estilo das novas tendências',
        'icon' => BASE_URL . '/common/img/haircut.png',
        'delay' => '0.1s'
    ],
    [
        'title' => 'Estética',
        'desc' => 'Vais brilhar com um rosto brilhante e único',
        'icon' => BASE_URL . '/common/img/makeup.png',
        'delay' => '0.3s'
    ],

    ]
?>

<div class="container-fluid service py-5">
    <div class="container">
        <div class="text-center wow fadeIn" data-wow-delay="0.1s">
            <h1 class="font-dancing-script text-primary">Os nossos serviços</h1>
            <h1 class="mb-5">Explorando os nossos serviços</h1>
        </div>
        <div class="row g-4 g-md-0 text-center">
            <?php foreach ($services as $service): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="service-item h-100 p-4 border-bottom border-end wow fadeIn" data-wow-delay="<?php echo $service['delay']; ?>">
                        <img class="img-fluid" src="<?php echo htmlspecialchars($service['icon']); ?>" alt="<?php echo htmlspecialchars($service['title']); ?>">
                        <h3 class="mb-3"><?php echo htmlspecialchars($service['title']); ?></h3>
                        <p class="mb-3"><?php echo htmlspecialchars($service['desc']); ?></p>
                        <a class="btn btn-sm btn-primary text-uppercase" href="<?= BASE_URL ?>/servicos">mais informações <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
