<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/EmployeeMapper.php";

class EmployeeRepository extends BaseRepository {

    protected ?string $mapper = EmployeeMapper::class;

    /** Colunas comuns (funcionário + utilizador) — uma só fonte (§18.2). */
    private const SELECT = "SELECT f.id, f.tipo_contrato, f.salario_base, f.percentagem_comissao, f.irs_taxa, f.cc, f.ativo,
                       u.nome, u.email, u.telemovel, u.nif, u.foto
                FROM funcionario f
                INNER JOIN utilizador u ON f.id = u.id";

    public function find(?int $id = null): mixed {
        $sql = self::SELECT;
        $params = [];

        if ($id !== null) {
            $sql .= " WHERE f.id = :id LIMIT 1";
            $params["id"] = $id;
            return $this->fetch($sql, $params);
        }

        $sql .= " ORDER BY f.ativo DESC, u.nome ASC";
        return $this->fetchAll($sql, $params);
    }

    /** Funcionários (ativos e inativos) com o utilizador — a página de RH lista ambos. */
    public function findAllWithUser(): array {
        $rows = $this->fetchAll(self::SELECT . " ORDER BY f.ativo DESC, u.nome ASC");
        return is_array($rows) ? $rows : [];
    }

    /**
     * Funcionários EFETIVOS ativos — são estes que o gestor pode alocar a um
     * serviço de ambulatório (F4 · C-08): o RV aceita por si, o efetivo é alocado.
     */
    public function findActiveEffective(): array {
        $sql = self::SELECT . "
                WHERE f.tipo_contrato = 'efetivo_contratado' AND f.ativo = 1
                ORDER BY u.nome ASC";

        $rows = $this->fetchAll($sql);
        return is_array($rows) ? $rows : [];
    }

    /** Funcionários ativos — base dos indicadores públicos da equipa (§24.7). */
    public function countActive(): int {
        return (int)$this->fetchRaw("SELECT COUNT(*) AS total FROM funcionario WHERE ativo = 1")["total"];
    }

    public function create(array $data): int {
        $sql = "INSERT INTO funcionario (id, tipo_contrato, salario_base, percentagem_comissao, irs_taxa, cc, ativo)
                VALUES (:id, :tipo_contrato, :salario_base, :percentagem_comissao, :irs_taxa, :cc, 1)";

        $this->execute($sql, [
            "id"                   => $data["userId"],
            "tipo_contrato"        => $data["contractType"],
            "salario_base"         => (float)($data["salary"] ?? 0),
            "percentagem_comissao" => (float)($data["commissionPercentage"] ?? 0),
            "irs_taxa"             => ($data["irsRate"] ?? null) !== null && $data["irsRate"] !== "" ? (float)$data["irsRate"] : null,
            "cc"                   => $data["cc"] ?? null
        ]);

        return $data["userId"];
    }

    /** Indicadores do RH: nº de efetivos, nº de RV, custo fixo mensal e inativos (§7.4). */
    public function summary(): array {
        $row = $this->fetchRaw(
            "SELECT
                SUM(ativo = 1 AND tipo_contrato = 'efetivo_contratado') AS efetivos,
                SUM(ativo = 1 AND tipo_contrato = 'recibo_verde')      AS rv,
                COALESCE(SUM(CASE WHEN ativo = 1 THEN salario_base ELSE 0 END), 0) AS custo_fixo,
                SUM(ativo = 0) AS inativos
             FROM funcionario"
        );

        return [
            "effective"    => (int)($row["efetivos"] ?? 0),
            "greenReceipt" => (int)($row["rv"] ?? 0),
            "fixedCost"    => round((float)($row["custo_fixo"] ?? 0), 2),
            "inactive"     => (int)($row["inativos"] ?? 0)
        ];
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE funcionario
                SET tipo_contrato = :tipo_contrato, salario_base = :salario_base,
                    percentagem_comissao = :percentagem_comissao, irs_taxa = :irs_taxa, cc = :cc
                WHERE id = :id";

        return $this->execute($sql, [
            "tipo_contrato"        => $data["contractType"],
            "salario_base"         => (float)($data["salary"] ?? 0),
            "percentagem_comissao" => (float)($data["commissionPercentage"] ?? 0),
            "irs_taxa"             => ($data["irsRate"] ?? null) !== null && $data["irsRate"] !== "" ? (float)$data["irsRate"] : null,
            "cc"                   => $data["cc"] ?? null,
            "id"                   => $id
        ]) >= 0;
    }

    /** Soft delete (§7.5): nunca se apaga a linha — só se marca inativa. */
    public function setActive(int $id, bool $active): bool {
        return $this->execute("UPDATE funcionario SET ativo = :ativo WHERE id = :id", [
            "ativo" => $active ? 1 : 0,
            "id"    => $id
        ]) >= 0;
    }

    // ------------------------------------------------------------------
    // Impacto da desativação (§7.5)
    // ------------------------------------------------------------------

    /** Serviços do funcionário em agendamentos de ROTA CONFIRMADA — bloqueiam a desativação. */
    public function countAssignedInConfirmed(int $employeeId): int {
        $row = $this->fetchRaw(
            "SELECT COUNT(*) AS total
             FROM agendamento_servico s
             INNER JOIN agendamento a ON a.id = s.agendamento_id
             WHERE s.funcionario_id = :id AND a.estado_reserva = 'confirmado'",
            ["id" => $employeeId]
        );

        return (int)($row["total"] ?? 0);
    }

    /**
     * Agendamentos NÃO terminais nem confirmados onde o funcionário tem serviços —
     * são estes que a desativação liberta (§7.5). Devolve os ids dos agendamentos.
     */
    public function listAssignedBookingIds(int $employeeId): array {
        $rows = $this->fetchAllRaw(
            "SELECT DISTINCT s.agendamento_id
             FROM agendamento_servico s
             INNER JOIN agendamento a ON a.id = s.agendamento_id
             WHERE s.funcionario_id = :id
               AND a.estado_reserva IN ('pendente_alocacao', 'pendente_validacao_logistica_loja', 'totalmente_alocado')",
            ["id" => $employeeId]
        );

        return array_map(fn($r) => (int)$r["agendamento_id"], $rows);
    }

    /** Detalhe legível dos serviços que a desativação vai libertar (diálogo de confirmação). */
    public function listAssignedServices(int $employeeId): array {
        $sql = "SELECT s.id AS service_id, s.agendamento_id, sv.nome AS servico_nome,
                       a.data_hora_pretendida, a.estado_reserva
                FROM agendamento_servico s
                INNER JOIN agendamento a ON a.id = s.agendamento_id
                INNER JOIN servico sv ON sv.id = s.servico_id
                WHERE s.funcionario_id = :id
                  AND a.estado_reserva IN ('pendente_alocacao', 'pendente_validacao_logistica_loja', 'totalmente_alocado')
                ORDER BY a.data_hora_pretendida ASC, s.id ASC";

        return $this->fetchAllRaw($sql, ["id" => $employeeId]);
    }

    /** Liberta os serviços do funcionário nos agendamentos não terminais (§7.5 passo 3a). */
    public function unassignForEmployee(int $employeeId): int {
        $sql = "UPDATE agendamento_servico s
                INNER JOIN agendamento a ON a.id = s.agendamento_id
                SET s.funcionario_id = NULL,
                    s.estado_aceitacao = 'pendente',
                    s.aceito_em = NULL,
                    s.percentagem_funcionario_aplicada = NULL
                WHERE s.funcionario_id = :id
                  AND a.estado_reserva IN ('pendente_alocacao', 'pendente_validacao_logistica_loja', 'totalmente_alocado')";

        return $this->execute($sql, ["id" => $employeeId]);
    }

    /** Reverte para «por alocar» os agendamentos que deixaram de estar totalmente alocados (§7.5 passo 3b). */
    public function revertToPendingAllocation(array $bookingIds): int {
        $bookingIds = array_values(array_filter(array_map("intval", $bookingIds), fn($id) => $id > 0));
        if (empty($bookingIds)) return 0;

        $placeholders = [];
        $params = [];
        foreach ($bookingIds as $i => $id) {
            $key = "b" . $i;
            $placeholders[] = ":" . $key;
            $params[$key] = $id;
        }

        $sql = "UPDATE agendamento
                SET estado_reserva = 'pendente_alocacao'
                WHERE estado_reserva = 'totalmente_alocado'
                  AND id IN (" . implode(",", $placeholders) . ")";

        return $this->execute($sql, $params);
    }
}
