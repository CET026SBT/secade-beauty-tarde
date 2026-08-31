<?php
register_script('components/about', 'main');
?>

<div class="container-fluid py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-6 wow fadeIn" data-wow-delay="0.2s">
                <img class="img-fluid mb-3" src="<?= BASE_URL ?>/common/img/about.jpg" alt="Sobre nós">
                <div class="d-flex align-items-center bg-light">
                    <div class="btn-phone btn-square flex-shrink-0 bg-primary">
                        <i class="fa fa-phone fa-2x text-dark"></i>
                    </div>
                    <div class="px-3">
                        <h3><?php echo htmlspecialchars(SITE_PHONE); ?></h3>
                        <span>Liga-nos para uma experiência personalizada...</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                <h1 class="font-dancing-script text-primary">Sobre nós</h1>
                <h1 class="mb-5">Porque somos os escolhidos</h1>
                <p class="mb-4">Com os nossos 30 anos de experiência, aliados aos nossos clientes satisfeitos, ímpares na vanguarda das tendências de moda e com profissionais simpáticos e sempre prontos a ajudar, estas são algumas das valências que fazem de nós o número um de mercado, venha experimentar!</p>
                <div class="row g-3 mb-5">
                    <div class="col-sm-6">
                        <div class="bg-light text-center p-4">
                            <i class="fas fa-calendar-alt fa-4x text-primary"></i>
                            <h1 class="display-5" data-toggle="counter-up">30</h1>
                            <p class="text-dark text-uppercase mb-0">Anos de experiência</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="bg-light text-center p-4">
                            <i class="fas fa-users fa-4x text-primary"></i>
                            <h1 class="display-5" data-toggle="counter-up">999</h1>
                            <p class="text-dark text-uppercase mb-0">Clientes satisfeitos</p>
                        </div>
                    </div>
                </div>
                <?php
                if ($currentPage != "about.php" && $currentPage != "sobre") {
                    echo '<a class="btn btn-primary text-uppercase px-5 py-3" href="' . BASE_URL . '/sobre">Mais sobre nós</a>';
                }
                ?>
            </div>
        </div>
    </div>
</div>
