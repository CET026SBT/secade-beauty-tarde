<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/FiscalAlertRepository.php";
require_once APP_PATH . "/repositories/FiscalObligationRepository.php";
require_once APP_PATH . "/repositories/BookingServiceRepository.php";
require_once APP_PATH . "/repositories/BookingRepository.php";

/**
 * Avisos por perfil — Fase 6.0 (RF-81 · D-15 · §24.7).
 *
 * O sino do backoffice conta o que está por tratar e a página `/gestao/avisos`
 * mostra o detalhe. Os avisos são **derivados de dados que já existem** (alertas
 * fiscais, obrigações em atraso, serviços por aceitar e rotas por decidir): nenhum
 * aviso é inventado e nenhum deles decide ou bloqueia nada (§3.1).
 *
 * A leitura dos alertas fiscais é **global** (há um só gestor — §22.2 · C-03); os
 * lembretes não fiscais por utilizador entram quando existir a tabela `notificacao`.
 */
class AlertService extends BaseService {

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

    /**
     * Contador para o sino — pode ser pedido em qualquer página do backoffice.
     */
    public function count(): int {
        $summary = $this->buildGroups();
        return (int)$summary["count"];
    }

    /**
     * Avisos do utilizador autenticado, agrupados por origem.
     */
    public function list(): array {
        $summary = $this->buildGroups();

        return [
            "count"     => $summary["count"],
            "profile"   => Session::isEmployee() ? "funcionario" : "gestor",
            "groups"    => $summary["groups"],
            "readScope" => "global"
        ];
    }

    /**
     * Marca como lidos os avisos fiscais (a página de avisos é o ponto de leitura).
     */
    public function markAllAsRead(): array {
        $updated = $this->fiscalAlertRepository->markAllAsRead();

        return [
            "updated" => $updated,
            "message" => $updated > 0
                ? "Alertas fiscais marcados como lidos."
                : "Não existiam alertas fiscais por ler."
        ];
    }

    private function buildGroups(): array {
        $groups = [];

        if (Session::isManager()) {
            $groups[] = $this->fiscalGroup();
            $groups[] = $this->overdueGroup();
        }

        $groups[] = $this->pendingServicesGroup();
        $groups[] = $this->routesGroup();

        $count = 0;
        foreach ($groups as $group) {
            $count += (int)$group["count"];
        }

        return ["count" => $count, "groups" => $groups];
    }

    private function fiscalGroup(): array {
        $alerts = $this->fiscalAlertRepository->findUnread();
        $items  = [];

        foreach ($alerts as $alert) {
            $items[] = [
                "title"   => (string)$alert["obrigacao_designacao"],
                "detail"  => "Alerta de " . str_replace("_", " ", (string)$alert["tipo_alerta"]) . " · prazo " . (string)$alert["obrigacao_prazo"],
                "type"    => "fiscal",
                "page"    => "fiscal",
                "pageUrl" => "/gestao/fiscal"
            ];
        }

        return [
            "key"   => "fiscal",
            "label" => "Alertas fiscais por ler",
            "count" => count($items),
            "items" => $items
        ];
    }

    private function overdueGroup(): array {
        $overdue = $this->fiscalObligationRepository->findOverdue(date("Y-m-d"));
        $items   = [];

        foreach ($overdue as $obligation) {
            $items[] = [
                "title"   => (string)$obligation["designacao"],
                "detail"  => "Prazo vencido em " . (string)$obligation["data_prazo"],
                "type"    => "fiscal_atraso",
                "page"    => "fiscal",
                "pageUrl" => "/gestao/fiscal"
            ];
        }

        return [
            "key"   => "fiscal_atraso",
            "label" => "Obrigações fiscais em atraso",
            "count" => count($items),
            "items" => $items
        ];
    }

    private function pendingServicesGroup(): array {
        $pending = $this->bookingServiceRepository->findPending();
        $items   = [];

        foreach ($pending as $service) {
            // `findPending()` devolve linhas mapeadas (camelCase — §18.5).
            $items[] = [
                "title"   => (string)($service["serviceName"] ?? ""),
                "detail"  => "Agendamento #" . (int)($service["bookingId"] ?? 0) . " · " . (string)($service["dateTime"] ?? ""),
                "type"    => "servico_pendente",
                "page"    => "services",
                "pageUrl" => "/gestao/servicos"
            ];
        }

        return [
            "key"   => "servicos_pendentes",
            "label" => "Serviços por aceitar",
            "count" => count($items),
            "items" => $items
        ];
    }

    private function routesGroup(): array {
        if (!Session::isManager()) {
            return [
                "key"   => "rotas",
                "label" => "Rotas por decidir",
                "count" => 0,
                "items" => []
            ];
        }

        $groups = $this->bookingRepository->findAmbulatoryGroups(null, null, ["totalmente_alocado"]);
        $items  = [];

        foreach ($groups as $group) {
            $items[] = [
                "title"   => "Rota de " . (string)$group["cidade_nome"] . " (" . (string)$group["data_rota"] . ")",
                "detail"  => (int)$group["total_agendamentos"] . " agendamento(s) qualificado(s) à espera de decisão",
                "type"    => "rota_pendente",
                "page"    => "routes",
                "pageUrl" => "/gestao/rotas"
            ];
        }

        return [
            "key"   => "rotas",
            "label" => "Rotas por decidir",
            "count" => count($items),
            "items" => $items
        ];
    }
}