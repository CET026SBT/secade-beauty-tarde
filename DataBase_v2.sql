-- --------------------------------------------------------
-- SECADE BEAUTY - Base de Dados v2.0 (alinhada com especificacao_mvp.md)
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- --------------------------------------------------------
-- ALTERAÇÕES face a DataBase.sql v1:
--   1. agendamento.estado_reserva  -> nova máquina de estados (loja + ambulatório)
--   2. agendamento_pessoa (NOVA)   -> estrutura por pessoa (ambulatório)
--   3. agendamento_servico         -> agendamento_pessoa_id, estado_aceitacao, recibos verdes
--   4. config_recibo_verde (NOVA)  -> percentagens configuráveis do simulador (70/30)
--   5. rota_ambulante              -> estados aprovada/recusada (decisão manual), quota-parte,
--                                     lucro dos serviços, lucro total, auditoria da decisão
--   6. obrigacao_fiscal (NOVA)     -> calendário fiscal (IVA, IRC, SS, Seguros)
--   7. alerta_fiscal (NOVA)        -> alertas progressivos 30/15/7/3/1 dia + em atraso
--   8. transacao_financeira        -> novo tipo 'quota_parte_deslocacao'
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
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.utilizador: ~0 rows (approximately)
DELETE FROM `utilizador`;

-- Dumping structure for table secade_beauty.cliente
DROP TABLE IF EXISTS `cliente`;
CREATE TABLE IF NOT EXISTS `cliente` (
  `id` int NOT NULL,
  `telemovel_validado_otp` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_cliente_utilizador` FOREIGN KEY (`id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.cliente: ~0 rows (approximately)
DELETE FROM `cliente`;

-- Dumping structure for table secade_beauty.funcionario
DROP TABLE IF EXISTS `funcionario`;
CREATE TABLE IF NOT EXISTS `funcionario` (
  `id` int NOT NULL,
  `tipo_contrato` enum('efetivo_contratado','recibo_verde') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `salario_base` decimal(10,2) NOT NULL,
  `cc` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_funcionario_utilizador` FOREIGN KEY (`id`) REFERENCES `utilizador` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.funcionario: ~0 rows (approximately)
DELETE FROM `funcionario`;

-- NOTA (v3.0): tabela `funcionario_categoria` REMOVIDA.
--   As categorias sempre foram apenas filtros/agrupadores visuais (especificacao_mvp.md
--   v3.0) e a relação N:N nunca era LIDA por nenhum caminho de código:
--     - o filtro do backoffice usa `categoria_profissional` completa (CategoryRepository);
--     - a filtragem de serviços pendentes usa `servico.categoria_id`.
--   Era apenas uma escrita órfã (EmployeeService::createEmployee), sem UI que a alimentasse.
--   Ver database_migration_v3.sql (passo 3) para a remoção em BDs existentes.

-- Dumping structure for table secade_beauty.categoria_profissional
DROP TABLE IF EXISTS `categoria_profissional`;
CREATE TABLE IF NOT EXISTS `categoria_profissional` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.categoria_profissional: ~3 rows (approximately)
DELETE FROM `categoria_profissional`;
INSERT INTO `categoria_profissional` (`id`, `nome`, `descricao`) VALUES
	(1, 'Cabelereiro', 'Tranças e Penteados'),
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=UTF8MB4_UNICODE_CI;

-- Dumping data for table secade_beauty.cliente_morada: ~0 rows (approximately)
DELETE FROM `cliente_morada`;

-- Dumping structure for table secade_beauty.base_partida
DROP TABLE IF EXISTS `base_partida`;
CREATE TABLE IF NOT EXISTS `base_partida` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `morada` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.base_partida: ~0 rows (approximately)
DELETE FROM `base_partida`;
INSERT INTO `base_partida` (`id`, `nome`, `morada`) VALUES
	(1, 'Évora', 'Rua do Centro de Formação');

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

-- Dumping data for table secade_beauty.matriz_deslocacao: ~9 rows (approximately)
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
	(9, 1, 9, 110.00, 90, 16.42);

-- Dumping structure for table secade_beauty.agendamento
-- estado_reserva (v3.0):
--   LOJA:        criado com 'pendente_validacao_logistica_loja' (serviços auto-aceites)
--   AMBULATÓRIO: criado com 'pendente_aceitacao_funcionarios'
--             -> 'totalmente_aceite_funcionarios' (último serviço aceite; bloqueia janela temporal)
--             -> 'confirmado' (gestor aprova rota) | 'recusado'/'cancelado' (gestor recusa rota)
DROP TABLE IF EXISTS `agendamento`;
CREATE TABLE IF NOT EXISTS `agendamento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `cliente_morada_id` int DEFAULT NULL,
  `local_prestacao` enum('loja_fisica','carrinha_ambulante') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_hora_pretendida` datetime NOT NULL,
  `estado_reserva` enum('pendente_aceitacao_funcionarios','pendente_validacao_logistica_loja','totalmente_aceite_funcionarios','confirmado','recusado','cancelado','executado','concluido') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pendente_aceitacao_funcionarios',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.agendamento: ~0 rows (approximately)
DELETE FROM `agendamento`;

-- Dumping structure for table secade_beauty.agendamento_pessoa (NOVA - v3.0)
-- Agrupador logístico/visual "Pessoa 1, Pessoa 2, ..." exclusivo do ambulatório.
-- Várias pessoas podem partilhar os mesmos serviços (registos separados em agendamento_servico).
-- Permite estimar a duração total do slot no terreno.
DROP TABLE IF EXISTS `agendamento_pessoa`;
CREATE TABLE IF NOT EXISTS `agendamento_pessoa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agendamento_id` int NOT NULL,
  `nome_pessoa` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `fk_agend_pessoa_agendamento` (`agendamento_id`),
  CONSTRAINT `fk_agend_pessoa_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.agendamento_pessoa: ~0 rows (approximately)
DELETE FROM `agendamento_pessoa`;

-- Dumping structure for table secade_beauty.agendamento_servico
-- v3.0: um registo por (agendamento, pessoa, serviço) no ambulatório.
--   agendamento_pessoa_id: NULL na loja (sem estrutura por pessoa).
--   estado_aceitacao:      'aceite' por defeito (loja = aceitação automática);
--                          ambulatório cria com 'pendente' e o funcionário aceita individualmente.
--   Simulador Recibos Verdes: percentagens registadas no momento da aceitação.
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
  `valor_recibo_verde_funcionario` decimal(10,2) DEFAULT NULL,
  `valor_recibo_verde_plataforma` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_agend_serv_agendamento` (`agendamento_id`),
  KEY `fk_agend_serv_pessoa` (`agendamento_pessoa_id`),
  KEY `fk_agend_serv_servico` (`servico_id`),
  KEY `fk_agend_serv_funcionario` (`funcionario_id`),
  CONSTRAINT `fk_agend_serv_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agend_serv_pessoa` FOREIGN KEY (`agendamento_pessoa_id`) REFERENCES `agendamento_pessoa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_agend_serv_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_agend_serv_servico` FOREIGN KEY (`servico_id`) REFERENCES `servico` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.agendamento_servico: ~0 rows (approximately)
DELETE FROM `agendamento_servico`;

-- Dumping structure for table secade_beauty.config_recibo_verde (NOVA - v3.0)
-- Percentagens do Simulador de Recibos Verdes, configuráveis pelo gestor.
-- Vigência por data: na aceitação aplica-se a configuração em vigor (mais recente com data_vigencia <= NOW()).
DROP TABLE IF EXISTS `config_recibo_verde`;
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

-- Dumping data for table secade_beauty.config_recibo_verde: ~0 rows (approximately)
DELETE FROM `config_recibo_verde`;

-- Dumping structure for table secade_beauty.rota_ambulante
-- v3.0: decisão SEMPRE manual e livre do gestor (sem limiar automático).
--   Valor de referência 50€ é apenas indicador visual (sem campo de bloqueio).
--   Campos financeiros: custo combustível, quota-parte do cliente, lucro dos serviços, lucro total.
--   Auditoria da decisão: decidido_por / decidido_em / observacoes_decisao.
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.rota_ambulante: ~0 rows (approximately)
DELETE FROM `rota_ambulante`;

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

-- Dumping data for table secade_beauty.rota_funcionario: ~0 rows (approximately)
DELETE FROM `rota_funcionario`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.execucao_agendamento: ~0 rows (approximately)
DELETE FROM `execucao_agendamento`;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.feedback_cliente: ~0 rows (approximately)
DELETE FROM `feedback_cliente`;

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
  CONSTRAINT `fk_servico_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categoria_profissional` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.servico: ~35 rows (approximately)
DELETE FROM `servico`;
INSERT INTO `servico` (`id`, `nome`, `descricao`, `categoria_id`, `duracao_estimada_minutos`, `preco_base`, `requer_espaco_fisico`) VALUES
	(1, 'Box Braids', 'Serviço de tranças Box Braids.', 1, 240, 32.52, 0),
	(2, 'Cordelete', 'Serviço de tranças Cordelete.', 1, 180, 48.78, 0),
	(3, 'Demão', 'Serviço de tranças Demão.', 1, 180, 36.59, 0),
	(4, 'Tranças Nagô', 'Serviço de tranças Nagô.', 1, 150, 28.46, 0),
	(5, 'Retro Braids', 'Serviço de tranças Retro Braids.', 1, 240, 48.78, 0),
	(6, 'Dreads Look', 'Aplicação de Dreads Look.', 1, 240, 44.72, 0),
	(7, 'Trança Twist', 'Serviço de trança Twist.', 1, 210, 40.65, 0),
	(8, 'Box Braid Crochet Hair', 'Aplicação de Box Braids com Crochet Hair.', 1, 180, 36.59, 0),
	(9, 'Trança Boxeadora', 'Serviço de trança Boxeadora.', 1, 60, 24.39, 0),
	(10, 'Trança Butterfly', 'Serviço de trança Butterfly.', 1, 240, 45.52, 0),
	(11, 'Trança Fulani', 'Serviço de trança Fulani.', 1, 210, 36.59, 0),
	(12, 'French Curl', 'Serviço de tranças French Curl.', 1, 240, 48.78, 0),
	(13, 'Faux Locs', 'Aplicação de Faux Locs.', 1, 300, 47.15, 0),
	(14, 'Gypsy Braids', 'Serviço de Gypsy Braids.', 1, 300, 48.78, 0),
	(15, 'Bohemian Braids', 'Serviço de Bohemian Braids.', 1, 300, 45.53, 0),
	(16, 'Tranças Pipocas', 'Serviço de tranças Pipocas.', 1, 180, 40.65, 0),
	(17, 'Rabo de Cavalo (Ponytail)', 'Penteado rabo de cavalo.', 1, 60, 20.33, 0),
	(18, 'Coque (Bun)', 'Penteado coque.', 1, 60, 24.39, 0),
	(19, 'Trança Francesa', 'Penteado com trança francesa.', 1, 45, 13.82, 0),
	(20, 'Half Bun (Meio Coque)', 'Penteado meio coque.', 1, 60, 36.59, 0),
	(21, 'Beach Waves', 'Penteado Beach Waves.', 1, 60, 16.26, 0),
	(22, 'Cabelo Liso com Franja', 'Alisamento e finalização com franja.', 1, 90, 32.52, 0),
	(23, 'Coque Messy', 'Penteado coque messy.', 1, 60, 28.46, 0),
	(24, 'Trança Espinha de Peixe', 'Penteado trança espinha de peixe.', 1, 60, 16.26, 0),
	(25, 'Cabelo Preso Lateral', 'Penteado preso lateral.', 1, 60, 36.59, 0),
	(26, 'Cabelo Solto com Ondas', 'Penteado cabelo solto com ondas.', 1, 90, 40.65, 0),
	(27, 'Coque Baixo Elegante', 'Penteado coque baixo elegante.', 1, 60, 20.33, 0),
	(28, 'Corte de Cabelo', 'Corte de cabelo masculino.', 2, 30, 12.20, 0),
	(29, 'Barba', 'Aparar e modelar barba.', 2, 20, 4.07, 0),
	(30, 'Maquilhagem para Noivas', 'Maquilhagem profissional para noivas.', 3, 120, 40.65, 0),
	(31, 'Maquilhagem para Festa', 'Maquilhagem profissional para festas.', 3, 90, 28.46, 0),
	(32, 'Maquilhagem Simples', 'Maquilhagem simples.', 3, 45, 16.26, 0),
	(33, 'Manicure', 'Serviço de manicure.', 3, 45, 12.20, 0),
	(34, 'Limpeza Facial', 'Limpeza facial.', 3, 60, 24.39, 1),
	(35, 'Design de Sobrancelha com Linha', 'Design de sobrancelhas com linha.', 3, 30, 8.13, 0);

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

-- Dumping data for table secade_beauty.servico_foto: ~0 rows (approximately)
DELETE FROM `servico_foto`;

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
-- v3.0: novo tipo 'quota_parte_deslocacao' (quota-parte do cliente no custo da rota).
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

-- Dumping structure for table secade_beauty.obrigacao_fiscal (NOVA - v3.0)
-- Calendário Fiscal centralizado no backoffice (módulo do gestor).
-- Tipos: IVA, IRC, Segurança Social, Seguros. Estado: pendente/pago.
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.obrigacao_fiscal: ~0 rows (approximately)
DELETE FROM `obrigacao_fiscal`;

-- Dumping structure for table secade_beauty.alerta_fiscal (NOVA - v3.0)
-- Alertas automáticos progressivos: 30, 15, 7, 3, 1 dia antes do prazo e diários em atraso.
-- Geração on-demand no acesso ao backoffice (sem CRON, coerente com restrições académicas).
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table secade_beauty.alerta_fiscal: ~0 rows (approximately)
DELETE FROM `alerta_fiscal`;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
