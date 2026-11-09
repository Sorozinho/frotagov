-- =====================================================================
-- FrotaGov - Dados FICTÍCIOS para teste e demonstração
-- Rode DEPOIS do schema.sql
-- As datas são relativas ao dia de hoje, para o painel sempre ter dados do mês
-- =====================================================================
USE frotagov;
SET NAMES utf8mb4;

INSERT INTO secretaria (nome, sigla, criado_em) VALUES
 ('Secretaria de Obras e Transportes', 'SOT', NOW()),
 ('Secretaria de Saúde',               'SMS', NOW()),
 ('Secretaria de Agricultura',         'SAG', NOW());

INSERT INTO veiculo (secretaria_id, tipo, placa, descricao, medidor_atual, criado_em) VALUES
 (1, 'automovel', 'ABC1D23', 'Fiat Strada 2022',          49300, NOW()),
 (1, 'maquina',   NULL,      'Retroescavadeira JCB 3CX',   3120, NOW()),
 (2, 'automovel', 'XYZ4E56', 'VW Gol 2021 (ambulância)',  80310, NOW()),
 (3, 'maquina',   NULL,      'Trator New Holland TL75',    1985, NOW());

INSERT INTO abastecimento (veiculo_id, data, litros, valor_total, medidor, posto, criado_em) VALUES
 (1, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 40.00, 248.00, 48800, 'Posto Exemplo', NOW()),
 (1, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 42.00, 260.40, 49300, 'Posto Exemplo', NOW()),
 (2, DATE_SUB(CURDATE(), INTERVAL 19 DAY), 80.00, 488.00,  3100, 'Posto Exemplo', NOW()),
 (2, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 70.00, 427.00,  3120, 'Posto Exemplo', NOW()),
 (3, DATE_SUB(CURDATE(), INTERVAL 16 DAY), 35.00, 217.00, 79900, 'Posto Exemplo', NOW()),
 (3, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 38.00, 235.60, 80310, 'Posto Exemplo', NOW());

INSERT INTO ordem_manutencao (veiculo_id, data, oficina, descricao, criado_em) VALUES
 (1, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Oficina Exemplo', 'Revisão dos 45.000 km', NOW());

INSERT INTO item_servico (ordem_id, tipo, descricao, quantidade, valor_unitario) VALUES
 (1, 'peca',    'Óleo de motor 5W30 (litro)', 4, 45.00),
 (1, 'peca',    'Filtro de óleo',             1, 38.00),
 (1, 'servico', 'Mão de obra troca de óleo',  1, 80.00);
