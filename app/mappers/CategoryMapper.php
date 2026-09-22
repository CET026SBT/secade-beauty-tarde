<?php

require_once __DIR__ . "/BaseMapper.php";

class CategoryMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",        "id",           "int")
                    ->cast("nome",      "name",         "string")
                    ->cast("descricao", "description",  "string")
                    ->toArray();
    }
}
