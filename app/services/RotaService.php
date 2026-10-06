<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/RotaRepository.php";
require_once APP_PATH . "/repositories/BookingRepository.php";
require_once APP_PATH . "/repositories/BookingServiceRepository.php";
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
    private BookingServiceRepository $bookingServiceRepository;

    public function __construct() {
        parent::__construct();
        $this->rotaRepository = new RotaRepository();
        $this->bookingRepository = new BookingRepository();
        $this->bookingServiceRepository = new BookingServiceRepository();
    }

    /**
     * Lista de agendamentos que o gestor incluiu na rota (RN-34).
     * Vazia = decidir sobre todos os qualificados.
     */
    private function normalizeBookingIds(mixed $rawIds): array {
        if (!is_array($rawIds)) {
            return [];
        }

        $ids = [];
        foreach ($rawIds as $value) {
            $id = (int)$value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
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
        $requestedIds = $this->normalizeBookingIds($data["bookingIds"] ?? null);

        return $this->executeTransactional(function() use ($cityId, $date, $decision, $notes, $managerId, $requestedIds) {
            // RN-31: uma rota NÃO se decide com serviços por aceitar.
            $pending = $this->bookingRepository->countPendingByCityAndDate($date, $cityId);

            if ($pending > 0) {
                throw new Exception(
                    "Esta rota tem " . $pending . " agendamento(s) com serviços por aceitar. "
                    . "Aceite (ou desfaça) todos os serviços na área de Serviços antes de decidir a rota.",
                    409
                );
            }

            $bookings = $this->bookingRepository->findDecidableByCityAndDate($date, $cityId);

            if (empty($bookings)) {
                throw new Exception("Não existem agendamentos de ambulatório em condições de decisão para esta cidade e data.", 409);
            }

            $qualifiedIds = [];
            $prices = [];

            foreach ($bookings as $booking) {
                $bookingId = (int)$booking["id"];
                $qualifiedIds[] = $bookingId;
                $prices[$bookingId] = (float)$booking["valor_total"];
            }

            // RN-34: a decisão aplica-se ao CONJUNTO INCLUÍDO. Sem lista explícita
            // aplica-se a todos os qualificados; os excluídos ficam qualificados
            // (`totalmente_alocado`) — nunca `cancelado`.
            $bookingIds = $requestedIds === []
                ? $qualifiedIds
                : array_values(array_intersect($qualifiedIds, $requestedIds));

            if ($requestedIds !== [] && empty($bookingIds)) {
                throw new Exception("Nenhum dos agendamentos indicados está qualificado para esta rota.", 422);
            }

            $excludedIds = array_values(array_diff($qualifiedIds, $bookingIds));
            $revenue     = 0.0;

            foreach ($bookingIds as $bookingId) {
                $revenue += $prices[$bookingId] ?? 0.0;
            }

            // R-24H (§4.4 · RF-58/RN-24): nenhum agendamento a menos de 24 h entra
            // numa rota — a decisão é bloqueada e cabe à reconciliação (F10/F6)
            // retirá-lo/recusá-lo.
            $tooSoon = $this->bookingRepository->findIdsWithin24Hours($bookingIds);

            if (!empty($tooSoon)) {
                throw new Exception(
                    "Existem " . count($tooSoon) . " agendamento(s) a menos de 24 horas da execução. "
                    . "Não podem ser incluídos numa rota (RF-58/RN-24).",
                    409
                );
            }

            // R-CONF (§4.4): a confirmação valida «mesma cidade + janela sobreposta».
            if ($decision === "aprovada") {
                $conflicts = $this->bookingRepository->findCityWindowConflicts($cityId, $date, $bookingIds);

                if ($conflicts > 0) {
                    throw new Exception(
                        "Existe uma rota já confirmada nesta cidade com janela temporal sobreposta. "
                        . "A carrinha não pode estar em dois locais à mesma hora.",
                        409
                    );
                }
            }

            $fuelCost      = $this->rotaRepository->getFuelCost($cityId, self::BASE_PARTIDA_ID);
            $profitability = round($revenue - $fuelCost, 2);
            $approved      = $decision === "aprovada";

            // 6.1/C-01: a staff **recusa** (não cancela) — `cancelado` fica para o cliente.
            $bookingState = $approved ? "confirmado" : "recusado";
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
                "excludedIds"   => $excludedIds,
                "excluded"      => count($excludedIds),
                "revenue"       => $revenue,
                "fuelCost"      => $fuelCost,
                "profitability" => $profitability,
                "meetsReference"=> $reference,
                "bookingStatus" => $bookingState,
                "message"       => ($approved
                    ? "Rota aprovada. " . count($bookingIds) . " agendamento(s) confirmado(s); clientes notificados (simulado)."
                    : "Rota recusada. " . count($bookingIds) . " agendamento(s) cancelado(s); clientes notificados com alternativas (simulado).")
                    . (count($excludedIds) > 0
                        ? " " . count($excludedIds) . " agendamento(s) ficaram fora da rota e continuam qualificados."
                        : "")
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

            // RN-31: o que ainda aguarda aceitação é MOSTRADO (aviso), nunca agregado.
            $awaitingAcceptance = $this->bookingRepository->countPendingByCityAndDate($group["data_rota"], $groupCityId);

            $route = $routesIndex[$key] ?? null;
            unset($routesIndex[$key]);

            $status = $route["status"] ?? "planeada";
            $canDecide = in_array($status, ["planeada", "aprovada", "recusada"], true) && $total > 0 && $awaitingAcceptance === 0;

            $rows[] = [
                "routeId"        => $route["id"] ?? null,
                "routeDate"      => $group["data_rota"],
                "cityId"         => $groupCityId,
                "cityName"       => $group["cidade_nome"],
                "district"       => $group["distrito"] ?? null,
                "bookings"       => $total,
                "consolidated"   => $consolidated,
                "awaitingAcceptance" => $awaitingAcceptance,
                "bookingIds"     => array_map("intval", array_filter(explode(",", (string)($group["agendamentos_ids"] ?? "")))),
                "bookingsDetail" => $this->bookingDetailRows($group["data_rota"], $groupCityId),
                "revenue"        => $revenue,
                "fuelCost"       => $fuelCost,
                "profitability"  => $profitability,
                "meetsReference" => $profitability >= self::REFERENCE_PROFITABILITY,
                "canDecide"      => $canDecide,
                "decideBlockReason" => $awaitingAcceptance > 0
                    ? $awaitingAcceptance . " agendamento(s) com serviços por aceitar"
                    : null,
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
                "bookingIds"     => [],
                "bookingsDetail" => [],
                "revenue"        => (float)($route["servicesProfit"] ?? 0),
                "fuelCost"       => (float)($route["fuelCost"] ?? 0),
                "profitability"  => (float)($route["totalProfit"] ?? 0),
                "meetsReference" => (float)($route["totalProfit"] ?? 0) >= self::REFERENCE_PROFITABILITY,
                "canDecide"      => false,
                "decideBlockReason" => null,
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

    /**
     * Detalhe de cada agendamento qualificado da rota (RN-34 · §24.7 item 7).
     *
     * Os serviços são contados pelo `BookingServiceRepository` (a coleção filha
     * nunca é lida por JOIN — §18.2).
     */
    private function bookingDetailRows(string $date, int $cityId): array {
        $bookings = $this->bookingRepository->findAmbulatoryBookingsByCityAndDate($date, $cityId);
        $rows = [];

        foreach ($bookings as $booking) {
            $states = $this->bookingServiceRepository->countByBookingGroupedByState((int)$booking["id"]);

            $total    = 0;
            $accepted = 0;

            foreach ($states as $state) {
                $count = (int)$state["total"];
                $total += $count;

                if (($state["estado_aceitacao"] ?? "") === "aceite") {
                    $accepted += $count;
                }
            }

            $rows[] = [
                "bookingId"       => (int)$booking["id"],
                "customerName"    => (string)$booking["cliente_nome"],
                "customerPhone"   => $booking["cliente_telemovel"] ?? null,
                "dateTime"        => (string)$booking["data_hora_pretendida"],
                "status"          => (string)$booking["estado_reserva"],
                "totalAmount"     => (float)$booking["valor_total"],
                "servicesTotal"   => $total,
                "servicesAccepted" => $accepted
            ];
        }

        return $rows;
    }
}
