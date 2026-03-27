-- Schema for Personal Financial Management System (SGF)
-- Database: finan_db (or as configured)

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    cargo ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    primeiro_acesso BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('receita', 'despesa', 'ambos') DEFAULT 'ambos',
    cor VARCHAR(7) DEFAULT '#3498db',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    categoria_id INT NOT NULL,
    tipo ENUM('receita', 'despesa') NOT NULL,
    data DATE NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

-- Initial Admin Password: Admin@123 (Bcrypt hashed)
-- Use: password_hash('Admin@123', PASSWORD_BCRYPT)
INSERT INTO usuarios (nome, usuario, senha, cargo, status, primeiro_acesso)
SELECT 'Administrador', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'ativo', TRUE
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE usuario = 'admin');

-- Default Categories
INSERT INTO categorias (nome, tipo, cor) VALUES
('Salário', 'receita', '#2ecc71'),
('Alimentação', 'despesa', '#e67e22'),
('Moradia', 'despesa', '#e74c3c'),
('Transporte', 'despesa', '#f1c40b'),
('Lazer', 'despesa', '#9b59b6'),
('Investimentos', 'ambos', '#3498db');
