<?php

require_once __DIR__ . "/BaseMapper.php";

class GreenReceiptConfigMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",                       "id",                 "int")
                    ->cast("percentagem_funcionario",  "employeePercentage", "float")
                    ->cast("percentagem_plataforma",   "platformPercentage", "float")
                    ->cast("data_vigencia",            "effectiveFrom",      "string")
                    ->cast("configurado_por",          "configuredBy",       "int")
                    ->toArray();
    }
}