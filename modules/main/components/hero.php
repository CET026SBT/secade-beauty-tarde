<?php
register_script('components/hero', 'main');
?>

<div class="container-fluid p-0 hero-header bg-light mb-5">
    <div class="container p-0">
        <div class="row g-0 align-items-center">
            <div class="col-lg-6 py-5 text-center">
                <div class="py-5 px-3 text-start d-inline-block">
                    <h1 class="font-dancing-script text-primary animated slideInLeft">Bem Vindos</h1>
                    <img class="h1 mb-4 img-fluid animated slideInLeft" src="<?= BASE_URL ?>/common/img/sb-title.svg" alt="Site name">
                    <div class="d-flex flex-column flex-md-row flex-lg-column animated slideInLeft">
                        <div class="d-flex align-items-center mb-2">
                            <div class="btn-square btn btn-primary flex-shrink-0">
                                <i class="fa fa-phone text-dark"></i>
                            </div>
                            <div class="px-3">
                                <h5 class="text-primary mb-0">Liga nos</h5>
                                <p class="fs-5 text-dark mb-0"><?php echo htmlspecialchars(SITE_PHONE); ?></p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="btn-square btn btn-primary flex-shrink-0">
                                <i class="fa fa-envelope text-dark"></i>
                            </div>
                            <div class="px-3">
                                <h5 class="text-primary mb-0">E-Mail</h5>
                                <p class="fs-5 text-dark mb-0"><?php echo htmlspecialchars(SITE_EMAIL); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="owl-carousel header-carousel animated fadeIn">
                    <img class="img-fluid" src="<?= BASE_URL ?>/common/img/hero-slider-1.jpg" alt="">
                    <img class="img-fluid" src="<?= BASE_URL ?>/common/img/hero-slider-2.jpg" alt="">
                    <img class="img-fluid" src="<?= BASE_URL ?>/common/img/hero-slider-3.jpg" alt="">
                </div>
            </div>
        </div>
    </div>
</div>
