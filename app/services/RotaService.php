<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/RotaRepository.php";
require_once APP_PATH . "/repositories/BookingRepository.php";
require_once APP_PATH . "/utils/Session.php";

/**
 * Rotas ambulantes.
 *
 * A decisão é SEMPRE MANUAL e LIVRE do gestor (Fase 4 — especificacao_mvp.md §3.1).
 * O valor de referência de 50 € é APENAS um indicador visual na listagem
 * (nunca aprova nem recusa automaticamente).
 *
 * Ao decidir:
 *   aprovada -> rota 'aprovada' + agendamentos 'confirmado'
 *   recusada -> rota 'recusada' + agendamentos 'cancelado'
 */
class RotaService extends BaseService {

    private const BASE_PARTIDA_ID         = 1;    // Base fixa (Évora)
    private const REFERENCE_PROFITABILITY = 50.0; // Indicador VISUAL de referência (Fase 4)

    private RotaRepository $rotaRepository;
    private BookingRepository $bookingRepository;

    public function __construct() {
        parent::__construct();
        $this->rotaRepository = new RotaRepository();
        $this->bookingRepository = new BookingRepository();
    }

    /**
     * DECISÃO MANUAL DO GESTOR (Fase 4 — especificacao_mvp.md §3.1).
     *
     * Aprova ou recusa livremente uma rota (dia + cidade), sem limiar de bloqueio.
     * Os 50 € de referência são apenas um INDICADOR VISUAL na listagem.
     *
     * Efeitos:
     *   aprovada -> rota 'aprovada' + agendamentos 'confirmado'
     *   recusada -> rota 'recusada' + agendamentos 'cancelado'
     */
    public function decideRoute(array $data): array {
        $cityId   = (int)($data["cityId"] ?? 0);
        $date     = $data["date"] ?? null;
        $decision = $data["decision"] ?? null;
        $notes    = $data["notes"] ?? null;

        if ($cityId <= 0) {
            throw new Exception("Indique a cidade da rota a decidir.", 422);
        }

        if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", (string)$date)) {
            throw new Exception("Data inválida. Formato esperado: AAAA-MM-DD.", 422);
        }

        if (!in_array($decision, ["aprovada", "recusada"], true)) {
            throw new Exception("Decisão inválida. Use 'aprovada' ou 'recusada'.", 422);
        }

        $managerId = Session::userId();

        return $this->executeTransactional(function() use ($cityId, $date, $decision, $notes, $managerId) {
            $bookings = $this->bookingRepository->findDecidableByCityAndDate($date, $cityId);

            if (empty($bookings)) {
                throw new Exception("Não existem agendamentos de ambulatório em condições de decisão para esta cidade e data.", 409);
            }

            $revenue   = 0.0;
            $bookingIds = [];

            foreach ($bookings as $booking) {
                $revenue += (float)$booking["valor_total"];
                $bookingIds[] = (int)$booking["id"];
            }

            $fuelCost      = $this->rotaRepository->getFuelCost($cityId, self::BASE_PARTIDA_ID);
            $profitability = round($revenue - $fuelCost, 2);
            $approved      = $decision === "aprovada";

            $bookingState = $approved ? "confirmado" : "cancelado";
            $this->bookingRepository->updateEstadoMany($bookingIds, $bookingState);

            $decisionNotes = $notes !== null && $notes !== ""
                ? $notes
                : ($approved
                    ? "Decisão manual: rota aprovada (rentabilidade " . $profitability . " EUR)."
                    : "Decisão manual: rota recusada (rentabilidade " . $profitability . " EUR).");

            $routeData = [
                "routeDate"      => $date,
                "baseId"         => self::BASE_PARTIDA_ID,
                "cityId"         => $cityId,
                "status"         => $decision,
                "fuelCost"       => $fuelCost,
                "customerShare"  => 0.0,
                "servicesProfit" => $revenue,
                "totalProfit"    => $profitability,
                "decidedBy"      => $managerId,
                "decisionNotes"  => $decisionNotes
            ];

            $existing = $this->rotaRepository->findByDateAndCity($date, $cityId);

            if ($existing) {
                $routeId = (int)$existing["id"];
                $this->rotaRepository->updateDecision($routeId, $routeData);
            } else {
                $routeId = $this->rotaRepository->create($routeData);
            }

            $reference = $profitability >= self::REFERENCE_PROFITABILITY;

            return [
                "routeId"       => $routeId,
                "routeDate"     => $date,
                "cityId"        => $cityId,
                "status"        => $decision,
                "bookings"      => count($bookingIds),
                "bookingIds"    => $bookingIds,
                "revenue"       => $revenue,
                "fuelCost"      => $fuelCost,
                "profitability" => $profitability,
                "meetsReference"=> $reference,
                "bookingStatus" => $bookingState,
                "message"       => $approved
                    ? "Rota aprovada. " . count($bookingIds) . " agendamento(s) confirmado(s); clientes notificados (simulado)."
                    : "Rota recusada. " . count($bookingIds) . " agendamento(s) cancelado(s); clientes notificados com alternativas (simulado)."
            ];
        });
    }

    /**
     * Listagem para o backoffice: rotas candidatas/integradas (data+cidade)
     * combinadas com as decisões já registadas.
     *
     * O campo `meetsReference` é APENAS um indicador visual (rentabilidade >= 50 €);
     * NÃO aprova nem recusa nada — a decisão é sempre do gestor.
     */
    public function findRouteSummaries(array $filters = []): array {
        $date   = !empty($filters["date"]) ? $filters["date"] : null;
        $cityId = !empty($filters["cityId"]) ? (int)$filters["cityId"] : null;

        $groups = $this->bookingRepository->findAmbulatoryGroups($date, $cityId);
        $routes = $this->rotaRepository->listWithDetails($filters);

        $routesIndex = [];
        foreach ($routes as $route) {
            $routesIndex[$route["routeDate"] . "|" . $route["cityId"]] = $route;
        }

        $rows = [];

        foreach ($groups as $group) {
            $groupCityId = (int)$group["cidade_id"];
            $revenue     = (float)$group["receita_prevista"];
            $fuelCost      = $this->rotaRepository->getFuelCost($groupCityId, self::BASE_PARTIDA_ID);
            $profitability = round($revenue - $fuelCost, 2);
            $consolidated = (int)($group["total_consolidados"] ?? 0);
            $total        = (int)$group["total_agendamentos"];
            $key = $group["data_rota"] . "|" . $groupCityId;

            $route = $routesIndex[$key] ?? null;
            unset($routesIndex[$key]);

            $status = $route["status"] ?? "planeada";

            $rows[] = [
                "routeId"        => $route["id"] ?? null,
                "routeDate"      => $group["data_rota"],
                "cityId"         => $groupCityId,
                "cityName"       => $group["cidade_nome"],
                "district"       => $group["distrito"] ?? null,
                "bookings"       => $total,
                "consolidated"   => $consolidated,
                "awaitingAcceptance" => $total - $consolidated,
                "revenue"        => $revenue,
                "fuelCost"       => $fuelCost,
                "profitability"  => $profitability,
                "meetsReference" => $profitability >= self::REFERENCE_PROFITABILITY,
                "canDecide"      => in_array($status, ["planeada", "aprovada", "recusada"], true),
                "status"         => $status,
                "decidedAt"      => $route["decidedAt"] ?? null,
                "notes"          => $route["decisionNotes"] ?? null
            ];
        }

        // Rotas já decididas sem agendamentos em estado de decisão (histórico)
        foreach ($routesIndex as $route) {
            $rows[] = [
                "routeId"        => $route["id"],
                "routeDate"      => $route["routeDate"],
                "cityId"         => $route["cityId"],
                "cityName"       => $route["cityName"],
                "district"       => $route["district"] ?? null,
                "bookings"       => 0,
                "consolidated"   => 0,
                "awaitingAcceptance" => 0,
                "revenue"        => (float)($route["servicesProfit"] ?? 0),
                "fuelCost"       => (float)($route["fuelCost"] ?? 0),
                "profitability"  => (float)($route["totalProfit"] ?? 0),
                "meetsReference" => (float)($route["totalProfit"] ?? 0) >= self::REFERENCE_PROFITABILITY,
                "canDecide"      => false,
                "status"         => $route["status"],
                "decidedAt"      => $route["decidedAt"] ?? null,
                "notes"          => $route["decisionNotes"] ?? null
            ];
        }

        usort($rows, function($a, $b) {
            return [$b["routeDate"], $a["cityName"]] <=> [$a["routeDate"], $b["cityName"]];
        });

        return [
            "routes"               => $rows,
            "baseId"               => self::BASE_PARTIDA_ID,
            "referenceProfitability" => self::REFERENCE_PROFITABILITY,
            "decisionMode"         => "manual"
        ];
    }
}
