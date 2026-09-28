<?php

require_once __DIR__ . "/BaseMapper.php";

class SupplierMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",           "id",        "int")
                    ->cast("nome",         "name",      "string")
                    ->cast("nif",          "nif",       "string")
                    ->cast("email",        "email",     "string")
                    ->cast("telemovel",    "phone",     "string")
                    ->cast("ativo",        "active",    "bool")
                    ->cast("observacoes",  "notes",     "string")
                    ->cast("criado_em",    "createdAt", "string")
                    ->toArray();
    }
}