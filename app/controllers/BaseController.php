<?php

/**
 * Base dos controllers de API.
 *
 * Guarda o que é comum ao TRANSPORTE do pedido — e só isso. Não acolhe guardas de perfil:
 * existiu aqui um `requireCustomer()`/`requireProfile()` que nada tinha que ver com este
 * ficheiro (o `BaseController` não sabe de perfis). Quem autoriza chama o `Session`
 * directamente — ver `.clinerules` §2.
 */
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

    }
