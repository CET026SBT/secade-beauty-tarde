<?php

require_once __DIR__ . "/BaseMapper.php";

class ServiceMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",                       "id",                       "int")
                    ->cast("categoria_id",             "categoryId",               "int")
                    ->cast("nome",                     "name",                     "string")
                    ->cast("descricao",                "description",              "string")
                    ->cast("preco_base",               "basePrice",                "float")
                    ->cast("duracao_estimada_minutos", "estimatedDurationMinutes", "int")
                    ->cast("requer_espaco_fisico",     "requiresPhysicalSpace",    "bool")
                    
                    ->cast("categoria_nome",           "categoryName",             "string")
                    ->toArray();
    }
}
