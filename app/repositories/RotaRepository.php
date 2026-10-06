<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/RotaMapper.php";

/**
 * Acesso à tabela `rota_ambulante` (e matriz de deslocação, contexto de rotas).
 */
class RotaRepository extends BaseRepository {

    protected ?string $mapper = RotaMapper::class;

    public function find(?int $id = null): mixed {
        $sql = "SELECT r.id, r.data_rota, r.base_partida_id, r.cidade_id, r.estado_rota,
                       r.custo_estimado_combustivel, r.quota_parte_cliente, r.lucro_servicos,
                       r.lucro_total, r.decidido_por, r.decidido_em, r.observacoes_decisao,
                       c.nome AS cidade_nome, c.distrito, bp.nome AS base_nome
                FROM rota_ambulante r
                LEFT JOIN cidade c ON r.cidade_id = c.id
                LEFT JOIN base_partida bp ON r.base_partida_id = bp.id
                WHERE 1=1";
        $params = [];

        if ($id !== null) {
            $sql .= " AND r.id = :id LIMIT 1";
            $params["id"] = $id;
            return $this->fetch($sql, $params);
        }

        $sql .= " ORDER BY r.data_rota DESC, c.nome ASC";
        return $this->fetchAll($sql, $params);
    }

    public function findByDateAndCity(string $routeDate, int $cityId): mixed {
        $sql = "SELECT r.id, r.data_rota, r.base_partida_id, r.cidade_id, r.estado_rota,
                       r.custo_estimado_combustivel, r.quota_parte_cliente, r.lucro_servicos,
                       r.lucro_total, r.decidido_por, r.decidido_em, r.observacoes_decisao
                FROM rota_ambulante r
                WHERE r.data_rota = :data_rota AND r.cidade_id = :cidade_id
                LIMIT 1";

        return $this->fetch($sql, ["data_rota" => $routeDate, "cidade_id" => $cityId]);
    }

    public function listWithDetails(array $filters = []): array {
        $sql = "SELECT r.id, r.data_rota, r.base_partida_id, r.cidade_id, r.estado_rota,
                       r.custo_estimado_combustivel, r.quota_parte_cliente, r.lucro_servicos,
                       r.lucro_total, r.decidido_por, r.decidido_em, r.observacoes_decisao,
                       c.nome AS cidade_nome, c.distrito, bp.nome AS base_nome
                FROM rota_ambulante r
                LEFT JOIN cidade c ON r.cidade_id = c.id
                LEFT JOIN base_partida bp ON r.base_partida_id = bp.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters["date"])) {
            $sql .= " AND r.data_rota = :data_rota";
            $params["data_rota"] = $filters["date"];
        }

        if (!empty($filters["status"])) {
            $sql .= " AND r.estado_rota = :estado";
            $params["estado"] = $filters["status"];
        }

        if (!empty($filters["cityId"])) {
            $sql .= " AND r.cidade_id = :cidade_id";
            $params["cidade_id"] = (int)$filters["cityId"];
        }

        $sql .= " ORDER BY r.data_rota DESC, c.nome ASC";

        $rows = $this->fetchAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    public function create(array $data): int {
        $sql = "INSERT INTO rota_ambulante (
                    data_rota, base_partida_id, cidade_id, estado_rota,
                    custo_estimado_combustivel, quota_parte_cliente, lucro_servicos, lucro_total,
                    decidido_por, decidido_em, observacoes_decisao
                ) VALUES (
                    :data_rota, :base_partida_id, :cidade_id, :estado_rota,
                    :custo_combustivel, :quota_parte, :lucro_servicos, :lucro_total,
                    :decidido_por, NOW(), :observacoes
                )";

        $this->execute($sql, [
            "data_rota"         => $data["routeDate"],
            "base_partida_id"   => $data["baseId"],
            "cidade_id"         => $data["cityId"],
            "estado_rota"       => $data["status"],
            "custo_combustivel" => $data["fuelCost"],
            "quota_parte"       => $data["customerShare"] ?? 0,
            "lucro_servicos"    => $data["servicesProfit"] ?? 0,
            "lucro_total"       => $data["totalProfit"] ?? 0,
            "decidido_por"      => $data["decidedBy"] ?? null,
            "observacoes"       => $data["decisionNotes"] ?? null
        ]);

        return (int)$this->lastInsertId();
    }

    public function updateDecision(int $id, array $data): bool {
        $sql = "UPDATE rota_ambulante
                SET estado_rota = :estado_rota,
                    custo_estimado_combustivel = :custo_combustivel,
                    quota_parte_cliente = :quota_parte,
                    lucro_servicos = :lucro_servicos,
                    lucro_total = :lucro_total,
                    decidido_por = :decidido_por,
                    decidido_em = NOW(),
                    observacoes_decisao = :observacoes
                WHERE id = :id";

        return $this->execute($sql, [
            "estado_rota"       => $data["status"],
            "custo_combustivel" => $data["fuelCost"],
            "quota_parte"       => $data["customerShare"] ?? 0,
            "lucro_servicos"    => $data["servicesProfit"] ?? 0,
            "lucro_total"       => $data["totalProfit"] ?? 0,
            "decidido_por"      => $data["decidedBy"] ?? null,
            "observacoes"       => $data["decisionNotes"] ?? null,
            "id"                => $id
        ]) > 0;
    }

    /**
     * Custo de combustível (matriz de deslocação) entre a base e a cidade.
     */
    public function getFuelCost(int $cityId, int $baseId = 1): float {
        $sql = "SELECT custo_estimado_combustivel
                FROM matriz_deslocacao
                WHERE cidade_id = :cidade_id AND base_partida_id = :base_partida_id
                LIMIT 1";

        $row = $this->fetchRaw($sql, ["cidade_id" => $cityId, "base_partida_id" => $baseId]);
        return is_array($row) ? (float)$row["custo_estimado_combustivel"] : 0.0;
    }
}