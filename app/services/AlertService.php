<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/FiscalAlertRepository.php";
require_once APP_PATH . "/repositories/FiscalObligationRepository.php";
require_once APP_PATH . "/repositories/BookingServiceRepository.php";
require_once APP_PATH . "/repositories/BookingRepository.php";
require_once APP_PATH . "/utils/Session.php";

/**
 * Avisos por perfil — Fase 6.0, ajustado na F5 (RF-81 · D-15 · §24.7).
 *
 * Os avisos são **derivados de dados que já existem** (alertas fiscais, obrigações
 * em atraso, serviços por alocar/aceitar e rotas por decidir): nenhum é inventado e
 * nenhum decide nem bloqueia nada (§3.1).
 *
 * Cada item traz:
 *   - `highlighted` — realce (ex.: obrigações fiscais em atraso);
 *   - `highlight`   — chave para o destino (ex.: id do agendamento) que a página
 *     de origem usa para fazer scroll + realce (`#rotaDinamica`).
 *
 * A leitura dos alertas fiscais é **global** (há um só gestor — §22.2 · C-03).
 */
class AlertService extends BaseService {

    private const CARD_LIMIT = 10;     // #limiteCards: cartões visíveis por secção
    private const MAX_ITEMS  = 100;    // teto defensivo do payload

    private FiscalAlertRepository $fiscalAlertRepository;
    private FiscalObligationRepository $fiscalObligationRepository;
    private BookingServiceRepository $bookingServiceRepository;
    private BookingRepository $bookingRepository;

    public function __construct() {
        parent::__construct();
        $this->fiscalAlertRepository = new FiscalAlertRepository();
        $this->fiscalObligationRepository = new FiscalObligationRepository();
        $this->bookingServiceRepository = new BookingServiceRepository();
        $this->bookingRepository = new BookingRepository();
    }

    /** Contador para o sino — pode ser pedido em qualquer página do backoffice. */
    public function count(): int {
        $summary = $this->buildGroups();
        return (int)$summary["count"];
    }

    /** Avisos do utilizador autenticado, agrupados por origem. */
    public function list(): array {
        $summary = $this->buildGroups();

        return [
            "count"     => $summary["count"],
            "profile"   => Session::getUserProfile(),
            "cardLimit" => self::CARD_LIMIT,
            "groups"    => $summary["groups"],
            "readScope" => "global"
        ];
    }

    /** Marca como lidos os avisos fiscais (a página de avisos é o ponto de leitura). */
    public function markAllAsRead(): array {
        $updated = $this->fiscalAlertRepository->markAllAsRead();

        return [
            "updated" => $updated,
            "message" => $updated > 0
                ? "Alertas fiscais marcados como lidos."
                : "Não existiam alertas fiscais por ler."
        ];
    }

    /**
     * Grupos por perfil:
     *   - GESTOR: fiscais (com atrasos realçados), serviços por alocar, rotas por decidir.
     *   - FUNCIONÁRIO RV: serviços por aceitar.
     *   - FUNCIONÁRIO EFETIVO: serviços já em rota confirmada (coerente com a agenda).
     *   - Rotas por decidir NUNCA aparece ao funcionário.
     */
    private function buildGroups(): array {
        $groups = [];

        if (Session::isManager()) {
            $groups[] = $this->fiscalGroup();
            $groups[] = $this->pendingServicesGroup(true);
            $groups[] = $this->routesGroup();
            $groups[] = $this->closingGroup(0);
        } elseif (Session::isEmployee()) {
            if (Session::employeeContractType() === "efetivo_contratado") {
                $groups[] = $this->allocationsGroup((int)Session::userId());
            } else {
                $groups[] = $this->pendingServicesGroup(false);
            }
            $groups[] = $this->closingGroup((int)Session::userId());
        }

        $count = 0;
        foreach ($groups as $group) {
            $count += (int)$group["count"];
        }

        return ["count" => $count, "groups" => $groups];
    }

    // ------------------------------------------------------------------
    // Grupos
    // ------------------------------------------------------------------

    /**
     * Alertas fiscais — **todos numa só secção**, com as obrigações em atraso
     * primeiro e **realçadas** (F5). Sem duplicar: um alerta cuja obrigação já
     * aparece em atraso não é repetido.
     */
    private function fiscalGroup(): array {
        $items = [];
        $seen  = [];

        foreach ($this->fiscalObligationRepository->findOverdue(date("Y-m-d")) as $obligation) {
            $seen[(int)$obligation["id"]] = true;
            $items[] = [
                "title"       => (string)$obligation["designacao"],
                "detail"      => "Prazo vencido em " . (string)$obligation["data_prazo"],
                "type"        => "fiscal_atraso",
                "page"        => "fiscal",
                "pageUrl"     => "/gestao/fiscal",
                "highlighted" => true,
                "highlight"   => null
            ];
        }

        foreach ($this->fiscalAlertRepository->findUnread() as $alert) {
            $obligationId = (int)$alert["obrigacao_fiscal_id"];
            if (isset($seen[$obligationId])) continue;

            $items[] = [
                "title"       => (string)$alert["obrigacao_designacao"],
                "detail"      => "Alerta de " . str_replace("_", " ", (string)$alert["tipo_alerta"]) . " · prazo " . (string)$alert["obrigacao_prazo"],
                "type"        => "fiscal",
                "page"        => "fiscal",
                "pageUrl"     => "/gestao/fiscal",
                "highlighted" => false,
                "highlight"   => null
            ];
        }

        return $this->group("fiscal", "Alertas fiscais", $items);
    }

    /**
     * Serviços de ambulatório por tratar. `$forManager` troca o rótulo e o detalhe
     * (por alocar vs. por aceitar); em ambos o destino leva a chave `highlight`.
     */
    private function pendingServicesGroup(bool $forManager): array {
        $pending = $this->bookingServiceRepository->findPending();
        $items   = [];

        foreach ($pending as $service) {
            $bookingId = (int)($service["bookingId"] ?? 0);
            $items[] = [
                "title"       => (string)($service["serviceName"] ?? ""),
                "detail"      => "Agendamento #" . $bookingId . " · " . (string)($service["dateTime"] ?? ""),
                "type"        => "servico_pendente",
                "page"        => "services",
                "pageUrl"     => "/gestao/servicos?highlight=" . $bookingId,
                "highlighted" => false,
                "highlight"   => $bookingId
            ];
        }

        return $this->group(
            "servicos_pendentes",
            $forManager ? "Serviços por alocar" : "Serviços por aceitar",
            $items
        );
    }

    /**
     * Alocações do funcionário EFETIVO, **só as que já estão em rota confirmada**
     * (verbo do gestor) — coerente com a agenda (`/gestao/agenda` mostra apenas
     * agendamentos `confirmado`, RN-33). Antes de a rota ser aprovada, o efetivo
     * não é avisado: a alocação ainda pode mudar e o sítio para a acompanhar é a
     * lista de Serviços. Cada item traz serviço, pessoa, cidade, data e o **extra
     * que recebe** (F5 · C-10).
     */
    private function allocationsGroup(int $employeeId): array {
        $services = $this->bookingServiceRepository->findAllocated($employeeId, []);
        $items    = [];

        foreach ($services as $service) {
            // Só rota confirmada (RN-33): o efetivo é avisado do que já é compromisso.
            if ((string)($service["bookingStatus"] ?? "") !== "confirmado") continue;

            $bookingId = (int)($service["bookingId"] ?? 0);
            $person    = trim((string)($service["personName"] ?? ""));
            $city      = trim((string)($service["cityName"] ?? ""));
            $earn      = (float)($service["greenReceiptEmployee"] ?? 0);

            $parts = array_filter([
                $person !== "" ? $person : null,
                $city !== "" ? $city : null,
                (string)($service["dateTime"] ?? "")
            ]);

            $items[] = [
                "title"       => (string)($service["serviceName"] ?? ""),
                "detail"      => "Agendamento #" . $bookingId . " · " . implode(" · ", $parts)
                                 . " · + " . number_format($earn, 2, ",", "") . " €",
                "type"        => "alocacao_planeada",
                "page"        => "agenda",
                "pageUrl"     => "/gestao/agenda",
                "highlighted" => false,
                "highlight"   => null
            ];
        }

        return $this->group("alocacoes", "Serviços em rota confirmada", $items);
    }

    /**
     * R1b (§8.2): agendamentos **confirmados** cuja hora já passou e continuam por
     * fechar. O sistema **não** muda o estado sozinho — avisa o staff para registar
     * a execução/conclusão. `$employeeId = 0` mostra tudo (gestor); caso contrário
     * fica restrito aos serviços daquele funcionário.
     */
    private function closingGroup(int $employeeId): array {
        $rows  = $this->bookingRepository->findConfirmedPast($employeeId > 0 ? $employeeId : null);
        $items = [];

        foreach ($rows as $row) {
            $bookingId = (int)$row["id"];

            $items[] = [
                "title"       => "Agendamento #" . $bookingId . " por fechar",
                "detail"      => "Passou a hora (" . (string)$row["data_hora_pretendida"]
                                 . ") e continua confirmado — falta registar o estado final.",
                "type"        => "por_fechar",
                "page"        => "services",
                "pageUrl"     => "/gestao/servicos?highlight=" . $bookingId,
                "highlighted" => true,
                "highlight"   => $bookingId
            ];
        }

        return $this->group("por_fechar", "Agendamentos por fechar", $items);
    }

    /** Rotas por decidir — só o gestor (o funcionário nunca vê este grupo). */
    private function routesGroup(): array {
        $groups = $this->bookingRepository->findAmbulatoryGroups(null, null, ["totalmente_alocado"]);
        $items  = [];

        foreach ($groups as $group) {
            $items[] = [
                "title"       => "Rota de " . (string)$group["cidade_nome"] . " (" . (string)$group["data_rota"] . ")",
                "detail"      => (int)$group["total_agendamentos"] . " agendamento(s) qualificado(s) à espera de decisão",
                "type"        => "rota_pendente",
                "page"        => "routes",
                "pageUrl"     => "/gestao/rotas",
                "highlighted" => false,
                "highlight"   => null
            ];
        }

        return $this->group("rotas", "Rotas por decidir", $items);
    }

    /** Estrutura comum de um grupo, com o teto defensivo do payload. */
    private function group(string $key, string $label, array $items): array {
        $count = count($items);
        if ($count > self::MAX_ITEMS) {
            $items = array_slice($items, 0, self::MAX_ITEMS);
        }

        return [
            "key"   => $key,
            "label" => $label,
            "count" => $count,
            "items" => $items
        ];
    }
}