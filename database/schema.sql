-- =====================================================================
-- FrotaGov - Script de criação do banco de dados (MySQL 8 / MariaDB)
-- Execute este arquivo inteiro no phpMyAdmin (aba SQL) ou no terminal:
--   mysql -u root -p < database/schema.sql
-- =====================================================================

SET NAMES utf8mb4;
DROP DATABASE IF EXISTS frotagov;
CREATE DATABASE frotagov CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE frotagov;

-- Secretarias da Prefeitura (Obras, Saúde, Educação...)
CREATE TABLE secretaria (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(100) NOT NULL,
    sigla         VARCHAR(10)  NOT NULL UNIQUE,
    criado_em     DATETIME     NOT NULL,
    atualizado_em DATETIME     NULL
) ENGINE=InnoDB;

-- Veículos da frota.
-- Uma única tabela guarda os dois tipos (automóvel e máquina pesada);
-- a coluna "tipo" diz qual classe PHP deve ser criada ao ler o registro.
CREATE TABLE veiculo (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    secretaria_id  INT          NOT NULL,
    tipo           ENUM('automovel','maquina') NOT NULL,
    placa          VARCHAR(10)  NULL UNIQUE,      -- máquinas podem não ter placa
    descricao      VARCHAR(100) NOT NULL,         -- ex.: "Fiat Strada 2022"
    medidor_atual  DECIMAL(10,1) NOT NULL DEFAULT 0, -- km (automóvel) ou horas (máquina)
    criado_em      DATETIME     NOT NULL,
    atualizado_em  DATETIME     NULL,
    CONSTRAINT fk_veiculo_secretaria
        FOREIGN KEY (secretaria_id) REFERENCES secretaria(id)
        ON DELETE RESTRICT   -- não deixa apagar secretaria que ainda tem veículo
) ENGINE=InnoDB;

-- Abastecimentos
CREATE TABLE abastecimento (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id    INT           NOT NULL,
    data          DATE          NOT NULL,
    litros        DECIMAL(8,2)  NOT NULL CHECK (litros > 0),
    valor_total   DECIMAL(10,2) NOT NULL CHECK (valor_total > 0),
    medidor       DECIMAL(10,1) NOT NULL,         -- km ou horas no momento do abastecimento
    posto         VARCHAR(100)  NOT NULL,
    criado_em     DATETIME      NOT NULL,
    atualizado_em DATETIME      NULL,
    CONSTRAINT fk_abastecimento_veiculo
        FOREIGN KEY (veiculo_id) REFERENCES veiculo(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Ordens de manutenção (cabeçalho)
CREATE TABLE ordem_manutencao (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    veiculo_id    INT          NOT NULL,
    data          DATE         NOT NULL,
    oficina       VARCHAR(100) NOT NULL,
    descricao     VARCHAR(255) NOT NULL,
    criado_em     DATETIME     NOT NULL,
    atualizado_em DATETIME     NULL,
    CONSTRAINT fk_ordem_veiculo
        FOREIGN KEY (veiculo_id) REFERENCES veiculo(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Itens da ordem (peças e serviços).
-- ON DELETE CASCADE = COMPOSIÇÃO: apagou a ordem, os itens somem junto.
CREATE TABLE item_servico (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    ordem_id        INT           NOT NULL,
    tipo            ENUM('peca','servico') NOT NULL,
    descricao       VARCHAR(150)  NOT NULL,
    quantidade      DECIMAL(8,2)  NOT NULL CHECK (quantidade > 0),
    valor_unitario  DECIMAL(10,2) NOT NULL CHECK (valor_unitario >= 0),
    CONSTRAINT fk_item_ordem
        FOREIGN KEY (ordem_id) REFERENCES ordem_manutencao(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
