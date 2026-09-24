<?php

require_once __DIR__ . "/BaseMapper.php";

class FeedbackMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",                          "id",             "int")
                    ->cast("execucao_agendamento_id",     "executionId",    "int")
                    ->cast("classificacao_estrelas",      "rating",         "int")
                    ->cast("comentario",                  "comment",        "string")
                    ->cast("data_feedback",               "createdAt",      "string")

                    // Enriquecimento (listagens)
                    ->cast("agendamento_id",              "bookingId",      "int")
                    ->cast("cliente_nome",                "customerName",   "string")
                    ->cast("local_prestacao",             "local",          "string")
                    ->cast("data_hora_pretendida",        "dateTime",       "string")
                    ->toArray();
    }
}