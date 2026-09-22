<?php

require_once __DIR__ . "/BaseMapper.php";

class CustomerMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",                        "id",                   "int")
                    ->cast("telemovel_validado_otp",    "isMobileValidated",    "bool")
                    ->toArray();
    }
}
