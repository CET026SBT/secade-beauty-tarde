<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/SupplierMapper.php";

/**
 * Acesso à tabela `fornecedor` (Fase 6.1 · RF-85 · §17.9).
 *
 * A tabela entrou com a migração v4 (`database_migration_v4.sql`) já com os
 * **43 fornecedores reais** entregues pelo cliente — esta camada só lê e escreve
 * na própria tabela (nunca por JOIN — §18.2).
 */
class SupplierRepository extends BaseRepository {

    protected ?string $mapper = SupplierMapper::class;

    public function find(?int $id = null): mixed {
        $sql = "SELECT f.id, f.nome, f.nif, f.email, f.telemovel, f.ativo, f.observacoes, f.criado_em
                FROM fornecedor f
                WHERE 1=1";
        $params = [];

        if ($id !== null) {
            $sql .= " AND f.id = :id LIMIT 1";
            $params["id"] = $id;
            return $this->fetch($sql, $params);
        }

        $sql .= " ORDER BY f.nome ASC";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Listagem com filtros de pesquisa e estado (a página pede os dois).
     */
    public function search(array $filters = []): array {
        $sql = "SELECT f.id, f.nome, f.nif, f.email, f.telemovel, f.ativo, f.observacoes, f.criado_em
                FROM fornecedor f
                WHERE 1=1";
        $params = [];

        if (!empty($filters["term"])) {
            $sql .= " AND (f.nome LIKE :term OR f.nif LIKE :term OR f.email LIKE :term)";
            $params["term"] = "%" . $filters["term"] . "%";
        }

        if ($filters["active"] !== null && $filters["active"] !== "") {
            $sql .= " AND f.ativo = :ativo";
            $params["ativo"] = (int)$filters["active"];
        }

        $sql .= " ORDER BY f.nome ASC";

        $rows = $this->fetchAll($sql, $params);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Indicadores do módulo: total, ativos e registos sem NIF.
     */
    public function summary(): array {
        $sql = "SELECT COUNT(*) AS total,
                       SUM(ativo = 1) AS ativos,
                       SUM(nif IS NULL OR nif = '') AS sem_nif
                FROM fornecedor";

        $row = $this->fetchRaw($sql);

        return [
            "total"  => (int)($row["total"] ?? 0),
            "active" => (int)($row["ativos"] ?? 0),
            "withoutNif" => (int)($row["sem_nif"] ?? 0)
        ];
    }

    public function create(array $data): int {
        $sql = "INSERT INTO fornecedor (nome, nif, email, telemovel, ativo, observacoes)
                VALUES (:nome, :nif, :email, :telemovel, :ativo, :observacoes)";

        $this->execute($sql, [
            "nome"        => $data["name"],
            "nif"         => $data["nif"] ?? null,
            "email"       => $data["email"] ?? null,
            "telemovel"   => $data["phone"] ?? null,
            "ativo"       => (int)($data["active"] ?? 1),
            "observacoes" => $data["notes"] ?? null
        ]);

        return (int)$this->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE fornecedor
                SET nome = :nome, nif = :nif, email = :email, telemovel = :telemovel,
                    ativo = :ativo, observacoes = :observacoes
                WHERE id = :id";

        return $this->execute($sql, [
            "nome"        => $data["name"],
            "nif"         => $data["nif"] ?? null,
            "email"       => $data["email"] ?? null,
            "telemovel"   => $data["phone"] ?? null,
            "ativo"       => (int)($data["active"] ?? 1),
            "observacoes" => $data["notes"] ?? null,
            "id"          => $id
        ]) >= 0;
    }

    /**
     * Ativar/desativar. Não há eliminação física: o fornecedor pode estar
     * referenciado por despesas (módulo de contabilidade, Fase 6.2).
     */
    public function setActive(int $id, bool $active): bool {
        $sql = "UPDATE fornecedor SET ativo = :ativo WHERE id = :id";

        return $this->execute($sql, ["ativo" => $active ? 1 : 0, "id" => $id]) >= 0;
    }
}