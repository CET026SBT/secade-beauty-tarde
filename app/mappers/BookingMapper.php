<?php

require_once __DIR__ . "/BaseMapper.php";

class BookingMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",                       "id",                 "int")
                    ->cast("cliente_id",               "customerId",         "int")
                    ->cast("cliente_morada_id",        "addressId",          "int")
                    ->cast("local_prestacao",          "local",              "string")
                    ->cast("data_hora_pretendida",     "dateTime",           "string")
                    ->cast("estado_reserva",           "status",             "string")
                    ->cast("valor_total",              "totalAmount",        "float")
                    ->cast("sinal_pago",               "isDepositPaid",      "bool")
                    ->cast("valor_sinal",              "depositAmount",      "float")
                    ->cast("validado_logistica_loja",  "isLogisticsValidated","bool")
                    ->cast("criado_em",                "createdAt",          "string")

                    // Enriquecimento (listagem/detalhe do backoffice: cliente + cidade)
                    ->cast("cliente_nome",             "customerName",       "string")
                    ->cast("cliente_email",            "customerEmail",      "string")
                    ->cast("cliente_telemovel",        "customerPhone",      "string")
                    ->cast("cidade_id",                "cityId",             "int")
                    ->cast("cidade_nome",              "cityName",           "string")
                    ->cast("morada_completa",          "fullAddress",        "string")
                    ->toArray();
    }
}
