<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/FiscalAlertMapper.php";

/**
 * Acesso à tabela `alerta_fiscal` (alertas progressivos 30/15/7/3/1/em_atraso).
 */
class FiscalAlertRepository extends BaseRepository {

    protected ?string $mapper = FiscalAlertMapper::class;

    /**
     * Cria o alerta se ainda não existir. Devolve 1 quando criou (o `INSERT
     * IGNORE` respeita a chave única) e 0 quando já existia — é assim que o
     * `MaintenanceService` conta o que gerou.
     */
    public function createIfAbsent(int $obligationId, string $alertType, string $alertDate): int {
        $sql = "INSERT IGNORE INTO alerta_fiscal (obrigacao_fiscal_id, tipo_alerta, data_alerta)
                VALUES (:obrigacao_id, :tipo_alerta, :data_alerta)";

        return $this->execute($sql, [
            "obrigacao_id" => $obligationId,
            "tipo_alerta"  => $alertType,
            "data_alerta"  => $alertDate
        ]);
    }

    /**
     * Alertas não visualizados, com dados da obrigação associada (contexto de fiscalidade).
     */
    public function findUnread(): array {
        $sql = "SELECT al.id, al.obrigacao_fiscal_id, al.tipo_alerta, al.data_alerta, al.visualizado,
                       o.tipo AS obrigacao_tipo, o.designacao AS obrigacao_designacao,
                       o.data_prazo AS obrigacao_prazo, o.estado AS obrigacao_estado
                FROM alerta_fiscal al
                INNER JOIN obrigacao_fiscal o ON al.obrigacao_fiscal_id = o.id
                WHERE al.visualizado = 0
                ORDER BY o.data_prazo ASC, al.id ASC";

        return $this->fetchAllRaw($sql);
    }

    public function markAllAsRead(): int {
        $sql = "UPDATE alerta_fiscal SET visualizado = 1 WHERE visualizado = 0";
        return $this->execute($sql);
    }
}