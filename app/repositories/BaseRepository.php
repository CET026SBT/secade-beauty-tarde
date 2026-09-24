<?php

require_once APP_PATH . "/config/connection.php";

abstract class BaseRepository {
    protected PDO $db;
    protected ?string $mapper = null;

    public function __construct() {
        global $conn;
        if (!$conn) {
            throw new Exception("Erro crítico: A ligação à base de dados não foi estabelecida.");
        }
        $this->db = $conn;
    }

    protected function fetchAll(string $sql, array $params=[]): mixed {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $this->transform($result);
    }

    protected function fetch(string $sql, array $params=[]): mixed {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $row = $result !== false ? $result : null;

        return $this->transform($row);
    }

    /**
     * Fetch de resultados auxiliares (lookups): NÃO aplica o mapper da entidade.
     */
    protected function fetchRaw(string $sql, array $params=[]): mixed {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    /**
     * Fetch de resultados agregados/relatórios: NÃO aplica o mapper da entidade
     * (as linhas não representam a entidade principal do repository).
     */
    protected function fetchAllRaw(string $sql, array $params=[]): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    private function transform(mixed $data): mixed {
        if ($data === null) {
            return null;
        }

        if ($this->mapper && class_exists($this->mapper)) {
            return call_user_func([$this->mapper, 'map'], $data);
        }

        return $data;
    }
    
    protected function execute(string $sql, array $params=[]): int {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected function lastInsertId(): string {
        return $this->db->lastInsertId();
    }

    protected function exists(string $sql, array $params=[]): bool {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() !== false;
    }
}
