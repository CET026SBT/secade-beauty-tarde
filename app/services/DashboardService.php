<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/DashboardRepository.php";
require_once APP_PATH . "/repositories/FiscalAlertRepository.php";
require_once APP_PATH . "/repositories/FiscalObligationRepository.php";
require_once __DIR__ . "/ReconciliationService.php";

/**
 * Painel do gestor (`/gestao/painel`) — Fase 6.0 (§24.7 · D-14 · §3.13).
 *
 * Regras:
 *   - Nenhuma query vive aqui: os números são pedidos aos Repositories (§18.1).
 *   - Nenhum indicador decide ou bloqueia nada (decisão é manual — §3.1).
 *   - Módulos ainda não implementados aparecem como **estado vazio explicativo**,
 *     nunca com um valor inventado (a contabilidade é a Fase 6.2).
 */
class DashboardService extends BaseService {

    /** Janela do gráfico "próximos dias". */
    private const FORECAST_DAYS = 7;

    private DashboardRepository $dashboardRepository;
    private FiscalAlertRepository $fiscalAlertRepository;
    private FiscalObligationRepository $fiscalObligationRepository;

    public function __construct() {
        parent::__construct();
        $this->dashboardRepository = new DashboardRepository();
        $this->fiscalAlertRepository = new FiscalAlertRepository();
        $this->fiscalObligationRepository = new FiscalObligationRepository();
    }

    /**
     * Todos os indicadores e séries do painel numa só resposta.
     */
    public function summary(): array {
        // §8: os contadores do painel ficam coerentes com as listagens.
        (new ReconciliationService())->reconcile();

        $today    = date("Y-m-d");
        $forecast = date("Y-m-d", strtotime("+" . (self::FORECAST_DAYS - 1) . " days"));

        $todaySummary  = $this->dashboardRepository->summarizeBetween($today, $today);
        $periodSummary = $this->dashboardRepository->summarizeBetween($today, $forecast);

        $acceptance   = $this->dashboardRepository->countServicesByAcceptance();
        $unreadAlerts = $this->fiscalAlertRepository->findUnread();
        $overdue      = $this->fiscalObligationRepository->findOverdue($today);

        return [
            "today"  => $today,
            "period" => ["from" => $today, "to" => $forecast, "days" => self::FORECAST_DAYS],
            "kpis"   => [
                "bookingsToday"          => $todaySummary["total"],
                "confirmedToday"         => $todaySummary["confirmed"],
                "revenueToday"           => round($todaySummary["expectedRevenue"], 2),
                "bookingsForecast"       => $periodSummary["total"],
                "revenueForecast"        => round($periodSummary["expectedRevenue"], 2),
                "servicesPending"        => $this->sumByKey($acceptance, "estado", "pendente"),
                "routesAwaitingDecision" => $this->dashboardRepository->countRoutesAwaitingDecision(),
                "alertsUnread"           => count($unreadAlerts),
                "fiscalOverdue"          => count($overdue),
                "activeServices"         => $this->dashboardRepository->countActiveServices(),
                "activeSuppliers"        => $this->dashboardRepository->countActiveSuppliers(),
                "customers"              => $this->dashboardRepository->countCustomers()
            ],
            "charts" => [
                "bookingsByState"      => $this->chartBookingsByState(),
                "bookingsPerDay"       => $this->chartBookingsPerDay($today, $forecast),
                "servicesByAcceptance" => $this->chartServicesByAcceptance($acceptance),
                "fiscalByType"         => $this->chartFiscalByType()
            ],
            "accounting" => $this->accountingState()
        ];
    }

    /**
     * A contabilidade (6.2) ainda não tem tabelas nem importação: a honestidade
     * sobre o que falta é preferível a um número inventado (§24.7 · §3.12).
     */
    private function accountingState(): array {
        return [
            "available" => false,
            "title"     => "Contabilidade",
            "message"   => "As disponibilidades, as dívidas a receber, as dívidas a pagar e o resultado entram com o módulo de contabilidade (Fase 6.2). Até lá este bloco fica vazio — não se publica nenhum valor estimado."
        ];
    }

    private function chartBookingsByState(): array {
        $rows   = $this->dashboardRepository->countBookingsByState();
        $labels = [];
        $data   = [];

        foreach ($rows as $row) {
            $labels[] = (string)$row["estado"];
            $data[]   = (int)$row["total"];
        }

        return ["labels" => $labels, "data" => $data];
    }

    /**
     * Série da semana: dias sem marcações entram a zero (o gráfico não pode ter buracos).
     */
    private function chartBookingsPerDay(string $dateFrom, string $dateTo): array {
        $rows = $this->dashboardRepository->countBookingsPerDay($dateFrom, $dateTo);

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[(string)$row["dia"]] = (int)$row["total"];
        }

        $labels = [];
        $data   = [];

        for ($offset = 0; $offset < self::FORECAST_DAYS; $offset++) {
            $day      = date("Y-m-d", strtotime($dateFrom . " +{$offset} days"));
            $labels[] = $day;
            $data[]   = $byDay[$day] ?? 0;
        }

        return ["labels" => $labels, "data" => $data];
    }

    private function chartServicesByAcceptance(array $rows): array {
        $labels = [];
        $data   = [];

        foreach ($rows as $row) {
            $labels[] = (string)$row["estado"];
            $data[]   = (int)$row["total"];
        }

        return ["labels" => $labels, "data" => $data];
    }

    private function chartFiscalByType(): array {
        $rows   = $this->dashboardRepository->sumFiscalByType();
        $labels = [];
        $data   = [];

        foreach ($rows as $row) {
            $labels[] = (string)$row["tipo"];
            $data[]   = round((float)$row["valor"], 2);
        }

        return ["labels" => $labels, "data" => $data];
    }

    private function sumByKey(array $rows, string $key, string $value): int {
        $total = 0;
        foreach ($rows as $row) {
            if ((string)($row[$key] ?? "") === $value) {
                $total += (int)$row["total"];
            }
        }

        return $total;
    }
}