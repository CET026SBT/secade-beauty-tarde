-- ============================================================
-- SECADE BEAUTY — Esquema completo da base de dados
-- Base: `secade_beauty` · MySQL 8.4 · InnoDB · utf8mb4 (unicode_ci)
-- ------------------------------------------------------------
-- Versao limpa (DataBase_clean.sql): sem comentarios de ferramenta,
-- sem AUTO_INCREMENT nem charset repetido por coluna, e sem os indices
-- que ja sao criados automaticamente pelas chaves estrangeiras.
-- ============================================================

DROP DATABASE IF EXISTS `secade_beauty`;
CREATE DATABASE `secade_beauty` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `secade_beauty`;

-- Os valores TIMESTAMP foram escritos em UTC (independente do fuso de quem importa).
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Tabela: agendamento
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `agendamento`;
CREATE TABLE `agendamento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `cliente_morada_id` int DEFAULT NULL,
  `local_prestacao` enum('loja_fisica','carrinha_ambulante') NOT NULL,
  `data_hora_pretendida` datetime NOT NULL,
  `estado_reserva` enum('pendente_alocacao','pendente_validacao_logistica_loja','totalmente_alocado','confirmado','recusado','cancelado','executado','concluido') DEFAULT 'pendente_alocacao',
  `modo_urgencia` tinyint(1) DEFAULT '0',
  `valor_total` decimal(10,2) NOT NULL,
  `sinal_pago` tinyint(1) DEFAULT '0',
  `valor_sinal` decimal(10,2) DEFAULT '0.00',
  `validado_logistica_loja` tinyint(1) DEFAULT '0',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_agendamento_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_agendamento_morada` FOREIGN KEY (`cliente_morada_id`) REFERENCES `cliente_morada` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `agendamento` (`id`, `cliente_id`, `cliente_morada_id`, `local_prestacao`, `data_hora_pretendida`, `estado_reserva`, `modo_urgencia`, `valor_total`, `sinal_pago`, `valor_sinal`, `validado_logistica_loja`, `criado_em`) VALUES
  (190, 53, 75, 'carrinha_ambulante', '2026-09-24 09:00:00', 'totalmente_alocado', 0, 32.52, 0, 0.00, 0, '2026-09-22 13:37:26'),
  (191, 53, NULL, 'loja_fisica', '2026-09-23 09:00:00', 'pendente_validacao_logistica_loja', 0, 28.46, 0, 2.85, 0, '2026-09-22 13:40:10'),
  (210, 3, NULL, 'loja_fisica', '2026-09-23 10:00:00', 'pendente_validacao_logistica_loja', 0, 16.27, 0, 1.63, 0, '2026-09-22 14:33:27'),
  (211, 3, 1, 'carrinha_ambulante', '2026-09-23 09:00:00', 'pendente_alocacao', 0, 16.27, 0, 0.00, 0, '2026-09-22 14:33:27'),
  (212, 3, NULL, 'carrinha_ambulante', '2026-09-29 10:00:00', 'confirmado', 0, 4.07, 0, 0.00, 0, '2026-09-22 14:33:27'),
  (213, 3, 98, 'carrinha_ambulante', '2026-09-29 09:00:00', 'cancelado', 0, 199.20, 0, 0.00, 0, '2026-09-22 14:33:27'),
  (214, 3, NULL, 'loja_fisica', '2026-09-23 16:00:00', 'cancelado', 0, 12.20, 0, 1.22, 0, '2026-09-22 14:33:27'),
  (215, 3, 99, 'carrinha_ambulante', '2026-10-01 09:00:00', 'executado', 0, 12.20, 0, 0.00, 0, '2026-09-22 14:33:27'),
  (216, 3, NULL, 'loja_fisica', '2026-09-23 17:00:00', 'pendente_validacao_logistica_loja', 0, 40.65, 0, 4.07, 0, '2026-09-22 14:33:27'),
  (217, 3, 99, 'carrinha_ambulante', '2026-10-06 10:00:00', 'pendente_alocacao', 0, 12.20, 0, 0.00, 0, '2026-09-22 14:33:27');

-- ------------------------------------------------------------
-- Tabela: agendamento_pessoa
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `agendamento_pessoa`;
CREATE TABLE `agendamento_pessoa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agendamento_id` int NOT NULL,
  `nome_pessoa` varchar(150) NOT NULL,
  `observacoes` text,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_agend_pessoa_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `agendamento_pessoa` (`id`, `agendamento_id`, `nome_pessoa`, `observacoes`) VALUES
  (148, 190, 'Daniel Branco', NULL),
  (164, 211, 'João Cliente', NULL),
  (165, 211, 'Maria Familiar', NULL),
  (166, 212, 'João Cliente', NULL),
  (167, 213, 'João Cliente', NULL),
  (168, 215, 'João Cliente', NULL),
  (169, 215, 'Maria Familiar', NULL),
  (170, 217, 'João Cliente', NULL);

-- ------------------------------------------------------------
-- Tabela: agendamento_servico
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `agendamento_servico`;
CREATE TABLE `agendamento_servico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agendamento_id` int NOT NULL,
  `agendamento_pessoa_id` int DEFAULT NULL,
  `servico_id` int NOT NULL,
  `funcionario_id` int DEFAULT NULL,
  `preco_praticado` decimal(10,2) NOT NULL,
  `duracao_minutos` int NOT NULL,
  `estado_aceitacao` enum('pendente','aceite') DEFAULT 'aceite',
  `aceito_em` datetime DEFAULT NULL,
  `percentagem_funcionario_aplicada` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_agend_serv_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agend_serv_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_agend_serv_pessoa` FOREIGN KEY (`agendamento_pessoa_id`) REFERENCES `agendamento_pessoa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agend_serv_servico` FOREIGN KEY (`servico_id`) REFERENCES `servico` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `agendamento_servico` (`id`, `agendamento_id`, `agendamento_pessoa_id`, `servico_id`, `funcionario_id`, `preco_praticado`, `duracao_minutos`, `estado_aceitacao`, `aceito_em`, `percentagem_funcionario_aplicada`) VALUES
  (394, 190, 148, 1, 2, 32.52, 240, 'aceite', '2026-09-22 15:06:33', 70.00),
  (395, 191, NULL, 4, NULL, 28.46, 150, 'aceite', NULL, NULL),
  (435, 210, NULL, 28, NULL, 12.20, 30, 'aceite', NULL, NULL),
  (436, 210, NULL, 29, NULL, 4.07, 20, 'aceite', NULL, NULL),
  (437, 211, 164, 29, NULL, 4.07, 20, 'pendente', NULL, NULL),
  (438, 211, 165, 29, NULL, 4.07, 20, 'pendente', NULL, NULL),
  (439, 211, 165, 35, NULL, 8.13, 30, 'pendente', NULL, NULL),
  (440, 212, 166, 29, NULL, 4.07, 20, 'pendente', NULL, NULL),
  (441, 213, 167, 20, NULL, 36.59, 60, 'pendente', NULL, NULL),
  (442, 213, 167, 18, NULL, 24.39, 60, 'pendente', NULL, NULL),
  (443, 213, 167, 23, NULL, 28.46, 60, 'pendente', NULL, NULL),
  (444, 213, 167, 21, NULL, 16.26, 60, 'pendente', NULL, NULL),
  (445, 213, 167, 9, NULL, 24.39, 60, 'pendente', NULL, NULL),
  (446, 213, 167, 2, NULL, 48.78, 180, 'pendente', NULL, NULL),
  (447, 213, 167, 27, NULL, 20.33, 60, 'pendente', NULL, NULL),
  (448, 214, NULL, 33, NULL, 12.20, 45, 'aceite', NULL, NULL),
  (449, 215, 168, 29, 2, 4.07, 20, 'aceite', '2026-09-22 15:33:27', 70.00),
  (450, 215, 169, 35, 2, 8.13, 30, 'aceite', '2026-09-22 15:33:27', 70.00),
  (451, 216, NULL, 30, NULL, 40.65, 120, 'aceite', NULL, NULL),
  (452, 217, 170, 33, NULL, 12.20, 45, 'pendente', NULL, NULL);

-- ------------------------------------------------------------
-- Tabela: alerta_fiscal
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `alerta_fiscal`;
CREATE TABLE `alerta_fiscal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `obrigacao_fiscal_id` int NOT NULL,
  `tipo_alerta` enum('30_dias','15_dias','7_dias','3_dias','1_dia','em_atraso') NOT NULL,
  `data_alerta` date NOT NULL,
  `visualizado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_alerta_obrigacao_tipo` (`obrigacao_fiscal_id`,`tipo_alerta`,`data_alerta`),
  CONSTRAINT `fk_alerta_obrigacao` FOREIGN KEY (`obrigacao_fiscal_id`) REFERENCES `obrigacao_fiscal` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `alerta_fiscal` (`id`, `obrigacao_fiscal_id`, `tipo_alerta`, `data_alerta`, `visualizado`) VALUES
  (420, 84, '7_dias', '2026-09-22', 1),
  (421, 85, 'em_atraso', '2026-09-22', 1),
  (425, 86, '30_dias', '2026-09-22', 1);

-- ------------------------------------------------------------
-- Tabela: base_partida
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `base_partida`;
CREATE TABLE `base_partida` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `morada` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `base_partida` (`id`, `nome`, `morada`) VALUES
  (1, 'Évora', 'Rua do Centro de Formação');

-- ------------------------------------------------------------
-- Tabela: categoria_servico
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `categoria_servico`;
CREATE TABLE `categoria_servico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categoria_servico` (`id`, `nome`, `descricao`) VALUES
  (1, 'Cabeleireiro', 'Tranças e Penteados'),
  (2, 'Barbearia', 'Cortes'),
  (3, 'Estética', 'Maquiagem, Manicure e limpeza facial');

-- ------------------------------------------------------------
-- Tabela: cidade
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `cidade`;
CREATE TABLE `cidade` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `distrito` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cidade` (`id`, `nome`, `distrito`) VALUES
  (1, 'Arraiolos', 'Évora'),
  (2, 'Montemor-o-Novo', 'Évora'),
  (3, 'Viana do Alentejo', 'Évora'),
  (4, 'Reguengos de Monsaraz', 'Évora'),
  (5, 'Redondo', 'Évora'),
  (6, 'Vendas Novas', 'Évora'),
  (7, 'Estremoz', 'Évora'),
  (8, 'Vila Viçosa', 'Évora'),
  (9, 'Mourão', 'Évora'),
  (10, 'Évora', 'Évora');

-- ------------------------------------------------------------
-- Tabela: cliente
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `cliente`;
CREATE TABLE `cliente` (
  `id` int NOT NULL,
  `telemovel_validado_otp` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_cliente_utilizador` FOREIGN KEY (`id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cliente` (`id`, `telemovel_validado_otp`) VALUES
  (3, 1),
  (53, 0),
  (100, 0),
  (101, 0),
  (102, 0),
  (103, 0),
  (104, 0),
  (105, 0),
  (106, 0),
  (107, 0),
  (108, 0),
  (109, 0),
  (110, 0),
  (111, 0),
  (112, 0),
  (113, 0),
  (114, 0),
  (115, 0),
  (116, 0),
  (117, 0),
  (118, 0),
  (119, 0),
  (120, 0),
  (121, 0),
  (122, 0),
  (123, 0),
  (124, 0),
  (125, 0),
  (126, 0),
  (127, 0),
  (128, 0),
  (129, 0),
  (130, 0),
  (131, 0),
  (132, 0),
  (133, 0),
  (134, 0),
  (135, 0),
  (136, 0),
  (137, 0),
  (138, 0),
  (139, 0),
  (140, 0),
  (141, 0),
  (142, 0),
  (143, 0),
  (144, 0),
  (145, 0),
  (146, 0),
  (147, 0),
  (148, 0),
  (149, 0),
  (150, 0),
  (151, 0),
  (152, 0),
  (153, 0),
  (154, 0),
  (155, 0),
  (156, 0),
  (157, 0),
  (158, 0),
  (159, 0),
  (160, 0),
  (161, 0),
  (162, 0),
  (163, 0),
  (164, 0);

-- ------------------------------------------------------------
-- Tabela: cliente_morada
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `cliente_morada`;
CREATE TABLE `cliente_morada` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `cidade_id` int NOT NULL,
  `designacao` varchar(100) DEFAULT 'Casa',
  `rua` varchar(255) NOT NULL,
  `numero_porta` varchar(50) DEFAULT NULL,
  `andar_bloco` varchar(50) DEFAULT NULL,
  `codigo_postal` varchar(20) DEFAULT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_cliente_morada_cidade` FOREIGN KEY (`cidade_id`) REFERENCES `cidade` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cliente_morada_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cliente_morada` (`id`, `cliente_id`, `cidade_id`, `designacao`, `rua`, `numero_porta`, `andar_bloco`, `codigo_postal`, `principal`) VALUES
  (1, 3, 10, 'Casa', 'Rua de Aviz', '10', '1º Esq', '7000-123', 1),
  (75, 53, 10, 'Casa', 'Rua Frei Carlos, 7000-737, Évora', '4', '2Esq', '7000-737', 1),
  (98, 3, 7, 'Casa', 'Rua Teste Rica', '2', NULL, '7000-200', 1),
  (99, 3, 10, 'Casa', 'Rua Aceitacao', '5', NULL, '7000-300', 0),
  (200, 100, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (201, 101, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (202, 102, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (203, 103, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (204, 104, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (205, 105, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (206, 106, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (207, 107, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (208, 108, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (209, 109, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (210, 110, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (211, 111, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (212, 112, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (213, 113, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (214, 114, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (215, 115, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (216, 116, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (217, 117, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (218, 118, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (219, 119, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (220, 120, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (221, 121, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (222, 122, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (223, 123, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (224, 124, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (225, 125, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (226, 126, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (227, 127, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (228, 128, 10, 'Casa', '', NULL, NULL, NULL, 1),
  (229, 129, 1, 'Casa', '', NULL, NULL, NULL, 1),
  (230, 130, 1, 'Casa', '', NULL, NULL, NULL, 1),
  (231, 131, 2, 'Casa', '', NULL, NULL, NULL, 1),
  (232, 132, 2, 'Casa', '', NULL, NULL, NULL, 1),
  (233, 133, 3, 'Casa', '', NULL, NULL, NULL, 1),
  (234, 134, 3, 'Casa', '', NULL, NULL, NULL, 1),
  (235, 135, 4, 'Casa', '', NULL, NULL, NULL, 1),
  (236, 136, 4, 'Casa', '', NULL, NULL, NULL, 1),
  (237, 137, 5, 'Casa', '', NULL, NULL, NULL, 1),
  (238, 138, 5, 'Casa', '', NULL, NULL, NULL, 1),
  (239, 139, 6, 'Casa', '', NULL, NULL, NULL, 1),
  (240, 140, 6, 'Casa', '', NULL, NULL, NULL, 1),
  (241, 141, 7, 'Casa', '', NULL, NULL, NULL, 1),
  (242, 142, 7, 'Casa', '', NULL, NULL, NULL, 1),
  (243, 143, 8, 'Casa', '', NULL, NULL, NULL, 1),
  (244, 144, 8, 'Casa', '', NULL, NULL, NULL, 1),
  (245, 145, 8, 'Casa', '', NULL, NULL, NULL, 1),
  (246, 146, 9, 'Casa', '', NULL, NULL, NULL, 1),
  (247, 147, 9, 'Casa', '', NULL, NULL, NULL, 1),
  (248, 148, 9, 'Casa', '', NULL, NULL, NULL, 1),
  (249, 149, 1, 'Casa', '', NULL, NULL, NULL, 1),
  (250, 150, 1, 'Casa', '', NULL, NULL, NULL, 1),
  (251, 151, 2, 'Casa', '', NULL, NULL, NULL, 1),
  (252, 152, 2, 'Casa', '', NULL, NULL, NULL, 1),
  (253, 153, 3, 'Casa', '', NULL, NULL, NULL, 1),
  (254, 154, 3, 'Casa', '', NULL, NULL, NULL, 1),
  (255, 155, 4, 'Casa', '', NULL, NULL, NULL, 1),
  (256, 156, 5, 'Casa', '', NULL, NULL, NULL, 1),
  (257, 157, 5, 'Casa', '', NULL, NULL, NULL, 1),
  (258, 158, 6, 'Casa', '', NULL, NULL, NULL, 1),
  (259, 159, 8, 'Casa', '', NULL, NULL, NULL, 1),
  (260, 160, 8, 'Casa', '', NULL, NULL, NULL, 1),
  (261, 161, 8, 'Casa', '', NULL, NULL, NULL, 1),
  (262, 162, 3, 'Casa', '', NULL, NULL, NULL, 1),
  (263, 163, 4, 'Casa', '', NULL, NULL, NULL, 1),
  (264, 164, 1, 'Casa', '', NULL, NULL, NULL, 1);

-- ------------------------------------------------------------
-- Tabela: config_percentagem_padrao
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `config_percentagem_padrao`;
CREATE TABLE `config_percentagem_padrao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_contrato` enum('efetivo_contratado','recibo_verde') NOT NULL,
  `percentagem_comissao` decimal(5,2) NOT NULL DEFAULT '0.00',
  `data_vigencia` date NOT NULL,
  `configurado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_pct_tipo_vig` (`tipo_contrato`,`data_vigencia`),
  CONSTRAINT `fk_config_pct_utilizador` FOREIGN KEY (`configurado_por`) REFERENCES `utilizador` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `config_percentagem_padrao` (`id`, `tipo_contrato`, `percentagem_comissao`, `data_vigencia`, `configurado_por`) VALUES
  (1, 'efetivo_contratado', 0.00, '2026-01-01', NULL),
  (2, 'recibo_verde', 70.00, '2026-01-01', NULL);

-- ------------------------------------------------------------
-- Tabela: execucao_agendamento
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `execucao_agendamento`;
CREATE TABLE `execucao_agendamento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agendamento_id` int NOT NULL,
  `rota_id` int DEFAULT NULL,
  `data_hora_inicio_real` datetime DEFAULT NULL,
  `data_hora_fim_real` datetime DEFAULT NULL,
  `estado_execucao` enum('em_curso','concluido','no_show_cliente','cancelado_terreno') DEFAULT 'em_curso',
  `observacoes_tecnico` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agendamento_id` (`agendamento_id`),
  CONSTRAINT `fk_exec_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_exec_rota` FOREIGN KEY (`rota_id`) REFERENCES `rota_ambulante` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `execucao_agendamento` (`id`, `agendamento_id`, `rota_id`, `data_hora_inicio_real`, `data_hora_fim_real`, `estado_execucao`, `observacoes_tecnico`) VALUES
  (21, 215, NULL, '2026-09-22 15:33:27', '2026-09-22 15:33:27', 'concluido', NULL);

-- ------------------------------------------------------------
-- Tabela: fecho_caixa_diario
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `fecho_caixa_diario`;
CREATE TABLE `fecho_caixa_diario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  `funcionario_id` int NOT NULL,
  `total_esperado_faturas` decimal(10,2) DEFAULT '0.00',
  `total_recolhido_campo` decimal(10,2) DEFAULT '0.00',
  `diferenca` decimal(10,2) DEFAULT '0.00',
  `observacoes` text,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_fecho_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: feedback_cliente
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `feedback_cliente`;
CREATE TABLE `feedback_cliente` (
  `id` int NOT NULL AUTO_INCREMENT,
  `execucao_agendamento_id` int NOT NULL,
  `classificacao_estrelas` int DEFAULT NULL,
  `comentario` text,
  `data_feedback` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `execucao_agendamento_id` (`execucao_agendamento_id`),
  CONSTRAINT `fk_feedback_execucao` FOREIGN KEY (`execucao_agendamento_id`) REFERENCES `execucao_agendamento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_cliente_chk_1` CHECK ((`classificacao_estrelas` between 1 and 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `feedback_cliente` (`id`, `execucao_agendamento_id`, `classificacao_estrelas`, `comentario`, `data_feedback`) VALUES
  (21, 21, 5, 'Serviço excelente (teste).', '2026-09-22 14:33:27');

-- ------------------------------------------------------------
-- Tabela: fornecedor
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `fornecedor`;
CREATE TABLE `fornecedor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `nif` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telemovel` varchar(20) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `observacoes` text,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `fornecedor` (`id`, `nome`, `nif`, `email`, `telemovel`, `ativo`, `observacoes`, `criado_em`) VALUES
  (1, 'Stand Virtual', '508069491', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (2, 'Worten', '503630330', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (3, 'Leroy-Merlin', '506848558', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (4, 'AOSOM', '980683386', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (5, 'Staples', '503789372', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (6, 'Beleza 37', '514749636', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (7, 'Extintores online', '518352080', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (8, 'Continente', '502011475', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (9, 'Logo seguros', '508278600', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (10, 'Generali', '500940231', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (11, 'OK! Seguros', '504011944', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (12, 'Fidelidade', '500918880', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (13, 'Liberty', '500068658', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (14, 'SK pro Med Beauty Solutions', '508161320', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (15, 'Primor', '980663695', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (16, 'Pluri cosmética', '503890278', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (17, 'AfroQueen', '516416081', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (18, 'Temu', NULL, NULL, NULL, 1, 'NP (não possui) NIF', '2026-10-03 21:41:30'),
  (19, 'IKEA', '505416654', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (20, 'Lusini', '517386402', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (21, 'DRUNI', '518530752', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (22, 'Wells', '508037514', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (23, 'Baber tools profissional', '589053345', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (24, 'Ideal Cosméticos', '514239085', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (25, 'Barberalia', '516206370', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (26, 'Bandido Portugal.pt', NULL, NULL, NULL, 1, 'NIF indisponível nas plataformas digitais', '2026-10-03 21:41:30'),
  (27, 'ViceDeal.com', NULL, NULL, NULL, 1, 'NP (não possui) NIF', '2026-10-03 21:41:30'),
  (28, 'Casa do Barbeiro', '501766448', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (29, 'Espaço barbeiro', '510427103', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (30, 'Aliexpress', NULL, NULL, NULL, 1, 'NP (não possui) NIF', '2026-10-03 21:41:30'),
  (31, 'Município de Évora', '504828576', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (32, 'Endesa', '508855950', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (33, 'MEO', '504615947', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (34, 'Galp', '505060515', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (35, 'Repsol', '500246963', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (36, 'Manuel jacinto (renda)', '320491366', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (37, 'Banco CTT', '513412417', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (38, 'Consumíveis', NULL, NULL, NULL, 1, 'NP (não possui) NIF', '2026-10-03 21:41:30'),
  (39, 'Norauto', '503629995', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (40, 'Gestévora', '500785708', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (41, 'Manuel jacinto (renda)', '285284240', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (42, 'Stela Cristina', '200846922', NULL, NULL, 1, NULL, '2026-10-03 21:41:30'),
  (43, 'Moloni', '513321527', NULL, NULL, 1, NULL, '2026-10-03 21:41:30');

-- ------------------------------------------------------------
-- Tabela: funcionario
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `funcionario`;
CREATE TABLE `funcionario` (
  `id` int NOT NULL,
  `tipo_contrato` enum('efetivo_contratado','recibo_verde') NOT NULL,
  `salario_base` decimal(10,2) NOT NULL DEFAULT '0.00',
  `percentagem_comissao` decimal(5,2) NOT NULL DEFAULT '0.00',
  `cc` varchar(20) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_funcionario_utilizador` FOREIGN KEY (`id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `funcionario` (`id`, `tipo_contrato`, `salario_base`, `percentagem_comissao`, `cc`, `ativo`) VALUES
  (2, 'recibo_verde', 0.00, 70.00, '999999990Z7R', 1);

-- ------------------------------------------------------------
-- Tabela: gorjeta
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `gorjeta`;
CREATE TABLE `gorjeta` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agendamento_id` int NOT NULL,
  `funcionario_id` int NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_registo` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_gorjeta_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gorjeta_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: matriz_deslocacao
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `matriz_deslocacao`;
CREATE TABLE `matriz_deslocacao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `base_partida_id` int NOT NULL,
  `cidade_id` int NOT NULL,
  `distancia_km` decimal(8,2) NOT NULL DEFAULT '0.00',
  `tempo_estimado_minutos` int NOT NULL DEFAULT '0',
  `custo_estimado_combustivel` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_base_cidade` (`base_partida_id`,`cidade_id`),
  CONSTRAINT `fk_matriz_base` FOREIGN KEY (`base_partida_id`) REFERENCES `base_partida` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_matriz_cidade` FOREIGN KEY (`cidade_id`) REFERENCES `cidade` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `matriz_deslocacao` (`id`, `base_partida_id`, `cidade_id`, `distancia_km`, `tempo_estimado_minutos`, `custo_estimado_combustivel`) VALUES
  (1, 1, 1, 45.00, 40, 6.75),
  (2, 1, 2, 60.00, 50, 8.94),
  (3, 1, 3, 60.00, 55, 8.94),
  (4, 1, 4, 80.00, 70, 11.95),
  (5, 1, 5, 75.00, 65, 11.22),
  (6, 1, 6, 110.00, 80, 16.42),
  (7, 1, 7, 95.00, 70, 14.23),
  (8, 1, 8, 115.00, 85, 17.24),
  (9, 1, 9, 110.00, 90, 16.42);

-- ------------------------------------------------------------
-- Tabela: notificacao
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `notificacao`;
CREATE TABLE `notificacao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilizador_id` int NOT NULL,
  `tipo` enum('agendamento_confirmado','agendamento_recusado','agendamento_cancelado','lembrete_24h','logistica','sistema') NOT NULL,
  `mensagem` varchar(500) NOT NULL,
  `lida` tinyint(1) DEFAULT '0',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_notificacao_utilizador` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: obrigacao_fiscal
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `obrigacao_fiscal`;
CREATE TABLE `obrigacao_fiscal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` enum('iva','irc','seguranca_social','seguros') NOT NULL,
  `designacao` varchar(150) NOT NULL,
  `periodicidade` enum('mensal','trimestral','anual') NOT NULL,
  `valor_estimado` decimal(12,2) DEFAULT '0.00',
  `data_prazo` date NOT NULL,
  `estado` enum('pendente','pago') DEFAULT 'pendente',
  `data_pagamento` date DEFAULT NULL,
  `observacoes` text,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_obrigacao_prazo` (`data_prazo`),
  KEY `idx_obrigacao_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `obrigacao_fiscal` (`id`, `tipo`, `designacao`, `periodicidade`, `valor_estimado`, `data_prazo`, `estado`, `data_pagamento`, `observacoes`, `criado_em`) VALUES
  (84, 'iva', 'IVA Trimestral (teste)', 'trimestral', 1234.56, '2026-09-29', 'pago', '2026-09-22', NULL, '2026-09-22 14:33:27'),
  (85, 'seguranca_social', 'SS em atraso (teste)', 'mensal', 350.00, '2026-09-17', 'pendente', NULL, NULL, '2026-09-22 14:33:27'),
  (86, 'irc', 'IRC 30 dias (teste)', 'anual', 5000.00, '2026-10-22', 'pendente', NULL, NULL, '2026-09-22 14:33:27');

-- ------------------------------------------------------------
-- Tabela: rota_ambulante
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `rota_ambulante`;
CREATE TABLE `rota_ambulante` (
  `id` int NOT NULL AUTO_INCREMENT,
  `data_rota` date NOT NULL,
  `base_partida_id` int NOT NULL,
  `cidade_id` int NOT NULL,
  `estado_rota` enum('planeada','aprovada','recusada','em_execucao','concluida') DEFAULT 'planeada',
  `custo_estimado_combustivel` decimal(10,2) DEFAULT '0.00',
  `quota_parte_cliente` decimal(10,2) DEFAULT '0.00',
  `lucro_servicos` decimal(10,2) DEFAULT '0.00',
  `lucro_total` decimal(10,2) DEFAULT '0.00',
  `decidido_por` int DEFAULT NULL,
  `decidido_em` datetime DEFAULT NULL,
  `observacoes_decisao` text,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_rota_base` FOREIGN KEY (`base_partida_id`) REFERENCES `base_partida` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rota_cidade` FOREIGN KEY (`cidade_id`) REFERENCES `cidade` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rota_decidido_por` FOREIGN KEY (`decidido_por`) REFERENCES `utilizador` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rota_ambulante` (`id`, `data_rota`, `base_partida_id`, `cidade_id`, `estado_rota`, `custo_estimado_combustivel`, `quota_parte_cliente`, `lucro_servicos`, `lucro_total`, `decidido_por`, `decidido_em`, `observacoes_decisao`) VALUES
  (63, '2026-09-29', 1, 1, 'aprovada', 6.75, 0.00, 4.07, -52.68, 3, '2026-09-22 15:33:27', 'Decisão manual de teste: aprovada apesar da referência.'),
  (64, '2026-09-29', 1, 7, 'recusada', 14.23, 0.00, 199.20, 134.97, 3, '2026-09-22 15:33:27', 'Decisão manual: rota recusada (rentabilidade 134.97 EUR).');

-- ------------------------------------------------------------
-- Tabela: rota_funcionario
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `rota_funcionario`;
CREATE TABLE `rota_funcionario` (
  `rota_id` int NOT NULL,
  `funcionario_id` int NOT NULL,
  PRIMARY KEY (`rota_id`,`funcionario_id`),
  CONSTRAINT `fk_rota_func_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rota_func_rota` FOREIGN KEY (`rota_id`) REFERENCES `rota_ambulante` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: servico
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `servico`;
CREATE TABLE `servico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `descricao` text NOT NULL,
  `categoria_id` int NOT NULL,
  `duracao_estimada_minutos` int NOT NULL,
  `preco_base` decimal(10,2) NOT NULL,
  `requer_espaco_fisico` tinyint(1) DEFAULT '0',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_servico_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categoria_servico` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `servico` (`id`, `nome`, `descricao`, `categoria_id`, `duracao_estimada_minutos`, `preco_base`, `requer_espaco_fisico`, `ativo`) VALUES
  (1, 'Box Braids', 'Serviço de tranças Box Braids.', 1, 150, 32.52, 0, 1),
  (2, 'Cordelete', 'Serviço de tranças Cordelete.', 1, 150, 48.78, 0, 1),
  (3, 'Demão', 'Serviço de tranças Demão.', 1, 120, 36.59, 0, 1),
  (4, 'Tranças Nagô', 'Serviço de tranças Nagô.', 1, 120, 28.46, 0, 1),
  (5, 'Retro Braids', 'Serviço de tranças Retro Braids.', 1, 150, 48.78, 0, 1),
  (6, 'Dreads Look', 'Aplicação de Dreads Look.', 1, 150, 44.72, 0, 1),
  (7, 'Trança Twist', 'Serviço de trança Twist.', 1, 120, 40.65, 0, 1),
  (8, 'Box Braid Crochet Hair', 'Aplicação de Box Braids com Crochet Hair.', 1, 120, 36.59, 0, 1),
  (9, 'Trança Boxeadora', 'Serviço de trança Boxeadora.', 1, 60, 24.39, 0, 1),
  (10, 'Trança Butterfly', 'Serviço de trança Butterfly.', 1, 150, 45.52, 0, 1),
  (11, 'Trança Fulani', 'Serviço de trança Fulani.', 1, 120, 36.59, 0, 1),
  (12, 'French Curl', 'Serviço de tranças French Curl.', 1, 150, 48.78, 0, 1),
  (13, 'Faux Locs', 'Aplicação de Faux Locs.', 1, 150, 47.15, 0, 1),
  (14, 'Gypsy Braids', 'Serviço de Gypsy Braids.', 1, 150, 48.78, 0, 1),
  (15, 'Bohemian Braids', 'Serviço de Bohemian Braids.', 1, 150, 45.53, 0, 1),
  (16, 'Tranças Pipocas', 'Serviço de tranças Pipocas.', 1, 120, 40.65, 0, 1),
  (17, 'Rabo de Cavalo (Ponytail)', 'Penteado rabo de cavalo.', 1, 45, 20.33, 0, 1),
  (18, 'Coque (Bun)', 'Penteado coque.', 1, 60, 24.39, 0, 1),
  (19, 'Trança Francesa', 'Penteado com trança francesa.', 1, 30, 13.82, 0, 1),
  (20, 'Half Bun (Meio Coque)', 'Penteado meio coque.', 1, 60, 36.59, 0, 1),
  (21, 'Beach Waves', 'Penteado Beach Waves.', 1, 45, 16.26, 0, 1),
  (22, 'Cabelo Liso com Franja', 'Alisamento e finalização com franja.', 1, 60, 32.52, 0, 1),
  (23, 'Coque Messy', 'Penteado coque messy.', 1, 45, 28.46, 0, 1),
  (24, 'Trança Espinha de Peixe', 'Penteado trança espinha de peixe.', 1, 45, 16.26, 0, 1),
  (25, 'Cabelo Preso Lateral', 'Penteado preso lateral.', 1, 60, 36.59, 0, 1),
  (26, 'Cabelo Solto com Ondas', 'Penteado cabelo solto com ondas.', 1, 60, 40.65, 0, 1),
  (27, 'Coque Baixo Elegante', 'Penteado coque baixo elegante.', 1, 45, 20.33, 0, 1),
  (28, 'Corte de Cabelo', 'Corte de cabelo masculino.', 2, 30, 12.20, 0, 1),
  (29, 'Barba', 'Aparar e modelar barba.', 2, 8, 4.07, 0, 1),
  (30, 'Maquilhagem para Noivas', 'Maquilhagem profissional para noivas.', 3, 150, 40.65, 0, 1),
  (31, 'Maquilhagem para Festa', 'Maquilhagem profissional para festas.', 3, 60, 28.46, 0, 1),
  (32, 'Maquilhagem Simples', 'Maquilhagem simples.', 3, 45, 16.26, 0, 1),
  (33, 'Manicure', 'Serviço de manicure.', 3, 45, 12.20, 0, 1),
  (34, 'Limpeza Facial', 'Limpeza facial.', 3, 60, 24.39, 1, 1),
  (35, 'Design de Sobrancelha com Linha', 'Design de sobrancelhas com linha.', 3, 20, 8.13, 0, 1);

-- ------------------------------------------------------------
-- Tabela: servico_foto
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `servico_foto`;
CREATE TABLE `servico_foto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `servico_id` int NOT NULL,
  `url_foto` varchar(255) NOT NULL,
  `destaque` tinyint(1) DEFAULT '0',
  `ordem_exibicao` int DEFAULT '0',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_servico_foto_servico` FOREIGN KEY (`servico_id`) REFERENCES `servico` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: servico_local
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `servico_local`;
CREATE TABLE `servico_local` (
  `id` int NOT NULL AUTO_INCREMENT,
  `servico_id` int NOT NULL,
  `tipo_local` enum('loja_fisica','carrinha_ambulante') NOT NULL,
  `disponivel` tinyint(1) DEFAULT '1',
  `preco_especifico` decimal(10,2) DEFAULT NULL,
  `ajuste_logistico` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_servico_local_servico` FOREIGN KEY (`servico_id`) REFERENCES `servico` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: transacao_financeira
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `transacao_financeira`;
CREATE TABLE `transacao_financeira` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agendamento_id` int NOT NULL,
  `funcionario_id` int NOT NULL,
  `metodo_pagamento` enum('numerario_dinheiro','mb_way','multibanco_pos') NOT NULL,
  `tipo_transacao` enum('sinal_inicial','restante_90_porcento','pagamento_integral','quota_parte_deslocacao') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `estado_offline` tinyint(1) DEFAULT '0',
  `recibo_manual_numero` varchar(50) DEFAULT NULL,
  `data_transacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_transacao_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transacao_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Tabela: utilizador
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `utilizador`;
CREATE TABLE `utilizador` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `telemovel` varchar(20) NOT NULL,
  `nif` varchar(20) DEFAULT NULL,
  `tipo_perfil` enum('cliente','funcionario','gestor') NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `utilizador` (`id`, `nome`, `email`, `password_hash`, `telemovel`, `nif`, `tipo_perfil`, `criado_em`) VALUES
  (1, 'Gestor Secade', 'gestor@secade.pt', '$2y$10$DX3o9mDbE2M00tUg21KO1OOtvl7SGpPvhNZ4SFnYBXHNetwcdt0F6', '+351911111111', '123456789', 'gestor', '2026-09-21 21:27:40'),
  (2, 'Ana Técnica', 'funcionario@secade.pt', '$2y$10$D22wXVD90rZoILW3X3QalOTr1mb3dSz8IxR5eIbulvc0jN0Eitvta', '+351922222222', '298765438', 'funcionario', '2026-09-21 21:27:40'),
  (3, 'João Cliente', 'cliente@teste.pt', '$2y$10$NRQDxzU490d8RvpmHVmLb.6AnC74Pds1ozKySPI85.1801rGGDWSS', '+351933333333', '345678915', 'cliente', '2026-09-21 21:27:40'),
  (53, 'Daniel Branco', 'hb.daniel@gmail.com', '$2y$10$g95ZP3MI6AsHPIoPuvddk.VEoEKqUONmvT/XVLkPTQoIs8raP4YVu', '923456789', NULL, 'cliente', '2026-09-22 11:59:11'),
  (100, 'Leonor Santos', 'leonor.santos.251989518@cliente.secade.local', '*', '', '251989518', 'cliente', '2026-10-03 21:41:30'),
  (101, 'Catarina Pixoto', 'catarina.pixoto.298784076@cliente.secade.local', '*', '', '298784076', 'cliente', '2026-10-03 21:41:30'),
  (102, 'Elvisson Daniel', 'elvisson.daniel.278561519@cliente.secade.local', '*', '', '278561519', 'cliente', '2026-10-03 21:41:30'),
  (103, 'Leandro Silva', 'leandro.silva.214056686@cliente.secade.local', '*', '', '214056686', 'cliente', '2026-10-03 21:41:30'),
  (104, 'Tomásia Contreiras', 'tom.asia.contreiras.227748204@cliente.secade.local', '*', '', '227748204', 'cliente', '2026-10-03 21:41:30'),
  (105, 'Joaquina Matos', 'joaquina.matos.235820776@cliente.secade.local', '*', '', '235820776', 'cliente', '2026-10-03 21:41:30'),
  (106, 'Lídia Correia', 'l.idia.correia.201303140@cliente.secade.local', '*', '', '201303140', 'cliente', '2026-10-03 21:41:30'),
  (107, 'Emanuela Vigílio', 'emanuela.vig.ilio.213916819@cliente.secade.local', '*', '', '213916819', 'cliente', '2026-10-03 21:41:30'),
  (108, 'Augusta Contente', 'augusta.contente.240660501@cliente.secade.local', '*', '', '240660501', 'cliente', '2026-10-03 21:41:30'),
  (109, 'Laura Vanuza', 'laura.vanuza.235159026@cliente.secade.local', '*', '', '235159026', 'cliente', '2026-10-03 21:41:30'),
  (110, 'Florentino Rosa', 'florentino.rosa.278084737@cliente.secade.local', '*', '', '278084737', 'cliente', '2026-10-03 21:41:30'),
  (111, 'Beatriz Baessa', 'beatriz.baessa.232443777@cliente.secade.local', '*', '', '232443777', 'cliente', '2026-10-03 21:41:30'),
  (112, 'Tiago Fortunato', 'tiago.fortunato.214803155@cliente.secade.local', '*', '', '214803155', 'cliente', '2026-10-03 21:41:30'),
  (113, 'Elias Patente', 'elias.patente.243363010@cliente.secade.local', '*', '', '243363010', 'cliente', '2026-10-03 21:41:30'),
  (114, 'Partrícia Gomes', 'partr.icia.gomes.274907810@cliente.secade.local', '*', '', '274907810', 'cliente', '2026-10-03 21:41:30'),
  (115, 'Alexanda Pinto', 'alexanda.pinto.261788396@cliente.secade.local', '*', '', '261788396', 'cliente', '2026-10-03 21:41:30'),
  (116, 'Maria Luana', 'maria.luana.270325492@cliente.secade.local', '*', '', '270325492', 'cliente', '2026-10-03 21:41:30'),
  (117, 'Rena Nunes', 'rena.nunes.259969931@cliente.secade.local', '*', '', '259969931', 'cliente', '2026-10-03 21:41:30'),
  (118, 'Vanessa Cardoso', 'vanessa.cardoso.204914663@cliente.secade.local', '*', '', '204914663', 'cliente', '2026-10-03 21:41:30'),
  (119, 'Tiago Alves', 'tiago.alves.272698679@cliente.secade.local', '*', '', '272698679', 'cliente', '2026-10-03 21:41:30'),
  (120, 'Maria Pedro', 'maria.pedro.229658660@cliente.secade.local', '*', '', '229658660', 'cliente', '2026-10-03 21:41:30'),
  (121, 'Luísa Evidência', 'lu.isa.evid.encia.289524121@cliente.secade.local', '*', '', '289524121', 'cliente', '2026-10-03 21:41:30'),
  (122, 'Bianca Brico', 'bianca.brico.273711148@cliente.secade.local', '*', '', '273711148', 'cliente', '2026-10-03 21:41:30'),
  (123, 'Amanda Alves', 'amanda.alves.265052955@cliente.secade.local', '*', '', '265052955', 'cliente', '2026-10-03 21:41:30'),
  (124, 'Patrícia Freitas', 'patr.icia.freitas.279336055@cliente.secade.local', '*', '', '279336055', 'cliente', '2026-10-03 21:41:30'),
  (125, 'Beatriz Oliveira', 'beatriz.oliveira.207109176@cliente.secade.local', '*', '', '207109176', 'cliente', '2026-10-03 21:41:30'),
  (126, 'Lúcia Camarada', 'l.ucia.camarada.299502309@cliente.secade.local', '*', '', '299502309', 'cliente', '2026-10-03 21:41:30'),
  (127, 'Vivalda Libolo', 'vivalda.libolo.254824498@cliente.secade.local', '*', '', '254824498', 'cliente', '2026-10-03 21:41:30'),
  (128, 'Ricardo Sebastião', 'ricardo.sebasti.ao.217579906@cliente.secade.local', '*', '', '217579906', 'cliente', '2026-10-03 21:41:30'),
  (129, 'Beatriz Gonçalves', 'beatriz.goncalves.219082081@cliente.secade.local', '*', '', '219082081', 'cliente', '2026-10-03 21:41:30'),
  (130, 'Sónia Silva', 's.onia.silva.299763374@cliente.secade.local', '*', '', '299763374', 'cliente', '2026-10-03 21:41:30'),
  (131, 'Marta Gonçalves', 'marta.goncalves.250174332@cliente.secade.local', '*', '', '250174332', 'cliente', '2026-10-03 21:41:30'),
  (132, 'Joana Silva', 'joana.silva.248171240@cliente.secade.local', '*', '', '248171240', 'cliente', '2026-10-03 21:41:30'),
  (133, 'Sílvia Almeida', 's.ilvia.almeida.295931540@cliente.secade.local', '*', '', '295931540', 'cliente', '2026-10-03 21:41:30'),
  (134, 'Rita Costa', 'rita.costa.252554620@cliente.secade.local', '*', '', '252554620', 'cliente', '2026-10-03 21:41:30'),
  (135, 'Teresa Correia', 'teresa.correia.275536394@cliente.secade.local', '*', '', '275536394', 'cliente', '2026-10-03 21:41:30'),
  (136, 'Teresa Cardoso', 'teresa.cardoso.231413882@cliente.secade.local', '*', '', '231413882', 'cliente', '2026-10-03 21:41:30'),
  (137, 'Claúdia Marques', 'cla.udia.marques.275469727@cliente.secade.local', '*', '', '275469727', 'cliente', '2026-10-03 21:41:30'),
  (138, 'Sílvia Gonçalves', 's.ilvia.goncalves.228145651@cliente.secade.local', '*', '', '228145651', 'cliente', '2026-10-03 21:41:30'),
  (139, 'Carla Morauto', 'carla.morauto.261207890@cliente.secade.local', '*', '', '261207890', 'cliente', '2026-10-03 21:41:30'),
  (140, 'Patrícia Costa', 'patr.icia.costa.287024350@cliente.secade.local', '*', '', '287024350', 'cliente', '2026-10-03 21:41:30'),
  (141, 'Joana Pereira', 'joana.pereira.258735082@cliente.secade.local', '*', '', '258735082', 'cliente', '2026-10-03 21:41:30'),
  (142, 'Filipa Costa', 'filipa.costa.244808929@cliente.secade.local', '*', '', '244808929', 'cliente', '2026-10-03 21:41:30'),
  (143, 'Joana Pinto', 'joana.pinto.264883268@cliente.secade.local', '*', '', '264883268', 'cliente', '2026-10-03 21:41:30'),
  (144, 'Daniela Cardoso', 'daniela.cardoso.254736130@cliente.secade.local', '*', '', '254736130', 'cliente', '2026-10-03 21:41:30'),
  (145, 'Catarina Correia', 'catarina.correia.277691400@cliente.secade.local', '*', '', '277691400', 'cliente', '2026-10-03 21:41:30'),
  (146, 'Catarina Lopes', 'catarina.lopes.234560657@cliente.secade.local', '*', '', '234560657', 'cliente', '2026-10-03 21:41:30'),
  (147, 'Sónia Fenandes', 's.onia.fenandes.269836870@cliente.secade.local', '*', '', '269836870', 'cliente', '2026-10-03 21:41:30'),
  (148, 'Margarida Santos', 'margarida.santos.232060649@cliente.secade.local', '*', '', '232060649', 'cliente', '2026-10-03 21:41:30'),
  (149, 'Rita Pereira', 'rita.pereira.243526512@cliente.secade.local', '*', '', '243526512', 'cliente', '2026-10-03 21:41:30'),
  (150, 'Inês Mendes', 'in.es.mendes.203624513@cliente.secade.local', '*', '', '203624513', 'cliente', '2026-10-03 21:41:30'),
  (151, 'Vera Costa', 'vera.costa.245056220@cliente.secade.local', '*', '', '245056220', 'cliente', '2026-10-03 21:41:30'),
  (152, 'Filipa Santos', 'filipa.santos.281021970@cliente.secade.local', '*', '', '281021970', 'cliente', '2026-10-03 21:41:30'),
  (153, 'Marta Rodrigues', 'marta.rodrigues.248193848@cliente.secade.local', '*', '', '248193848', 'cliente', '2026-10-03 21:41:30'),
  (154, 'Marta Almeida', 'marta.almeida.241834961@cliente.secade.local', '*', '', '241834961', 'cliente', '2026-10-03 21:41:30'),
  (155, 'Sílvia Ferreira', 's.ilvia.ferreira.299343049@cliente.secade.local', '*', '', '299343049', 'cliente', '2026-10-03 21:41:30'),
  (156, 'Rita Jesus', 'rita.jesus.291337392@cliente.secade.local', '*', '', '291337392', 'cliente', '2026-10-03 21:41:30'),
  (157, 'Maria Ferreira', 'maria.ferreira.250412365@cliente.secade.local', '*', '', '250412365', 'cliente', '2026-10-03 21:41:30'),
  (158, 'Margarida Gonçalves', 'margarida.goncalves.211275859@cliente.secade.local', '*', '', '211275859', 'cliente', '2026-10-03 21:41:30'),
  (159, 'Daniela Ribeiro', 'daniela.ribeiro.221393366@cliente.secade.local', '*', '', '221393366', 'cliente', '2026-10-03 21:41:30'),
  (160, 'Daniela Oliveira', 'daniela.oliveira.266255264@cliente.secade.local', '*', '', '266255264', 'cliente', '2026-10-03 21:41:30'),
  (161, 'Ana Lopes', 'ana.lopes.255884117@cliente.secade.local', '*', '', '255884117', 'cliente', '2026-10-03 21:41:30'),
  (162, 'Teresa Lopes', 'teresa.lopes.259591696@cliente.secade.local', '*', '', '259591696', 'cliente', '2026-10-03 21:41:30'),
  (163, 'Inês Lopes', 'in.es.lopes.261930966@cliente.secade.local', '*', '', '261930966', 'cliente', '2026-10-03 21:41:30'),
  (164, 'Daniela Jesus', 'daniela.jesus.273038273@cliente.secade.local', '*', '', '273038273', 'cliente', '2026-10-03 21:41:30');

SET FOREIGN_KEY_CHECKS = 1;
