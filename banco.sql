-- ============================================
-- PLANER SESI - BANCO DE DADOS
-- ============================================

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS planer_sesi 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE planer_sesi;

-- ============================================
-- TABELA DE PROFESSORES
-- ============================================
CREATE TABLE professores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    data_nascimento DATE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABELA DE ALUNOS
-- ============================================
CREATE TABLE alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    data_nascimento DATE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABELA DE JOGOS ESCOLARES
-- ============================================
CREATE TABLE jogos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    local VARCHAR(255),
    max_participantes INT DEFAULT 100,
    status ENUM('aberto', 'fechado', 'em_andamento', 'finalizado') DEFAULT 'aberto',
    icone VARCHAR(32) DEFAULT 'esporte',
    cor_primaria VARCHAR(7) DEFAULT '#CC0000',
    cor_secundaria VARCHAR(7) DEFAULT '#990000',
    imagem_url VARCHAR(255) NULL,
    criado_por INT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_jogos_professor FOREIGN KEY (criado_por) REFERENCES professores(id) ON DELETE SET NULL
);

-- ============================================
-- TABELA DE INSCRIÇÕES
-- ============================================
CREATE TABLE inscricoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    jogo_id INT NOT NULL,
    nome_completo VARCHAR(255) NOT NULL,
    serie VARCHAR(50) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    status ENUM('pendente', 'confirmada', 'cancelada') DEFAULT 'pendente',
    confirmacao_email TINYINT(1) DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id),
    FOREIGN KEY (jogo_id) REFERENCES jogos(id),
    UNIQUE KEY unique_aluno_jogo (aluno_id, jogo_id)
);

-- ============================================
-- TABELA DE PLACAR
-- ============================================
CREATE TABLE placar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jogo_id INT NOT NULL,
    time_a VARCHAR(100) NOT NULL DEFAULT 'Time A',
    time_b VARCHAR(100) NOT NULL DEFAULT 'Time B',
    placar_a INT DEFAULT 0,
    placar_b INT DEFAULT 0,
    status ENUM('aguardando', 'em_andamento', 'finalizado') DEFAULT 'aguardando',
    professor_id INT,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (jogo_id) REFERENCES jogos(id),
    UNIQUE KEY unique_placar_jogo (jogo_id),
    FOREIGN KEY (professor_id) REFERENCES professores(id)
);

-- ============================================
-- INSERIR JOGOS PADRÃO
-- ============================================
INSERT INTO jogos (nome, descricao, data_inicio, data_fim, local, max_participantes, status, icone) VALUES
('Futsal', 'Campeonato de Futsal dos Jogos Escolares SESI 2025', '2025-08-01', '2025-08-15', 'Quadra Poliesportiva SESI', 50, 'aberto', 'esporte'),
('Handebol', 'Campeonato de Handebol dos Jogos Escolares SESI 2025', '2025-08-01', '2025-08-15', 'Quadra Poliesportiva SESI', 40, 'aberto', 'esporte'),
('Basquete', 'Campeonato de Basquete dos Jogos Escolares SESI 2025', '2025-08-02', '2025-08-16', 'Quadra de Basquete SESI', 40, 'aberto', 'esporte'),
('Vôlei', 'Campeonato de Vôlei dos Jogos Escolares SESI 2025', '2025-08-02', '2025-08-16', 'Quadra Poliesportiva SESI', 36, 'aberto', 'esporte'),
('Beach Tennis', 'Campeonato de Beach Tennis dos Jogos Escolares SESI 2025', '2025-08-03', '2025-08-17', 'Área de Beach Tennis SESI', 30, 'aberto', 'esporte'),
('Vôlei de Praia', 'Campeonato de Vôlei de Praia dos Jogos Escolares SESI 2025', '2025-08-03', '2025-08-17', 'Arena de Areia SESI', 32, 'aberto', 'esporte'),
('Futevôlei', 'Campeonato de Futevôlei dos Jogos Escolares SESI 2025', '2025-08-04', '2025-08-18', 'Arena de Areia SESI', 28, 'aberto', 'esporte');

-- ============================================
-- INSERIR PLACAR INICIAL PARA CADA JOGO
-- ============================================
INSERT INTO placar (jogo_id, time_a, time_b, placar_a, placar_b, status) VALUES
(1, 'Time A', 'Time B', 0, 0, 'aguardando'),
(2, 'Time A', 'Time B', 0, 0, 'aguardando'),
(3, 'Time A', 'Time B', 0, 0, 'aguardando'),
(4, 'Time A', 'Time B', 0, 0, 'aguardando'),
(5, 'Time A', 'Time B', 0, 0, 'aguardando'),
(6, 'Time A', 'Time B', 0, 0, 'aguardando'),
(7, 'Time A', 'Time B', 0, 0, 'aguardando');

-- ============================================
-- PROFESSOR ADMIN PADRÃO
-- E-mail: admin@sesi.com  |  Senha: admin123
-- ============================================
INSERT INTO professores (nome_completo, cpf, data_nascimento, email, senha) VALUES
('Administrador SESI', '000.000.000-00', '1980-01-01', 'admin@sesi.com', '$2y$12$ZYi/3MbJIrmM2RmR3C.YUOap3HrbZGieuYDJ.kKkb2rHfxoogaZhe');