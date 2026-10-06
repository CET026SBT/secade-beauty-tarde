<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/GreenReceiptConfigMapper.php";

class GreenReceiptConfigRepository extends BaseRepository {

    protected ?string $mapper = GreenReceiptConfigMapper::class;

    /**
     * Percentagem padrão em vigor para um tipo de contrato
     * (`config_percentagem_padrao`). Devolve `null` se não houver configuração.
     */
    public function findActiveByContractType(string $tipoContrato): ?array {
        $sql = "SELECT id, tipo_contrato, percentagem_comissao, data_vigencia, configurado_por
                FROM config_percentagem_padrao
                WHERE tipo_contrato = :tipo_contrato
                  AND data_vigencia <= CURDATE()
                ORDER BY data_vigencia DESC, id DESC
                LIMIT 1";

        $row = $this->fetch($sql, ["tipo_contrato" => $tipoContrato]);
        return is_array($row) ? $row : null;
    }

    /** Todas as configurações em vigor (uma por tipo de contrato). */
    public function findActiveAll(): array {
        $sql = "SELECT c.id, c.tipo_contrato, c.percentagem_comissao, c.data_vigencia, c.configurado_por
                FROM config_percentagem_padrao c
                INNER JOIN (
                    SELECT tipo_contrato, MAX(data_vigencia) AS max_data
                    FROM config_percentagem_padrao
                    WHERE data_vigencia <= CURDATE()
                    GROUP BY tipo_contrato
                ) v ON v.tipo_contrato = c.tipo_contrato AND v.max_data = c.data_vigencia
                ORDER BY c.tipo_contrato ASC, c.id DESC";

        $rows = $this->fetchAll($sql);
        return is_array($rows) ? $rows : [];
    }

    public function findHistory(): array {
        $sql = "SELECT id, tipo_contrato, percentagem_comissao, data_vigencia, configurado_por
                FROM config_percentagem_padrao
                ORDER BY data_vigencia DESC, id DESC";

        $rows = $this->fetchAll($sql);
        return is_array($rows) ? $rows : [];
    }

    public function create(string $tipoContrato, float $percentagemComissao, string $dataVigencia, ?int $configuradoPor): int {
        $sql = "INSERT INTO config_percentagem_padrao (tipo_contrato, percentagem_comissao, data_vigencia, configurado_por)
                VALUES (:tipo_contrato, :percentagem_comissao, :data_vigencia, :configurado_por)";

        $this->execute($sql, [
            "tipo_contrato"        => $tipoContrato,
            "percentagem_comissao" => $percentagemComissao,
            "data_vigencia"        => $dataVigencia,
            "configurado_por"      => $configuradoPor
        ]);

        return (int)$this->lastInsertId();
    }
}
