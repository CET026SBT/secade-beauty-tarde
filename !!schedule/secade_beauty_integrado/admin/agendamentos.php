<?php
require_once __DIR__ . '/includes/admin-data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $estado = $_POST['estado_reserva'] ?? '';
    $allowed = ['pendente_aprovacao_viabilidade', 'confirmado', 'cancelado'];

    if ($id > 0 && in_array($estado, $allowed, true)) {
        $stmt = db()->prepare('UPDATE agendamento SET estado_reserva = ? WHERE id = ?');
        $stmt->execute([$estado, $id]);
    }
    redirect_to('agendamentos.php');
}

$bookings = db()->query(
    'SELECT a.*, u.nome AS cliente, u.email, u.telefone, cm.morada_completa, c.nome AS cidade,
            GROUP_CONCAT(s.nome ORDER BY s.nome SEPARATOR ", ") AS servicos
     FROM agendamento a
     JOIN utilizador u ON u.id = a.cliente_id
     LEFT JOIN cliente_morada cm ON cm.id = a.cliente_morada_id
     LEFT JOIN cidade c ON c.id = cm.cidade_id
     LEFT JOIN agendamento_servico ags ON ags.agendamento_id = a.id
     LEFT JOIN servico s ON s.id = ags.servico_id
     GROUP BY a.id, u.nome, u.email, u.telefone, cm.morada_completa, c.nome
     ORDER BY a.data_hora_pretendida DESC'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4 text-gray-800">Agendamentos</h1>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Reservas recebidas pelo site</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Contacto</th>
                        <th>Data</th>
                        <th>Local</th>
                        <th>Servicos</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= (int) $booking['id'] ?></td>
                            <td>
                                <strong><?= e($booking['cliente']) ?></strong><br>
                                <small><?= e($booking['morada_completa']) ?> <?= $booking['cidade'] ? '- ' . e($booking['cidade']) : '' ?></small>
                            </td>
                            <td><?= e($booking['email']) ?><br><small><?= e($booking['telefone']) ?></small></td>
                            <td><?= e($booking['data_hora_pretendida']) ?></td>
                            <td><?= e($booking['local_prestacao']) ?></td>
                            <td><?= e($booking['servicos']) ?></td>
                            <td><?= money($booking['valor_total']) ?><br><small>Sinal: <?= money($booking['valor_sinal']) ?></small></td>
                            <td>
                                <form method="post" class="d-flex">
                                    <input type="hidden" name="id" value="<?= (int) $booking['id'] ?>">
                                    <select name="estado_reserva" class="form-control form-control-sm mr-2">
                                        <?php foreach (['pendente_aprovacao_viabilidade', 'confirmado', 'cancelado'] as $state): ?>
                                            <option value="<?= e($state) ?>" <?= $state === $booking['estado_reserva'] ? 'selected' : '' ?>><?= e($state) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-success">OK</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
