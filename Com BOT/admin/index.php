<?php
require_once __DIR__ . '/includes/admin-data.php';

$siteContent = site_content();
$imageMessage = '';
$imageError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_site_feature') {
    try {
        $newImage = handle_featured_image_upload($_FILES['featured_image'] ?? []);
        $siteContent['featured_title'] = trim($_POST['featured_title'] ?? '') ?: 'Destaque Secade Beauty';
        $siteContent['featured_text'] = trim($_POST['featured_text'] ?? '') ?: '';

        if ($newImage !== '') {
            $siteContent['featured_image'] = $newImage;
        }

        save_site_content($siteContent);
        $imageMessage = 'Destaque atualizado com sucesso.';
    } catch (Throwable $exception) {
        $imageError = $exception->getMessage();
    }
}

$latestBookings = db()->query(
    'SELECT a.id, a.data_hora_pretendida, a.estado_reserva, a.valor_total, u.nome AS cliente
     FROM agendamento a
     JOIN utilizador u ON u.id = a.cliente_id
     ORDER BY a.criado_em DESC
     LIMIT 8'
)->fetchAll();

$income = (float) db()->query('SELECT COALESCE(SUM(valor_total), 0) FROM agendamento WHERE estado_reserva = "confirmado"')->fetchColumn();

include __DIR__ . '/includes/header.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
</div>

<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Servicos</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count_table('servico') ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Agendamentos</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count_table('agendamento') ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Utilizadores</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count_table('utilizador') ?></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Receita confirmada</div>
                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= money($income) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Imagem de destaque do site</h6>
    </div>
    <div class="card-body">
        <?php if ($imageMessage !== ''): ?>
            <div class="alert alert-success"><?= e($imageMessage) ?></div>
        <?php endif; ?>
        <?php if ($imageError !== ''): ?>
            <div class="alert alert-danger"><?= e($imageError) ?></div>
        <?php endif; ?>

        <div class="row align-items-center">
            <div class="col-lg-5 mb-4 mb-lg-0">
                <?php if ($siteContent['featured_image'] !== ''): ?>
                    <img src="../<?= e($siteContent['featured_image']) ?>" class="img-fluid rounded shadow-sm" alt="<?= e($siteContent['featured_title']) ?>">
                <?php else: ?>
                    <div class="border rounded p-5 text-center text-muted">Ainda nao existe imagem de destaque.</div>
                <?php endif; ?>
            </div>
            <div class="col-lg-7">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_site_feature">
                    <div class="form-group">
                        <label>Imagem</label>
                        <input type="file" name="featured_image" class="form-control-file" accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="form-text text-muted">JPG, PNG, GIF ou WEBP ate 4 MB. Se nao escolher imagem, mantem a atual.</small>
                    </div>
                    <div class="form-group">
                        <label>Titulo</label>
                        <input name="featured_title" class="form-control" value="<?= e($siteContent['featured_title']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Texto</label>
                        <textarea name="featured_text" class="form-control" rows="3"><?= e($siteContent['featured_text']) ?></textarea>
                    </div>
                    <button class="btn btn-primary">Guardar destaque</button>
                    <a class="btn btn-outline-secondary" href="../index.php" target="_blank">Ver no site</a>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Agendamentos recentes</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Data pretendida</th>
                        <th>Estado</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($latestBookings as $booking): ?>
                        <tr>
                            <td><?= (int) $booking['id'] ?></td>
                            <td><?= e($booking['cliente']) ?></td>
                            <td><?= e($booking['data_hora_pretendida']) ?></td>
                            <td><?= e($booking['estado_reserva']) ?></td>
                            <td><?= money($booking['valor_total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
