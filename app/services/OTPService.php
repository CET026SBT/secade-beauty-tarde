<?php

require_once __DIR__ . "/BaseService.php";
require_once APP_PATH . "/utils/Session.php";

/**
 * OTP simulada (restrição académica): o código é gerado e mostrado no ecrã,
 * validado contra a sessão. Sem envio real de SMS.
 */
class OTPService extends BaseService {

    private const OTP_SESSION_KEY = "otp_ambulatorio";
    private const OTP_EXPIRY_SECONDS = 600;

    public function request(int $customerId): array {
        Session::requireLoginApi();

        $code = str_pad((string)random_int(0, 999999), 6, "0", STR_PAD_LEFT);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION[self::OTP_SESSION_KEY] = [
            "customerId" => $customerId,
            "code"       => $code,
            "expiresAt"  => time() + self::OTP_EXPIRY_SECONDS
        ];

        // Simulação: em produção seria enviado por SMS. Mostrado no ecrã conforme planeamento.
        return [
            "message" => "Código OTP gerado (simulação SMS).",
            "otpCode" => $code
        ];
    }

    /**
     * Valida o código OTP contra o valor guardado na sessão.
     * (Nome 'verify' para não colidir com BaseService::validate().)
     */
    public function verify(int $customerId, string $code): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $otp = $_SESSION[self::OTP_SESSION_KEY] ?? null;

        if (!$otp || $otp["customerId"] !== $customerId) {
            return false;
        }

        if (time() > $otp["expiresAt"]) {
            unset($_SESSION[self::OTP_SESSION_KEY]);
            return false;
        }

        if (!hash_equals($otp["code"], (string)$code)) {
            return false;
        }

        unset($_SESSION[self::OTP_SESSION_KEY]);
        return true;
    }
}
