-- Schema for Personal Financial Management System (SGF)
-- Database: finan_db (or as configured)

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NULL,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    cargo ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    primeiro_acesso BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('receita', 'despesa', 'ambos') DEFAULT 'ambos',
    cor VARCHAR(7) DEFAULT '#3498db',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    grupo_id INT NULL,
    categoria_id INT NOT NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    data DATE NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

-- Default Group
INSERT INTO grupos (nome) SELECT 'Geral' WHERE NOT EXISTS (SELECT 1 FROM grupos WHERE nome = 'Geral');

-- Update existing records to use the default group
UPDATE usuarios SET grupo_id = (SELECT id FROM grupos WHERE nome = 'Geral' LIMIT 1) WHERE grupo_id IS NULL;
UPDATE categorias SET grupo_id = (SELECT id FROM grupos WHERE nome = 'Geral' LIMIT 1) WHERE grupo_id IS NULL;
UPDATE movimentacoes SET grupo_id = (SELECT id FROM grupos WHERE nome = 'Geral' LIMIT 1) WHERE grupo_id IS NULL;

-- Initial Admin Password: Admin@123 (Bcrypt hashed)
-- Use: password_hash('Admin@123', PASSWORD_BCRYPT)
INSERT INTO usuarios (nome, usuario, senha, cargo, status, primeiro_acesso, grupo_id)
SELECT 'Administrador', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'ativo', TRUE, (SELECT id FROM grupos WHERE nome = 'Geral' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'admin');

-- Default Categories for the first group
SET @geral_id = (SELECT id FROM grupos WHERE nome = 'Geral' LIMIT 1);
INSERT INTO categorias (nome, tipo, cor, grupo_id) VALUES
('Salário', 'receita', '#2ecc71', @geral_id),
('Alimentação', 'despesa', '#e67e22', @geral_id),
('Moradia', 'despesa', '#e74c3c', @geral_id),
('Transporte', 'despesa', '#f1c40b', @geral_id),
('Lazer', 'despesa', '#9b59b6', @geral_id),
('Investimentos', 'ambos', '#3498db', @geral_id);
