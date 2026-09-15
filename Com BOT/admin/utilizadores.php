<?php
require_once __DIR__ . '/includes/admin-data.php';

$users = db()->query(
    'SELECT u.*, 
            CASE WHEN c.id IS NOT NULL THEN c.morada ELSE NULL END AS morada_cliente,
            CASE WHEN f.id IS NOT NULL THEN f.tipo_contrato ELSE NULL END AS tipo_contrato
     FROM utilizador u
     LEFT JOIN cliente c ON c.id = u.id
     LEFT JOIN funcionario f ON f.id = u.id
     ORDER BY u.criado_em DESC, u.nome'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4 text-gray-800">Utilizadores</h1>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Clientes, funcionarios e gestores</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Perfil</th>
                        <th>Detalhe</th>
                        <th>Criado em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= (int) $user['id'] ?></td>
                            <td><?= e($user['nome']) ?></td>
                            <td><?= e($user['email']) ?></td>
                            <td><?= e($user['telefone']) ?></td>
                            <td><span class="badge badge-primary"><?= e($user['tipo_perfil']) ?></span></td>
                            <td><?= e($user['morada_cliente'] ?: $user['tipo_contrato'] ?: '-') ?></td>
                            <td><?= e($user['criado_em']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
