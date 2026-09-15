<?php
require_once __DIR__ . '/includes/public-data.php';
$stats = public_stats();
$services = featured_services(6);
$siteContent = site_content();
include __DIR__ . '/includes/public-header.php';
?>

<div class="jumbotron jumbotron-fluid bg-jumbotron mb-5">
    <div class="container text-center py-5">
        <div class="hero-copy mx-auto">
            <h1 class="text-white display-3 mb-4">Secade Beauty</h1>
            <p class="text-white lead mb-4">Cabelereiro, barbearia e estetica com agendamento online, servicos em loja e atendimento ambulante.</p>
            <a href="agendar.php" class="btn btn-primary py-3 px-5">Agendar servico</a>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row text-center mb-5">
        <div class="col-md-4 mb-3">
            <h2 class="text-primary"><?= $stats['servicos'] ?></h2>
            <p>Servicos ativos</p>
        </div>
        <div class="col-md-4 mb-3">
            <h2 class="text-primary"><?= $stats['categorias'] ?></h2>
            <p>Categorias profissionais</p>
        </div>
        <div class="col-md-4 mb-3">
            <h2 class="text-primary"><?= $stats['cidades'] ?></h2>
            <p>Cidades ambulantes</p>
        </div>
    </div>

    <?php if ($siteContent['featured_image'] !== ''): ?>
        <div class="site-featured mb-5">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <img class="img-fluid site-featured-image" src="<?= e($siteContent['featured_image']) ?>" alt="<?= e($siteContent['featured_title']) ?>">
                </div>
                <div class="col-lg-5">
                    <h6 class="d-inline-block bg-light text-primary text-uppercase py-1 px-2">Destaque</h6>
                    <h2 class="mb-3"><?= e($siteContent['featured_title']) ?></h2>
                    <p class="lead mb-0"><?= e($siteContent['featured_text']) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="text-center mb-5">
        <h6 class="d-inline-block bg-light text-primary text-uppercase py-1 px-2">Catalogo</h6>
        <h1>Servicos em destaque</h1>
    </div>
    <div class="row">
        <?php foreach ($services as $service): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card border-0 shadow-sm service-card">
                    <img class="card-img-top service-card-image" src="<?= e(service_image($service)) ?>" alt="<?= e($service['nome']) ?>">
                    <div class="card-body">
                        <span class="badge badge-light mb-2"><?= e($service['categoria']) ?></span>
                        <h5><?= e($service['nome']) ?></h5>
                        <p><?= e($service['descricao']) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="service-price text-primary"><?= money($service['preco_base']) ?></span>
                            <small><?= (int) $service['duracao_estimada_minutos'] ?> min</small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="text-center mt-3">
        <a href="servicos.php" class="btn btn-outline-primary">Ver todos os servicos</a>
    </div>
</div>

<?php include __DIR__ . '/includes/public-footer.php'; ?>
