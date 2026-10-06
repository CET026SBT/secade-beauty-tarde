<?php

require_once __DIR__ . "/BaseMapper.php";

/**
 * Foto de serviço (`servico_foto`) — §3.4.4 / F3.1.
 *
 * `destaque` = a imagem do card; `ordem_exibicao` ordena o carousel dos detalhes.
 */
class ServicePhotoMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",            "id",           "int")
                    ->cast("servico_id",    "serviceId",    "int")
                    ->cast("url_foto",      "url",          "string")
                    ->cast("destaque",      "featured",     "bool")
                    ->cast("ordem_exibicao","displayOrder", "int")
                    ->toArray();
    }
}