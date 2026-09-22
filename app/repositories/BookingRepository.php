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
                  AND estado_reserva IN ('pendente_validacao_logistica_loja','totalmente_aceite_funcionarios','confirmado')
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
     * Por omissão considera os estados que podem receber decisão manual do gestor
     * (Fase 4): aguardam aceitação e totalmente aceites.
     */
    public function findAmbulatoryGroups(?string $date = null, ?int $cityId = null, array $states = []): array {
        if (empty($states)) {
            $states = ["pendente_aceitacao_funcionarios", "totalmente_aceite_funcionarios"];
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
                       SUM(a.estado_reserva = 'totalmente_aceite_funcionarios') AS total_consolidados,
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
     * Agendamentos de ambulatório de uma cidade+data que estão em condições
     * de receber a decisão MANUAL do gestor (Fase 4 — sem limiar automático).
     * Inclui os consolidados e os que aguardam aceitação (recusa antecipada).
     */
    public function findDecidableByCityAndDate(string $date, int $cityId): array {
        $sql = "SELECT a.id, a.estado_reserva, a.valor_total
                FROM agendamento a
                INNER JOIN cliente_morada cm ON a.cliente_morada_id = cm.id
                WHERE a.local_prestacao = 'carrinha_ambulante'
                  AND cm.cidade_id = :cidade_id
                  AND DATE(a.data_hora_pretendida) = :data_rota
                  AND a.estado_reserva IN ('pendente_aceitacao_funcionarios','totalmente_aceite_funcionarios')
                ORDER BY a.id ASC";

        return $this->fetchAllRaw($sql, ["cidade_id" => $cityId, "data_rota" => $date]);
    }
}
