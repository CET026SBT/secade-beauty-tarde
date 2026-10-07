<?php

require_once __DIR__ . "/BaseMapper.php";

class EmployeeMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",             "id",          "int")
                    ->cast("tipo_contrato",  "contractType","string")
                    ->cast("salario_base",   "salary",      "float")
                    ->cast("percentagem_comissao", "commissionPercentage", "float")
                    ->cast("irs_taxa",       "irsRate",     "float")
                    ->cast("cc",             "cc",          "string")
                    ->cast("ativo",          "isActive",    "bool")

                    ->cast("nome",           "name",        "string")
                    ->cast("email",          "email",       "string")
                    ->cast("telemovel",      "phone",       "string")
                    ->cast("nif",            "nif",         "string")
                    ->cast("foto",           "photo",       "string")
                    ->toArray();
    }
}
