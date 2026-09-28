<?php

class Session {
    private static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Garante a sessão iniciada, para quem guarda estado PRÓPRIO em sessão (ex.: o OTP do
     * `OTPService`, dono da sua chave). O utilizador autenticado tem os acessores acima;
     * isto não é uma API genérica de chaves de sessão.
     */
    public static function start(): void {
        self::init();
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

    /** Id do utilizador em sessão, ou `null`. Evita desestruturar o array de `user()`. */
    public static function userId(): ?int {
        return self::isLoggedIn() ? (int)$_SESSION["user_id"] : null;
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

    /**
     * Guard de PÁGINA (HTML): exige sessão e um dos perfis, redireccionando se falhar.
     * Simétrico ao requireProfileApi(), que serve as APIs (401/403 em JSON).
     * Substitui o bloco `requireLogin() + if (!isX()) { header(...); exit; }` que estava
     * repetido em cada página do backoffice.
     */
    public static function requireProfile(array $profiles, ?string $redirectUrl = null): void {
        self::requireLogin($redirectUrl);

        if (!in_array(self::getUserProfile(), $profiles, true)) {
            $url = $redirectUrl ?? (defined("BASE_URL") ? BASE_URL . "/" : "/");
            header("Location: {$url}");
            exit;
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
