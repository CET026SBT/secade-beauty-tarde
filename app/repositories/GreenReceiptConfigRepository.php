<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/GreenReceiptConfigMapper.php";

class GreenReceiptConfigRepository extends BaseRepository {

    protected ?string $mapper = GreenReceiptConfigMapper::class;

    /**
     * Default em vigor por tipo de contrato (a linha de maior `data_vigencia` já atingida).
     */
    public function findDefaults(): array {
        $sql = "SELECT c.id, c.tipo_contrato, c.percentagem_comissao, c.data_vigencia, c.configurado_por
                FROM config_percentagem_padrao c
                INNER JOIN (
                    SELECT tipo_contrato, MAX(data_vigencia) AS vigente
                    FROM config_percentagem_padrao
                    WHERE data_vigencia <= CURDATE()
                    GROUP BY tipo_contrato
                ) latest ON latest.tipo_contrato = c.tipo_contrato AND latest.vigente = c.data_vigencia
                ORDER BY c.tipo_contrato ASC";

        $rows = $this->fetchAll($sql);
        return is_array($rows) ? $rows : [];
    }

    public function findHistory(): array {
        $sql = "SELECT id, tipo_contrato, percentagem_comissao, data_vigencia, configurado_por
                FROM config_percentagem_padrao
                ORDER BY tipo_contrato ASC, data_vigencia DESC, id DESC";

        $rows = $this->fetchAll($sql);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Define (ou atualiza) o default de um tipo de contrato numa data de vigência.
     * A chave única `(tipo_contrato, data_vigencia)` garante idempotência.
     */
    public function setDefault(string $contractType, float $percentage, string $effectiveFrom, ?int $configuredBy): int {
        $sql = "INSERT INTO config_percentagem_padrao (tipo_contrato, percentagem_comissao, data_vigencia, configurado_por)
                VALUES (:tipo, :pct, :vigencia, :configurado_por)
                ON DUPLICATE KEY UPDATE
                    percentagem_comissao = VALUES(percentagem_comissao),
                    configurado_por = VALUES(configurado_por)";

        $this->execute($sql, [
            "tipo"            => $contractType,
            "pct"             => $percentage,
            "vigencia"        => $effectiveFrom,
            "configurado_por" => $configuredBy
        ]);

        return (int)$this->lastInsertId();
    }
}
