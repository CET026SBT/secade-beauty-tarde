<?php

require_once __DIR__ . "/BaseMapper.php";

class CustomerAddressMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",                "id",               "int")
                    ->cast("cliente_id",        "customerId",       "int")
                    ->cast("cidade_id",         "cityId",           "int")
                    ->cast("designacao",        "designation",      "string")
                    ->cast("rua",               "street",           "string")
                    ->cast("numero_porta",      "doorNumber",       "string")
                    ->cast("andar_bloco",       "floor",            "string")
                    ->cast("codigo_postal",     "zipCode",          "string")
                    ->cast("principal",         "isMain",           "bool")
                    
                    ->cast("cidade_nome",       "cityName",         "string")
                    ->cast("distrito",          "district",         "string")
                    ->toArray();
    }
}
