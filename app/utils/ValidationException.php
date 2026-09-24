<?php

class ValidationException extends Exception {
    protected array $errors;

    public function __construct(array $errors, string $message="Erro de validação.", int $code=422) {
        $this->errors = $errors;
        parent::__construct($message, $code);
    }

    public function getErrors(): array {
        return $this->errors;
    }
}
