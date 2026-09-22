-- --------------------------------------------------------
-- MIGRAÇÃO INCREMENTAL secade_beauty: v2 -> v3 (MVP Corrections)
-- Aplica sobre a BD existente SEM destruir dados de negócio.
-- A única remoção estrutural é a tabela obsoleta `funcionario_categoria` (passo 3).
-- Idempotente: pode correr várias vezes e em qualquer schema (v1, v2 ou v3).
-- --------------------------------------------------------
USE `secade_beauty`;

-- --------------------------------------------------------
-- 1. servico: coluna 'ativo' (catálogo público usa s.ativo = 1)
--    Nota: presente em ServiceRepository::findActive mas em falta no schema.
--    ALTER condicional (idempotente) — só adiciona se ainda não existir.
-- --------------------------------------------------------
SET @servico_ativo_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'servico' AND COLUMN_NAME = 'ativo'
);

SET @sql_add_ativo = IF(@servico_ativo_exists = 0,
    'ALTER TABLE `servico` ADD COLUMN `ativo` tinyint(1) NOT NULL DEFAULT ''1'' AFTER `requer_espaco_fisico`',
    'SELECT ''servico.ativo já existe'' AS `info`');

PREPARE stmt_add_ativo FROM @sql_add_ativo;
EXECUTE stmt_add_ativo;
DEALLOCATE PREPARE stmt_add_ativo;

-- --------------------------------------------------------
-- 2. cliente: 'morada' NOT NULL (legado v1) impedia o registo de clientes.
--    A morada passa a residir exclusivamente em cliente_morada (planeamento v3.0).
--    ALTER condicional (idempotente): numa BD v2+ a coluna já não existe
--    (DataBase_v2.sql removeu-a), pelo que este passo é ignorado sem erro.
-- --------------------------------------------------------
SET @cliente_morada_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cliente' AND COLUMN_NAME = 'morada'
);

SET @sql_morada_nullable = IF(@cliente_morada_exists = 1,
    'ALTER TABLE `cliente` MODIFY COLUMN `morada` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL',
    'SELECT ''cliente.morada ja removida (schema v2+)'' AS `info`');

PREPARE stmt_morada FROM @sql_morada_nullable;
EXECUTE stmt_morada;
DEALLOCATE PREPARE stmt_morada;

-- --------------------------------------------------------
-- 3. funcionario_categoria (REMOVIDA)
--    A relação N:N entre funcionários e categorias não tem consumidor:
--      - as categorias são apenas filtros/agrupadores visuais (especificacao_mvp.md §3.2);
--      - o filtro do backoffice usa `categoria_profissional` completa;
--      - a filtragem dos serviços pendentes usa `servico.categoria_id`;
--      - nenhum repositório/serviço LÊ esta tabela (só havia escrita órfã, sem UI).
--    DROP IF EXISTS é idempotente e não afeta nenhuma FK (nada lhe aponta).
-- --------------------------------------------------------
DROP TABLE IF EXISTS `funcionario_categoria`;