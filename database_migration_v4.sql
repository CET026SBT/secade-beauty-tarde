-- --------------------------------------------------------
-- MIGRAÇÃO INCREMENTAL secade_beauty: v3 -> v4 (Fase 6 — dados entregues pelo cliente)
-- Aplica sobre a BD existente SEM destruir dados de negócio.
-- Idempotente: pode correr várias vezes (ids explícitos + ON DUPLICATE KEY UPDATE).
--
-- FONTES (28/09/2026, ambas do cliente):
--   * `Secade Duração Serviços 1.ods`          -> `servico.duracao_estimada_minutos` (35 serviços)
--   * `Serviços, Clientes e Fornecedores.xlsx` -> `fornecedor` (43) + clientes (65)
--
-- NOTAS DE INTERPRETAÇÃO (o que o ficheiro NÃO traz não foi inventado):
--   * Preços: os 35 valores base do .xlsx já coincidiam com a BD — nada a alterar.
--     (o ficheiro mostra o PVP com IVA a 23 % e o valor base tributável)
--   * Durações: vêm do .ods e substituem as do seed (eram estimativas por defeito).
--   * Clientes: o ficheiro só traz nome, NIF e localidade. O e-mail de acesso é
--     gerado (<slug-do-nome>.<NIF>@cliente.secade.local) e a conta fica SEM
--     credenciais utilizáveis; o telemóvel fica em branco; a rua fica em branco
--     (só a cidade é conhecida). A completar pelo cliente/backoffice.
--   * Fornecedores: 5 linhas não têm NIF ("NP (não possui) NIF" em 4 — Temu,
--     ViceDeal.com, Aliexpress, Consumíveis — e "NIF indisponível nas plataformas
--     digitais" em Bandido Portugal.pt): o rótulo do ficheiro vai para
--     `observacoes` e o `nif` fica NULL. "Manuel jacinto (renda)" aparece duas
--     vezes com NIF diferente: são dois registos (rendas distintas).
--   * Rótulos de fornecedores e localidades vêm de células de FÓRMULA
--     (`t="str"`) — lidas pelo valor, não pela fórmula.
-- --------------------------------------------------------
USE `secade_beauty`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. servico: duração estimada real (fonte: `Secade Duração Serviços 1.ods`)
--    O .ods é uma tabela de 3 blocos (Cabeleireiro | Estética | Barbearia).
-- --------------------------------------------------------
UPDATE `servico` SET `duracao_estimada_minutos` = 150 WHERE `id` = 1;  -- Tranças Box Braids
UPDATE `servico` SET `duracao_estimada_minutos` = 150 WHERE `id` = 2;  -- Cordolete
UPDATE `servico` SET `duracao_estimada_minutos` = 120 WHERE `id` = 3;  -- Demão
UPDATE `servico` SET `duracao_estimada_minutos` = 120 WHERE `id` = 4;  -- Tranças Nagô
UPDATE `servico` SET `duracao_estimada_minutos` = 150 WHERE `id` = 5;  -- Retrobraids
UPDATE `servico` SET `duracao_estimada_minutos` = 150 WHERE `id` = 6;  -- Dreads Look
UPDATE `servico` SET `duracao_estimada_minutos` = 120 WHERE `id` = 7;  -- Trança Twist
UPDATE `servico` SET `duracao_estimada_minutos` = 120 WHERE `id` = 8;  -- Box Braid Crochet Hair
UPDATE `servico` SET `duracao_estimada_minutos` = 60 WHERE `id` = 9;  -- Trança Boxeadora
UPDATE `servico` SET `duracao_estimada_minutos` = 150 WHERE `id` = 10;  -- Trança Butterfly
UPDATE `servico` SET `duracao_estimada_minutos` = 120 WHERE `id` = 11;  -- Trança Fulani
UPDATE `servico` SET `duracao_estimada_minutos` = 150 WHERE `id` = 12;  -- French Curl
UPDATE `servico` SET `duracao_estimada_minutos` = 150 WHERE `id` = 13;  -- Faux Locs
UPDATE `servico` SET `duracao_estimada_minutos` = 150 WHERE `id` = 14;  -- Gypsy Braids
UPDATE `servico` SET `duracao_estimada_minutos` = 150 WHERE `id` = 15;  -- Bohemian Braids
UPDATE `servico` SET `duracao_estimada_minutos` = 120 WHERE `id` = 16;  -- Tranças Pipocas
UPDATE `servico` SET `duracao_estimada_minutos` = 45 WHERE `id` = 17;  -- Rabo de cavalo (Ponytail)
UPDATE `servico` SET `duracao_estimada_minutos` = 60 WHERE `id` = 18;  -- Coque (Bun)
UPDATE `servico` SET `duracao_estimada_minutos` = 30 WHERE `id` = 19;  -- Trança Francesa
UPDATE `servico` SET `duracao_estimada_minutos` = 60 WHERE `id` = 20;  -- Half bun (meio coque)
UPDATE `servico` SET `duracao_estimada_minutos` = 45 WHERE `id` = 21;  -- Beach waves
UPDATE `servico` SET `duracao_estimada_minutos` = 60 WHERE `id` = 22;  -- Cabelo liso com franja
UPDATE `servico` SET `duracao_estimada_minutos` = 45 WHERE `id` = 23;  -- Coque Messy
UPDATE `servico` SET `duracao_estimada_minutos` = 45 WHERE `id` = 24;  -- Trança espinha de peixe
UPDATE `servico` SET `duracao_estimada_minutos` = 60 WHERE `id` = 25;  -- Cabelo preso lateral
UPDATE `servico` SET `duracao_estimada_minutos` = 60 WHERE `id` = 26;  -- Cabelo solto com ondas
UPDATE `servico` SET `duracao_estimada_minutos` = 45 WHERE `id` = 27;  -- Coque baixo elegante
UPDATE `servico` SET `duracao_estimada_minutos` = 30 WHERE `id` = 28;  -- Corte de cabelo
UPDATE `servico` SET `duracao_estimada_minutos` = 8 WHERE `id` = 29;  -- Barba
UPDATE `servico` SET `duracao_estimada_minutos` = 150 WHERE `id` = 30;  -- Maquilhagem para noivas
UPDATE `servico` SET `duracao_estimada_minutos` = 60 WHERE `id` = 31;  -- Maquilhagem para festa
UPDATE `servico` SET `duracao_estimada_minutos` = 45 WHERE `id` = 32;  -- Maquilhagem simples
UPDATE `servico` SET `duracao_estimada_minutos` = 45 WHERE `id` = 33;  -- Manicure
UPDATE `servico` SET `duracao_estimada_minutos` = 60 WHERE `id` = 34;  -- Limpeza facial
UPDATE `servico` SET `duracao_estimada_minutos` = 20 WHERE `id` = 35;  -- Design de sobrancelha com linha

-- --------------------------------------------------------
-- 2. fornecedor (NOVO — módulo da Fase 6.1, prioridade máxima da §25.1)
--    Campos só com o que existe ou é laborável: o ficheiro traz nome + NIF.
-- --------------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `fornecedor` (`id`, `nome`, `nif`, `observacoes`) VALUES
	(1, 'Stand Virtual', '508069491', NULL),
	(2, 'Worten', '503630330', NULL),
	(3, 'Leroy-Merlin', '506848558', NULL),
	(4, 'AOSOM', '980683386', NULL),
	(5, 'Staples', '503789372', NULL),
	(6, 'Beleza 37', '514749636', NULL),
	(7, 'Extintores online', '518352080', NULL),
	(8, 'Continente', '502011475', NULL),
	(9, 'Logo seguros', '508278600', NULL),
	(10, 'Generali', '500940231', NULL),
	(11, 'OK! Seguros', '504011944', NULL),
	(12, 'Fidelidade', '500918880', NULL),
	(13, 'Liberty', '500068658', NULL),
	(14, 'SK pro Med Beauty Solutions', '508161320', NULL),
	(15, 'Primor', '980663695', NULL),
	(16, 'Pluri cosmética', '503890278', NULL),
	(17, 'AfroQueen', '516416081', NULL),
	(18, 'Temu', NULL, 'NP (não possui) NIF'),
	(19, 'IKEA', '505416654', NULL),
	(20, 'Lusini', '517386402', NULL),
	(21, 'DRUNI', '518530752', NULL),
	(22, 'Wells', '508037514', NULL),
	(23, 'Baber tools profissional', '589053345', NULL),
	(24, 'Ideal Cosméticos', '514239085', NULL),
	(25, 'Barberalia', '516206370', NULL),
	(26, 'Bandido Portugal.pt', NULL, 'NIF indisponível nas plataformas digitais'),
	(27, 'ViceDeal.com', NULL, 'NP (não possui) NIF'),
	(28, 'Casa do Barbeiro', '501766448', NULL),
	(29, 'Espaço barbeiro', '510427103', NULL),
	(30, 'Aliexpress', NULL, 'NP (não possui) NIF'),
	(31, 'Município de Évora', '504828576', NULL),
	(32, 'Endesa', '508855950', NULL),
	(33, 'MEO', '504615947', NULL),
	(34, 'Galp', '505060515', NULL),
	(35, 'Repsol', '500246963', NULL),
	(36, 'Manuel jacinto (renda)', '320491366', NULL),
	(37, 'Banco CTT', '513412417', NULL),
	(38, 'Consumíveis', NULL, 'NP (não possui) NIF'),
	(39, 'Norauto', '503629995', NULL),
	(40, 'Gestévora', '500785708', NULL),
	(41, 'Manuel jacinto (renda)', '285284240', NULL),
	(42, 'Stela Cristina', '200846922', NULL),
	(43, 'Moloni', '513321527', NULL)
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `nif` = VALUES(`nif`), `observacoes` = VALUES(`observacoes`);

-- --------------------------------------------------------
-- 3. clientes importados (fonte: `Serviços, Clientes e Fornecedores.xlsx`)
--    Ids reservados a partir de 100 (a BD usava 1, 2, 3, 53, 74).
--    `password_hash` = '*' — conta importada, sem credenciais utilizáveis:
--    `password_verify()` devolve sempre false, logo não há acesso.
-- --------------------------------------------------------
INSERT INTO `utilizador` (`id`, `nome`, `email`, `password_hash`, `telemovel`, `nif`, `tipo_perfil`) VALUES
	(100, 'Leonor Santos', 'leonor.santos.251989518@cliente.secade.local', '*', '', '251989518', 'cliente'),
	(101, 'Catarina Pixoto', 'catarina.pixoto.298784076@cliente.secade.local', '*', '', '298784076', 'cliente'),
	(102, 'Elvisson Daniel', 'elvisson.daniel.278561519@cliente.secade.local', '*', '', '278561519', 'cliente'),
	(103, 'Leandro Silva', 'leandro.silva.214056686@cliente.secade.local', '*', '', '214056686', 'cliente'),
	(104, 'Tomásia Contreiras', 'tom.asia.contreiras.227748204@cliente.secade.local', '*', '', '227748204', 'cliente'),
	(105, 'Joaquina Matos', 'joaquina.matos.235820776@cliente.secade.local', '*', '', '235820776', 'cliente'),
	(106, 'Lídia Correia', 'l.idia.correia.201303140@cliente.secade.local', '*', '', '201303140', 'cliente'),
	(107, 'Emanuela Vigílio', 'emanuela.vig.ilio.213916819@cliente.secade.local', '*', '', '213916819', 'cliente'),
	(108, 'Augusta Contente', 'augusta.contente.240660501@cliente.secade.local', '*', '', '240660501', 'cliente'),
	(109, 'Laura Vanuza', 'laura.vanuza.235159026@cliente.secade.local', '*', '', '235159026', 'cliente'),
	(110, 'Florentino Rosa', 'florentino.rosa.278084737@cliente.secade.local', '*', '', '278084737', 'cliente'),
	(111, 'Beatriz Baessa', 'beatriz.baessa.232443777@cliente.secade.local', '*', '', '232443777', 'cliente'),
	(112, 'Tiago Fortunato', 'tiago.fortunato.214803155@cliente.secade.local', '*', '', '214803155', 'cliente'),
	(113, 'Elias Patente', 'elias.patente.243363010@cliente.secade.local', '*', '', '243363010', 'cliente'),
	(114, 'Partrícia Gomes', 'partr.icia.gomes.274907810@cliente.secade.local', '*', '', '274907810', 'cliente'),
	(115, 'Alexanda Pinto', 'alexanda.pinto.261788396@cliente.secade.local', '*', '', '261788396', 'cliente'),
	(116, 'Maria Luana', 'maria.luana.270325492@cliente.secade.local', '*', '', '270325492', 'cliente'),
	(117, 'Rena Nunes', 'rena.nunes.259969931@cliente.secade.local', '*', '', '259969931', 'cliente'),
	(118, 'Vanessa Cardoso', 'vanessa.cardoso.204914663@cliente.secade.local', '*', '', '204914663', 'cliente'),
	(119, 'Tiago Alves', 'tiago.alves.272698679@cliente.secade.local', '*', '', '272698679', 'cliente'),
	(120, 'Maria Pedro', 'maria.pedro.229658660@cliente.secade.local', '*', '', '229658660', 'cliente'),
	(121, 'Luísa Evidência', 'lu.isa.evid.encia.289524121@cliente.secade.local', '*', '', '289524121', 'cliente'),
	(122, 'Bianca Brico', 'bianca.brico.273711148@cliente.secade.local', '*', '', '273711148', 'cliente'),
	(123, 'Amanda Alves', 'amanda.alves.265052955@cliente.secade.local', '*', '', '265052955', 'cliente'),
	(124, 'Patrícia Freitas', 'patr.icia.freitas.279336055@cliente.secade.local', '*', '', '279336055', 'cliente'),
	(125, 'Beatriz Oliveira', 'beatriz.oliveira.207109176@cliente.secade.local', '*', '', '207109176', 'cliente'),
	(126, 'Lúcia Camarada', 'l.ucia.camarada.299502309@cliente.secade.local', '*', '', '299502309', 'cliente'),
	(127, 'Vivalda Libolo', 'vivalda.libolo.254824498@cliente.secade.local', '*', '', '254824498', 'cliente'),
	(128, 'Ricardo Sebastião', 'ricardo.sebasti.ao.217579906@cliente.secade.local', '*', '', '217579906', 'cliente'),
	(129, 'Beatriz Gonçalves', 'beatriz.goncalves.219082081@cliente.secade.local', '*', '', '219082081', 'cliente'),
	(130, 'Sónia Silva', 's.onia.silva.299763374@cliente.secade.local', '*', '', '299763374', 'cliente'),
	(131, 'Marta Gonçalves', 'marta.goncalves.250174332@cliente.secade.local', '*', '', '250174332', 'cliente'),
	(132, 'Joana Silva', 'joana.silva.248171240@cliente.secade.local', '*', '', '248171240', 'cliente'),
	(133, 'Sílvia Almeida', 's.ilvia.almeida.295931540@cliente.secade.local', '*', '', '295931540', 'cliente'),
	(134, 'Rita Costa', 'rita.costa.252554620@cliente.secade.local', '*', '', '252554620', 'cliente'),
	(135, 'Teresa Correia', 'teresa.correia.275536394@cliente.secade.local', '*', '', '275536394', 'cliente'),
	(136, 'Teresa Cardoso', 'teresa.cardoso.231413882@cliente.secade.local', '*', '', '231413882', 'cliente'),
	(137, 'Claúdia Marques', 'cla.udia.marques.275469727@cliente.secade.local', '*', '', '275469727', 'cliente'),
	(138, 'Sílvia Gonçalves', 's.ilvia.goncalves.228145651@cliente.secade.local', '*', '', '228145651', 'cliente'),
	(139, 'Carla Morauto', 'carla.morauto.261207890@cliente.secade.local', '*', '', '261207890', 'cliente'),
	(140, 'Patrícia Costa', 'patr.icia.costa.287024350@cliente.secade.local', '*', '', '287024350', 'cliente'),
	(141, 'Joana Pereira', 'joana.pereira.258735082@cliente.secade.local', '*', '', '258735082', 'cliente'),
	(142, 'Filipa Costa', 'filipa.costa.244808929@cliente.secade.local', '*', '', '244808929', 'cliente'),
	(143, 'Joana Pinto', 'joana.pinto.264883268@cliente.secade.local', '*', '', '264883268', 'cliente'),
	(144, 'Daniela Cardoso', 'daniela.cardoso.254736130@cliente.secade.local', '*', '', '254736130', 'cliente'),
	(145, 'Catarina Correia', 'catarina.correia.277691400@cliente.secade.local', '*', '', '277691400', 'cliente'),
	(146, 'Catarina Lopes', 'catarina.lopes.234560657@cliente.secade.local', '*', '', '234560657', 'cliente'),
	(147, 'Sónia Fenandes', 's.onia.fenandes.269836870@cliente.secade.local', '*', '', '269836870', 'cliente'),
	(148, 'Margarida Santos', 'margarida.santos.232060649@cliente.secade.local', '*', '', '232060649', 'cliente'),
	(149, 'Rita Pereira', 'rita.pereira.243526512@cliente.secade.local', '*', '', '243526512', 'cliente'),
	(150, 'Inês Mendes', 'in.es.mendes.203624513@cliente.secade.local', '*', '', '203624513', 'cliente'),
	(151, 'Vera Costa', 'vera.costa.245056220@cliente.secade.local', '*', '', '245056220', 'cliente'),
	(152, 'Filipa Santos', 'filipa.santos.281021970@cliente.secade.local', '*', '', '281021970', 'cliente'),
	(153, 'Marta Rodrigues', 'marta.rodrigues.248193848@cliente.secade.local', '*', '', '248193848', 'cliente'),
	(154, 'Marta Almeida', 'marta.almeida.241834961@cliente.secade.local', '*', '', '241834961', 'cliente'),
	(155, 'Sílvia Ferreira', 's.ilvia.ferreira.299343049@cliente.secade.local', '*', '', '299343049', 'cliente'),
	(156, 'Rita Jesus', 'rita.jesus.291337392@cliente.secade.local', '*', '', '291337392', 'cliente'),
	(157, 'Maria Ferreira', 'maria.ferreira.250412365@cliente.secade.local', '*', '', '250412365', 'cliente'),
	(158, 'Margarida Gonçalves', 'margarida.goncalves.211275859@cliente.secade.local', '*', '', '211275859', 'cliente'),
	(159, 'Daniela Ribeiro', 'daniela.ribeiro.221393366@cliente.secade.local', '*', '', '221393366', 'cliente'),
	(160, 'Daniela Oliveira', 'daniela.oliveira.266255264@cliente.secade.local', '*', '', '266255264', 'cliente'),
	(161, 'Ana Lopes', 'ana.lopes.255884117@cliente.secade.local', '*', '', '255884117', 'cliente'),
	(162, 'Teresa Lopes', 'teresa.lopes.259591696@cliente.secade.local', '*', '', '259591696', 'cliente'),
	(163, 'Inês Lopes', 'in.es.lopes.261930966@cliente.secade.local', '*', '', '261930966', 'cliente'),
	(164, 'Daniela Jesus', 'daniela.jesus.273038273@cliente.secade.local', '*', '', '273038273', 'cliente')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `nif` = VALUES(`nif`);

INSERT INTO `cliente` (`id`, `telemovel_validado_otp`) VALUES
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
	(164, 0)
ON DUPLICATE KEY UPDATE `telemovel_validado_otp` = VALUES(`telemovel_validado_otp`);

-- Localidade do ficheiro -> `cliente_morada` (rua e código postal NÃO constam do
-- ficheiro: ficam em branco em vez de inventados). "Arraiaolos" (gralha de 5
-- linhas) foi casado com a cidade "Arraiolos".
INSERT INTO `cliente_morada` (`id`, `cliente_id`, `cidade_id`, `designacao`, `rua`, `numero_porta`, `andar_bloco`, `codigo_postal`, `principal`) VALUES
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
	(264, 164, 1, 'Casa', '', NULL, NULL, NULL, 1)
ON DUPLICATE KEY UPDATE `cidade_id` = VALUES(`cidade_id`);

SET FOREIGN_KEY_CHECKS = 1;
-- esperado: 35 durações novas · fornecedor=43 · utilizador cliente=65 · cliente=65
