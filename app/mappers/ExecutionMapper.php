<?php

require_once __DIR__ . "/BaseMapper.php";

class ExecutionMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",                   "id",              "int")
                    ->cast("agendamento_id",       "bookingId",       "int")
                    ->cast("rota_id",              "routeId",         "int")
                    ->cast("data_hora_inicio_real","startedAt",       "string")
                    ->cast("data_hora_fim_real",   "finishedAt",      "string")
                    ->cast("estado_execucao",      "executionStatus", "string")
                    ->cast("observacoes_tecnico",  "technicianNotes", "string")
                    ->toArray();
    }
}