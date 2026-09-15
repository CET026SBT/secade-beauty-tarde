<?php
require_once __DIR__ . '/includes/admin-data.php';

$rows = db()->query(
    'SELECT m.*, b.nome AS base_partida, c.nome AS cidade
     FROM matriz_deslocacao m
     JOIN base_partida b ON b.id = m.base_partida_id
     JOIN cidade c ON c.id = m.cidade_id
     ORDER BY c.nome'
)->fetchAll();

$routes = db()->query(
    'SELECT r.*, b.nome AS base_partida, c.nome AS cidade
     FROM rota_ambulante r
     JOIN base_partida b ON b.id = r.base_partida_id
     JOIN cidade c ON c.id = r.cidade_id
     ORDER BY r.data_rota DESC'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4 text-gray-800">Deslocacoes</h1>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Matriz de deslocacao</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered datatable">
                        <thead>
                            <tr>
                                <th>Base</th>
                                <th>Cidade</th>
                                <th>Distancia</th>
                                <th>Tempo</th>
                                <th>Custo combustivel</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= e($row['base_partida']) ?></td>
                                    <td><?= e($row['cidade']) ?></td>
                                    <td><?= e((string) $row['distancia_km']) ?> km</td>
                                    <td><?= (int) $row['tempo_estimado_minutos'] ?> min</td>
                                    <td><?= money($row['custo_estimado_combustivel']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Rotas ambulantes</h6>
            </div>
            <div class="card-body">
                <?php if (!$routes): ?>
                    <p class="mb-0">Ainda nao existem rotas registadas.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Cidade</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($routes as $route): ?>
                                    <tr>
                                        <td><?= e($route['data_rota']) ?></td>
                                        <td><?= e($route['cidade']) ?></td>
                                        <td><?= e($route['estado_rota']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
