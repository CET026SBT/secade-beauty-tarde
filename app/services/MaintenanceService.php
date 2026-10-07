<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/utils/Session.php";
require_once __DIR__ . "/ReconciliationService.php";
require_once __DIR__ . "/FiscalService.php";

/**
 * Manutenção automática — F6 (§8 · §9.3 · D9).
 *
 * Junta as tarefas que o sistema tem de fazer "sozinho, com o tempo que passa":
 *   - a **reconciliação de estados** (`ReconciliationService`, §8);
 *   - a **geração dos alertas fiscais** (`FiscalService`, §9.3) — antes só
 *     acontecia quando o gestor abria o calendário.
 *
 * Não há CRON (simplificação académica): corre **no login** com um guard de
 * tempo (não repete antes de `MIN_INTERVAL_SECONDS`) e, além disso, a
 * reconciliação é chamada no início das leituras que mostram estados (ver os
 * hooks nos respetivos Services). De tudo isto nada **decide** por indicador
 * (§3.1) — só mantém o estado guardado coerente com o tempo.
 */
class MaintenanceService extends BaseService {

    /** Guard de tempo do login: não repete antes de 5 minutos. */
    private const MIN_INTERVAL_SECONDS = 300;

    private const SESSION_KEY = "maintenance_last_run";

    /**
     * Corre reconciliação + alertas fiscais uma só vez e devolve o que mudou.
     */
    public function run(): array {
        return $this->executeTransactional(function () {
            $reconcile = (new ReconciliationService())->reconcile(true);
            $fiscalAlerts = (new FiscalService())->generateFiscalAlerts();

            return [
                "reconciliation" => $reconcile,
                "fiscalAlerts"   => $fiscalAlerts
            ];
        });
    }

    /**
     * Versão para o login: só corre se já passou o intervalo desde a última vez
     * nesta sessão (o `$force` serve o endpoint manual do gestor).
     */
    public function runIfDue(bool $force = false): array {
        Session::start();

        $last = (int)($_SESSION[self::SESSION_KEY] ?? 0);

        if (!$force && (time() - $last) < self::MIN_INTERVAL_SECONDS) {
            return ["skipped" => true];
        }

        $result = $this->run();
        $_SESSION[self::SESSION_KEY] = time();

        return $result;
    }
}