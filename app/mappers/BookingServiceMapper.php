<?php

require_once __DIR__ . "/BaseMapper.php";

class BookingServiceMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",                                "id",                   "int")
                    ->cast("agendamento_id",                    "bookingId",            "int")
                    ->cast("agendamento_pessoa_id",             "personId",             "int")
                    ->cast("servico_id",                        "serviceId",            "int")
                    ->cast("funcionario_id",                    "employeeId",           "int")
                    ->cast("preco_praticado",                   "price",                "float")
                    ->cast("duracao_minutos",                   "durationMinutes",      "int")
                    ->cast("estado_aceitacao",                  "acceptanceStatus",     "string")
                    ->cast("aceito_em",                         "acceptedAt",           "string")
                    ->cast("percentagem_funcionario_aplicada",  "employeePercentage",   "float")
                    ->cast("valor_recibo_verde_funcionario",    "greenReceiptEmployee", "float")
                    ->cast("valor_recibo_verde_plataforma",     "greenReceiptPlatform", "float")

                    ->cast("servico_nome",                      "serviceName",          "string")
                    ->cast("duracao_estimada_minutos",          "estimatedDuration",    "int")
                    ->cast("categoria_id",                      "categoryId",           "int")
                    ->cast("categoria_nome",                    "categoryName",         "string")
                    ->cast("nome_pessoa",                       "personName",           "string")
                    ->cast("observacoes",                       "personNotes",          "string")
                    ->cast("funcionario_nome",                  "employeeName",         "string")
                    ->cast("data_hora_pretendida",              "dateTime",             "string")
                    ->cast("local_prestacao",                   "local",                "string")
                    ->cast("estado_reserva",                    "bookingStatus",        "string")
                    ->cast("cliente_id",                        "customerId",           "int")
                    ->toArray();
    }
}
