-- ------------------------------------------------------------------
-- F11 — Dados de demonstração realistas (Q-06 / Q-14)
--
-- Substitui os agendamentos de teste por cenários que exercitam TODOS os
-- ecrãs: comissões (serviços prestados), rotas, agenda, avisos, fiscal.
-- Idempotente: limpa os dados operacionais antes de semear.
-- Uso: mysql -u root secade_beauty < _dev/tools/seed-fase7.sql
-- ------------------------------------------------------------------

DELETE FROM rota_funcionario;
DELETE FROM execucao_agendamento;
DELETE FROM feedback_cliente;
DELETE FROM agendamento_servico;
DELETE FROM agendamento_pessoa;
DELETE FROM agendamento;
DELETE FROM rota_ambulante;
DELETE FROM alerta_fiscal;
DELETE FROM notificacao;
DELETE FROM manutencao_execucao;
DELETE FROM funcionario WHERE id >= 9000;
DELETE FROM utilizador WHERE id >= 9000;
DELETE FROM cliente_morada WHERE id IN (300, 301, 302, 303);

-- Moradas na cidade de cada rota (a carrinha vai ao cliente)
INSERT INTO cliente_morada (id, cliente_id, cidade_id, designacao, rua, numero_porta, codigo_postal, principal) VALUES
(300, 104, 2, 'Casa', 'Rua dos Lagares',      '12', '7050-101', 0),
(301, 105, 2, 'Casa', 'Praça da República',   '4',  '7050-120', 0),
(302, 106, 5, 'Casa', 'Rua Nova',             '27', '7170-055', 0),
(303, 107, 5, 'Casa', 'Travessa do Outeiro',  '8',  '7170-070', 0);

-- ------------------------------------------------------------------
-- 1) SERVIÇOS PRESTADOS (alimentam a folha de comissões)
--    funcionário 2 (Ana Técnica — RV a 70%)
-- ------------------------------------------------------------------
INSERT INTO agendamento (id, cliente_id, cliente_morada_id, local_prestacao, data_hora_pretendida, estado_reserva, valor_total, sinal_pago, valor_sinal, validado_logistica_loja) VALUES
(1001, 100, 200, 'loja_fisica',        '2026-09-15 10:00:00', 'concluido',  24.40, 1, 2.44, 1),
(1002, 101, 201, 'loja_fisica',        '2026-09-22 15:00:00', 'concluido',  28.46, 1, 2.85, 1),
(1003, 102, 202, 'carrinha_ambulante', '2026-09-29 09:30:00', 'concluido',  40.65, 1, 4.07, 0),
(1004, 103, 203, 'loja_fisica',        '2026-10-03 11:00:00', 'executado',  13.82, 1, 1.38, 1);

INSERT INTO agendamento_servico (agendamento_id, servico_id, funcionario_id, preco_praticado, duracao_minutos, estado_aceitacao, aceito_em, percentagem_funcionario_aplicada) VALUES
(1001, 28, 2, 12.20,  30, 'aceite', '2026-09-15 09:10:00', 70.00),
(1001, 33, 2, 12.20,  45, 'aceite', '2026-09-15 09:10:00', 70.00),
(1002, 31, 2, 28.46,  60, 'aceite', '2026-09-22 14:05:00', 70.00),
(1003,  7, 2, 40.65, 120, 'aceite', '2026-09-29 08:40:00', 70.00),
(1004, 19, 2, 13.82,  30, 'aceite', '2026-10-03 10:20:00', 70.00);

INSERT INTO execucao_agendamento (id, agendamento_id, data_hora_inicio_real, data_hora_fim_real, estado_execucao, observacoes_tecnico) VALUES
(1, 1001, '2026-09-15 10:00:00', '2026-09-15 11:15:00', 'concluido', 'Serviço concluído sem observações.'),
(2, 1002, '2026-09-22 15:00:00', '2026-09-22 16:00:00', 'concluido', 'Cliente pediu ajuste de tom.'),
(3, 1003, '2026-09-29 09:35:00', '2026-09-29 11:35:00', 'concluido', 'Rota cumprida à hora prevista.'),
(4, 1004, '2026-10-03 11:00:00', '2026-10-03 11:30:00', 'concluido', NULL);

-- Rota concluída (histórico — serve os cartões de rentabilidade)
INSERT INTO rota_ambulante (id, data_rota, base_partida_id, cidade_id, estado_rota, custo_estimado_combustivel, quota_parte_cliente, lucro_servicos, lucro_total, decidido_por, decidido_em, observacoes_decisao) VALUES
(501, '2026-09-29', 1, 10, 'concluida', 12.50, 0.00, 40.65, 28.15, 1, '2026-09-27 18:20:00', 'Rota aprovada e cumprida.');

-- Feedback dos clientes (alimenta o ecrã de avaliações)
INSERT INTO feedback_cliente (execucao_agendamento_id, classificacao_estrelas, comentario) VALUES
(1, 5, 'Excelente atendimento, muito profissional.'),
(2, 4, 'Gostei muito do resultado.');
-- ------------------------------------------------------------------
-- 2) MARCAÇÕES FUTURAS (agenda, avisos e rotas por decidir)
-- ------------------------------------------------------------------
INSERT INTO agendamento (id, cliente_id, cliente_morada_id, local_prestacao, data_hora_pretendida, estado_reserva, valor_total, sinal_pago, valor_sinal, validado_logistica_loja) VALUES
(1005, 104, 300, 'carrinha_ambulante', '2026-10-14 09:30:00', 'confirmado',         28.46, 1, 2.85, 0),
(1006, 105, 301, 'carrinha_ambulante', '2026-10-14 11:00:00', 'confirmado',         24.39, 1, 2.44, 0),
(1007, 106, 302, 'carrinha_ambulante', '2026-10-21 10:00:00', 'totalmente_alocado', 20.33, 1, 2.03, 0),
(1008, 107, 303, 'carrinha_ambulante', '2026-10-21 14:00:00', 'pendente_alocacao',  16.26, 1, 1.63, 0),
(1009, 108, 208, 'loja_fisica',        '2026-10-16 16:00:00', 'cancelado',          16.26, 0, 0.00, 1),
(1010, 109, 209, 'loja_fisica',        '2026-10-17 11:00:00', 'recusado',           12.20, 0, 0.00, 1),
(1011, 110, 210, 'loja_fisica',        '2026-10-20 10:30:00', 'confirmado',         40.65, 1, 4.07, 1);

INSERT INTO agendamento_servico (agendamento_id, servico_id, funcionario_id, preco_praticado, duracao_minutos, estado_aceitacao, aceito_em, percentagem_funcionario_aplicada) VALUES
(1005,  4,    2,    28.46, 120, 'aceite',   '2026-10-12 09:00:00', 70.00),
(1006,  9,    2,    24.39,  60, 'aceite',   '2026-10-12 09:05:00', 70.00),
(1007, 17,    2,    20.33,  45, 'aceite',   '2026-10-19 10:00:00', 70.00),
(1008, 24,    NULL, 16.26,  45, 'pendente', NULL,                  NULL),
(1009, 21,    NULL, 16.26,  45, 'pendente', NULL,                  NULL),
(1010, 28,    NULL, 12.20,  30, 'pendente', NULL,                  NULL),
(1011,  8,    2,    40.65, 120, 'aceite',   '2026-10-18 15:00:00', 70.00);

-- Rota aprovada (Montemor-o-Novo, 14/10) — a carrinha já está comprometida
INSERT INTO rota_ambulante (id, data_rota, base_partida_id, cidade_id, estado_rota, custo_estimado_combustivel, quota_parte_cliente, lucro_servicos, lucro_total, decidido_por, decidido_em, observacoes_decisao) VALUES
(502, '2026-10-14', 1, 2, 'aprovada', 14.20, 52.85, 0.00, 38.65, 1, '2026-10-12 19:30:00', 'Dia com procura suficiente; aprovada.');

INSERT INTO rota_funcionario (rota_id, funcionario_id) VALUES (502, 2);

INSERT INTO execucao_agendamento (id, agendamento_id, rota_id, estado_execucao) VALUES
(5, 1005, 502, 'em_curso'),
(6, 1006, 502, 'em_curso');

-- Avisos do cliente (Área Cliente -> Lembretes)
INSERT INTO notificacao (utilizador_id, tipo, mensagem, lida) VALUES
(104, 'agendamento_confirmado', 'Agendamento #1005 confirmado para 14/10/2026.', 0),
(104, 'lembrete_24h', 'Agendamento #1005 é a 14/10/2026 09:30. Se precisar de alterar, temos disponibilidade em: 21/10/2026, 28/10/2026.', 0),
(108, 'agendamento_cancelado', 'Agendamento #1009 cancelado. O horário voltou a ficar disponível.', 0);

-- ------------------------------------------------------------------
-- 3) FISCAL — obrigações do ano corrente (calendário e avisos)
-- ------------------------------------------------------------------
INSERT INTO obrigacao_fiscal (tipo, designacao, periodicidade, valor_estimado, data_prazo, estado, observacoes) VALUES
('iva',              'IVA do 3.º trimestre',             'trimestral', 1840.00, '2026-11-20', 'pendente', 'Declaração periódica trimestral.'),
('irc',              'IRC — pagamento por conta',        'trimestral',  610.00, '2026-12-15', 'pendente', '3.º pagamento por conta.'),
('seguranca_social', 'Segurança Social — novembro',      'mensal',      520.00, '2026-11-20', 'pendente', 'Contribuições da equipa.'),
('seguros',          'Seguro de responsabilidade civil', 'anual',       395.00, '2026-12-31', 'pendente', 'Apólice anual do espaço e da carrinha.');