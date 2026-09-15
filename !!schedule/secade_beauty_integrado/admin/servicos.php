<?php
require_once __DIR__ . '/includes/admin-data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $data = [
        trim($_POST['nome'] ?? ''),
        trim($_POST['descricao'] ?? ''),
        (int) ($_POST['categoria_id'] ?? 0),
        (int) ($_POST['duracao_estimada_minutos'] ?? 0),
        (float) ($_POST['preco_base'] ?? 0),
        isset($_POST['requer_espaco_fisico']) ? 1 : 0,
    ];

    if (($_POST['action'] ?? '') === 'delete' && $id > 0) {
        $stmt = db()->prepare('DELETE FROM servico WHERE id = ?');
        $stmt->execute([$id]);
        redirect_to('servicos.php');
    }

    if ($id > 0) {
        $stmt = db()->prepare(
            'UPDATE servico
             SET nome = ?, descricao = ?, categoria_id = ?, duracao_estimada_minutos = ?, preco_base = ?, requer_espaco_fisico = ?
             WHERE id = ?'
        );
        $stmt->execute([...$data, $id]);
    } else {
        $stmt = db()->prepare(
            'INSERT INTO servico (nome, descricao, categoria_id, duracao_estimada_minutos, preco_base, requer_espaco_fisico)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute($data);
    }
    redirect_to('servicos.php');
}

$categories = admin_categories();
$services = db()->query(
    'SELECT s.*, c.nome AS categoria
     FROM servico s
     JOIN categoria_profissional c ON c.id = s.categoria_id
     ORDER BY c.nome, s.nome'
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4 text-gray-800">Servicos</h1>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Novo servico</h6>
    </div>
    <div class="card-body">
        <form method="post" class="row">
            <div class="form-group col-md-4">
                <label>Nome</label>
                <input name="nome" class="form-control" required>
            </div>
            <div class="form-group col-md-3">
                <label>Categoria</label>
                <select name="categoria_id" class="form-control" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"><?= e($category['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-md-2">
                <label>Duracao</label>
                <input name="duracao_estimada_minutos" type="number" class="form-control" required>
            </div>
            <div class="form-group col-md-2">
                <label>Preco</label>
                <input name="preco_base" type="number" step="0.01" class="form-control" required>
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <button class="btn btn-primary btn-block">Criar</button>
            </div>
            <div class="form-group col-md-12">
                <label>Descricao</label>
                <textarea name="descricao" class="form-control" required></textarea>
            </div>
            <div class="form-group col-md-12">
                <label><input type="checkbox" name="requer_espaco_fisico"> Requer espaco fisico</label>
            </div>
        </form>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Catalogo</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered datatable">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Duracao</th>
                        <th>Preco</th>
                        <th>Espaco</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <form method="post">
                                <input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                                <td><input name="nome" class="form-control" value="<?= e($service['nome']) ?>"></td>
                                <td>
                                    <select name="categoria_id" class="form-control">
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= (int) $category['id'] ?>" <?= (int) $category['id'] === (int) $service['categoria_id'] ? 'selected' : '' ?>><?= e($category['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <textarea name="descricao" class="form-control mt-2"><?= e($service['descricao']) ?></textarea>
                                </td>
                                <td><input name="duracao_estimada_minutos" type="number" class="form-control" value="<?= (int) $service['duracao_estimada_minutos'] ?>"></td>
                                <td><input name="preco_base" type="number" step="0.01" class="form-control" value="<?= e((string) $service['preco_base']) ?>"></td>
                                <td class="text-center"><input type="checkbox" name="requer_espaco_fisico" <?= (int) $service['requer_espaco_fisico'] === 1 ? 'checked' : '' ?>></td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-success">Guardar</button>
                                    <button class="btn btn-sm btn-danger" name="action" value="delete" onclick="return confirm('Eliminar este servico?')">Eliminar</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
