<?php
require_once __DIR__ . '/includes/public-data.php';

$services = all_services();
$cities = all_cities();
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $morada = trim($_POST['morada'] ?? '');
    $codigoPostal = trim($_POST['codigo_postal'] ?? '');
    $cidadeId = (int) ($_POST['cidade_id'] ?? 0);
    $dataHora = trim($_POST['data_hora'] ?? '');
    $local = $_POST['local_prestacao'] ?? 'loja_fisica';
    $servicoIds = array_map('intval', $_POST['servicos'] ?? []);

    if ($nome === '' || $email === '' || $telefone === '' || $morada === '' || $cidadeId <= 0 || $dataHora === '' || !$servicoIds) {
        $error = 'Preencha todos os campos obrigatorios e escolha pelo menos um servico.';
    } else {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            $placeholders = implode(',', array_fill(0, count($servicoIds), '?'));
            $stmt = $pdo->prepare("SELECT id, preco_base, duracao_estimada_minutos FROM servico WHERE id IN ($placeholders)");
            $stmt->execute($servicoIds);
            $selected = $stmt->fetchAll();

            if (count($selected) !== count(array_unique($servicoIds))) {
                throw new RuntimeException('Um dos servicos escolhidos nao existe.');
            }

            $total = array_sum(array_map(static fn(array $s): float => (float) $s['preco_base'], $selected));
            $stmt = $pdo->prepare('SELECT id FROM utilizador WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $clienteId = (int) $stmt->fetchColumn();

            if ($clienteId > 0) {
                $stmt = $pdo->prepare('UPDATE utilizador SET nome = ?, telefone = ? WHERE id = ?');
                $stmt->execute([$nome, $telefone, $clienteId]);

                $stmt = $pdo->prepare('SELECT id FROM cliente WHERE id = ? LIMIT 1');
                $stmt->execute([$clienteId]);
                if (!$stmt->fetchColumn()) {
                    $stmt = $pdo->prepare('INSERT INTO cliente (id, morada) VALUES (?, ?)');
                    $stmt->execute([$clienteId, $morada]);
                }
            } else {
                $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO utilizador (nome, email, password_hash, telefone, tipo_perfil) VALUES (?, ?, ?, ?, "cliente")');
                $stmt->execute([$nome, $email, $password, $telefone]);
                $clienteId = (int) $pdo->lastInsertId();

                $stmt = $pdo->prepare('INSERT INTO cliente (id, morada) VALUES (?, ?)');
                $stmt->execute([$clienteId, $morada]);
            }

            $stmt = $pdo->prepare('INSERT INTO cliente_morada (cliente_id, cidade_id, morada_completa, codigo_postal) VALUES (?, ?, ?, ?)');
            $stmt->execute([$clienteId, $cidadeId, $morada, $codigoPostal]);
            $moradaId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO agendamento (cliente_id, cliente_morada_id, local_prestacao, data_hora_pretendida, valor_total, valor_sinal)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$clienteId, $moradaId, $local, $dataHora, $total, round($total * 0.10, 2)]);
            $agendamentoId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO agendamento_servico (agendamento_id, servico_id, preco_praticado, duracao_minutos)
                 VALUES (?, ?, ?, ?)'
            );
            foreach ($selected as $service) {
                $stmt->execute([$agendamentoId, $service['id'], $service['preco_base'], $service['duracao_estimada_minutos']]);
            }

            $pdo->commit();
            $success = true;
        } catch (Throwable $exception) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Nao foi possivel criar o agendamento: ' . $exception->getMessage();
        }
    }
}

include __DIR__ . '/includes/public-header.php';
?>

<div class="container-fluid page-header py-5 mb-5">
    <div class="container py-5">
        <h1 class="display-3 text-white mb-3">Agendar</h1>
    </div>
</div>

<div class="container py-5">
    <?php if ($success): ?>
        <div class="alert alert-success">Pedido de agendamento criado. O estado inicial fica como pendente de aprovacao de viabilidade.</div>
    <?php elseif ($error !== ''): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="row">
        <div class="col-lg-6">
            <h4 class="mb-3">Dados do cliente</h4>
            <div class="form-group">
                <label>Nome *</label>
                <input class="form-control" name="nome" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input class="form-control" type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Telefone *</label>
                <input class="form-control" name="telefone" required>
            </div>
            <div class="form-group">
                <label>Morada *</label>
                <textarea class="form-control" name="morada" rows="3" required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group col-md-7">
                    <label>Cidade *</label>
                    <select class="form-control" name="cidade_id" required>
                        <option value="">Escolher</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= (int) $city['id'] ?>"><?= e($city['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-5">
                    <label>Codigo postal</label>
                    <input class="form-control" name="codigo_postal">
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <h4 class="mb-3">Marcacao</h4>
            <div class="form-group">
                <label>Data e hora *</label>
                <input class="form-control" type="datetime-local" name="data_hora" required>
            </div>
            <div class="form-group">
                <label>Local *</label>
                <select class="form-control" name="local_prestacao">
                    <option value="loja_fisica">Loja fisica</option>
                    <option value="carrinha_ambulante">Carrinha ambulante</option>
                </select>
            </div>
            <div class="form-group">
                <label>Servicos *</label>
                <div class="border rounded p-3" style="max-height: 320px; overflow:auto;">
                    <?php foreach ($services as $service): ?>
                        <label class="d-block">
                            <input type="checkbox" name="servicos[]" value="<?= (int) $service['id'] ?>">
                            <?= e($service['nome']) ?> -
                            <strong><?= money($service['preco_base']) ?></strong>
                            <span class="form-help">(<?= e($service['categoria']) ?>, <?= (int) $service['duracao_estimada_minutos'] ?> min)</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="btn btn-primary btn-block py-3" type="submit">Enviar pedido</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/public-footer.php'; ?>
