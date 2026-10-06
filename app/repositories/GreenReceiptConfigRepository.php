<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/GreenReceiptConfigMapper.php";

class GreenReceiptConfigRepository extends BaseRepository {

    protected ?string $mapper = GreenReceiptConfigMapper::class;

    public function findActive(): ?array {
        $sql = "SELECT id, percentagem_funcionario, percentagem_plataforma, data_vigencia, configurado_por
                FROM config_recibo_verde
                WHERE data_vigencia <= CURDATE()
                ORDER BY data_vigencia DESC, id DESC
                LIMIT 1";

        $row = $this->fetch($sql);
        return is_array($row) ? $row : null;
    }

    public function findHistory(): array {
        $sql = "SELECT id, percentagem_funcionario, percentagem_plataforma, data_vigencia, configurado_por
                FROM config_recibo_verde
                ORDER BY data_vigencia DESC, id DESC";

        $rows = $this->fetchAll($sql);
        return is_array($rows) ? $rows : [];
    }

    public function create(float $percentagemFuncionario, float $percentagemPlataforma, string $dataVigencia, ?int $configuradoPor): int {
        $sql = "INSERT INTO config_recibo_verde (percentagem_funcionario, percentagem_plataforma, data_vigencia, configurado_por)
                VALUES (:p_funcionario, :p_plataforma, :data_vigencia, :configurado_por)";

        $this->execute($sql, [
            "p_funcionario" => $percentagemFuncionario,
            "p_plataforma"  => $percentagemPlataforma,
            "data_vigencia" => $dataVigencia,
            "configurado_por" => $configuradoPor
        ]);

        return (int)$this->lastInsertId();
    }
}
