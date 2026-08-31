<?php

require_once __DIR__ . '/ValidationException.php';

class Validator {
    private array $errors = [];
    private array $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    private function getValue(string $field): string {
        return (string)($this->data[$field] ?? '');
    }

    public function hasError(?string $field = null): bool {
        return $field !== null 
            ? isset($this->errors[$field]) 
            : !empty($this->errors);
    }

    public function required(string $field, string $message="Este campo é obrigatório."): self {
        if (!$this->hasError($field) && empty(trim($this->getValue($field)))) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function regex(string $field, string $pattern, string $message="Formato inválido."): self {
        if (!$this->hasError($field) && !preg_match($pattern, $this->getValue($field))) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function match(string $valField, string $expField, string $message="Os valores não coincidem."): self {
        if (!$this->hasError($valField) && $this->getValue($valField) !== $this->getValue($expField)) {
            $this->errors[$valField] = $message;
        }
        return $this;
    }

    public function contains(string $field, array $allowedValues, string $message="Valor inválido."): self {
        if (!$this->hasError($field) && !in_array($this->getValue($field), $allowedValues, true)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function email(string $field, string $message="Endereço de email inválido."): self {
        if (!$this->hasError($field) && !filter_var($this->getValue($field), FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function password(string $field, string $message="A password deve ter pelo menos 8 caracteres, conter pelo menos 1 letra, 1 número e 1 símbolo válido."): self {
        if ($this->hasError($field)) return $this;
        
        $value = $this->getValue($field);

        $isLongEnough = strlen($value) >= 8;
        $hasLetter    = preg_match('/[a-zA-Z]/', $value);
        $hasDigit     = preg_match('/[0-9]/', $value);
        $hasSymbol    = preg_match('/[!@#\$%\^&\*\(\)_\+\-\=\[\]\{\}\|;:,.<>\?]/', $value);

        if (!$isLongEnough || !$hasLetter || !$hasDigit || !$hasSymbol) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function phonePT(string $field, string $message="Número de telemóvel/telefone inválido."): self {
        if ($this->hasError($field)) return $this;
        
        $cleaned = preg_replace('/\s+/', '', $this->getValue($field));
        $pattern = '/^(?:(?:\+|00)?351)?(2\d{8}|9[1236]\d{7})$/';
        
        if (!preg_match($pattern, $cleaned)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function nif(string $field, string $message="Número de Contribuinte (NIF) inválido."): self {
        if ($this->hasError($field)) return $this;
        
        $nif = preg_replace('/\s+/', '', $this->getValue($field));
        
        if (!preg_match('/^[12356789]\d{8}$/', $nif)) {
            $this->errors[$field] = $message;
            return $this;
        }

        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += intval($nif[$i]) * (9 - $i);
        }

        $calc = 11 - ($sum % 11);
        if ($calc >= 10) {
            $calc = 0;
        }

        if ($calc !== intval($nif[8])) {
            $this->errors[$field] = $message;
        }

        return $this;
    }

    public function cc(string $field, string $message="Número de Cartão de Cidadão inválido."): self {
        if ($this->hasError($field)) return $this;
        
        $cleaned = strtoupper(preg_replace('/\s+/', '', $this->getValue($field)));
        $pattern = '/^\d{8}[0-9A-Z]{2}\d[0-9A-Z]$/';
        
        if (!preg_match($pattern, $cleaned)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function zipCode(string $field, string $message="Código postal inválido (formato 0000-000)."): self {
        if ($this->hasError($field)) return $this;
        
        $value = trim($this->getValue($field));
        $pattern = '/^\d{4}-\d{3}$/';
        
        if (!preg_match($pattern, $value)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function accepted(string $field, string $message="Deve aceitar este campo para continuar."): self {
        if ($this->hasError($field)) return $this;

        $value = $this->data[$field] ?? false;
        $isAccepted = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        if (!$isAccepted) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function custom(string $field, bool|callable $condition, string $message): self {
        if ($this->hasError($field)) return $this;

        $failed = is_callable($condition)
            ? !empty(trim($this->getValue($field))) && $condition($this->data[$field] ?? null)
            : !$condition;

        if ($failed) {
            $this->errors[$field] = $message;
        }

        return $this;
    }

    public function throwIfFails(): void {
        if ($this->hasError()) {
            throw new ValidationException($this->errors);
        }
    }
}
