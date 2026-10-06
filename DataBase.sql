-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for secade_beauty
DROP DATABASE IF EXISTS `secade_beauty`;
CREATE DATABASE IF NOT EXISTS `secade_beauty` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `secade_beauty`;

-- Dumping structure for table secade_beauty.agendamento
DROP TABLE IF EXISTS `agendamento`;
CREATE TABLE IF NOT EXISTS `agendamento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `cliente_morada_id` int DEFAULT NULL,
  `local_prestacao` enum('loja_fisica','carrinha_ambulante') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_hora_pretendida` datetime NOT NULL,
  `estado_reserva` enum('pendente_alocacao','pendente_validacao_logistica_loja','totalmente_alocado','confirmado','recusado','cancelado','executado','concluido') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pendente_alocacao',
  `modo_urgencia` tinyint(1) DEFAULT '0',
  `valor_total` decimal(10,2) NOT NULL,
  `sinal_pago` tinyint(1) DEFAULT '0',
  `valor_sinal` decimal(10,2) DEFAULT '0.00',
  `validado_logistica_loja` tinyint(1) DEFAULT '0',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_agendamento_cliente` (`cliente_id`),
  KEY `fk_agendamento_morada` (`cliente_morada_id`),
  CONSTRAINT `fk_agendamento_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_agendamento_morada` FOREIGN KEY (`cliente_morada_id`) REFERENCES `cliente_morada` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=218 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.agendamento: ~11 rows (approximately)
DELETE FROM `agendamento`;
INSERT INTO `agendamento` (`id`, `cliente_id`, `cliente_morada_id`, `local_prestacao`, `data_hora_pretendida`, `estado_reserva`, `modo_urgencia`, `valor_total`, `sinal_pago`, `valor_sinal`, `validado_logistica_loja`, `criado_em`) VALUES
	(1001, 100, 200, 'loja_fisica', '2026-09-15 10:00:00', 'concluido', 0, 24.40, 1, 2.44, 1, '2026-10-06 14:49:34'),
	(1002, 101, 201, 'loja_fisica', '2026-09-22 15:00:00', 'concluido', 0, 28.46, 1, 2.85, 1, '2026-10-06 14:49:34'),
	(1003, 102, 202, 'carrinha_ambulante', '2026-09-29 09:30:00', 'concluido', 0, 40.65, 1, 4.07, 0, '2026-10-06 14:49:34'),
	(1004, 103, 203, 'loja_fisica', '2026-10-03 11:00:00', 'executado', 0, 13.82, 1, 1.38, 1, '2026-10-06 14:49:34'),
	(1005, 104, 300, 'carrinha_ambulante', '2026-10-14 09:30:00', 'confirmado', 0, 28.46, 1, 2.85, 0, '2026-10-06 14:49:34'),
	(1006, 105, 301, 'carrinha_ambulante', '2026-10-14 11:00:00', 'confirmado', 0, 24.39, 1, 2.44, 0, '2026-10-06 14:49:34'),
	(1007, 106, 302, 'carrinha_ambulante', '2026-10-21 10:00:00', 'totalmente_alocado', 0, 20.33, 1, 2.03, 0, '2026-10-06 14:49:34'),
	(1008, 107, 303, 'carrinha_ambulante', '2026-10-21 14:00:00', 'pendente_alocacao', 0, 16.26, 1, 1.63, 0, '2026-10-06 14:49:34'),
	(1009, 108, 208, 'loja_fisica', '2026-10-16 16:00:00', 'cancelado', 0, 16.26, 0, 0.00, 1, '2026-10-06 14:49:34'),
	(1010, 109, 209, 'loja_fisica', '2026-10-17 11:00:00', 'recusado', 0, 12.20, 0, 0.00, 1, '2026-10-06 14:49:34'),
	(1011, 110, 210, 'loja_fisica', '2026-10-20 10:30:00', 'confirmado', 0, 40.65, 1, 4.07, 1, '2026-10-06 14:49:34');
-- Dumping structure for table secade_beauty.agendamento_pessoa
DROP TABLE IF EXISTS `agendamento_pessoa`;
CREATE TABLE IF NOT EXISTS `agendamento_pessoa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agendamento_id` int NOT NULL,
  `nome_pessoa` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_agend_pessoa_agendamento` (`agendamento_id`),
  CONSTRAINT `fk_agend_pessoa_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=171 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.agendamento_pessoa: ~0 rows (approximately)
DELETE FROM `agendamento_pessoa`;
-- Dumping structure for table secade_beauty.agendamento_servico
DROP TABLE IF EXISTS `agendamento_servico`;
CREATE TABLE IF NOT EXISTS `agendamento_servico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agendamento_id` int NOT NULL,
  `agendamento_pessoa_id` int DEFAULT NULL,
  `servico_id` int NOT NULL,
  `funcionario_id` int DEFAULT NULL,
  `preco_praticado` decimal(10,2) NOT NULL,
  `duracao_minutos` int NOT NULL,
  `estado_aceitacao` enum('pendente','aceite') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'aceite',
  `aceito_em` datetime DEFAULT NULL,
  `percentagem_funcionario_aplicada` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_agend_serv_agendamento` (`agendamento_id`),
  KEY `fk_agend_serv_pessoa` (`agendamento_pessoa_id`),
  KEY `fk_agend_serv_servico` (`servico_id`),
  KEY `fk_agend_serv_funcionario` (`funcionario_id`),
  CONSTRAINT `fk_agend_serv_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agend_serv_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_agend_serv_pessoa` FOREIGN KEY (`agendamento_pessoa_id`) REFERENCES `agendamento_pessoa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agend_serv_servico` FOREIGN KEY (`servico_id`) REFERENCES `servico` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=453 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.agendamento_servico: ~12 rows (approximately)
DELETE FROM `agendamento_servico`;
INSERT INTO `agendamento_servico` (`id`, `agendamento_id`, `agendamento_pessoa_id`, `servico_id`, `funcionario_id`, `preco_praticado`, `duracao_minutos`, `estado_aceitacao`, `aceito_em`, `percentagem_funcionario_aplicada`) VALUES
	(1066, 1001, NULL, 28, 2, 12.20, 30, 'aceite', '2026-09-15 09:10:00', 70.00),
	(1067, 1001, NULL, 33, 2, 12.20, 45, 'aceite', '2026-09-15 09:10:00', 70.00),
	(1068, 1002, NULL, 31, 2, 28.46, 60, 'aceite', '2026-09-22 14:05:00', 70.00),
	(1069, 1003, NULL, 7, 2, 40.65, 120, 'aceite', '2026-09-29 08:40:00', 70.00),
	(1070, 1004, NULL, 19, 2, 13.82, 30, 'aceite', '2026-10-03 10:20:00', 70.00),
	(1071, 1005, NULL, 4, 2, 28.46, 120, 'aceite', '2026-10-12 09:00:00', 70.00),
	(1072, 1006, NULL, 9, 2, 24.39, 60, 'aceite', '2026-10-12 09:05:00', 70.00),
	(1073, 1007, NULL, 17, 2, 20.33, 45, 'aceite', '2026-10-19 10:00:00', 70.00),
	(1074, 1008, NULL, 24, NULL, 16.26, 45, 'pendente', NULL, NULL),
	(1075, 1009, NULL, 21, NULL, 16.26, 45, 'pendente', NULL, NULL),
	(1076, 1010, NULL, 28, NULL, 12.20, 30, 'pendente', NULL, NULL),
	(1077, 1011, NULL, 8, 2, 40.65, 120, 'aceite', '2026-10-18 15:00:00', 70.00);
-- Dumping structure for table secade_beauty.alerta_fiscal
DROP TABLE IF EXISTS `alerta_fiscal`;
CREATE TABLE IF NOT EXISTS `alerta_fiscal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `obrigacao_fiscal_id` int NOT NULL,
  `tipo_alerta` enum('30_dias','15_dias','7_dias','3_dias','1_dia','em_atraso') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_alerta` date NOT NULL,
  `visualizado` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_alerta_obrigacao_tipo` (`obrigacao_fiscal_id`,`tipo_alerta`,`data_alerta`),
  CONSTRAINT `fk_alerta_obrigacao` FOREIGN KEY (`obrigacao_fiscal_id`) REFERENCES `obrigacao_fiscal` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=438 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.alerta_fiscal: ~0 rows (approximately)
DELETE FROM `alerta_fiscal`;
-- Dumping structure for table secade_beauty.base_partida
DROP TABLE IF EXISTS `base_partida`;
CREATE TABLE IF NOT EXISTS `base_partida` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `morada` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.base_partida: ~1 rows (approximately)
DELETE FROM `base_partida`;
INSERT INTO `base_partida` (`id`, `nome`, `morada`) VALUES
	(1, 'Évora', 'Espaço comercial, Praça Joaquim António de Aguiar, 12 a 19, U-5-ag, Évora');
-- Dumping structure for table secade_beauty.categoria_servico
DROP TABLE IF EXISTS `categoria_servico`;
CREATE TABLE IF NOT EXISTS `categoria_servico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.categoria_servico: ~3 rows (approximately)
DELETE FROM `categoria_servico`;
INSERT INTO `categoria_servico` (`id`, `nome`, `descricao`) VALUES
	(1, 'Cabeleireiro', 'Tranças e Penteados'),
	(2, 'Barbearia', 'Cortes'),
	(3, 'Estética', 'Maquiagem, Manicure e limpeza facial');
-- Dumping structure for table secade_beauty.cidade
DROP TABLE IF EXISTS `cidade`;
CREATE TABLE IF NOT EXISTS `cidade` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `distrito` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.cidade: ~10 rows (approximately)
DELETE FROM `cidade`;
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
-- Dumping structure for table secade_beauty.cliente
DROP TABLE IF EXISTS `cliente`;
CREATE TABLE IF NOT EXISTS `cliente` (
  `id` int NOT NULL,
  `telemovel_validado_otp` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_cliente_utilizador` FOREIGN KEY (`id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.cliente: ~67 rows (approximately)
DELETE FROM `cliente`;
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
-- Dumping structure for table secade_beauty.cliente_morada
DROP TABLE IF EXISTS `cliente_morada`;
CREATE TABLE IF NOT EXISTS `cliente_morada` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `cidade_id` int NOT NULL,
  `designacao` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Casa',
  `rua` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_porta` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `andar_bloco` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_postal` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_cliente_morada_cliente` (`cliente_id`),
  KEY `fk_cliente_morada_cidade` (`cidade_id`),
  CONSTRAINT `fk_cliente_morada_cidade` FOREIGN KEY (`cidade_id`) REFERENCES `cidade` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_cliente_morada_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=265 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.cliente_morada: ~71 rows (approximately)
DELETE FROM `cliente_morada`;
INSERT INTO `cliente_morada` (`id`, `cliente_id`, `cidade_id`, `designacao`, `rua`, `numero_porta`, `andar_bloco`, `codigo_postal`, `principal`) VALUES
	(1, 3, 10, 'Casa', 'Rua de Aviz', '10', '1º Esq', '7000-123', 0),
	(75, 53, 10, 'Casa', 'Rua Frei Carlos, 7000-737, Évora', '4', '2Esq', '7000-737', 1),
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
	(264, 164, 1, 'Casa', '', NULL, NULL, NULL, 1),
	(300, 104, 2, 'Casa', 'Rua dos Lagares', '12', NULL, '7050-101', 0),
	(301, 105, 2, 'Casa', 'Praça da República', '4', NULL, '7050-120', 0),
	(302, 106, 5, 'Casa', 'Rua Nova', '27', NULL, '7170-055', 0),
	(303, 107, 5, 'Casa', 'Travessa do Outeiro', '8', NULL, '7170-070', 0);
-- Dumping structure for table secade_beauty.config_percentagem_padrao
DROP TABLE IF EXISTS `config_percentagem_padrao`;
CREATE TABLE IF NOT EXISTS `config_percentagem_padrao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo_contrato` enum('efetivo_contratado','recibo_verde') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `percentagem_comissao` decimal(5,2) NOT NULL DEFAULT '0.00',
  `data_vigencia` date NOT NULL,
  `configurado_por` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_pct_tipo_vig` (`tipo_contrato`,`data_vigencia`),
  KEY `fk_config_pct_utilizador` (`configurado_por`),
  CONSTRAINT `fk_config_pct_utilizador` FOREIGN KEY (`configurado_por`) REFERENCES `utilizador` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.config_percentagem_padrao: ~2 rows (approximately)
DELETE FROM `config_percentagem_padrao`;
INSERT INTO `config_percentagem_padrao` (`id`, `tipo_contrato`, `percentagem_comissao`, `data_vigencia`, `configurado_por`) VALUES
	(1, 'efetivo_contratado', 0.00, '2026-01-01', NULL),
	(2, 'recibo_verde', 70.00, '2026-01-01', NULL);
-- Dumping structure for table secade_beauty.execucao_agendamento
DROP TABLE IF EXISTS `execucao_agendamento`;
CREATE TABLE IF NOT EXISTS `execucao_agendamento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agendamento_id` int NOT NULL,
  `rota_id` int DEFAULT NULL,
  `data_hora_inicio_real` datetime DEFAULT NULL,
  `data_hora_fim_real` datetime DEFAULT NULL,
  `estado_execucao` enum('em_curso','concluido','no_show_cliente','cancelado_terreno') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'em_curso',
  `observacoes_tecnico` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agendamento_id` (`agendamento_id`),
  KEY `fk_exec_rota` (`rota_id`),
  CONSTRAINT `fk_exec_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_exec_rota` FOREIGN KEY (`rota_id`) REFERENCES `rota_ambulante` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.execucao_agendamento: ~6 rows (approximately)
DELETE FROM `execucao_agendamento`;
INSERT INTO `execucao_agendamento` (`id`, `agendamento_id`, `rota_id`, `data_hora_inicio_real`, `data_hora_fim_real`, `estado_execucao`, `observacoes_tecnico`) VALUES
	(1, 1001, NULL, '2026-09-15 10:00:00', '2026-09-15 11:15:00', 'concluido', 'Serviço concluído sem observações.'),
	(2, 1002, NULL, '2026-09-22 15:00:00', '2026-09-22 16:00:00', 'concluido', 'Cliente pediu ajuste de tom.'),
	(3, 1003, NULL, '2026-09-29 09:35:00', '2026-09-29 11:35:00', 'concluido', 'Rota cumprida à hora prevista.'),
	(4, 1004, NULL, '2026-10-03 11:00:00', '2026-10-03 11:30:00', 'concluido', NULL),
	(5, 1005, 502, NULL, NULL, 'em_curso', NULL),
	(6, 1006, 502, NULL, NULL, 'em_curso', NULL);
-- Dumping structure for table secade_beauty.fecho_caixa_diario
DROP TABLE IF EXISTS `fecho_caixa_diario`;
CREATE TABLE IF NOT EXISTS `fecho_caixa_diario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  `funcionario_id` int NOT NULL,
  `total_esperado_faturas` decimal(10,2) DEFAULT '0.00',
  `total_recolhido_campo` decimal(10,2) DEFAULT '0.00',
  `diferenca` decimal(10,2) DEFAULT '0.00',
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_fecho_funcionario` (`funcionario_id`),
  CONSTRAINT `fk_fecho_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.fecho_caixa_diario: ~0 rows (approximately)
DELETE FROM `fecho_caixa_diario`;
-- Dumping structure for table secade_beauty.feedback_cliente
DROP TABLE IF EXISTS `feedback_cliente`;
CREATE TABLE IF NOT EXISTS `feedback_cliente` (
  `id` int NOT NULL AUTO_INCREMENT,
  `execucao_agendamento_id` int NOT NULL,
  `classificacao_estrelas` int DEFAULT NULL,
  `comentario` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `data_feedback` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `execucao_agendamento_id` (`execucao_agendamento_id`),
  CONSTRAINT `fk_feedback_execucao` FOREIGN KEY (`execucao_agendamento_id`) REFERENCES `execucao_agendamento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feedback_cliente_chk_1` CHECK ((`classificacao_estrelas` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.feedback_cliente: ~2 rows (approximately)
DELETE FROM `feedback_cliente`;
INSERT INTO `feedback_cliente` (`id`, `execucao_agendamento_id`, `classificacao_estrelas`, `comentario`, `data_feedback`) VALUES
	(49, 1, 5, 'Excelente atendimento, muito profissional.', '2026-10-06 14:49:34'),
	(50, 2, 4, 'Gostei muito do resultado.', '2026-10-06 14:49:34');
-- Dumping structure for table secade_beauty.fornecedor
DROP TABLE IF EXISTS `fornecedor`;
CREATE TABLE IF NOT EXISTS `fornecedor` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nif` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telemovel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.fornecedor: ~43 rows (approximately)
DELETE FROM `fornecedor`;
INSERT INTO `fornecedor` (`id`, `nome`, `nif`, `email`, `telemovel`, `ativo`, `observacoes`, `criado_em`) VALUES
	(1, 'Stand Virtual', '508069491', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(2, 'Worten', '503630330', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(3, 'Leroy-Merlin', '506848558', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(4, 'AOSOM', '980683386', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(5, 'Staples', '503789372', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(6, 'Beleza 37', '514749636', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(7, 'Extintores online', '518352080', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(8, 'Continente', '502011475', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(9, 'Logo seguros', '508278600', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(10, 'Generali', '500940231', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(11, 'OK! Seguros', '504011944', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(12, 'Fidelidade', '500918880', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(13, 'Liberty', '500068658', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(14, 'SK pro Med Beauty Solutions', '508161320', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(15, 'Primor', '980663695', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(16, 'Pluri cosmética', '503890278', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(17, 'AfroQueen', '516416081', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(18, 'Temu', NULL, NULL, NULL, 1, 'NP (não possui) NIF', '2026-10-03 22:41:30'),
	(19, 'IKEA', '505416654', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(20, 'Lusini', '517386402', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(21, 'DRUNI', '518530752', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(22, 'Wells', '508037514', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(23, 'Baber tools profissional', '589053345', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(24, 'Ideal Cosméticos', '514239085', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(25, 'Barberalia', '516206370', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(26, 'Bandido Portugal.pt', NULL, NULL, NULL, 1, 'NIF indisponível nas plataformas digitais', '2026-10-03 22:41:30'),
	(27, 'ViceDeal.com', NULL, NULL, NULL, 1, 'NP (não possui) NIF', '2026-10-03 22:41:30'),
	(28, 'Casa do Barbeiro', '501766448', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(29, 'Espaço barbeiro', '510427103', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(30, 'Aliexpress', NULL, NULL, NULL, 1, 'NP (não possui) NIF', '2026-10-03 22:41:30'),
	(31, 'Município de Évora', '504828576', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(32, 'Endesa', '508855950', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(33, 'MEO', '504615947', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(34, 'Galp', '505060515', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(35, 'Repsol', '500246963', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(36, 'Manuel jacinto (renda)', '320491366', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(37, 'Banco CTT', '513412417', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(38, 'Consumíveis', NULL, NULL, NULL, 1, 'NP (não possui) NIF', '2026-10-03 22:41:30'),
	(39, 'Norauto', '503629995', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(40, 'Gestévora', '500785708', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(41, 'Manuel jacinto (renda)', '285284240', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(42, 'Stela Cristina', '200846922', NULL, NULL, 1, NULL, '2026-10-03 22:41:30'),
	(43, 'Moloni', '513321527', NULL, NULL, 1, NULL, '2026-10-03 22:41:30');
-- Dumping structure for table secade_beauty.funcionario
DROP TABLE IF EXISTS `funcionario`;
CREATE TABLE IF NOT EXISTS `funcionario` (
  `id` int NOT NULL,
  `tipo_contrato` enum('efetivo_contratado','recibo_verde') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `percentagem_comissao` decimal(5,2) NOT NULL DEFAULT '0.00',
  `salario_base` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cc` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_funcionario_utilizador` FOREIGN KEY (`id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.funcionario: ~1 rows (approximately)
DELETE FROM `funcionario`;
INSERT INTO `funcionario` (`id`, `tipo_contrato`, `percentagem_comissao`, `salario_base`, `cc`, `ativo`) VALUES
	(2, 'recibo_verde', 70.00, 900.00, '999999990Z7R', 1);
-- Dumping structure for table secade_beauty.gorjeta
DROP TABLE IF EXISTS `gorjeta`;
CREATE TABLE IF NOT EXISTS `gorjeta` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agendamento_id` int NOT NULL,
  `funcionario_id` int NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `data_registo` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_gorjeta_agendamento` (`agendamento_id`),
  KEY `fk_gorjeta_funcionario` (`funcionario_id`),
  CONSTRAINT `fk_gorjeta_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gorjeta_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.gorjeta: ~0 rows (approximately)
DELETE FROM `gorjeta`;
-- Dumping structure for table secade_beauty.manutencao_execucao
DROP TABLE IF EXISTS `manutencao_execucao`;
CREATE TABLE IF NOT EXISTS `manutencao_execucao` (
  `chave` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `executado_em` datetime NOT NULL,
  PRIMARY KEY (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.manutencao_execucao: ~0 rows (approximately)
DELETE FROM `manutencao_execucao`;
-- Dumping structure for table secade_beauty.matriz_deslocacao
DROP TABLE IF EXISTS `matriz_deslocacao`;
CREATE TABLE IF NOT EXISTS `matriz_deslocacao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `base_partida_id` int NOT NULL,
  `cidade_id` int NOT NULL,
  `distancia_km` decimal(8,2) NOT NULL DEFAULT '0.00',
  `tempo_estimado_minutos` int NOT NULL DEFAULT '0',
  `custo_estimado_combustivel` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_base_cidade` (`base_partida_id`,`cidade_id`),
  KEY `fk_matriz_cidade` (`cidade_id`),
  CONSTRAINT `fk_matriz_base` FOREIGN KEY (`base_partida_id`) REFERENCES `base_partida` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_matriz_cidade` FOREIGN KEY (`cidade_id`) REFERENCES `cidade` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.matriz_deslocacao: ~10 rows (approximately)
DELETE FROM `matriz_deslocacao`;
INSERT INTO `matriz_deslocacao` (`id`, `base_partida_id`, `cidade_id`, `distancia_km`, `tempo_estimado_minutos`, `custo_estimado_combustivel`) VALUES
	(1, 1, 1, 45.00, 40, 6.75),
	(2, 1, 2, 60.00, 50, 8.94),
	(3, 1, 3, 60.00, 55, 8.94),
	(4, 1, 4, 80.00, 70, 11.95),
	(5, 1, 5, 75.00, 65, 11.22),
	(6, 1, 6, 110.00, 80, 16.42),
	(7, 1, 7, 95.00, 70, 14.23),
	(8, 1, 8, 115.00, 85, 17.24),
	(9, 1, 9, 110.00, 90, 16.42),
	(10, 1, 10, 5.00, 5, 0.75);
-- Dumping structure for table secade_beauty.notificacao
DROP TABLE IF EXISTS `notificacao`;
CREATE TABLE IF NOT EXISTS `notificacao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilizador_id` int NOT NULL,
  `tipo` enum('agendamento_confirmado','agendamento_recusado','agendamento_cancelado','lembrete_24h','logistica','sistema') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lida` tinyint(1) DEFAULT '0',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notificacao_utilizador` (`utilizador_id`),
  CONSTRAINT `fk_notificacao_utilizador` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.notificacao: ~3 rows (approximately)
DELETE FROM `notificacao`;
INSERT INTO `notificacao` (`id`, `utilizador_id`, `tipo`, `mensagem`, `lida`, `criado_em`) VALUES
	(14, 104, 'agendamento_confirmado', 'Agendamento #1005 confirmado para 14/10/2026.', 0, '2026-10-06 14:49:34'),
	(15, 104, 'lembrete_24h', 'Agendamento #1005 é a 14/10/2026 09:30. Se precisar de alterar, temos disponibilidade em: 21/10/2026, 28/10/2026.', 0, '2026-10-06 14:49:34'),
	(16, 108, 'agendamento_cancelado', 'Agendamento #1009 cancelado. O horário voltou a ficar disponível.', 0, '2026-10-06 14:49:34');
-- Dumping structure for table secade_beauty.obrigacao_fiscal
DROP TABLE IF EXISTS `obrigacao_fiscal`;
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
) ENGINE=InnoDB AUTO_INCREMENT=87 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.obrigacao_fiscal: ~5 rows (approximately)
DELETE FROM `obrigacao_fiscal`;
INSERT INTO `obrigacao_fiscal` (`id`, `tipo`, `designacao`, `periodicidade`, `valor_estimado`, `data_prazo`, `estado`, `data_pagamento`, `observacoes`, `criado_em`) VALUES
	(181, 'iva', 'IVA HTTP (teste)', 'trimestral', 500.00, '2026-10-09', 'pago', '2026-10-06', NULL, '2026-10-06 14:42:44'),
	(182, 'iva', 'IVA do 3.º trimestre', 'trimestral', 1840.00, '2026-11-20', 'pendente', NULL, 'Declaração periódica trimestral.', '2026-10-06 14:49:35'),
	(183, 'irc', 'IRC — pagamento por conta', 'trimestral', 610.00, '2026-12-15', 'pendente', NULL, '3.º pagamento por conta.', '2026-10-06 14:49:35'),
	(184, 'seguranca_social', 'Segurança Social — novembro', 'mensal', 520.00, '2026-11-20', 'pendente', NULL, 'Contribuições da equipa.', '2026-10-06 14:49:35'),
	(185, 'seguros', 'Seguro de responsabilidade civil', 'anual', 395.00, '2026-12-31', 'pendente', NULL, 'Apólice anual do espaço e da carrinha.', '2026-10-06 14:49:35');
-- Dumping structure for table secade_beauty.rota_ambulante
DROP TABLE IF EXISTS `rota_ambulante`;
CREATE TABLE IF NOT EXISTS `rota_ambulante` (
  `id` int NOT NULL AUTO_INCREMENT,
  `data_rota` date NOT NULL,
  `base_partida_id` int NOT NULL,
  `cidade_id` int NOT NULL,
  `estado_rota` enum('planeada','aprovada','recusada','em_execucao','concluida') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'planeada',
  `custo_estimado_combustivel` decimal(10,2) DEFAULT '0.00',
  `quota_parte_cliente` decimal(10,2) DEFAULT '0.00',
  `lucro_servicos` decimal(10,2) DEFAULT '0.00',
  `lucro_total` decimal(10,2) DEFAULT '0.00',
  `decidido_por` int DEFAULT NULL,
  `decidido_em` datetime DEFAULT NULL,
  `observacoes_decisao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_rota_base` (`base_partida_id`),
  KEY `fk_rota_cidade` (`cidade_id`),
  KEY `fk_rota_decidido_por` (`decidido_por`),
  CONSTRAINT `fk_rota_base` FOREIGN KEY (`base_partida_id`) REFERENCES `base_partida` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rota_cidade` FOREIGN KEY (`cidade_id`) REFERENCES `cidade` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_rota_decidido_por` FOREIGN KEY (`decidido_por`) REFERENCES `utilizador` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.rota_ambulante: ~2 rows (approximately)
DELETE FROM `rota_ambulante`;
INSERT INTO `rota_ambulante` (`id`, `data_rota`, `base_partida_id`, `cidade_id`, `estado_rota`, `custo_estimado_combustivel`, `quota_parte_cliente`, `lucro_servicos`, `lucro_total`, `decidido_por`, `decidido_em`, `observacoes_decisao`) VALUES
	(501, '2026-09-29', 1, 10, 'concluida', 12.50, 0.00, 40.65, 28.15, 1, '2026-09-27 18:20:00', 'Rota aprovada e cumprida.'),
	(502, '2026-10-14', 1, 2, 'aprovada', 14.20, 52.85, 0.00, 38.65, 1, '2026-10-12 19:30:00', 'Dia com procura suficiente; aprovada.');
-- Dumping structure for table secade_beauty.rota_funcionario
DROP TABLE IF EXISTS `rota_funcionario`;
CREATE TABLE IF NOT EXISTS `rota_funcionario` (
  `rota_id` int NOT NULL,
  `funcionario_id` int NOT NULL,
  PRIMARY KEY (`rota_id`,`funcionario_id`),
  KEY `fk_rota_func_funcionario` (`funcionario_id`),
  CONSTRAINT `fk_rota_func_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rota_func_rota` FOREIGN KEY (`rota_id`) REFERENCES `rota_ambulante` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.rota_funcionario: ~1 rows (approximately)
DELETE FROM `rota_funcionario`;
INSERT INTO `rota_funcionario` (`rota_id`, `funcionario_id`) VALUES
	(502, 2);
-- Dumping structure for table secade_beauty.servico
DROP TABLE IF EXISTS `servico`;
CREATE TABLE IF NOT EXISTS `servico` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria_id` int NOT NULL,
  `duracao_estimada_minutos` int NOT NULL,
  `preco_base` decimal(10,2) NOT NULL,
  `requer_espaco_fisico` tinyint(1) DEFAULT '0',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_servico_categoria` (`categoria_id`),
  CONSTRAINT `fk_servico_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categoria_servico` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.servico: ~35 rows (approximately)
DELETE FROM `servico`;
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
-- Dumping structure for table secade_beauty.servico_foto
DROP TABLE IF EXISTS `servico_foto`;
CREATE TABLE IF NOT EXISTS `servico_foto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `servico_id` int NOT NULL,
  `url_foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `destaque` tinyint(1) DEFAULT '0',
  `ordem_exibicao` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_servico_foto_servico` (`servico_id`),
  CONSTRAINT `fk_servico_foto_servico` FOREIGN KEY (`servico_id`) REFERENCES `servico` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.servico_foto: ~23 rows (approximately)
DELETE FROM `servico_foto`;
INSERT INTO `servico_foto` (`id`, `servico_id`, `url_foto`, `destaque`, `ordem_exibicao`) VALUES
	(1, 1, 'uploads/services/service-1.jpg', 1, 0),
	(2, 2, 'uploads/services/service-2.jpg', 1, 0),
	(3, 3, 'uploads/services/service-3.jpg', 1, 0),
	(4, 4, 'uploads/services/service-4.jpg', 1, 0),
	(5, 5, 'uploads/services/service-5.jpg', 1, 0),
	(6, 6, 'uploads/services/service-6.jpg', 1, 0),
	(7, 7, 'uploads/services/service-7.jpg', 1, 0),
	(8, 8, 'uploads/services/service-8.png', 1, 0),
	(9, 9, 'uploads/services/service-9.jpg', 1, 0),
	(10, 10, 'uploads/services/service-10.jpg', 1, 0),
	(11, 11, 'uploads/services/service-11.jpg', 1, 0),
	(12, 12, 'uploads/services/service-12.jpg', 1, 0),
	(13, 13, 'uploads/services/service-13.jpg', 1, 0),
	(14, 14, 'uploads/services/service-14.jpg', 1, 0),
	(15, 15, 'uploads/services/service-15.jpg', 1, 0),
	(16, 16, 'uploads/services/service-16.jpg', 1, 0),
	(17, 17, 'uploads/services/service-17.jpg', 1, 0),
	(18, 18, 'uploads/services/service-18.jpg', 1, 0),
	(19, 19, 'uploads/services/service-19.jpg', 1, 0),
	(20, 20, 'uploads/services/service-20.jpg', 1, 0),
	(21, 21, 'uploads/services/service-21.jpg', 1, 0),
	(22, 22, 'uploads/services/service-22.jpg', 1, 0),
	(23, 23, 'uploads/services/service-23.jpg', 1, 0);
-- Dumping structure for table secade_beauty.servico_local
DROP TABLE IF EXISTS `servico_local`;
CREATE TABLE IF NOT EXISTS `servico_local` (
  `id` int NOT NULL AUTO_INCREMENT,
  `servico_id` int NOT NULL,
  `tipo_local` enum('loja_fisica','carrinha_ambulante') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `disponivel` tinyint(1) DEFAULT '1',
  `preco_especifico` decimal(10,2) DEFAULT NULL,
  `ajuste_logistico` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_servico_local_servico` (`servico_id`),
  CONSTRAINT `fk_servico_local_servico` FOREIGN KEY (`servico_id`) REFERENCES `servico` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.servico_local: ~0 rows (approximately)
DELETE FROM `servico_local`;
-- Dumping structure for table secade_beauty.transacao_financeira
DROP TABLE IF EXISTS `transacao_financeira`;
CREATE TABLE IF NOT EXISTS `transacao_financeira` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agendamento_id` int NOT NULL,
  `funcionario_id` int NOT NULL,
  `metodo_pagamento` enum('numerario_dinheiro','mb_way','multibanco_pos') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_transacao` enum('sinal_inicial','restante_90_porcento','pagamento_integral','quota_parte_deslocacao') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `estado_offline` tinyint(1) DEFAULT '0',
  `recibo_manual_numero` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_transacao` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_transacao_agendamento` (`agendamento_id`),
  KEY `fk_transacao_funcionario` (`funcionario_id`),
  CONSTRAINT `fk_transacao_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transacao_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.transacao_financeira: ~0 rows (approximately)
DELETE FROM `transacao_financeira`;
-- Dumping structure for table secade_beauty.utilizador
DROP TABLE IF EXISTS `utilizador`;
CREATE TABLE IF NOT EXISTS `utilizador` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telemovel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nif` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_perfil` enum('cliente','funcionario','gestor') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=165 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.utilizador: ~69 rows (approximately)
DELETE FROM `utilizador`;
INSERT INTO `utilizador` (`id`, `nome`, `email`, `password_hash`, `telemovel`, `nif`, `tipo_perfil`, `foto`, `criado_em`) VALUES
	(1, 'Gestor Secade', 'gestor@secade.pt', '$2y$10$DX3o9mDbE2M00tUg21KO1OOtvl7SGpPvhNZ4SFnYBXHNetwcdt0F6', '+351911111111', '123456789', 'gestor', NULL, '2026-09-21 22:27:40'),
	(2, 'Ana Técnica', 'funcionario@secade.pt', '$2y$10$D22wXVD90rZoILW3X3QalOTr1mb3dSz8IxR5eIbulvc0jN0Eitvta', '+351922222222', '298765438', 'funcionario', NULL, '2026-09-21 22:27:40'),
	(3, 'João Cliente', 'cliente@teste.pt', '$2y$10$NRQDxzU490d8RvpmHVmLb.6AnC74Pds1ozKySPI85.1801rGGDWSS', '+351933333333', '345678915', 'cliente', NULL, '2026-09-21 22:27:40'),
	(53, 'Daniel Branco', 'hb.daniel@gmail.com', '$2y$10$g95ZP3MI6AsHPIoPuvddk.VEoEKqUONmvT/XVLkPTQoIs8raP4YVu', '923456789', NULL, 'cliente', NULL, '2026-09-22 12:59:11'),
	(100, 'Leonor Santos', 'leonor.santos.251989518@cliente.secade.local', '*', '', '251989518', 'cliente', NULL, '2026-10-03 22:41:30'),
	(101, 'Catarina Pixoto', 'catarina.pixoto.298784076@cliente.secade.local', '*', '', '298784076', 'cliente', NULL, '2026-10-03 22:41:30'),
	(102, 'Elvisson Daniel', 'elvisson.daniel.278561519@cliente.secade.local', '*', '', '278561519', 'cliente', NULL, '2026-10-03 22:41:30'),
	(103, 'Leandro Silva', 'leandro.silva.214056686@cliente.secade.local', '*', '', '214056686', 'cliente', NULL, '2026-10-03 22:41:30'),
	(104, 'Tomásia Contreiras', 'tom.asia.contreiras.227748204@cliente.secade.local', '*', '', '227748204', 'cliente', NULL, '2026-10-03 22:41:30'),
	(105, 'Joaquina Matos', 'joaquina.matos.235820776@cliente.secade.local', '*', '', '235820776', 'cliente', NULL, '2026-10-03 22:41:30'),
	(106, 'Lídia Correia', 'l.idia.correia.201303140@cliente.secade.local', '*', '', '201303140', 'cliente', NULL, '2026-10-03 22:41:30'),
	(107, 'Emanuela Vigílio', 'emanuela.vig.ilio.213916819@cliente.secade.local', '*', '', '213916819', 'cliente', NULL, '2026-10-03 22:41:30'),
	(108, 'Augusta Contente', 'augusta.contente.240660501@cliente.secade.local', '*', '', '240660501', 'cliente', NULL, '2026-10-03 22:41:30'),
	(109, 'Laura Vanuza', 'laura.vanuza.235159026@cliente.secade.local', '*', '', '235159026', 'cliente', NULL, '2026-10-03 22:41:30'),
	(110, 'Florentino Rosa', 'florentino.rosa.278084737@cliente.secade.local', '*', '', '278084737', 'cliente', NULL, '2026-10-03 22:41:30'),
	(111, 'Beatriz Baessa', 'beatriz.baessa.232443777@cliente.secade.local', '*', '', '232443777', 'cliente', NULL, '2026-10-03 22:41:30'),
	(112, 'Tiago Fortunato', 'tiago.fortunato.214803155@cliente.secade.local', '*', '', '214803155', 'cliente', NULL, '2026-10-03 22:41:30'),
	(113, 'Elias Patente', 'elias.patente.243363010@cliente.secade.local', '*', '', '243363010', 'cliente', NULL, '2026-10-03 22:41:30'),
	(114, 'Partrícia Gomes', 'partr.icia.gomes.274907810@cliente.secade.local', '*', '', '274907810', 'cliente', NULL, '2026-10-03 22:41:30'),
	(115, 'Alexanda Pinto', 'alexanda.pinto.261788396@cliente.secade.local', '*', '', '261788396', 'cliente', NULL, '2026-10-03 22:41:30'),
	(116, 'Maria Luana', 'maria.luana.270325492@cliente.secade.local', '*', '', '270325492', 'cliente', NULL, '2026-10-03 22:41:30'),
	(117, 'Rena Nunes', 'rena.nunes.259969931@cliente.secade.local', '*', '', '259969931', 'cliente', NULL, '2026-10-03 22:41:30'),
	(118, 'Vanessa Cardoso', 'vanessa.cardoso.204914663@cliente.secade.local', '*', '', '204914663', 'cliente', NULL, '2026-10-03 22:41:30'),
	(119, 'Tiago Alves', 'tiago.alves.272698679@cliente.secade.local', '*', '', '272698679', 'cliente', NULL, '2026-10-03 22:41:30'),
	(120, 'Maria Pedro', 'maria.pedro.229658660@cliente.secade.local', '*', '', '229658660', 'cliente', NULL, '2026-10-03 22:41:30'),
	(121, 'Luísa Evidência', 'lu.isa.evid.encia.289524121@cliente.secade.local', '*', '', '289524121', 'cliente', NULL, '2026-10-03 22:41:30'),
	(122, 'Bianca Brico', 'bianca.brico.273711148@cliente.secade.local', '*', '', '273711148', 'cliente', NULL, '2026-10-03 22:41:30'),
	(123, 'Amanda Alves', 'amanda.alves.265052955@cliente.secade.local', '*', '', '265052955', 'cliente', NULL, '2026-10-03 22:41:30'),
	(124, 'Patrícia Freitas', 'patr.icia.freitas.279336055@cliente.secade.local', '*', '', '279336055', 'cliente', NULL, '2026-10-03 22:41:30'),
	(125, 'Beatriz Oliveira', 'beatriz.oliveira.207109176@cliente.secade.local', '*', '', '207109176', 'cliente', NULL, '2026-10-03 22:41:30'),
	(126, 'Lúcia Camarada', 'l.ucia.camarada.299502309@cliente.secade.local', '*', '', '299502309', 'cliente', NULL, '2026-10-03 22:41:30'),
	(127, 'Vivalda Libolo', 'vivalda.libolo.254824498@cliente.secade.local', '*', '', '254824498', 'cliente', NULL, '2026-10-03 22:41:30'),
	(128, 'Ricardo Sebastião', 'ricardo.sebasti.ao.217579906@cliente.secade.local', '*', '', '217579906', 'cliente', NULL, '2026-10-03 22:41:30'),
	(129, 'Beatriz Gonçalves', 'beatriz.goncalves.219082081@cliente.secade.local', '*', '', '219082081', 'cliente', NULL, '2026-10-03 22:41:30'),
	(130, 'Sónia Silva', 's.onia.silva.299763374@cliente.secade.local', '*', '', '299763374', 'cliente', NULL, '2026-10-03 22:41:30'),
	(131, 'Marta Gonçalves', 'marta.goncalves.250174332@cliente.secade.local', '*', '', '250174332', 'cliente', NULL, '2026-10-03 22:41:30'),
	(132, 'Joana Silva', 'joana.silva.248171240@cliente.secade.local', '*', '', '248171240', 'cliente', NULL, '2026-10-03 22:41:30'),
	(133, 'Sílvia Almeida', 's.ilvia.almeida.295931540@cliente.secade.local', '*', '', '295931540', 'cliente', NULL, '2026-10-03 22:41:30'),
	(134, 'Rita Costa', 'rita.costa.252554620@cliente.secade.local', '*', '', '252554620', 'cliente', NULL, '2026-10-03 22:41:30'),
	(135, 'Teresa Correia', 'teresa.correia.275536394@cliente.secade.local', '*', '', '275536394', 'cliente', NULL, '2026-10-03 22:41:30'),
	(136, 'Teresa Cardoso', 'teresa.cardoso.231413882@cliente.secade.local', '*', '', '231413882', 'cliente', NULL, '2026-10-03 22:41:30'),
	(137, 'Claúdia Marques', 'cla.udia.marques.275469727@cliente.secade.local', '*', '', '275469727', 'cliente', NULL, '2026-10-03 22:41:30'),
	(138, 'Sílvia Gonçalves', 's.ilvia.goncalves.228145651@cliente.secade.local', '*', '', '228145651', 'cliente', NULL, '2026-10-03 22:41:30'),
	(139, 'Carla Morauto', 'carla.morauto.261207890@cliente.secade.local', '*', '', '261207890', 'cliente', NULL, '2026-10-03 22:41:30'),
	(140, 'Patrícia Costa', 'patr.icia.costa.287024350@cliente.secade.local', '*', '', '287024350', 'cliente', NULL, '2026-10-03 22:41:30'),
	(141, 'Joana Pereira', 'joana.pereira.258735082@cliente.secade.local', '*', '', '258735082', 'cliente', NULL, '2026-10-03 22:41:30'),
	(142, 'Filipa Costa', 'filipa.costa.244808929@cliente.secade.local', '*', '', '244808929', 'cliente', NULL, '2026-10-03 22:41:30'),
	(143, 'Joana Pinto', 'joana.pinto.264883268@cliente.secade.local', '*', '', '264883268', 'cliente', NULL, '2026-10-03 22:41:30'),
	(144, 'Daniela Cardoso', 'daniela.cardoso.254736130@cliente.secade.local', '*', '', '254736130', 'cliente', NULL, '2026-10-03 22:41:30'),
	(145, 'Catarina Correia', 'catarina.correia.277691400@cliente.secade.local', '*', '', '277691400', 'cliente', NULL, '2026-10-03 22:41:30'),
	(146, 'Catarina Lopes', 'catarina.lopes.234560657@cliente.secade.local', '*', '', '234560657', 'cliente', NULL, '2026-10-03 22:41:30'),
	(147, 'Sónia Fenandes', 's.onia.fenandes.269836870@cliente.secade.local', '*', '', '269836870', 'cliente', NULL, '2026-10-03 22:41:30'),
	(148, 'Margarida Santos', 'margarida.santos.232060649@cliente.secade.local', '*', '', '232060649', 'cliente', NULL, '2026-10-03 22:41:30'),
	(149, 'Rita Pereira', 'rita.pereira.243526512@cliente.secade.local', '*', '', '243526512', 'cliente', NULL, '2026-10-03 22:41:30'),
	(150, 'Inês Mendes', 'in.es.mendes.203624513@cliente.secade.local', '*', '', '203624513', 'cliente', NULL, '2026-10-03 22:41:30'),
	(151, 'Vera Costa', 'vera.costa.245056220@cliente.secade.local', '*', '', '245056220', 'cliente', NULL, '2026-10-03 22:41:30'),
	(152, 'Filipa Santos', 'filipa.santos.281021970@cliente.secade.local', '*', '', '281021970', 'cliente', NULL, '2026-10-03 22:41:30'),
	(153, 'Marta Rodrigues', 'marta.rodrigues.248193848@cliente.secade.local', '*', '', '248193848', 'cliente', NULL, '2026-10-03 22:41:30'),
	(154, 'Marta Almeida', 'marta.almeida.241834961@cliente.secade.local', '*', '', '241834961', 'cliente', NULL, '2026-10-03 22:41:30'),
	(155, 'Sílvia Ferreira', 's.ilvia.ferreira.299343049@cliente.secade.local', '*', '', '299343049', 'cliente', NULL, '2026-10-03 22:41:30'),
	(156, 'Rita Jesus', 'rita.jesus.291337392@cliente.secade.local', '*', '', '291337392', 'cliente', NULL, '2026-10-03 22:41:30'),
	(157, 'Maria Ferreira', 'maria.ferreira.250412365@cliente.secade.local', '*', '', '250412365', 'cliente', NULL, '2026-10-03 22:41:30'),
	(158, 'Margarida Gonçalves', 'margarida.goncalves.211275859@cliente.secade.local', '*', '', '211275859', 'cliente', NULL, '2026-10-03 22:41:30'),
	(159, 'Daniela Ribeiro', 'daniela.ribeiro.221393366@cliente.secade.local', '*', '', '221393366', 'cliente', NULL, '2026-10-03 22:41:30'),
	(160, 'Daniela Oliveira', 'daniela.oliveira.266255264@cliente.secade.local', '*', '', '266255264', 'cliente', NULL, '2026-10-03 22:41:30'),
	(161, 'Ana Lopes', 'ana.lopes.255884117@cliente.secade.local', '*', '', '255884117', 'cliente', NULL, '2026-10-03 22:41:30'),
	(162, 'Teresa Lopes', 'teresa.lopes.259591696@cliente.secade.local', '*', '', '259591696', 'cliente', NULL, '2026-10-03 22:41:30'),
	(163, 'Inês Lopes', 'in.es.lopes.261930966@cliente.secade.local', '*', '', '261930966', 'cliente', NULL, '2026-10-03 22:41:30'),
	(164, 'Daniela Jesus', 'daniela.jesus.273038273@cliente.secade.local', '*', '', '273038273', 'cliente', NULL, '2026-10-03 22:41:30');
