<?php

require_once APP_PATH . "/config/connection.php";

abstract class BaseRepository {
    protected $db;

    public function __construct() {
        global $conn;
        if (!$conn) {
            throw new Exception("Erro crítico: A ligação à base de dados não foi estabelecida.");
        }
        $this->db = $conn;
    }
}
