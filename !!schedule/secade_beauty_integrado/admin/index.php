<?php
require_once __DIR__ . '/includes/admin-data.php';

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
