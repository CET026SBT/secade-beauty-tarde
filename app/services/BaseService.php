<?php

require_once APP_PATH . "/config/connection.php";
require_once APP_PATH . "/utils/Validator.php";

abstract class BaseService {
    protected $db;

    public function __construct() {
        global $conn;
        $this->db = $conn;
    }

    protected function validate(array $data, callable $rules): void {
        $v = new Validator($data);
        $rules($v);
        $v->throwIfFails();
    }

    protected function executeTransactional(callable $callback) {
        $inTransaction = $this->db->inTransaction();
        if (!$inTransaction) $this->db->beginTransaction();

        try {
            $result = $callback();
            if (!$inTransaction) $this->db->commit();
            return $result;
        } catch (Exception $e) {
            if (!$inTransaction) $this->db->rollBack();
            throw $e;
        }
    }
}
