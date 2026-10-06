<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/CommissionRepository.php";
require_once APP_PATH . "/repositories/EmployeeRepository.php";
require_once APP_PATH . "/utils/Session.php";

/**
 * Comissões — Fase 6.4 (RF-84 · §25.5) reinterpretada em F8 (§6 · C-12).
 *
 * A página deixa de falar de "aceitações" e passa a falar de **serviços prestados**:
 * o critério é o agendamento ter chegado a `executado`/`concluido` (C-12). Nada é
 * recalculado: os valores são o snapshot gravado na aceitação (§11).
 *
 * **D-07.1/D-07.2** — o que é fixo e o que é variável **não se misturam**: para o
 * efetivo, o salário base aparece num cartão próprio e **nunca** é somado às comissões.
 *
 * O **gestor** vê todos; o **funcionário** vê apenas os seus (o id sai da sessão — §18.6).
 */
class CommissionService extends BaseService {

    private CommissionRepository $commissionRepository;
    private EmployeeRepository $employeeRepository;

    public function __construct() {
        parent::__construct();
        $this->commissionRepository = new CommissionRepository();
        $this->employeeRepository = new EmployeeRepository();
    }

    public function summary(array $query): array {
        [$month, $dateFrom, $dateTo] = $this->resolveMonth($query["month"] ?? null);

        $employeeId = Session::isEmployee() ? Session::userId() : null;
        $totals     = $this->commissionRepository->sumByEmployee($dateFrom, $dateTo);
        $rows       = $this->commissionRepository->listAccepted($employeeId, $dateFrom, $dateTo);

        // O funcionário só vê a sua linha de totais.
        if ($employeeId !== null) {
            $totals = array_values(array_filter($totals, fn($row) => (int)$row["funcionario_id"] === $employeeId));
        }

        $grandTotal = 0.0;
        $services   = 0;
        $platformTotal = 0.0;

        foreach ($totals as $row) {
            $grandTotal    += (float)$row["valor_funcionario"];
            $platformTotal += (float)$row["valor_plataforma"];
            $services      += (int)$row["servicos"];
        }

        return [
            "month"        => $month,
            "from"         => $dateFrom,
            "to"           => $dateTo,
            "scope"        => $employeeId !== null ? "proprio" : "todos",
            "fixedSalary"  => $employeeId !== null ? $this->fixedSalaryFor($employeeId) : null,
            "employees"    => array_map(fn($row) => [
                "employeeId"   => (int)$row["funcionario_id"],
                "employeeName" => (string)$row["funcionario_nome"],
                "services"     => (int)$row["servicos"],
                "servicesValue" => round((float)$row["valor_servicos"], 2),
                "employeeValue" => round((float)$row["valor_funcionario"], 2),
                "platformValue" => round((float)$row["valor_plataforma"], 2),
                "averagePercentage" => round((float)$row["percentagem_media"], 2)
            ], $totals),
            "commissions"  => array_map(fn($row) => [
                "id"           => (int)$row["id"],
                "bookingId"    => (int)$row["agendamento_id"],
                "serviceName"  => (string)$row["servico_nome"],
                "employeeId"   => (int)$row["funcionario_id"],
                "employeeName" => (string)$row["funcionario_nome"],
                "acceptedAt"   => (string)$row["aceito_em"],
                "dateTime"     => (string)$row["data_hora_pretendida"],
                "local"        => (string)$row["local_prestacao"],
                "bookingState" => (string)$row["estado_reserva"],
                "price"        => round((float)$row["preco_praticado"], 2),
                "percentage"   => round((float)$row["percentagem_funcionario_aplicada"], 2),
                "employeeValue" => round((float)$row["valor_recibo_verde_funcionario"], 2),
                "platformValue" => round((float)$row["valor_recibo_verde_plataforma"], 2)
            ], $rows),
            "totals"       => [
                "services"      => $services,
                "employeeValue" => round($grandTotal, 2),
                "platformValue" => round($platformTotal, 2)
            ]
        ];
    }

    /**
     * Salário base do funcionário (só efetivos) — D-07.1/D-07.2: apresentado em
     * cartão próprio, **nunca** somado às comissões.
     */
    private function fixedSalaryFor(int $employeeId): array {
        $employee = $this->employeeRepository->find($employeeId);

        if (!$employee || ($employee["contractType"] ?? "") !== "efetivo_contratado") {
            return ["applicable" => false, "value" => 0.0];
        }

        return ["applicable" => true, "value" => round((float)($employee["salary"] ?? 0), 2)];
    }

    private function resolveMonth(?string $month): array {
        if (!is_string($month) || !preg_match("/^\d{4}-\d{2}$/", $month)) {
            $month = date("Y-m");
        }

        $dateFrom = $month . "-01";
        $dateTo   = date("Y-m-t", strtotime($dateFrom));

        return [$month, $dateFrom, $dateTo];
    }
}