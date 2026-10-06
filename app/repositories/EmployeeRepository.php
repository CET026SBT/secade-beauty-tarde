<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/EmployeeMapper.php";

class EmployeeRepository extends BaseRepository {

    protected ?string $mapper = EmployeeMapper::class;

    public function find(?int $id = null): mixed {
        $sql = "SELECT f.id, f.tipo_contrato, f.percentagem_comissao, f.salario_base, f.cc, f.ativo,
                       u.nome, u.email, u.telemovel, u.nif, u.foto
                FROM funcionario f
                INNER JOIN utilizador u ON f.id = u.id
                WHERE 1=1";
        $params = [];

        if ($id !== null) {
            $sql .= " AND f.id = :id LIMIT 1";
            $params["id"] = $id;
            return $this->fetch($sql, $params);
        }

        $sql .= " ORDER BY f.id DESC";
        return $this->fetchAll($sql, $params);
    }

    /** Funcionários ativos — base dos indicadores públicos da equipa (§24.7). */
    public function countActive(): int {
        return (int)$this->fetchRaw("SELECT COUNT(*) AS total FROM funcionario WHERE ativo = 1")["total"];
    }

    /**
     * Funcionários **ativos** para o seletor de alocação (F4). O gestor aloca a
     * qualquer funcionário ativo; o funcionário aloca a si próprio.
     */
    public function listActive(): array {
        $sql = "SELECT f.id, f.tipo_contrato, f.percentagem_comissao, f.salario_base, u.nome
                FROM funcionario f
                INNER JOIN utilizador u ON f.id = u.id
                WHERE f.ativo = 1
                ORDER BY u.nome ASC";

        $rows = $this->fetchAll($sql);
        return is_array($rows) ? $rows : [];
    }

    public function create(array $data): int {
        $sql = "INSERT INTO funcionario (id, tipo_contrato, percentagem_comissao, salario_base, cc, ativo)
                VALUES (:id, :tipo_contrato, :percentagem_comissao, :salario_base, :cc, 1)";

        $this->execute($sql, [
            "id"                   => $data["userId"],
            "tipo_contrato"        => $data["contractType"],
            "percentagem_comissao" => (float)($data["commissionPercentage"] ?? 0),
            "salario_base"         => (float)($data["salary"] ?? 0),
            "cc"                   => $data["cc"] ?? null
        ]);

        return $data["userId"];
    }

    /** Edita os dados de contrato/percentagem/IRS (nunca o id nem o histórico). */
    public function update(int $id, array $data): bool {
        $sql = "UPDATE funcionario
                SET tipo_contrato = :tipo_contrato,
                    percentagem_comissao = :percentagem_comissao,
                    salario_base = :salario_base,
                    cc = :cc
                WHERE id = :id";

        return $this->execute($sql, [
            "id"                   => $id,
            "tipo_contrato"        => $data["contractType"],
            "percentagem_comissao" => (float)($data["commissionPercentage"] ?? 0),
            "salario_base"         => (float)($data["salary"] ?? 0),
            "cc"                   => $data["cc"] ?? null
        ]) >= 0;
    }

    /** Soft delete (§7.5): nenhuma linha é apagada — só deixa de ficar ativo. */
    public function setActive(int $id, bool $active): bool {
        return $this->execute(
            "UPDATE funcionario SET ativo = :ativo WHERE id = :id",
            ["ativo" => $active ? 1 : 0, "id" => $id]
        ) >= 0;
    }

    /**
     * Serviços atribuídos a um funcionário em agendamentos **não terminais**
     * (base do aviso de impacto na desativação — §7.5).
     * `excludeConfirmed` protege os compromissos já comunicados (D-07.3).
     */
    public function countAssignedServices(int $employeeId, bool $excludeConfirmed = true): int {
        $sql = "SELECT COUNT(*)
                FROM agendamento_servico s
                INNER JOIN agendamento a ON s.agendamento_id = a.id
                WHERE s.funcionario_id = :funcionario_id
                  AND a.estado_reserva NOT IN ('recusado', 'cancelado', 'executado', 'concluido')";

        if ($excludeConfirmed) {
            $sql .= " AND a.estado_reserva <> 'confirmado'";
        }

        $row = $this->fetchRaw($sql, ["funcionario_id" => $employeeId]);
        return (int)($row["total"] ?? 0);
    }

    /** Devolve ao estado pendente os serviços não confirmados do funcionário (§7.5). */
    public function releaseAssignedServices(int $employeeId): int {
        $sql = "UPDATE agendamento_servico s
                INNER JOIN agendamento a ON s.agendamento_id = a.id
                SET s.funcionario_id = NULL,
                    s.estado_aceitacao = 'pendente',
                    s.aceito_em = NULL,
                    s.percentagem_funcionario_aplicada = NULL
                WHERE s.funcionario_id = :funcionario_id
                  AND a.estado_reserva NOT IN ('recusado', 'cancelado', 'executado', 'concluido', 'confirmado')";

        return $this->execute($sql, ["funcionario_id" => $employeeId]);
    }

    /** Agendamentos que deixaram de estar totalmente alocados → voltam a pendentes. */
    public function resetBookingsWithoutAllocation(): int {
        $sql = "UPDATE agendamento a
                SET a.estado_reserva = 'pendente_alocacao'
                WHERE a.estado_reserva = 'totalmente_alocado'
                  AND EXISTS (SELECT 1 FROM agendamento_servico s
                              WHERE s.agendamento_id = a.id AND s.estado_aceitacao = 'pendente')";

        return $this->execute($sql);
    }
}
