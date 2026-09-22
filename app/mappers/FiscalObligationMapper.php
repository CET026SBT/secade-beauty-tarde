<?php

require_once __DIR__ . "/BaseMapper.php";

class FiscalObligationMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",              "id",             "int")
                    ->cast("tipo",            "type",           "string")
                    ->cast("designacao",      "name",           "string")
                    ->cast("periodicidade",   "periodicity",    "string")
                    ->cast("valor_estimado",  "estimatedValue", "float")
                    ->cast("data_prazo",      "dueDate",        "string")
                    ->cast("estado",          "status",         "string")
                    ->cast("data_pagamento",  "paidAt",         "string")
                    ->cast("observacoes",     "notes",          "string")
                    ->cast("criado_em",       "createdAt",      "string")
                    ->toArray();
    }
}