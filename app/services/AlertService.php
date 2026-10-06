<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/FiscalAlertRepository.php";
require_once APP_PATH . "/repositories/FiscalObligationRepository.php";
require_once APP_PATH . "/repositories/BookingServiceRepository.php";
require_once APP_PATH . "/repositories/BookingRepository.php";
require_once APP_PATH . "/repositories/NotificationRepository.php";
require_once __DIR__ . "/MaintenanceService.php";

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
    private NotificationRepository $notificationRepository;

    public function __construct() {
        parent::__construct();
        $this->fiscalAlertRepository = new FiscalAlertRepository();
        $this->fiscalObligationRepository = new FiscalObligationRepository();
        $this->bookingServiceRepository = new BookingServiceRepository();
        $this->bookingRepository = new BookingRepository();
        $this->notificationRepository = new NotificationRepository();
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
        // F6 (§9.3): a leitura dos avisos é um momento relevante — aproveita-se para
        // correr o serviço de manutenção (reconciliação + alertas fiscais).
        (new MaintenanceService())->runIfDue();

        $summary = $this->buildGroups();

        // §3.7/F5: o perfil passa a distinguir RV de efetivo (G-03).
        $profile = "gestor";
        if (Session::isEmployee()) {
            $profile = Session::employeeContractType() === "efetivo_contratado" ? "efetivo" : "recibo_verde";
        } elseif (Session::isCustomer()) {
            $profile = "cliente";
        }

        return [
            "count"     => $summary["count"],
            "profile"   => $profile,
            "groups"    => $summary["groups"],
            "readScope" => $profile === "cliente" ? "proprio" : "global"
        ];
    }

    /**
     * O cliente marca como lidos **os seus** avisos (`notificacao`).
     */
    public function markMyNotificationsAsRead(): array {
        $userId = Session::userId();

        if ($userId === null) {
            throw new Exception("Sessão inválida.", 401);
        }

        $updated = $this->notificationRepository->markAllRead($userId);

        return [
            "updated" => $updated,
            "message" => $updated > 0
                ? "Avisos marcados como lidos."
                : "Não existiam avisos por ler."
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

    private const MAX_ITEMS = 10;

    /**
     * Grupos de avisos, por perfil.
     *
     * §3.7 (F5):
     *   - o grupo «Rotas por decidir» é **só do gestor** (ao RV não aparece);
     *   - o **efetivo** vê «Alocações planeadas» (o resultado da F4);
     *   - cada grupo é limitado a `MAX_ITEMS` (`#limiteCards`) com `hasMore`.
     */
    private function buildGroups(): array {
        $groups = [];

        if (Session::isCustomer()) {
            // §3.5/C-07 (F9): o cliente passa a ter avisos próprios — os lembretes
            // que lhe dizem respeito e as suas próximas marcações.
            $groups[] = $this->myNotificationsGroup();
            $groups[] = $this->myUpcomingBookingsGroup();
        } elseif (Session::isManager()) {
            $groups[] = $this->fiscalGroup();
            $groups[] = $this->overdueGroup();
            $groups[] = $this->pendingServicesGroup();
            $groups[] = $this->routesGroup();
        } else {
            $groups[] = $this->myNotificationsGroup();
            $groups[] = $this->myAllocationsGroup();
        }

        $count = 0;
        foreach ($groups as $group) {
            $count += (int)$group["count"];
        }

        return ["count" => $count, "groups" => $groups];
    }

    /**
     * Limita a lista mostrada e diz se há mais (§3.7 · Q-15). A contagem do grupo
     * mantém-se **total** — o limite é só de apresentação.
     */
    private function limitedGroup(string $key, string $label, array $items): array {
        $visible = array_slice($items, 0, self::MAX_ITEMS);

        return [
            "key"      => $key,
            "label"    => $label,
            "count"    => count($items),
            "items"    => $visible,
            "hasMore"  => count($items) > self::MAX_ITEMS,
            "hidden"   => max(0, count($items) - self::MAX_ITEMS),
            "showMoreUrl" => $items[0]["pageUrl"] ?? null
        ];
    }

    /** Avisos/lembretes do próprio utilizador (`notificacao` — C-07/C-14 · F6). */
    private function myNotificationsGroup(): array {
        $userId = Session::userId();
        $items = [];

        if ($userId !== null) {
            foreach ($this->notificationRepository->findUnread($userId) as $notification) {
                $items[] = [
                    "title"   => ucfirst(str_replace("_", " ", (string)$notification["tipo"])),
                    "detail"  => (string)$notification["mensagem"],
                    "type"    => "notificacao",
                    "page"    => "alerts",
                    "pageUrl" => "/gestao/avisos"
                ];
            }
        }

        return $this->limitedGroup("notificacoes", "Os meus avisos", $items);
    }

    /** §3.5/C-07 (F9): as próximas marcações do cliente, como lembrete. */
    private function myUpcomingBookingsGroup(): array {
        $customerId = Session::userId();
        $items = [];

        if ($customerId !== null) {
            foreach ($this->bookingRepository->findUpcomingByCustomer($customerId) as $booking) {
                $state = $this->bookingStateLabel((string)$booking["status"]);

                $items[] = [
                    "title"   => "Agendamento #" . (int)$booking["id"],
                    "detail"  => date("d/m/Y H:i", strtotime((string)$booking["dateTime"])) . " · " . $state,
                    "type"    => "agendamento",
                    "page"    => "agendamentos",
                    "pageUrl" => "/area-cliente#agendamentos"
                ];
            }
        }

        return $this->limitedGroup("proximas_marcacoes", "As minhas próximas marcações", $items);
    }

    private function bookingStateLabel(string $state): string {
        $labels = [
            "pendente_alocacao" => "Pendente de alocação",
            "pendente_validacao_logistica_loja" => "Pendente de validação",
            "totalmente_alocado" => "Totalmente alocado",
            "confirmado" => "Confirmado",
            "executado" => "Executado",
            "concluido" => "Concluído",
            "recusado" => "Recusado",
            "cancelado" => "Cancelado"
        ];

        return $labels[$state] ?? $state;
    }

    /** Alocações planeadas do funcionário (F4/F5) — o que lhe foi atribuído. */
    private function myAllocationsGroup(): array {
        $employeeId = Session::userId();
        $items = [];

        if ($employeeId !== null) {
            foreach ($this->bookingServiceRepository->findAcceptedByEmployee($employeeId, []) as $service) {
                $items[] = [
                    "title"   => (string)($service["serviceName"] ?? ""),
                    "detail"  => "Agendamento #" . (int)($service["bookingId"] ?? 0) . " · " . (string)($service["dateTime"] ?? ""),
                    "type"    => "alocacao",
                    "page"    => "services",
                    "pageUrl" => "/gestao/servicos"
                ];
            }
        }

        return $this->limitedGroup("alocacoes", "Alocações planeadas", $items);
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

        return $this->limitedGroup("fiscal", "Alertas fiscais por ler", $items);
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

        return $this->limitedGroup("fiscal_atraso", "Obrigações fiscais em atraso", $items);
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

        return $this->limitedGroup("servicos_pendentes", "Serviços por alocar", $items);
    }

    private function routesGroup(): array {
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

        return $this->limitedGroup("rotas", "Rotas por decidir", $items);
    }
}
