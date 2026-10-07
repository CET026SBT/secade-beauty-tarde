<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/BookingServiceRepository.php";
require_once __DIR__ . "/ReconciliationService.php";

/**
 * Agenda do funcionário — Fase 6.5 (RF-78 · RN-33 · D-14 · §24.7).
 *
 * Só entram agendamentos de **rotas confirmadas** (o que ainda se aceita/desfaz
 * fica na listagem de `/gestao/servicos`). A aceitação continua a ser feita em
 * listagem; o calendário serve para o funcionário ver o seu dia.
 */
class EmployeeAgendaService extends BaseService {

    private BookingServiceRepository $bookingServiceRepository;

    public function __construct() {
        parent::__construct();
        $this->bookingServiceRepository = new BookingServiceRepository();
    }

    /**
     * Agenda de um mês (`YYYY-MM`), agrupada por dia.
     */
    public function findMonth(int $employeeId, array $query): array {
        // §8: a agenda não mostra marcações fantasma.
        (new ReconciliationService())->reconcile();

        [$month, $dateFrom, $dateTo] = $this->resolveMonth($query["month"] ?? null);

        $services = $this->bookingServiceRepository->findByEmployeeAndRange($employeeId, $dateFrom, $dateTo);

        $days = [];
        $totalMinutes = 0;
        $totalGross   = 0.0;

        foreach ($services as $service) {
            $day = substr((string)$service["data_hora_pretendida"], 0, 10);

            if (!isset($days[$day])) {
                $days[$day] = ["date" => $day, "services" => [], "minutes" => 0, "gross" => 0.0];
            }

            $duration = (int)$service["duracao_minutos"];
            $price    = (float)$service["preco_praticado"];

            $days[$day]["services"][] = [
                "id"            => (int)$service["id"],
                "bookingId"     => (int)$service["agendamento_id"],
                "serviceName"   => (string)$service["servico_nome"],
                "categoryName"  => (string)$service["categoria_nome"],
                "customerName"  => (string)$service["cliente_nome"],
                "personName"    => $service["nome_pessoa"] ?? null,
                "dateTime"      => (string)$service["data_hora_pretendida"],
                "local"         => (string)$service["local_prestacao"],
                "cityName"      => $service["cidade_nome"] ?? null,
                "duration"      => $duration,
                "price"         => round($price, 2)
            ];

            $days[$day]["minutes"] += $duration;
            $days[$day]["gross"]   += $price;

            $totalMinutes += $duration;
            $totalGross   += $price;
        }

        ksort($days);

        $dayList = array_values($days);
        foreach ($dayList as &$day) {
            $day["gross"] = round($day["gross"], 2);
        }
        unset($day);

        return [
            "month"        => $month,
            "from"         => $dateFrom,
            "to"           => $dateTo,
            "filter"       => "rotas_confirmadas",
            "days"         => $dayList,
            "totalServices" => count($services),
            "totalMinutes" => $totalMinutes,
            "totalGross"   => round($totalGross, 2)
        ];
    }

    /**
     * Normaliza o mês pedido (nunca confia no parâmetro do cliente).
     */
    private function resolveMonth(?string $month): array {
        if (!is_string($month) || !preg_match("/^\d{4}-\d{2}$/", $month)) {
            $month = date("Y-m");
        }

        $dateFrom = $month . "-01";
        $dateTo   = date("Y-m-t", strtotime($dateFrom));

        return [$month, $dateFrom, $dateTo];
    }
}