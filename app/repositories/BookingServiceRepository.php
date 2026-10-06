<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/BookingServiceMapper.php";

class BookingServiceRepository extends BaseRepository {

    protected ?string $mapper = BookingServiceMapper::class;

    public function findByBooking(int $bookingId): array {
        $sql = "SELECT s.id, s.agendamento_id, s.agendamento_pessoa_id, s.servico_id,
                       s.funcionario_id, s.preco_praticado, s.duracao_minutos,
                       s.estado_aceitacao, s.aceito_em,
                       s.percentagem_funcionario_aplicada,
                       ROUND(s.preco_praticado * s.percentagem_funcionario_aplicada / 100, 2) AS valor_recibo_verde_funcionario,
                       ROUND(s.preco_praticado - ROUND(s.preco_praticado * s.percentagem_funcionario_aplicada / 100, 2), 2) AS valor_recibo_verde_plataforma,
                       sv.nome AS servico_nome, sv.duracao_estimada_minutos,
                       p.nome_pessoa, p.observacoes,
                       u.nome AS funcionario_nome
                FROM agendamento_servico s
                INNER JOIN servico sv ON s.servico_id = sv.id
                LEFT JOIN agendamento_pessoa p ON s.agendamento_pessoa_id = p.id
                LEFT JOIN funcionario f ON s.funcionario_id = f.id
                LEFT JOIN utilizador u ON f.id = u.id
                WHERE s.agendamento_id = :agendamento_id
                ORDER BY s.id ASC";

        $rows = $this->fetchAll($sql, ["agendamento_id" => $bookingId]);
        return is_array($rows) ? $rows : [];
    }

    public function findByBookingAndPerson(int $bookingId, int $personId): array {
        $sql = "SELECT s.id, s.agendamento_id, s.agendamento_pessoa_id, s.servico_id,
                       s.funcionario_id, s.preco_praticado, s.duracao_minutos, s.estado_aceitacao,
                       sv.nome AS servico_nome,
                       p.nome_pessoa
                FROM agendamento_servico s
                INNER JOIN servico sv ON s.servico_id = sv.id
                LEFT JOIN agendamento_pessoa p ON s.agendamento_pessoa_id = p.id
                WHERE s.agendamento_id = :agendamento_id
                  AND s.agendamento_pessoa_id = :pessoa_id";

        $rows = $this->fetchAll($sql, [
            "agendamento_id" => $bookingId,
            "pessoa_id"      => $personId
        ]);
        return is_array($rows) ? $rows : [];
    }

    public function create(int $bookingId, ?int $personId, int $serviceId, float $price, int $duration, string $estado): int {
        $sql = "INSERT INTO agendamento_servico (agendamento_id, agendamento_pessoa_id, servico_id,
                    preco_praticado, duracao_minutos, estado_aceitacao)
                VALUES (:agendamento_id, :pessoa_id, :servico_id, :preco_praticado, :duracao_minutos, :estado)";

        $this->execute($sql, [
            "agendamento_id"   => $bookingId,
            "pessoa_id"        => $personId,
            "servico_id"       => $serviceId,
            "preco_praticado"  => $price,
            "duracao_minutos"  => $duration,
            "estado"           => $estado
        ]);

        return (int)$this->lastInsertId();
    }

    public function countPendingByBooking(int $bookingId): int {
        $sql = "SELECT COUNT(*) FROM agendamento_servico
                WHERE agendamento_id = :agendamento_id AND estado_aceitacao = 'pendente'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(["agendamento_id" => $bookingId]);
        return (int)$stmt->fetchColumn();
    }

    public function countByBooking(int $bookingId): int {
        $sql = "SELECT COUNT(*) FROM agendamento_servico WHERE agendamento_id = :agendamento_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(["agendamento_id" => $bookingId]);
        return (int)$stmt->fetchColumn();
    }

    public function findById(int $id): ?array {
        $sql = "SELECT s.id, s.agendamento_id, s.agendamento_pessoa_id, s.servico_id,
                       s.funcionario_id, s.preco_praticado, s.duracao_minutos, s.estado_aceitacao,
                       sv.nome AS servico_nome, sv.categoria_id
                FROM agendamento_servico s
                INNER JOIN servico sv ON s.servico_id = sv.id
                WHERE s.id = :id LIMIT 1";

        $row = $this->fetch($sql, ["id" => $id]);
        return is_array($row) ? $row : null;
    }

    public function accept(int $id, int $funcionarioId, float $percentagemFuncionario): bool {
        // C-03/D-10: os valores por funcionário/empresa deixam de ser gravados —
        // calculam-se na leitura a partir de `preco_praticado × percentagem`.
        $sql = "UPDATE agendamento_servico
                SET funcionario_id = :funcionario_id,
                    estado_aceitacao = 'aceite',
                    aceito_em = NOW(),
                    percentagem_funcionario_aplicada = :percentagem
                WHERE id = :id";

        return $this->execute($sql, [
            "funcionario_id" => $funcionarioId,
            "percentagem"    => $percentagemFuncionario,
            "id"             => $id
        ]) > 0;
    }

    public function unaccept(int $id): bool {
        $sql = "UPDATE agendamento_servico
                SET funcionario_id = NULL, estado_aceitacao = 'pendente', aceito_em = NULL,
                    percentagem_funcionario_aplicada = NULL
                WHERE id = :id";

        return $this->execute($sql, ["id" => $id]) > 0;
    }

    public function findPending(array $filters = []): array {
        $sql = "SELECT s.id, s.agendamento_id, s.agendamento_pessoa_id, s.servico_id,
                       s.preco_praticado, s.duracao_minutos, s.estado_aceitacao,
                       sv.nome AS servico_nome, sv.categoria_id, cat.nome AS categoria_nome,
                       p.nome_pessoa,
                       a.data_hora_pretendida, a.local_prestacao, a.estado_reserva,
                       a.cliente_id
                FROM agendamento_servico s
                INNER JOIN servico sv ON s.servico_id = sv.id
                INNER JOIN categoria_servico cat ON sv.categoria_id = cat.id
                INNER JOIN agendamento a ON s.agendamento_id = a.id
                LEFT JOIN agendamento_pessoa p ON s.agendamento_pessoa_id = p.id
                WHERE s.estado_aceitacao = 'pendente'
                  AND a.local_prestacao = 'carrinha_ambulante'
                  -- RN-32 (§24.7): a partir do momento em que o agendamento entra numa
                  -- rota confirmada (`confirmado`) deixa de aparecer na lista Por
                  -- aceitar — o acompanhamento passa a ser a agenda do funcionário
                  -- (RN-33). Antes aceitavam-se serviços de rotas já aprovadas, o que
                  -- contrariava a RN-31 (a rota só agrega o que está todo aceite).
                  AND a.estado_reserva NOT IN ('cancelado', 'recusado', 'executado', 'concluido', 'confirmado')";
        $params = [];

        if (!empty($filters["categoriaId"])) {
            $sql .= " AND sv.categoria_id = :categoria_id";
            $params["categoria_id"] = (int)$filters["categoriaId"];
        }

        if (!empty($filters["data"])) {
            $sql .= " AND DATE(a.data_hora_pretendida) = :data";
            $params["data"] = $filters["data"];
        }

        $sql .= " ORDER BY a.data_hora_pretendida ASC, p.id ASC, s.id ASC";

        $rows = $this->fetchAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    /** Remove todos os serviços de um agendamento (edição — F9b · C-06). */
    public function deleteByBooking(int $bookingId): int {
        return $this->execute("DELETE FROM agendamento_servico WHERE agendamento_id = :id", ["id" => $bookingId]);
    }

    /** Duração total já gravada num agendamento (sem depender de JOIN). */
    public function totalDurationByBooking(int $bookingId): int {
        $sql = "SELECT COALESCE(SUM(duracao_minutos), 0) FROM agendamento_servico
                WHERE agendamento_id = :agendamento_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(["agendamento_id" => $bookingId]);
        return (int)$stmt->fetchColumn();
    }

    // ------------------------------------------------------------------
    // Fase 3 — Backoffice do funcionário
    // ------------------------------------------------------------------

    /**
     * Serviços alocados/aceites (opção "Totalmente Alocado" no backoffice).
     * `$employeeId = null` devolve as alocações de **todos** (visão do gestor).
     */
    public function findAcceptedByEmployee(?int $employeeId, array $filters = []): array {
        $sql = "SELECT s.id, s.agendamento_id, s.agendamento_pessoa_id, s.servico_id,
                       s.funcionario_id, s.preco_praticado, s.duracao_minutos, s.estado_aceitacao,
                       s.aceito_em, s.percentagem_funcionario_aplicada,
                       ROUND(s.preco_praticado * s.percentagem_funcionario_aplicada / 100, 2) AS valor_recibo_verde_funcionario,
                       ROUND(s.preco_praticado - ROUND(s.preco_praticado * s.percentagem_funcionario_aplicada / 100, 2), 2) AS valor_recibo_verde_plataforma,
                       sv.nome AS servico_nome, sv.categoria_id, cat.nome AS categoria_nome,
                       p.nome_pessoa,
                       fu.nome AS funcionario_nome,
                       a.data_hora_pretendida, a.local_prestacao, a.estado_reserva,
                       a.cliente_id, u.nome AS cliente_nome
                FROM agendamento_servico s
                INNER JOIN servico sv ON s.servico_id = sv.id
                INNER JOIN categoria_servico cat ON sv.categoria_id = cat.id
                INNER JOIN agendamento a ON s.agendamento_id = a.id
                INNER JOIN cliente c ON a.cliente_id = c.id
                INNER JOIN utilizador u ON c.id = u.id
                LEFT JOIN utilizador fu ON s.funcionario_id = fu.id
                LEFT JOIN agendamento_pessoa p ON s.agendamento_pessoa_id = p.id
                WHERE s.funcionario_id IS NOT NULL";
        $params = [];

        // O gestor vê todas as alocações; o funcionário só as suas.
        if ($employeeId !== null && $employeeId > 0) {
            $sql .= " AND s.funcionario_id = :funcionario_id";
            $params["funcionario_id"] = $employeeId;
        }

        if (!empty($filters["data"])) {
            $sql .= " AND DATE(a.data_hora_pretendida) = :data";
            $params["data"] = $filters["data"];
        }

        if (!empty($filters["categoriaId"])) {
            $sql .= " AND sv.categoria_id = :categoria_id";
            $params["categoria_id"] = (int)$filters["categoriaId"];
        }

        $sql .= " ORDER BY a.data_hora_pretendida ASC, s.id ASC";

        // Mapeado (camelCase) para ser consistente com findPending() e com o resto da API.
        return $this->fetchAll($sql, $params);
    }

    /**
     * Serviço individual validado contra o agendamento a que pertence
     * (usado nas verificações de segurança das operações de aceitação).
     */
    public function findByIdAndBooking(int $id, int $bookingId): mixed {
        $sql = "SELECT s.id, s.agendamento_id, s.agendamento_pessoa_id, s.servico_id,
                       s.funcionario_id, s.preco_praticado, s.duracao_minutos, s.estado_aceitacao,
                       sv.nome AS servico_nome, sv.categoria_id
                FROM agendamento_servico s
                INNER JOIN servico sv ON s.servico_id = sv.id
                WHERE s.id = :id AND s.agendamento_id = :agendamento_id
                LIMIT 1";

        return $this->fetch($sql, ["id" => $id, "agendamento_id" => $bookingId]);
    }

    /**
     * Contagem de serviços por estado de aceitação num agendamento.
     */
    public function countByBookingGroupedByState(int $bookingId): array {
        $sql = "SELECT estado_aceitacao, COUNT(*) AS total
                FROM agendamento_servico
                WHERE agendamento_id = :agendamento_id
                GROUP BY estado_aceitacao";

        return $this->fetchAllRaw($sql, ["agendamento_id" => $bookingId]);
    }

    // ------------------------------------------------------------------
    // Fase 6.5 — Agenda do funcionário
    // ------------------------------------------------------------------

    /**
     * Agenda do funcionário num intervalo de datas.
     *
     * RN-33 (§24.7 · §10.1): a agenda mostra **apenas** agendamentos de **rotas
     * confirmadas** (`estado_reserva = 'confirmado'`). O que ainda se aceita ou
     * desfaz continua na listagem, não no calendário.
     */
    public function findByEmployeeAndRange(int $employeeId, string $dateFrom, string $dateTo): array {
        $sql = "SELECT s.id, s.agendamento_id, s.servico_id, s.preco_praticado, s.duracao_minutos,
                       s.estado_aceitacao, sv.nome AS servico_nome, cat.nome AS categoria_nome,
                       p.nome_pessoa,
                       a.data_hora_pretendida, a.local_prestacao, a.estado_reserva,
                       a.cliente_id, u.nome AS cliente_nome,
                       cm.cidade_id, cid.nome AS cidade_nome
                FROM agendamento_servico s
                INNER JOIN servico sv ON s.servico_id = sv.id
                INNER JOIN categoria_servico cat ON sv.categoria_id = cat.id
                INNER JOIN agendamento a ON s.agendamento_id = a.id
                INNER JOIN cliente c ON a.cliente_id = c.id
                INNER JOIN utilizador u ON c.id = u.id
                LEFT JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                LEFT JOIN cidade cid ON cm.cidade_id = cid.id
                LEFT JOIN agendamento_pessoa p ON s.agendamento_pessoa_id = p.id
                WHERE s.funcionario_id = :funcionario_id
                  AND s.estado_aceitacao = 'aceite'
                  AND a.estado_reserva = 'confirmado'
                  AND DATE(a.data_hora_pretendida) BETWEEN :de AND :ate
                ORDER BY a.data_hora_pretendida ASC, s.id ASC";

        return $this->fetchAllRaw($sql, [
            "funcionario_id" => $employeeId,
            "de"             => $dateFrom,
            "ate"            => $dateTo
        ]);
    }
}
