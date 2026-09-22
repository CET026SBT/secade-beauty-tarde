<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/FiscalObligationMapper.php";

/**
 * Acesso à tabela `obrigacao_fiscal` (calendário fiscal do gestor).
 */
class FiscalObligationRepository extends BaseRepository {

    protected ?string $mapper = FiscalObligationMapper::class;

    public function find(?int $id = null, ?array $filters = []): mixed {
        $sql = "SELECT o.id, o.tipo, o.designacao, o.periodicidade, o.valor_estimado,
                       o.data_prazo, o.estado, o.data_pagamento, o.observacoes, o.criado_em
                FROM obrigacao_fiscal o
                WHERE 1=1";
        $params = [];

        if ($id !== null) {
            $sql .= " AND o.id = :id LIMIT 1";
            $params["id"] = $id;
            return $this->fetch($sql, $params);
        }

        if (!empty($filters["type"])) {
            $sql .= " AND o.tipo = :tipo";
            $params["tipo"] = $filters["type"];
        }

        if (!empty($filters["status"])) {
            $sql .= " AND o.estado = :estado";
            $params["estado"] = $filters["status"];
        }

        if (!empty($filters["from"]) && !empty($filters["to"])) {
            $sql .= " AND o.data_prazo BETWEEN :data_de AND :data_ate";
            $params["data_de"]  = $filters["from"];
            $params["data_ate"] = $filters["to"];
        }

        $sql .= " ORDER BY o.data_prazo ASC, o.id ASC";

        $rows = $this->fetchAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    public function create(array $data): int {
        $sql = "INSERT INTO obrigacao_fiscal
                    (tipo, designacao, periodicidade, valor_estimado, data_prazo, estado, observacoes)
                VALUES (:tipo, :designacao, :periodicidade, :valor_estimado, :data_prazo, :estado, :observacoes)";

        $this->execute($sql, [
            "tipo"           => $data["type"],
            "designacao"     => $data["name"],
            "periodicidade"  => $data["periodicity"],
            "valor_estimado" => $data["estimatedValue"] ?? 0,
            "data_prazo"     => $data["dueDate"],
            "estado"         => $data["status"] ?? "pendente",
            "observacoes"    => $data["notes"] ?? null
        ]);

        return (int)$this->lastInsertId();
    }

    public function markAsPaid(int $id, string $paidAt, ?string $notes = null): bool {
        $sql = "UPDATE obrigacao_fiscal
                SET estado = 'pago', data_pagamento = :data_pagamento, observacoes = :observacoes
                WHERE id = :id";

        return $this->execute($sql, [
            "data_pagamento" => $paidAt,
            "observacoes"    => $notes,
            "id"             => $id
        ]) > 0;
    }

    /**
     * Obrigações pendentes cujo prazo já passou (candidatas a alerta "em_atraso").
     */
    public function findOverdue(string $today): array {
        $sql = "SELECT o.id, o.tipo, o.designacao, o.data_prazo, o.estado
                FROM obrigacao_fiscal o
                WHERE o.estado = 'pendente' AND o.data_prazo < :hoje
                ORDER BY o.data_prazo ASC";

        return $this->fetchAllRaw($sql, ["hoje" => $today]);
    }
}