<?php

abstract class BaseController {
    
    protected function getRequestData(): array {
        $inputData = $_POST;
        
        if (empty($inputData)) {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $inputData = json_decode($input, true) ?? [];
            }
        }

        return is_array($inputData) ? $inputData : [];
    }
}
