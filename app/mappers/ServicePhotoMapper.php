<?php

require_once __DIR__ . "/BaseMapper.php";

class ServicePhotoMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",             "id",           "int")
                    ->cast("servico_id",     "serviceId",    "int")
                    ->cast("url_foto",       "url",          "string")
                    ->cast("destaque",       "featured",     "bool")
                    ->cast("ordem_exibicao", "displayOrder", "int")
                    ->toArray();
    }
}
