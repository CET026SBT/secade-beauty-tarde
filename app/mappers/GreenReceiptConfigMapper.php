<?php

require_once __DIR__ . "/BaseMapper.php";

/**
 * Configurações de percentagens padrão (`config_percentagem_padrao`, ex-`config_recibo_verde`).
 *
 * Cada linha define a **percentagem inicial** de um tipo de contrato, com vigência por
 * data. O formulário de RH pré-preenche a percentagem do funcionário com o valor em vigor
 * do respetivo tipo de contrato (NF-01).
 */
class GreenReceiptConfigMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",                     "id",                   "int")
                    ->cast("tipo_contrato",          "contractType",         "string")
                    ->cast("percentagem_comissao",   "commissionPercentage", "float")
                    ->cast("data_vigencia",          "effectiveFrom",        "string")
                    ->cast("configurado_por",        "configuredBy",         "int")
                    ->toArray();
    }
}