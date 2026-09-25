<?php

abstract class BaseController {
    
    protected function getRequestData(): array {
        $inputData = $_POST;
        
        if (empty($inputData)) {
            $input = file_get_contents("php://input");
            if (!empty($input)) {
                $inputData = json_decode($input, true) ?? [];
            }
        }

        return is_array($inputData) ? $inputData : [];
    }

    /**
     * Guard de API: exige um dos perfis e devolve o id do utilizador em sessão.
     * Via ÚNICA para APIs autenticadas (lança 401/403 via Session::requireProfileApi).
     * Substitui os guardas por perfil que existiam a duplicar este corpo
     * (`requireCustomer()` aqui e um `requireEmployee()` local no ServiceController).
     */
    protected function requireProfile(array $profiles): int {
        Session::requireProfileApi($profiles);
        return (int)Session::user()["id"];
    }

    protected function requireCustomer(): int {
        return $this->requireProfile(["cliente"]);
    }
}
