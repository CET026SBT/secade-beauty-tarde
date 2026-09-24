<?php

require_once __DIR__ . "/BaseMapper.php";

class FiscalAlertMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",                  "id",           "int")
                    ->cast("obrigacao_fiscal_id", "obligationId", "int")
                    ->cast("tipo_alerta",         "alertType",    "string")
                    ->cast("data_alerta",         "alertDate",    "string")
                    ->cast("visualizado",         "isRead",       "bool")
                    ->toArray();
    }
}