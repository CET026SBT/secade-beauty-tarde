-- --------------------------------------------------------
-- MIGRAÇÃO INCREMENTAL secade_beauty: v1 -> v2 (planeamento v3.0)
-- Aplica sobre a BD existente SEM destruir dados.
-- Utiliza ALTER/CREATE para alinhar com DataBase_v2.sql.
-- --------------------------------------------------------
USE `secade_beauty`;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. agendamento: nova máquina de estados + validação logística
-- --------------------------------------------------------
ALTER TABLE `agendamento`
    MODIFY COLUMN `estado_reserva`
        enum('pendente_aceitacao_funcionarios','pendente_validacao_logistica_loja','totalmente_aceite_funcionarios','confirmado','recusado','cancelado','executado','concluido')
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pendente_aceitacao_funcionarios',
    ADD COLUMN `validado_logistica_loja` tinyint(1) DEFAULT '0' AFTER `valor_sinal`;

-- Migrar registos do estado antigo
UPDATE `agendamento` SET `estado_reserva` = 'pendente_aceitacao_funcionarios'
    WHERE `estado_reserva` IN ('pendente_aprovacao_viabilidade');
UPDATE `agendamento` SET `estado_reserva` = 'confirmado' WHERE `estado_reserva` = 'confirmado';

-- --------------------------------------------------------
-- 2. agendamento_pessoa (NOVA)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agendamento_pessoa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agendamento_id` int NOT NULL,
  `nome_pessoa` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_agend_pessoa_agendamento` (`agendamento_id`),
  CONSTRAINT `fk_agend_pessoa_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. agendamento_servico: pessoa + aceitação + recibos verdes
-- --------------------------------------------------------
ALTER TABLE `agendamento_servico`
    ADD COLUMN `agendamento_pessoa_id` int DEFAULT NULL AFTER `agendamento_id`,
    ADD COLUMN `estado_aceitacao` enum('pendente','aceite') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'aceite' AFTER `duracao_minutos`,
    ADD COLUMN `aceito_em` datetime DEFAULT NULL AFTER `estado_aceitacao`,
    ADD COLUMN `percentagem_funcionario_aplicada` decimal(5,2) DEFAULT NULL AFTER `aceito_em`,
    ADD COLUMN `valor_recibo_verde_funcionario` decimal(10,2) DEFAULT NULL AFTER `percentagem_funcionario_aplicada`,
    ADD COLUMN `valor_recibo_verde_plataforma` decimal(10,2) DEFAULT NULL AFTER `valor_recibo_verde_funcionario`,
    ADD KEY `fk_agend_serv_pessoa` (`agendamento_pessoa_id`),
    ADD CONSTRAINT `fk_agend_serv_pessoa` FOREIGN KEY (`agendamento_pessoa_id`) REFERENCES `agendamento_pessoa` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------
-- 4. config_recibo_verde (NOVA)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `config_recibo_verde` (
  `id` int NOT NULL AUTO_INCREMENT,
  `percentagem_funcionario` decimal(5,2) NOT NULL DEFAULT '70.00',
  `percentagem_plataforma` decimal(5,2) NOT NULL DEFAULT '30.00',
  `data_vigencia` date NOT NULL,
  `configurado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_config_rv_utilizador` (`configurado_por`),
  CONSTRAINT `ck_config_rv_soma` CHECK (`percentagem_funcionario` + `percentagem_plataforma` = 100),
  CONSTRAINT `fk_config_rv_utilizador` FOREIGN KEY (`configurado_por`) REFERENCES `utilizador` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `config_recibo_verde` (`percentagem_funcionario`, `percentagem_plataforma`, `data_vigencia`)
    SELECT 70.00, 30.00, '2026-01-01' FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM `config_recibo_verde`);

-- --------------------------------------------------------
-- 5. rota_ambulante: estados manuais + financeiro + auditoria
-- --------------------------------------------------------
ALTER TABLE `rota_ambulante`
    MODIFY COLUMN `estado_rota`
        enum('planeada','aprovada','recusada','em_execucao','concluida')
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'planeada',
    ADD COLUMN `quota_parte_cliente` decimal(10,2) DEFAULT '0.00' AFTER `custo_estimado_combustivel`,
    ADD COLUMN `lucro_servicos` decimal(10,2) DEFAULT '0.00' AFTER `quota_parte_cliente`,
    ADD COLUMN `lucro_total` decimal(10,2) DEFAULT '0.00' AFTER `lucro_servicos`,
    ADD COLUMN `decidido_por` int DEFAULT NULL AFTER `lucro_total`,
    ADD COLUMN `decidido_em` datetime DEFAULT NULL AFTER `decidido_por`,
    ADD COLUMN `observacoes_decisao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci AFTER `decidido_em`,
    ADD KEY `fk_rota_decidido_por` (`decidido_por`),
    ADD CONSTRAINT `fk_rota_decidido_por` FOREIGN KEY (`decidido_por`) REFERENCES `utilizador` (`id`) ON DELETE SET NULL;

-- Mapear estados antigos
UPDATE `rota_ambulante` SET `estado_rota` = 'aprovada' WHERE `estado_rota` = 'aprovada_viabilidade';
UPDATE `rota_ambulante` SET `estado_rota` = 'recusada' WHERE `estado_rota` = 'cancelada_por_rentabilidade';

-- --------------------------------------------------------
-- 6. transacao_financeira: novo tipo quota_parte_deslocacao
-- --------------------------------------------------------
ALTER TABLE `transacao_financeira`
    MODIFY COLUMN `tipo_transacao`
        enum('sinal_inicial','restante_90_porcento','pagamento_integral','quota_parte_deslocacao')
        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;

-- --------------------------------------------------------
-- 7. obrigacao_fiscal (NOVA)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `obrigacao_fiscal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` enum('iva','irc','seguranca_social','seguros') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `designacao` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `periodicidade` enum('mensal','trimestral','anual') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_estimado` decimal(12,2) DEFAULT '0.00',
  `data_prazo` date NOT NULL,
  `estado` enum('pendente','pago') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `data_pagamento` date DEFAULT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_obrigacao_prazo` (`data_prazo`),
  KEY `idx_obrigacao_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. alerta_fiscal (NOVA)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `alerta_fiscal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `obrigacao_fiscal_id` int NOT NULL,
  `tipo_alerta` enum('30_dias','15_dias','7_dias','3_dias','1_dia','em_atraso') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_alerta` date NOT NULL,
  `visualizado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_alerta_obrigacao_tipo` (`obrigacao_fiscal_id`,`tipo_alerta`,`data_alerta`),
  CONSTRAINT `fk_alerta_obrigacao` FOREIGN KEY (`obrigacao_fiscal_id`) REFERENCES `obrigacao_fiscal` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
