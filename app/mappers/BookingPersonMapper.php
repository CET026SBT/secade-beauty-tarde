<?php

require_once __DIR__ . "/BaseMapper.php";

/**
 * Traduz a linha de `agendamento_pessoa` (BD, PT) para o contrato do código (EN).
 *
 * Existia um repository (`BookingPersonRepository`) sem mapper — era o único dos 18 —
 * pelo que devolvia a linha crua com chaves em português, ao contrário de todos os outros.
 *
 * Nota de nomenclatura: `observacoes` mapeia para `notes` (e não `personNotes`) porque
 * este é o mapa da ENTIDADE pessoa; `notes` é o nome que o contrato de entrada já usa
 * (`BookingService::createAmbulatoryBooking` lê `$person["notes"]`). O `personNotes` do
 * `BookingServiceMapper` existe porque lá a linha é de um SERVIÇO com dados de pessoa
 * juntos por JOIN e precisa do prefixo para desambiguar.
 */
class BookingPersonMapper extends BaseMapper {

    protected function mapRow(): array {
        return $this->cast("id",             "id",         "int")
                    ->cast("agendamento_id", "bookingId",  "int")
                    ->cast("nome_pessoa",    "personName", "string")
                    ->cast("observacoes",    "notes",      "string")
                    ->toArray();
    }
}