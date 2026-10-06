<?php

require_once __DIR__ . "/BaseRepository.php";

/**
 * Registo da última execução do serviço de manutenção (F6 · §9.3).
 *
 * Serve de **guard de tempo**: impede repetir o trabalho automático antes de
 * decorrer o intervalo mínimo. Uma linha por chave (`reconciliacao`, `fiscal`…).
 */
class MaintenanceRepository extends BaseRepository {

    public function lastRun(string $key): ?string {
        $row = $this->fetchRaw(
            "SELECT executado_em FROM manutencao_execucao WHERE chave = :chave LIMIT 1",
            ["chave" => $key]
        );

        return $row["executado_em"] ?? null;
    }

    public function markRun(string $key, string $dateTime): void {
        $sql = "INSERT INTO manutencao_execucao (chave, executado_em)
                VALUES (:chave, :executado_em)
                ON DUPLICATE KEY UPDATE executado_em = VALUES(executado_em)";

        $this->execute($sql, ["chave" => $key, "executado_em" => $dateTime]);
    }
}