<?php

class Session {
    private static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function createLoginSession(array $user): void {
        self::init();
        $_SESSION["user_id"] = $user["id"];
        // O mapper de utilizador devolve chaves em inglês (name/email/profileType);
        // mantém-se compatibilidade com as chaves originais da BD (nome/tipo_perfil).
        $_SESSION["user_name"]    = $user["name"]        ?? $user["nome"]        ?? null;
        $_SESSION["user_email"]   = $user["email"]       ?? null;
        $_SESSION["user_profile"] = $user["profileType"] ?? $user["tipo_perfil"] ?? null;
    }

    public static function isLoggedIn() {
        self::init();
        return isset($_SESSION["user_id"]);
    }

    public static function user() {
        if (!self::isLoggedIn()) {
            return null;
        }
        return [
            "id"      => $_SESSION["user_id"],
            "name"    => $_SESSION["user_name"],
            "email"   => $_SESSION["user_email"],
            "profile" => $_SESSION["user_profile"]
        ];
    }

    public static function getUserProfile(): ?string {
        self::init();
        return $_SESSION["user_profile"] ?? null;
    }

    public static function isManager(): bool {
        return self::getUserProfile() === "gestor";
    }

    public static function isEmployee(): bool {
        return self::getUserProfile() === "funcionario";
    }

    public static function isCustomer(): bool {
        return self::getUserProfile() === "cliente";
    }

    public static function requireLogin($redirectUrl = null) {
        $url = $redirectUrl ?? (defined("BASE_URL") ? BASE_URL . "/login" : "/login");
        if (!self::isLoggedIn()) {
            header("Location: {$url}");
            exit;
        }
    }

    public static function requireLoginApi(): void {
        if (!self::isLoggedIn()) {
            throw new Exception("Sessão não iniciada.", 401);
        }
    }

    public static function requireProfileApi(array $profiles): void {
        self::requireLoginApi();
        if (!in_array(self::getUserProfile(), $profiles, true)) {
            throw new Exception("Sem permissões para esta operação.", 403);
        }
    }

    public static function destroy() {
        self::init();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), "", time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
