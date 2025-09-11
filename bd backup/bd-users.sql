
-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    data_nascimento DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('ativo', 'inativo', 'pendente') DEFAULT 'ativo',
    email_verificado BOOLEAN DEFAULT FALSE,
    token_verificacao VARCHAR(255) NULL,
    token_reset_senha VARCHAR(255) NULL,
    token_reset_expira DATETIME NULL
);

-- Tabela de sessões (opcional, para controle de sessões)
CREATE TABLE sessoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Índices para otimização
CREATE INDEX idx_email ON usuarios(email);
CREATE INDEX idx_token_verificacao ON usuarios(token_verificacao);
CREATE INDEX idx_token_reset ON usuarios(token_reset_senha);
CREATE INDEX idx_sessao_token ON sessoes(token);
CREATE INDEX idx_sessao_usuario ON sessoes(usuario_id);