-- ============================================
-- ATUALIZAÇÃO DO BANCO - Novas colunas para jogos
-- Execute este arquivo se já tiver o banco criado
-- ============================================

SET NAMES utf8mb4;

USE planer_sesi;

-- Adicionar coluna de ícone personalizado (caso não exista)
ALTER TABLE jogos 
ADD COLUMN IF NOT EXISTS icone VARCHAR(32) DEFAULT 'esporte',
ADD COLUMN IF NOT EXISTS cor_primaria VARCHAR(7) DEFAULT '#CC0000',
ADD COLUMN IF NOT EXISTS cor_secundaria VARCHAR(7) DEFAULT '#990000',
ADD COLUMN IF NOT EXISTS criado_por INT NULL,
ADD COLUMN IF NOT EXISTS imagem_url VARCHAR(255) NULL;

-- Atualizar ícones dos jogos existentes
UPDATE jogos SET icone = 'esporte' WHERE nome = 'Futsal';
UPDATE jogos SET icone = 'esporte' WHERE nome = 'Handebol';
UPDATE jogos SET icone = 'esporte' WHERE nome = 'Basquete';
UPDATE jogos SET icone = 'esporte' WHERE nome = 'Vôlei';
UPDATE jogos SET icone = 'esporte' WHERE nome = 'Vôlei de Praia';
UPDATE jogos SET icone = 'esporte' WHERE nome = 'Futevôlei';

-- Corrigir o nome da modalidade em bancos de versões anteriores
UPDATE jogos
SET nome = 'Beach Tennis',
    descricao = REPLACE(REPLACE(descricao, 'Beat Tênis', 'Beach Tennis'), 'Beach Tênis', 'Beach Tennis'),
    local_jogo = REPLACE(REPLACE(local_jogo, 'Beat Tênis', 'Beach Tennis'), 'Beach Tênis', 'Beach Tennis')
WHERE LOWER(nome) IN ('beat tênis', 'beat tenis', 'beach tênis', 'beach tenis');
UPDATE jogos SET icone = 'esporte' WHERE nome = 'Beach Tennis';
-- Garanta apenas um placar por modalidade (execute uma vez em bancos antigos):
-- ALTER TABLE placar ADD UNIQUE KEY unique_placar_jogo (jogo_id);
