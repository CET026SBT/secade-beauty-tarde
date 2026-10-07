<?php
/**
 * SEED TEMPORARIO — dados para testar a F4 (alocacao) e a F5 (avisos).
 * Nao versionar. Re-executavel: limpa primeiro o que criou.
 */
require __DIR__ . "/app/config/config.php";
require APP_PATH . "/config/connection.php";
global $conn;

$CUSTOMER = 3;        // cliente@teste.pt
$EFFECTIVE = 4;       // Janeth Tecnica (efetivo, 10%)
$RV = 2;              // Ana Tecnica (recibo verde, 70%)

function workingDate(int $offset): string {
    $ts = strtotime("+{$offset} day");
    while ((int)date("N", $ts) < 2 || (int)date("N", $ts) > 6) { $ts = strtotime("+1 day", $ts); }
    return date("Y-m-d", $ts);
}

$D1 = workingDate(6);
$D2 = workingDate(7);
$D3 = workingDate(8);

// ---- limpeza (re-executavel) --------------------------------------------
$conn->exec("DELETE FROM agendamento_servico WHERE agendamento_id IN (SELECT id FROM agendamento WHERE cliente_morada_id IN (SELECT id FROM cliente_morada WHERE rua LIKE 'SEED F4F5%'))");
$conn->exec("DELETE FROM agendamento WHERE cliente_morada_id IN (SELECT id FROM cliente_morada WHERE rua LIKE 'SEED F4F5%')");
$conn->exec("DELETE FROM cliente_morada WHERE rua LIKE 'SEED F4F5%'");
$conn->exec("DELETE FROM alerta_fiscal WHERE obrigacao_fiscal_id IN (SELECT id FROM obrigacao_fiscal WHERE observacoes = 'SEED F4F5')");
$conn->exec("DELETE FROM obrigacao_fiscal WHERE observacoes = 'SEED F4F5'");

// ---- moradas temporarias -------------------------------------------------
function address(PDO $conn, int $customer, int $cityId, string $rua): int {
    $stmt = $conn->prepare("INSERT INTO cliente_morada (cliente_id, cidade_id, designacao, rua, numero_porta, principal) VALUES (:c, :city, 'Temporaria', :rua, '1', 0)");
    $stmt->execute(["c" => $customer, "city" => $cityId, "rua" => $rua]);
    return (int)$conn->lastInsertId();
}

$A1 = address($conn, $CUSTOMER, 1, "SEED F4F5 Rua Arraiolos");
$A2 = address($conn, $CUSTOMER, 2, "SEED F4F5 Rua Montemor");
$A3 = address($conn, $CUSTOMER, 3, "SEED F4F5 Rua Viana");

// ---- agendamentos + servicos --------------------------------------------
function booking(PDO $conn, int $customer, int $addressId, string $date, string $time, array $services, string $estado, ?int $assignTo = null, float $pct = 0.0): int {
    $total = 0.0;
    foreach ($services as $s) { $total += $s[1]; }

    $stmt = $conn->prepare("INSERT INTO agendamento (cliente_id, cliente_morada_id, local_prestacao, data_hora_pretendida, estado_reserva, valor_total) VALUES (:c, :m, 'carrinha_ambulante', :dt, :estado, :total)");
    $stmt->execute(["c" => $customer, "m" => $addressId, "dt" => "{$date} {$time}:00", "estado" => $estado, "total" => $total]);
    $bookingId = (int)$conn->lastInsertId();

    foreach ($services as $s) {
        [$serviceId, $price, $duration] = $s;
        $aceite = $assignTo !== null;
        $ins = $conn->prepare("INSERT INTO agendamento_servico (agendamento_id, servico_id, funcionario_id, preco_praticado, duracao_minutos, estado_aceitacao, aceito_em, percentagem_funcionario_aplicada) VALUES (:a, :s, :f, :p, :d, :estado, :aceito, :pct)");
        $ins->execute([
            "a" => $bookingId, "s" => $serviceId, "f" => $assignTo,
            "p" => $price, "d" => $duration,
            "estado" => $aceite ? "aceite" : "pendente",
            "aceito" => $aceite ? date("Y-m-d H:i:s") : null,
            "pct" => $aceite ? $pct : null
        ]);
    }

    return $bookingId;
}

// B1/B2 — por alocar/aceitar (ambulatorio, pendente_alocacao)
$b1 = booking($conn, $CUSTOMER, $A1, $D1, "09:30", [[1, 32.52, 150], [35, 8.13, 20]], "pendente_alocacao");
$b2 = booking($conn, $CUSTOMER, $A2, $D2, "11:00", [[30, 40.65, 150]], "pendente_alocacao");

// B3 — alocado ao efetivo (totalmente_alocado) -> rota por decidir + alocacao planeada
$b3 = booking($conn, $CUSTOMER, $A3, $D3, "14:00", [[1, 32.52, 150]], "totalmente_alocado", $EFFECTIVE, 10.0);

// ---- fiscal (F5: atraso realcado + alerta por ler) ----------------------
$ins = $conn->prepare("INSERT INTO obrigacao_fiscal (tipo, designacao, periodicidade, valor_estimado, data_prazo, estado, observacoes) VALUES (:t, :d, :p, :v, :prazo, 'pendente', 'SEED F4F5')");
$ins->execute(["t" => "seguranca_social", "d" => "Segurança Social (SEED)", "p" => "mensal", "v" => 320.00, "prazo" => date("Y-m-d", strtotime("-4 day"))]);
$overdueId = (int)$conn->lastInsertId();

$ins->execute(["t" => "iva", "d" => "IVA trimestral (SEED)", "p" => "trimestral", "v" => 780.00, "prazo" => date("Y-m-d", strtotime("+5 day"))]);
$soonId = (int)$conn->lastInsertId();

$conn->prepare("INSERT INTO alerta_fiscal (obrigacao_fiscal_id, tipo_alerta, data_alerta, visualizado) VALUES (:o, '7_dias', :d, 0)")
     ->execute(["o" => $soonId, "d" => date("Y-m-d")]);

echo "SEED F4/F5 criado:\n";
echo "  B1 #{$b1} cidade 1 ({$D1} 09:30) 2 servicos pendentes\n";
echo "  B2 #{$b2} cidade 2 ({$D2} 11:00) 1 servico pendente\n";
echo "  B3 #{$b3} cidade 3 ({$D3} 14:00) alocado ao efetivo {$EFFECTIVE}\n";
echo "  Fiscal: obrigacao em atraso #{$overdueId}; obrigacao #{$soonId} com alerta por ler\n";