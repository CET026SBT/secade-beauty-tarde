<?php

require_once __DIR__ . "/BaseRepository.php";
require_once APP_PATH . "/mappers/BookingMapper.php";

class BookingRepository extends BaseRepository {

    protected ?string $mapper = BookingMapper::class;

    public function find(int $id): mixed {
        $sql = "SELECT a.id, a.cliente_id, a.cliente_morada_id, a.local_prestacao,
                       a.data_hora_pretendida, a.estado_reserva, a.valor_total,
                       a.sinal_pago, a.valor_sinal, a.validado_logistica_loja, a.criado_em
                FROM agendamento a
                WHERE a.id = :id LIMIT 1";

        return $this->fetch($sql, ["id" => $id]);
    }

    public function findByCustomer(int $customerId, ?array $filters = []): array {
        $sql = "SELECT a.id, a.cliente_id, a.local_prestacao, a.data_hora_pretendida,
                       a.estado_reserva, a.valor_total, a.criado_em
                FROM agendamento a
                WHERE a.cliente_id = :cliente_id";
        $params = ["cliente_id" => $customerId];

        if (!empty($filters["estado"])) {
            $sql .= " AND a.estado_reserva = :estado";
            $params["estado"] = $filters["estado"];
        }

        $sql .= " ORDER BY a.data_hora_pretendida DESC";

        $rows = $this->fetchAll($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    public function create(array $data): int {
        $sql = "INSERT INTO agendamento (cliente_id, cliente_morada_id, local_prestacao,
                    data_hora_pretendida, estado_reserva, valor_total, sinal_pago, valor_sinal)
                VALUES (:cliente_id, :cliente_morada_id, :local_prestacao,
                    :data_hora_pretendida, :estado_reserva, :valor_total, :sinal_pago, :valor_sinal)";

        $this->execute($sql, [
            "cliente_id"            => $data["customerId"],
            "cliente_morada_id"     => $data["addressId"] ?? null,
            "local_prestacao"       => $data["local"],
            "data_hora_pretendida"  => $data["dateTime"],
            "estado_reserva"        => $data["estado"],
            "valor_total"           => $data["totalAmount"],
            "sinal_pago"            => (int)($data["sinalPago"] ?? 0),
            "valor_sinal"           => $data["sinalAmount"] ?? 0
        ]);

        return (int)$this->lastInsertId();
    }

    public function updateEstado(int $id, string $estado): bool {
        $sql = "UPDATE agendamento SET estado_reserva = :estado WHERE id = :id";

        return $this->execute($sql, ["estado" => $estado, "id" => $id]) >= 0;
    }

    public function countByDateWindow(string $dateTimeStart, int $durationMinutes, string $local, array $excludeIds = []): int {
        $sql = "SELECT COUNT(*) FROM agendamento
                WHERE local_prestacao = :local
                  AND estado_reserva IN ('pendente_validacao_logistica_loja','totalmente_alocado','confirmado')
                  AND data_hora_pretendida < :fim
                  AND DATE_ADD(data_hora_pretendida, INTERVAL :duracao MINUTE) > :inicio";
        $params = [
            "local"   => $local,
            "inicio"  => $dateTimeStart,
            "fim"     => date("Y-m-d H:i:s", strtotime($dateTimeStart) + $durationMinutes * 60),
            "duracao" => $durationMinutes
        ];

        if (!empty($excludeIds)) {
            $placeholders = [];
            foreach (array_values($excludeIds) as $i => $excludedId) {
                $placeholders[] = ":excl{$i}";
                $params["excl{$i}"] = $excludedId;
            }
            $sql .= " AND id NOT IN (" . implode(",", $placeholders) . ")";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // ------------------------------------------------------------------
    // Fase 7 (F4) — regras de alocação e confirmação (§4.4)
    // ------------------------------------------------------------------

    /** Cidade da morada de um agendamento (nulo quando não tem morada). */
    public function findCityId(int $bookingId): ?int {
        $row = $this->fetchRaw(
            "SELECT cm.cidade_id
             FROM agendamento a
             LEFT JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
             WHERE a.id = :id LIMIT 1",
            ["id" => $bookingId]
        );

        return isset($row["cidade_id"]) ? (int)$row["cidade_id"] : null;
    }

    /**
     * R-ALOC (§4.4): quantos serviços **aceites** este funcionário já tem, no mesmo
     * dia, em **agendamentos de outra cidade**. Se > 0, não pode ser alocado aqui
     * (não pode estar em duas cidades no mesmo dia).
     */
    public function countEmployeeInOtherCitySameDay(int $employeeId, int $bookingId): int {
        $sql = "SELECT COUNT(*)
                FROM agendamento_servico s
                INNER JOIN agendamento a ON s.agendamento_id = a.id
                LEFT JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                WHERE s.funcionario_id = :funcionario_id
                  AND s.estado_aceitacao = 'aceite'
                  AND s.agendamento_id <> :agendamento_id
                  AND a.estado_reserva NOT IN ('cancelado', 'recusado', 'executado', 'concluido')
                  AND DATE(a.data_hora_pretendida) = (
                        SELECT DATE(a2.data_hora_pretendida) FROM agendamento a2 WHERE a2.id = :agendamento_id
                  )
                  AND cm.cidade_id IS NOT NULL AND cm.cidade_id <> (
                        SELECT cm2.cidade_id
                        FROM agendamento a3
                        LEFT JOIN cliente_morada cm2 ON a3.cliente_morada_id = cm2.id
                        WHERE a3.id = :agendamento_id
                  )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(["funcionario_id" => $employeeId, "agendamento_id" => $bookingId]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * R-CONF (§4.4): **quantos** agendamentos já confirmados na mesma cidade e data
     * têm janela temporal sobreposta à dos agendamentos a confirmar. > 0 bloqueia a
     * confirmação da rota (a carrinha não está em dois sítios à mesma hora).
     */
    public function findCityWindowConflicts(int $cityId, string $date, array $bookingIds): int {
        if (empty($bookingIds)) {
            return 0;
        }

        $placeholders = [];
        $params = ["cidade_id" => $cityId, "data_rota" => $date];

        foreach (array_values($bookingIds) as $i => $id) {
            $placeholders[] = ":inc{$i}";
            $params["inc{$i}"] = (int)$id;
        }

        $inList = implode(",", $placeholders);

        // A duração de cada agendamento é a soma das durações dos seus serviços.
        $durationJoin = "INNER JOIN (
                            SELECT agendamento_id, COALESCE(SUM(duracao_minutos), 0) AS dur
                            FROM agendamento_servico
                            GROUP BY agendamento_id
                        ) %s ON %s.agendamento_id = %s.id";

        $sql = "SELECT COUNT(*)
                FROM agendamento a
                INNER JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                " . sprintf($durationJoin, "d", "d", "a") . "
                WHERE a.local_prestacao = 'carrinha_ambulante'
                  AND cm.cidade_id = :cidade_id
                  AND DATE(a.data_hora_pretendida) = :data_rota
                  AND a.estado_reserva = 'confirmado'
                  AND EXISTS (
                        SELECT 1
                        FROM agendamento inc
                        " . sprintf($durationJoin, "di", "di", "inc") . "
                        WHERE inc.id IN ({$inList})
                          AND inc.data_hora_pretendida < DATE_ADD(a.data_hora_pretendida, INTERVAL GREATEST(d.dur, 1) MINUTE)
                          AND DATE_ADD(inc.data_hora_pretendida, INTERVAL GREATEST(di.dur, 1) MINUTE) > a.data_hora_pretendida
                  )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * R-ALOC (§4.4) — lista: funcionários **ocupados noutra cidade** no dia deste
     * agendamento. O front-end usa-a para desativar as opções do seletor.
     */
    public function findEmployeesBusyElsewhereOnDate(int $bookingId): array {
        $sql = "SELECT DISTINCT s.funcionario_id
                FROM agendamento_servico s
                INNER JOIN agendamento a ON s.agendamento_id = a.id
                LEFT JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                WHERE s.funcionario_id IS NOT NULL
                  AND s.estado_aceitacao = 'aceite'
                  AND s.agendamento_id <> :agendamento_id
                  AND a.estado_reserva NOT IN ('cancelado', 'recusado', 'executado', 'concluido')
                  AND DATE(a.data_hora_pretendida) = (
                        SELECT DATE(a2.data_hora_pretendida) FROM agendamento a2 WHERE a2.id = :agendamento_id
                  )
                  AND cm.cidade_id IS NOT NULL AND cm.cidade_id <> (
                        SELECT cm2.cidade_id
                        FROM agendamento a3
                        LEFT JOIN cliente_morada cm2 ON a3.cliente_morada_id = cm2.id
                        WHERE a3.id = :agendamento_id
                  )";

        $rows = $this->fetchAllRaw($sql, ["agendamento_id" => $bookingId]);

        return array_values(array_map(fn($row) => (int)$row["funcionario_id"], $rows));
    }

    /** Ids (dentre os indicados) cuja execução é a **menos de 24 h** — R-24H (§4.4). */
    public function findIdsWithin24Hours(array $bookingIds): array {
        if (empty($bookingIds)) {
            return [];
        }

        $placeholders = [];
        $params = [];

        foreach (array_values($bookingIds) as $i => $id) {
            $placeholders[] = ":b{$i}";
            $params["b{$i}"] = (int)$id;
        }

        $sql = "SELECT a.id
                FROM agendamento a
                WHERE a.id IN (" . implode(",", $placeholders) . ")
                  AND a.data_hora_pretendida < DATE_ADD(NOW(), INTERVAL 24 HOUR)";

        $rows = $this->fetchAllRaw($sql, $params);

        return array_values(array_map(fn($row) => (int)$row["id"], $rows));
    }

    /**
     * Próximas marcações do cliente (não terminais e ainda no futuro) — F9 · §3.5.
     */
    public function findUpcomingByCustomer(int $customerId): array {
        $sql = "SELECT id, data_hora_pretendida AS dateTime, estado_reserva AS status, local_prestacao AS local, valor_total AS totalAmount
                FROM agendamento
                WHERE cliente_id = :cliente_id
                  AND data_hora_pretendida >= NOW()
                  AND estado_reserva NOT IN ('cancelado', 'recusado', 'concluido')
                ORDER BY data_hora_pretendida ASC
                LIMIT 10";

        return $this->fetchAllRaw($sql, ["cliente_id" => $customerId]);
    }

    /**
     * R1a (§8.2 · RF-59/RN-25): a **24 h** da execução, sem rota confirmada, o
     * agendamento é auto-recusado (o cliente é avisado por `notificacao`).
     *
     * Devolve as linhas afetadas para que o serviço de manutenção possa avisar os
     * clientes — a reconciliação não escreve avisos sem saber a quem.
     */
    public function refuseWithoutRouteAtCutoff(string $now, int $hoursBefore): array {
        $rows = $this->fetchAllRaw(
            "SELECT id, cliente_id
             FROM agendamento
             WHERE estado_reserva IN ('pendente_alocacao', 'pendente_validacao_logistica_loja', 'totalmente_alocado')
               AND data_hora_pretendida <= DATE_ADD(:agora, INTERVAL :horas HOUR)",
            ["agora" => $now, "horas" => $hoursBefore]
        );

        if (empty($rows)) {
            return [];
        }

        $ids = array_map(fn($row) => (int)$row["id"], $rows);

        $this->execute(
            "UPDATE agendamento SET estado_reserva = 'recusado'
             WHERE id IN (" . implode(",", array_map("intval", $ids)) . ")"
        );

        return $rows;
    }

    /** R1b (§8.2): agendamentos `confirmado` cuja execução já começou. */
    public function findConfirmedStarted(string $now): array {
        $sql = "SELECT id, data_hora_pretendida, cliente_id
                FROM agendamento
                WHERE estado_reserva = 'confirmado'
                  AND data_hora_pretendida < :agora";

        return $this->fetchAllRaw($sql, ["agora" => $now]);
    }

    /**
     * Marcações **confirmadas** que entram nas próximas 24 h e ainda não têm
     * lembrete (RF-13). A ausência de aviso evita repetir todos os dias.
     */
    public function findConfirmedWithin24Hours(string $now): array {
        $sql = "SELECT a.id, a.cliente_id, a.data_hora_pretendida, a.local_prestacao,
                       cm.cidade_id, c.nome AS cidade_nome
                FROM agendamento a
                LEFT JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                LEFT JOIN cidade c ON cm.cidade_id = c.id
                WHERE a.estado_reserva = 'confirmado'
                  AND a.data_hora_pretendida BETWEEN :agora AND DATE_ADD(:agora, INTERVAL 24 HOUR)
                  AND NOT EXISTS (
                      SELECT 1 FROM notificacao n
                      WHERE n.utilizador_id = a.cliente_id
                        AND n.tipo = 'lembrete_24h'
                        AND n.mensagem LIKE CONCAT('Agendamento #', a.id, ' %')
                  )";

        return $this->fetchAllRaw($sql, ["agora" => $now]);
    }

    /**
     * Alternativas para o lembrete (RF-13): os **próximos horários livres** da mesma
     * cidade/canal, para o cliente poder reagendar em vez de perder a marcação.
     */
    public function findAlternativeSlots(array $booking, int $limit = 3): array {
        $cityId = $booking["cidade_id"] ?? null;
        $local  = (string)($booking["local_prestacao"] ?? "loja_fisica");
        $params = [
            "data" => substr((string)$booking["data_hora_pretendida"], 0, 10),
            "local" => $local
        ];

        $cityFilter = "";

        if ($cityId !== null && (int)$cityId > 0) {
            $cityFilter = " AND cm.cidade_id = :cidade_id";
            $params["cidade_id"] = (int)$cityId;
        }

        $sql = "SELECT DATE(a.data_hora_pretendida) AS data_alt, COUNT(*) AS total
                FROM agendamento a
                LEFT JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                WHERE a.local_prestacao = :local
                  AND DATE(a.data_hora_pretendida) > :data
                  AND a.estado_reserva NOT IN ('cancelado', 'recusado')
                  {$cityFilter}
                GROUP BY DATE(a.data_hora_pretendida)
                ORDER BY data_alt ASC
                LIMIT " . (int)$limit;

        return $this->fetchAllRaw($sql, $params);
    }

    /** Funcionários com serviços aceites num agendamento (para o aviso R1b). */
    public function findAmbulatoryBookingEmployees(int $bookingId): array {
        $rows = $this->fetchAllRaw(
            "SELECT DISTINCT funcionario_id
             FROM agendamento_servico
             WHERE agendamento_id = :agendamento_id
               AND funcionario_id IS NOT NULL
               AND estado_aceitacao = 'aceite'",
            ["agendamento_id" => $bookingId]
        );

        return array_values(array_map(fn($row) => (int)$row["funcionario_id"], $rows));
    }

    /**
     * R2 (§8.2): agendamentos `executado` cuja janela terminou há mais de N horas
     * → passam a `concluido`.
     */
    public function completeExecutedAfterWindow(string $now, int $hoursAfterEnd): int {
        $sql = "UPDATE agendamento a
                INNER JOIN (
                    SELECT agendamento_id, COALESCE(SUM(duracao_minutos), 0) AS dur
                    FROM agendamento_servico
                    GROUP BY agendamento_id
                ) d ON d.agendamento_id = a.id
                SET a.estado_reserva = 'concluido'
                WHERE a.estado_reserva = 'executado'
                  AND DATE_ADD(a.data_hora_pretendida, INTERVAL (GREATEST(d.dur, 1) + :horas * 60) MINUTE) < :agora";

        return $this->execute($sql, ["horas" => $hoursAfterEnd, "agora" => $now]);
    }

    /** Clientes + ids de uma lista de agendamentos (para os avisos de rota — F10). */
    public function findCustomersOfBookings(array $bookingIds): array {
        if (empty($bookingIds)) {
            return [];
        }

        $ids = implode(",", array_map("intval", $bookingIds));

        return $this->fetchAllRaw(
            "SELECT id, cliente_id FROM agendamento WHERE id IN ({$ids}) AND cliente_id IS NOT NULL"
        );
    }

    /** Estados dos agendamentos filhos de uma rota (para R3/R4 — cascata). */
    public function childStatesOfRoute(int $routeId): array {
        $rows = $this->fetchAllRaw(
            "SELECT a.estado_reserva
             FROM execucao_agendamento e
             INNER JOIN agendamento a ON e.agendamento_id = a.id
             WHERE e.rota_id = :rota_id",
            ["rota_id" => $routeId]
        );

        if (empty($rows)) {
            // Sem execuções registadas: os filhos são os agendamentos da cidade+data.
            $route = $this->fetchRaw(
                "SELECT data_rota, cidade_id FROM rota_ambulante WHERE id = :id",
                ["id" => $routeId]
            );

            if ($route) {
                $children = $this->fetchAllRaw(
                    "SELECT a.estado_reserva
                     FROM agendamento a
                     INNER JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                     WHERE a.local_prestacao = 'carrinha_ambulante'
                       AND cm.cidade_id = :cidade_id
                       AND DATE(a.data_hora_pretendida) = :data_rota",
                    ["cidade_id" => (int)$route["cidade_id"], "data_rota" => (string)$route["data_rota"]]
                );

                return array_values(array_map(fn($row) => (string)$row["estado_reserva"], $children));
            }

            return [];
        }

        return array_values(array_map(fn($row) => (string)$row["estado_reserva"], $rows));
    }

    // ------------------------------------------------------------------
    // Backoffice (admin)
    // ------------------------------------------------------------------

    /**
     * Agendamento com dados do cliente, telemóvel, morada e cidade
     * (usado no detalhe por serviço/funcionário do backoffice).
     */
    public function findDetailed(int $id): mixed {
        $sql = "SELECT a.id, a.cliente_id, u.nome AS cliente_nome, u.email AS cliente_email,
                       u.telemovel AS cliente_telemovel, a.cliente_morada_id,
                       a.local_prestacao, a.data_hora_pretendida, a.estado_reserva,
                       a.valor_total, a.sinal_pago, a.valor_sinal, a.validado_logistica_loja,
                       a.criado_em, cm.cidade_id, cid.nome AS cidade_nome,
                       CONCAT_WS(', ', cm.rua, cm.numero_porta, cm.codigo_postal) AS morada_completa
                FROM agendamento a
                INNER JOIN cliente c ON a.cliente_id = c.id
                INNER JOIN utilizador u ON c.id = u.id
                LEFT JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                LEFT JOIN cidade cid ON cm.cidade_id = cid.id
                WHERE a.id = :id LIMIT 1";

        return $this->fetch($sql, ["id" => $id]);
    }

    private function buildAdminFilters(array $filters, array &$params): string {
        $sql = "";

        if (!empty($filters["local"])) {
            $sql .= " AND a.local_prestacao = :local";
            $params["local"] = $filters["local"];
        }

        if (!empty($filters["status"])) {
            $sql .= " AND a.estado_reserva = :estado_reserva";
            $params["estado_reserva"] = $filters["status"];
        }

        if (!empty($filters["date"])) {
            $sql .= " AND DATE(a.data_hora_pretendida) = :data_hora";
            $params["data_hora"] = $filters["date"];
        }

        if (!empty($filters["cityId"])) {
            $sql .= " AND cm.cidade_id = :cidade_id";
            $params["cidade_id"] = (int)$filters["cityId"];
        }

        return $sql;
    }

    public function findAllForAdmin(array $filters, int $limit, int $offset): array {
        $sql = "SELECT a.id, a.cliente_id, u.nome AS cliente_nome, u.telemovel AS cliente_telemovel,
                       a.cliente_morada_id, a.local_prestacao, a.data_hora_pretendida, a.estado_reserva,
                       a.valor_total, a.sinal_pago, a.valor_sinal, a.validado_logistica_loja, a.criado_em,
                       cm.cidade_id, cid.nome AS cidade_nome
                FROM agendamento a
                INNER JOIN cliente c ON a.cliente_id = c.id
                INNER JOIN utilizador u ON c.id = u.id
                LEFT JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                LEFT JOIN cidade cid ON cm.cidade_id = cid.id
                WHERE 1=1";
        $params = [];
        $sql .= $this->buildAdminFilters($filters, $params);
        $sql .= " ORDER BY a.data_hora_pretendida DESC, a.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":" . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return BookingMapper::map($rows) ?? [];
    }

    public function countAllForAdmin(array $filters): int {
        $sql = "SELECT COUNT(*)
                FROM agendamento a
                INNER JOIN cliente c ON a.cliente_id = c.id
                INNER JOIN utilizador u ON c.id = u.id
                LEFT JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                WHERE 1=1";
        $params = [];
        $sql .= $this->buildAdminFilters($filters, $params);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // ------------------------------------------------------------------
    // Algoritmo de viabilidade de rotas
    // ------------------------------------------------------------------

    /**
     * Agrupa os agendamentos de ambulatório por data + cidade.
     *
     * RN-31 (§24.7): a rota só agrega agendamentos com **todos** os serviços aceites
     * (`totalmente_alocado`). Os que aguardam aceitação **não** entram na
     * agregação — aparecem apenas como aviso na listagem (não se agrega o que não
     * está qualificado).
     */
    public function findAmbulatoryGroups(?string $date = null, ?int $cityId = null, array $states = []): array {
        if (empty($states)) {
            $states = ["totalmente_alocado"];
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($states) as $i => $state) {
            $placeholders[] = ":estado{$i}";
            $params["estado{$i}"] = $state;
        }

        $sql = "SELECT DATE(a.data_hora_pretendida) AS data_rota,
                       cm.cidade_id,
                       cid.nome AS cidade_nome,
                       cid.distrito,
                       SUM(a.valor_total) AS receita_prevista,
                       COUNT(a.id) AS total_agendamentos,
                       SUM(a.estado_reserva = 'totalmente_alocado') AS total_consolidados,
                       GROUP_CONCAT(a.id) AS agendamentos_ids
                FROM agendamento a
                INNER JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                INNER JOIN cidade cid ON cm.cidade_id = cid.id
                WHERE a.local_prestacao = 'carrinha_ambulante'
                  AND a.estado_reserva IN (" . implode(",", $placeholders) . ")";

        if (!empty($date)) {
            $sql .= " AND DATE(a.data_hora_pretendida) = :data_rota";
            $params["data_rota"] = $date;
        }

        if (!empty($cityId)) {
            $sql .= " AND cm.cidade_id = :cidade_id";
            $params["cidade_id"] = $cityId;
        }

        $sql .= " GROUP BY DATE(a.data_hora_pretendida), cm.cidade_id, cid.nome, cid.distrito
                  ORDER BY data_rota ASC, cid.nome ASC";

        return $this->fetchAllRaw($sql, $params);
    }

    public function updateEstadoMany(array $ids, string $estado): int {
        $ids = array_values(array_filter(array_map("intval", $ids)));
        if (empty($ids)) {
            return 0;
        }

        $placeholders = [];
        $params = ["estado_reserva" => $estado];
        foreach ($ids as $i => $id) {
            $placeholders[] = ":id{$i}";
            $params["id{$i}"] = $id;
        }

        $sql = "UPDATE agendamento SET estado_reserva = :estado_reserva
                WHERE id IN (" . implode(",", $placeholders) . ")";

        return $this->execute($sql, $params);
    }

    /**
     * Agendamentos de ambulatório de uma cidade+data em condições de receber a
     * decisão MANUAL do gestor (Fase 4 — sem limiar automático).
     *
     * RN-31 (§24.7): só os **qualificados** (`totalmente_alocado`), ou
     * seja com todos os serviços aceites. Um grupo com serviços pendentes é
     * recusado com 409 por `RotaService::decideRoute` (ver `countPendingByCityAndDate`).
     */
    public function findDecidableByCityAndDate(string $date, int $cityId): array {
        $sql = "SELECT a.id, a.estado_reserva, a.valor_total
                FROM agendamento a
                INNER JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                WHERE a.local_prestacao = 'carrinha_ambulante'
                  AND cm.cidade_id = :cidade_id
                  AND DATE(a.data_hora_pretendida) = :data_rota
                  AND a.estado_reserva = 'totalmente_alocado'
                ORDER BY a.id ASC";

        return $this->fetchAllRaw($sql, ["cidade_id" => $cityId, "data_rota" => $date]);
    }

    /**
     * Agendamentos de ambulatório de uma cidade+data que ainda aguardam aceitação
     * de serviços (RN-31) — mostrados na listagem, nunca agregados na rota.
     */
    public function countPendingByCityAndDate(string $date, int $cityId): int {
        $sql = "SELECT COUNT(*)
                FROM agendamento a
                INNER JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                WHERE a.local_prestacao = 'carrinha_ambulante'
                  AND cm.cidade_id = :cidade_id
                  AND DATE(a.data_hora_pretendida) = :data_rota
                  AND a.estado_reserva = 'pendente_alocacao'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(["cidade_id" => $cityId, "data_rota" => $date]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Detalhe dos agendamentos de ambulatório de uma cidade+data (RN-34): serve o
     * diálogo "Ver detalhes" da rota, onde o gestor inclui/exclui cada agendamento
     * antes de decidir. Sem coleções filhas: os serviços são lidos pelo Service
     * (`BookingServiceRepository`), como manda a §18.2.
     */
    public function findAmbulatoryBookingsByCityAndDate(string $date, int $cityId, array $states = []): array {
        if (empty($states)) {
            $states = ["totalmente_alocado"];
        }

        $placeholders = [];
        $params = ["cidade_id" => $cityId, "data_rota" => $date];

        foreach (array_values($states) as $i => $state) {
            $placeholders[] = ":estado{$i}";
            $params["estado{$i}"] = $state;
        }

        $sql = "SELECT a.id, a.data_hora_pretendida, a.estado_reserva, a.valor_total, a.local_prestacao,
                       u.nome AS cliente_nome, u.telemovel AS cliente_telemovel
                FROM agendamento a
                INNER JOIN cliente c ON a.cliente_id = c.id
                INNER JOIN utilizador u ON c.id = u.id
                INNER JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                WHERE a.local_prestacao = 'carrinha_ambulante'
                  AND cm.cidade_id = :cidade_id
                  AND DATE(a.data_hora_pretendida) = :data_rota
                  AND a.estado_reserva IN (" . implode(",", $placeholders) . ")
                ORDER BY a.data_hora_pretendida ASC, a.id ASC";

        return $this->fetchAllRaw($sql, $params);
    }
}
