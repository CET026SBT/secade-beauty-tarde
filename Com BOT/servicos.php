<?php
require_once __DIR__ . '/includes/public-data.php';
$services = all_services();
include __DIR__ . '/includes/public-header.php';
?>

<div class="container-fluid page-header py-5 mb-5">
    <div class="container py-5">
        <h1 class="display-3 text-white mb-3">Servicos</h1>
    </div>
</div>

<div class="container py-5">
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
</div>

<?php include __DIR__ . '/includes/public-footer.php'; ?>
