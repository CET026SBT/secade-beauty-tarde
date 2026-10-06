<?php

require_once __DIR__ . "/BaseMapper.php";

class UserMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",            "id",           "int")
                    ->cast("nome",          "name",         "string")
                    ->cast("email",         "email",        "string")
                    ->cast("password_hash", "passwordHash", "string")
                    ->cast("telemovel",     "phone",        "string")
                    ->cast("nif",           "nif",          "string")
                    ->cast("tipo_perfil",   "profileType",  "string")
                    ->cast("criado_em",     "createdAt",    "string")
                    ->toArray();
    }
}
