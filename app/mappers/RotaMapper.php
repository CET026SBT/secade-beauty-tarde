<?php

require_once __DIR__ . "/BaseMapper.php";

class RotaMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",                             "id",                    "int")
                    ->cast("data_rota",                      "routeDate",             "string")
                    ->cast("base_partida_id",                "baseId",                "int")
                    ->cast("cidade_id",                      "cityId",                "int")
                    ->cast("estado_rota",                    "status",                "string")
                    ->cast("custo_estimado_combustivel",     "fuelCost",              "float")
                    ->cast("quota_parte_cliente",            "customerShare",         "float")
                    ->cast("lucro_servicos",                 "servicesProfit",        "float")
                    ->cast("lucro_total",                    "totalProfit",           "float")
                    ->cast("decidido_por",                   "decidedBy",             "int")
                    ->cast("decidido_em",                    "decidedAt",             "string")
                    ->cast("observacoes_decisao",            "decisionNotes",         "string")

                    ->cast("cidade_nome",                    "cityName",              "string")
                    ->cast("distrito",                       "district",              "string")
                    ->cast("base_nome",                      "baseName",              "string")
                    ->toArray();
    }
}