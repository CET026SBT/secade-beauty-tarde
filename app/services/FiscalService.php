<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/repositories/FiscalObligationRepository.php";
require_once APP_PATH . "/repositories/FiscalAlertRepository.php";

/**
 * Fase 4 — Calendário Fiscal centralizado no backoffice do gestor.
 *
 * Alertas progressivos: 30, 15, 7, 3 e 1 dia antes do prazo + diário em atraso.
 * Geração ON-DEMAND (sem CRON), coerente com as simplificações académicas.
 */
class FiscalService extends BaseService {

    /** Dias antes do prazo para cada tipo de alerta. */
    private const ALERT_OFFSETS = [
        "30_dias" => 30,
        "15_dias" => 15,
        "7_dias"  => 7,
        "3_dias"  => 3,
        "1_dia"   => 1
    ];

    private FiscalObligationRepository $obligationRepository;
    private FiscalAlertRepository $alertRepository;

    public function __construct() {
        parent::__construct();
        $this->obligationRepository = new FiscalObligationRepository();
        $this->alertRepository = new FiscalAlertRepository();
    }

    // ------------------------------------------------------------------
    // Calendário + alertas
    // ------------------------------------------------------------------

    /**
     * Obrigações fiscais com o respetivo estado de alerta (geração on-demand).
     */
    public function findCalendar(array $filters = []): array {
        $today = date("Y-m-d");
        $this->generateAlerts($today);

        $obligations = $this->obligationRepository->find(null, $filters);

        $rows = array_map(function($obligation) use ($today) {
            $daysLeft = (int)floor((strtotime($obligation["dueDate"]) - strtotime($today)) / 86400);

            $obligation["daysLeft"]   = $daysLeft;
            $obligation["isOverdue"]  = $obligation["status"] !== "pago" && $daysLeft < 0;
            $obligation["alertLevel"] = $obligation["status"] === "pago"
                ? "pago"
                : $this->resolveAlertLevel($daysLeft);

            return $obligation;
        }, $obligations);

        return [
            "obligations" => $rows,
            "summary"     => $this->buildSummary($rows, $today),
            "alertTypes"  => array_keys(self::ALERT_OFFSETS)
        ];
    }

    public function findAlerts(): array {
        $today = date("Y-m-d");
        $this->generateAlerts($today);

        $alerts = $this->alertRepository->findUnread();

        return [
            "alerts" => array_map(function($alert) use ($today) {
                $daysLeft = (int)floor((strtotime($alert["obrigacao_prazo"]) - strtotime($today)) / 86400);

                return [
                    "id"               => (int)$alert["id"],
                    "obligationId"     => (int)$alert["obrigacao_fiscal_id"],
                    "type"             => $alert["tipo_alerta"],
                    "alertDate"        => $alert["data_alerta"],
                    "obligationType"   => $alert["obrigacao_tipo"],
                    "obligationName"   => $alert["obrigacao_designacao"],
                    "dueDate"          => $alert["obrigacao_prazo"],
                    "obligationStatus" => $alert["obrigacao_estado"],
                    "daysLeft"         => $daysLeft,
                    "isOverdue"        => $daysLeft < 0
                ];
            }, $alerts)
        ];
    }

    // ------------------------------------------------------------------
    // Operações
    // ------------------------------------------------------------------

    public function createObligation(array $data): array {
        return $this->executeTransactional(function() use ($data) {
            $this->validate($data, function($v) {
                $v  ->required("type", "O tipo de obrigação é obrigatório.")
                    ->contains("type", ["iva", "irc", "seguranca_social", "seguros"], "Tipo de obrigação inválido.")
                    ->required("name", "A designação é obrigatória.")
                    ->required("periodicity", "A periodicidade é obrigatória.")
                    ->contains("periodicity", ["mensal", "trimestral", "anual"], "Periodicidade inválida.")
                    ->required("dueDate", "A data de prazo é obrigatória.");
            });

            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", (string)$data["dueDate"])) {
                throw new Exception("Data de prazo inválida. Formato esperado: AAAA-MM-DD.", 422);
            }

            if (isset($data["estimatedValue"]) && (float)$data["estimatedValue"] < 0) {
                throw new Exception("O valor estimado não pode ser negativo.", 422);
            }

            $obligationId = $this->obligationRepository->create([
                "type"           => $data["type"],
                "name"           => $data["name"],
                "periodicity"    => $data["periodicity"],
                "estimatedValue" => $data["estimatedValue"] ?? 0,
                "dueDate"        => $data["dueDate"],
                "status"         => "pendente",
                "notes"          => $data["notes"] ?? null
            ]);

            $this->generateAlerts(date("Y-m-d"));

            return [
                "obligationId" => $obligationId,
                "message"      => "Obrigação fiscal registada com sucesso."
            ];
        });
    }

    public function markAsPaid(int $obligationId, array $data = []): array {
        return $this->executeTransactional(function() use ($obligationId, $data) {
            $obligation = $this->obligationRepository->find($obligationId);

            if (!$obligation) {
                throw new Exception("Obrigação fiscal não encontrada.", 404);
            }

            if ($obligation["status"] === "pago") {
                throw new Exception("Esta obrigação já se encontra marcada como paga.", 409);
            }

            $paidAt = $data["paidAt"] ?? date("Y-m-d");

            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", (string)$paidAt)) {
                throw new Exception("Data de pagamento inválida. Formato esperado: AAAA-MM-DD.", 422);
            }

            $this->obligationRepository->markAsPaid($obligationId, $paidAt, $data["notes"] ?? ($obligation["notes"] ?? null));

            return [
                "obligationId" => $obligationId,
                "message"      => "Obrigação marcada como paga."
            ];
        });
    }

    public function markAlertsAsRead(): array {
        $updated = $this->alertRepository->markAllAsRead();

        return [
            "updated" => $updated,
            "message" => "Alertas fiscais marcados como visualizados."
        ];
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    private function resolveAlertLevel(int $daysLeft): string {
        if ($daysLeft < 0) return "em_atraso";

        // Percorre do limiar mais próximo (mais urgente) para o mais distante,
        // devolvendo o primeiro degrau que ainda cobre os dias restantes.
        $offsets = self::ALERT_OFFSETS;
        asort($offsets);

        foreach ($offsets as $type => $offset) {
            if ($daysLeft <= $offset) return $type;
        }

        return "sem_alerta";
    }

    /**
     * Cria os alertas em falta para as obrigações pendentes. Idempotente
     * graças à chave única (obrigacao, tipo_alerta, data_alerta).
     */
    private function generateAlerts(string $today): void {
        $obligations = $this->obligationRepository->find(null, ["status" => "pendente"]);

        foreach ($obligations as $obligation) {
            $daysLeft = (int)floor((strtotime($obligation["dueDate"]) - strtotime($today)) / 86400);

            if ($daysLeft < 0) {
                // Alerta diário enquanto se mantiver em atraso
                $this->alertRepository->createIfAbsent((int)$obligation["id"], "em_atraso", $today);
                continue;
            }

            foreach (self::ALERT_OFFSETS as $type => $offset) {
                if ($daysLeft === $offset) {
                    $this->alertRepository->createIfAbsent((int)$obligation["id"], $type, $today);
                }
            }
        }
    }

    private function buildSummary(array $rows, string $today): array {
        $pending = 0; $paid = 0; $overdue = 0; $dueSoon = 0;

        foreach ($rows as $row) {
            if ($row["status"] === "pago") {
                $paid++;
                continue;
            }

            $pending++;

            if ($row["isOverdue"]) {
                $overdue++;
            } elseif ($row["daysLeft"] <= 7) {
                $dueSoon++;
            }
        }

        return [
            "total"   => count($rows),
            "pending" => $pending,
            "paid"    => $paid,
            "overdue" => $overdue,
            "dueSoon" => $dueSoon,
            "today"   => $today
        ];
    }
}