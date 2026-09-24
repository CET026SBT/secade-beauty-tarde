<?php

abstract class BaseMapper {
    protected array $row;
    protected array $mapped = [];

    public function __construct(array $row) {
        $this->row = $row;
    }

    public static function map(mixed $data): mixed {
        if (empty($data)) {
            return is_array($data) && array_is_list($data) ? [] : null;
        }

        if (array_is_list($data) && is_array($data[0])) {
            return array_map(fn($row) => (new static($row))->mapRow(), $data);
        }

        return (new static($data))->mapRow();
    }

    protected function cast(string $fromKey, string $toKey, string $type): self {
        if (!array_key_exists($fromKey, $this->row) || $this->row[$fromKey] === null) {
            return $this;
        }

        $value = $this->row[$fromKey];

        $this->mapped[$toKey] = match ($type) {
            'int'    => (int) $value,
            'float'  => (float) $value,
            'bool'   => (bool) $value,
            'string' => (string) $value,
            default  => $value
        };

        return $this;
    }

    protected function toArray(): array {
        return $this->mapped;
    }

    abstract protected function mapRow(): array;
}
