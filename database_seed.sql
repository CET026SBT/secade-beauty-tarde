-- --------------------------------------------------------
-- SEED DATA - SECADE BEAUTY (dados de teste/académicos)
-- Executar APÓS database_migration_v3.sql (ou DataBase_v2.sql).
-- Passwords (bcrypt):
--   gestor@secade.pt    -> Gestor@123
--   funcionario@secade.pt-> Func@12345
--   cliente@teste.pt    -> Cliente@123
-- --------------------------------------------------------
USE `secade_beauty`;

SET FOREIGN_KEY_CHECKS = 0;

SET NAMES utf8mb4;

-- Gestor de testes
INSERT INTO `utilizador` (`id`, `nome`, `email`, `password_hash`, `telemovel`, `nif`, `tipo_perfil`)
VALUES (1, 'Gestor Secade', 'gestor@secade.pt',
        '$2y$10$DX3o9mDbE2M00tUg21KO1OOtvl7SGpPvhNZ4SFnYBXHNetwcdt0F6',
        '+351911111111', '123456789', 'gestor')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `password_hash` = VALUES(`password_hash`), `tipo_perfil` = VALUES(`tipo_perfil`);

-- Funcionário de testes (recibos verdes)
INSERT INTO `utilizador` (`id`, `nome`, `email`, `password_hash`, `telemovel`, `nif`, `tipo_perfil`)
VALUES (2, 'Ana Técnica', 'funcionario@secade.pt',
        '$2y$10$D22wXVD90rZoILW3X3QalOTr1mb3dSz8IxR5eIbulvc0jN0Eitvta',
        '+351922222222', '298765438', 'funcionario')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `password_hash` = VALUES(`password_hash`), `tipo_perfil` = VALUES(`tipo_perfil`);

INSERT INTO `funcionario` (`id`, `tipo_contrato`, `salario_base`, `cc`, `ativo`)
VALUES (2, 'recibo_verde', 900.00, '999999990Z7R', 1)
ON DUPLICATE KEY UPDATE `tipo_contrato` = VALUES(`tipo_contrato`), `salario_base` = VALUES(`salario_base`);

-- NOTA (v3.0): removido o INSERT em `funcionario_categoria` — a tabela foi eliminada
-- (as categorias são apenas filtros visuais e a relação nunca era lida pelo código).

-- Cliente de testes (com morada em Évora)
INSERT INTO `utilizador` (`id`, `nome`, `email`, `password_hash`, `telemovel`, `nif`, `tipo_perfil`)
VALUES (3, 'João Cliente', 'cliente@teste.pt',
        '$2y$10$NRQDxzU490d8RvpmHVmLb.6AnC74Pds1ozKySPI85.1801rGGDWSS',
        '+351933333333', '345678915', 'cliente')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `password_hash` = VALUES(`password_hash`), `tipo_perfil` = VALUES(`tipo_perfil`);

INSERT INTO `cliente` (`id`, `telemovel_validado_otp`) VALUES (3, 1)
ON DUPLICATE KEY UPDATE `telemovel_validado_otp` = VALUES(`telemovel_validado_otp`);

INSERT INTO `cliente_morada` (`id`, `cliente_id`, `cidade_id`, `designacao`, `rua`, `numero_porta`, `andar_bloco`, `codigo_postal`, `principal`)
VALUES (1, 3, 10, 'Casa', 'Rua de Aviz', '10', '1º Esq', '7000-123', 1)
ON DUPLICATE KEY UPDATE `rua` = VALUES(`rua`), `cidade_id` = VALUES(`cidade_id`), `principal` = VALUES(`principal`);

SET FOREIGN_KEY_CHECKS = 1;