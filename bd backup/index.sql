-- =============================================
-- SISTEMA DE PROVAS SEICE
-- Banco de Dados MySQL - Versão 1.0
-- =============================================

-- Criação do banco de dados
DROP DATABASE IF EXISTS sistema_seice;
CREATE DATABASE sistema_seice 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE sistema_seice;

-- =============================================
-- TABELAS DE USUÁRIOS E AUTENTICAÇÃO
-- =============================================

-- Tabela de tipos de usuários
CREATE TABLE tipos_usuario (
    id_tipo_usuario INT PRIMARY KEY AUTO_INCREMENT,
    nome_tipo VARCHAR(50) NOT NULL UNIQUE,
    descricao TEXT,
    permissoes JSON,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela principal de usuários
CREATE TABLE usuarios (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    nome_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    cpf CHAR(11) UNIQUE,
    data_nascimento DATE,
    telefone VARCHAR(20),
    endereco TEXT,
    instituicao VARCHAR(255),
    matricula_registro VARCHAR(100),
    id_tipo_usuario INT NOT NULL,
    status ENUM('ativo', 'inativo', 'suspenso', 'pendente') DEFAULT 'pendente',
    email_verificado BOOLEAN DEFAULT FALSE,
    token_verificacao VARCHAR(255),
    aceita_newsletter BOOLEAN DEFAULT FALSE,
    foto_perfil VARCHAR(500),
    ultimo_login TIMESTAMP NULL,
    tentativas_login INT DEFAULT 0,
    bloqueado_ate TIMESTAMP NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_tipo_usuario) REFERENCES tipos_usuario(id_tipo_usuario),
    INDEX idx_email (email),
    INDEX idx_cpf (cpf),
    INDEX idx_status (status),
    INDEX idx_tipo_usuario (id_tipo_usuario)
);

-- Tabela de tokens de recuperação de senha
CREATE TABLE tokens_recuperacao (
    id_token INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    usado BOOLEAN DEFAULT FALSE,
    data_expiracao TIMESTAMP NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_usuario (id_usuario)
);

-- Tabela de sessões de usuário
CREATE TABLE sessoes_usuario (
    id_sessao INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    token_sessao VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    ativa BOOLEAN DEFAULT TRUE,
    data_expiracao TIMESTAMP NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_token (token_sessao),
    INDEX idx_usuario (id_usuario)
);

-- =============================================
-- TABELAS DE DISCIPLINAS E CURSOS
-- =============================================

-- Tabela de cursos
CREATE TABLE cursos (
    id_curso INT PRIMARY KEY AUTO_INCREMENT,
    nome_curso VARCHAR(255) NOT NULL,
    descricao TEXT,
    codigo_curso VARCHAR(50) UNIQUE,
    carga_horaria INT,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_codigo (codigo_curso),
    INDEX idx_status (status)
);

-- Tabela de disciplinas
CREATE TABLE disciplinas (
    id_disciplina INT PRIMARY KEY AUTO_INCREMENT,
    nome_disciplina VARCHAR(255) NOT NULL,
    codigo_disciplina VARCHAR(50) UNIQUE,
    descricao TEXT,
    carga_horaria INT,
    id_curso INT,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_curso) REFERENCES cursos(id_curso),
    INDEX idx_codigo (codigo_disciplina),
    INDEX idx_curso (id_curso),
    INDEX idx_status (status)
);

-- Tabela de turmas
CREATE TABLE turmas (
    id_turma INT PRIMARY KEY AUTO_INCREMENT,
    nome_turma VARCHAR(255) NOT NULL,
    codigo_turma VARCHAR(50) UNIQUE,
    id_disciplina INT NOT NULL,
    id_professor INT NOT NULL,
    ano_letivo YEAR NOT NULL,
    semestre TINYINT NOT NULL CHECK (semestre IN (1, 2)),
    capacidade_maxima INT DEFAULT 50,
    status ENUM('ativo', 'inativo', 'concluido') DEFAULT 'ativo',
    data_inicio DATE,
    data_fim DATE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_disciplina) REFERENCES disciplinas(id_disciplina),
    FOREIGN KEY (id_professor) REFERENCES usuarios(id_usuario),
    INDEX idx_codigo (codigo_turma),
    INDEX idx_disciplina (id_disciplina),
    INDEX idx_professor (id_professor),
    INDEX idx_ano_semestre (ano_letivo, semestre)
);

-- Tabela de matrículas (relacionamento aluno-turma)
CREATE TABLE matriculas (
    id_matricula INT PRIMARY KEY AUTO_INCREMENT,
    id_aluno INT NOT NULL,
    id_turma INT NOT NULL,
    status ENUM('ativo', 'cancelado', 'concluido', 'trancado') DEFAULT 'ativo',
    nota_final DECIMAL(5,2) DEFAULT NULL,
    frequencia DECIMAL(5,2) DEFAULT NULL,
    situacao_final ENUM('aprovado', 'reprovado', 'em_andamento') DEFAULT 'em_andamento',
    data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_aluno) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_turma) REFERENCES turmas(id_turma),
    UNIQUE KEY unique_aluno_turma (id_aluno, id_turma),
    INDEX idx_aluno (id_aluno),
    INDEX idx_turma (id_turma),
    INDEX idx_status (status)
);

-- =============================================
-- TABELAS DE PROVAS E QUESTÕES
-- =============================================

-- Tabela de categorias de questões
CREATE TABLE categorias_questao (
    id_categoria INT PRIMARY KEY AUTO_INCREMENT,
    nome_categoria VARCHAR(255) NOT NULL,
    descricao TEXT,
    cor_identificacao VARCHAR(7), -- Para cores em hexadecimal
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de banco de questões
CREATE TABLE questoes (
    id_questao INT PRIMARY KEY AUTO_INCREMENT,
    id_disciplina INT NOT NULL,
    id_categoria INT, 
    id_professor_autor INT NOT NULL,
    titulo VARCHAR(500),
    enunciado TEXT NOT NULL,
    tipo_questao ENUM('multipla_escolha', 'verdadeiro_falso', 'dissertativa', 'numerica', 'associacao') NOT NULL,
    nivel_dificuldade ENUM('facil', 'medio', 'dificil') DEFAULT 'medio',
    tempo_resposta_sugerido INT, -- em minutos
    pontuacao_padrao DECIMAL(5,2) DEFAULT 1.00,
    explicacao_resposta TEXT,
    tags JSON, -- Tags para busca e categorização
    uso_publico BOOLEAN DEFAULT FALSE,
    ativa BOOLEAN DEFAULT TRUE,
    aprovada BOOLEAN DEFAULT FALSE,
    id_aprovador INT NULL,
    data_aprovacao TIMESTAMP NULL,
    estatisticas JSON, -- Estatísticas de uso e acertos
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_disciplina) REFERENCES disciplinas(id_disciplina),
    FOREIGN KEY (id_categoria) REFERENCES categorias_questao(id_categoria),
    FOREIGN KEY (id_professor_autor) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_aprovador) REFERENCES usuarios(id_usuario),
    INDEX idx_disciplina (id_disciplina),
    INDEX idx_categoria (id_categoria),
    INDEX idx_autor (id_professor_autor),
    INDEX idx_tipo (tipo_questao),
    INDEX idx_dificuldade (nivel_dificuldade),
    FULLTEXT idx_busca (titulo, enunciado)
);

-- Tabela de alternativas (para questões de múltipla escolha)
CREATE TABLE alternativas_questao (
    id_alternativa INT PRIMARY KEY AUTO_INCREMENT,
    id_questao INT NOT NULL,
    letra_alternativa CHAR(1) NOT NULL,
    texto_alternativa TEXT NOT NULL,
    correta BOOLEAN DEFAULT FALSE,
    explicacao TEXT,
    ordem_exibicao TINYINT DEFAULT 1,
    
    FOREIGN KEY (id_questao) REFERENCES questoes(id_questao) ON DELETE CASCADE,
    INDEX idx_questao (id_questao),
    INDEX idx_correta (correta)
);

-- Tabela de arquivos/mídias das questões
CREATE TABLE midias_questao (
    id_midia INT PRIMARY KEY AUTO_INCREMENT,
    id_questao INT NOT NULL,
    tipo_midia ENUM('imagem', 'audio', 'video', 'documento') NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    tamanho_arquivo INT, -- em bytes
    posicao_questao ENUM('enunciado', 'alternativa', 'explicacao') DEFAULT 'enunciado',
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_questao) REFERENCES questoes(id_questao) ON DELETE CASCADE,
    INDEX idx_questao (id_questao),
    INDEX idx_tipo (tipo_midia)
);

-- Tabela de provas
CREATE TABLE provas (
    id_prova INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(500) NOT NULL,
    descricao TEXT,
    id_disciplina INT NOT NULL,
    id_professor_criador INT NOT NULL,
    id_turma INT,
    tipo_prova ENUM('diagnostica', 'formativa', 'somativa', 'recuperacao', 'final') DEFAULT 'formativa',
    data_abertura TIMESTAMP NOT NULL,
    data_fechamento TIMESTAMP NOT NULL,
    duracao_minutos INT NOT NULL,
    tentativas_permitidas TINYINT DEFAULT 1,
    nota_minima_aprovacao DECIMAL(5,2) DEFAULT NULL,
    peso_prova DECIMAL(3,2) DEFAULT 1.00,
    
    -- Configurações da prova
    embaralhar_questoes BOOLEAN DEFAULT FALSE,
    embaralhar_alternativas BOOLEAN DEFAULT FALSE,
    mostrar_resultado_imediato BOOLEAN DEFAULT FALSE,
    permitir_voltar_questao BOOLEAN DEFAULT TRUE,
    mostrar_gabarito BOOLEAN DEFAULT FALSE,
    data_liberacao_gabarito TIMESTAMP NULL,
    
    -- Configurações de segurança
    ip_restrito VARCHAR(500), -- IPs permitidos separados por vírgula
    navegador_completo BOOLEAN DEFAULT FALSE, -- Modo kiosk
    detectar_mudanca_foco BOOLEAN DEFAULT FALSE,
    capturar_webcam BOOLEAN DEFAULT FALSE,
    
    status ENUM('rascunho', 'publicada', 'em_andamento', 'finalizada', 'cancelada') DEFAULT 'rascunho',
    publicada_em TIMESTAMP NULL,
    
    -- Metadados
    instrucoes TEXT,
    observacoes TEXT,
    
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_disciplina) REFERENCES disciplinas(id_disciplina),
    FOREIGN KEY (id_professor_criador) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_turma) REFERENCES turmas(id_turma),
    INDEX idx_disciplina (id_disciplina),
    INDEX idx_professor (id_professor_criador),
    INDEX idx_turma (id_turma),
    INDEX idx_status (status),
    INDEX idx_datas (data_abertura, data_fechamento)
);

-- Tabela de questões da prova (relacionamento prova-questão)
CREATE TABLE prova_questoes (
    id_prova_questao INT PRIMARY KEY AUTO_INCREMENT,
    id_prova INT NOT NULL,
    id_questao INT NOT NULL,
    ordem_questao INT NOT NULL,
    pontuacao DECIMAL(5,2) NOT NULL,
    obrigatoria BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (id_prova) REFERENCES provas(id_prova) ON DELETE CASCADE,
    FOREIGN KEY (id_questao) REFERENCES questoes(id_questao),
    UNIQUE KEY unique_prova_questao (id_prova, id_questao),
    INDEX idx_prova (id_prova),
    INDEX idx_questao (id_questao),
    INDEX idx_ordem (ordem_questao)
);

-- =============================================
-- TABELAS DE RESPOSTAS E TENTATIVAS
-- =============================================

-- Tabela de tentativas de prova
CREATE TABLE tentativas_prova (
    id_tentativa INT PRIMARY KEY AUTO_INCREMENT,
    id_prova INT NOT NULL,
    id_aluno INT NOT NULL,
    numero_tentativa TINYINT NOT NULL,
    data_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_fim TIMESTAMP NULL,
    duracao_segundos INT DEFAULT 0,
    
    -- Status da tentativa
    status ENUM('em_andamento', 'finalizada', 'cancelada', 'tempo_esgotado') DEFAULT 'em_andamento',
    finalizada_pelo_aluno BOOLEAN DEFAULT FALSE,
    
    -- Pontuação
    nota_obtida DECIMAL(5,2) DEFAULT 0,
    nota_maxima DECIMAL(5,2) NOT NULL,
    percentual_acerto DECIMAL(5,2) DEFAULT 0,
    
    -- Informações técnicas
    ip_acesso VARCHAR(45),
    user_agent TEXT,
    mudancas_foco INT DEFAULT 0, -- Quantas vezes saiu da aba
    tempo_total_fora_foco INT DEFAULT 0, -- Tempo em segundos fora da aba
    
    -- Dados de segurança
    dados_webcam JSON, -- URLs das capturas se habilitado
    eventos_suspeitos JSON, -- Log de eventos suspeitos
    
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_prova) REFERENCES provas(id_prova),
    FOREIGN KEY (id_aluno) REFERENCES usuarios(id_usuario),
    UNIQUE KEY unique_aluno_prova_tentativa (id_aluno, id_prova, numero_tentativa),
    INDEX idx_prova (id_prova),
    INDEX idx_aluno (id_aluno),
    INDEX idx_status (status)
);

-- Tabela de respostas dos alunos
CREATE TABLE respostas_aluno (
    id_resposta INT PRIMARY KEY AUTO_INCREMENT,
    id_tentativa INT NOT NULL,
    id_prova_questao INT NOT NULL,
    
    -- Resposta para questões objetivas
    id_alternativa_escolhida INT NULL,
    
    -- Resposta para questões dissertativas/numéricas
    resposta_texto TEXT,
    resposta_numerica DECIMAL(15,6),
    
    -- Correção
    pontuacao_obtida DECIMAL(5,2) DEFAULT NULL,
    correta BOOLEAN DEFAULT NULL,
    corrigida BOOLEAN DEFAULT FALSE,
    feedback_professor TEXT,
    id_professor_corretor INT NULL,
    data_correcao TIMESTAMP NULL,
    
    -- Metadados
    tempo_resposta_segundos INT DEFAULT 0,
    marcada_revisao BOOLEAN DEFAULT FALSE,
    data_primeira_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_ultima_modificacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_tentativa) REFERENCES tentativas_prova(id_tentativa) ON DELETE CASCADE,
    FOREIGN KEY (id_prova_questao) REFERENCES prova_questoes(id_prova_questao),
    FOREIGN KEY (id_alternativa_escolhida) REFERENCES alternativas_questao(id_alternativa),
    FOREIGN KEY (id_professor_corretor) REFERENCES usuarios(id_usuario),
    UNIQUE KEY unique_tentativa_questao (id_tentativa, id_prova_questao),
    INDEX idx_tentativa (id_tentativa),
    INDEX idx_prova_questao (id_prova_questao),
    INDEX idx_corretor (id_professor_corretor)
);

-- =============================================
-- TABELAS DE NOTIFICAÇÕES E LOGS
-- =============================================

-- Tabela de notificações
CREATE TABLE notificacoes (
    id_notificacao INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    tipo_notificacao ENUM('prova_disponivel', 'prova_corrigida', 'lembrete_prova', 'sistema', 'academica') NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    lida BOOLEAN DEFAULT FALSE,
    data_leitura TIMESTAMP NULL,
    link_acao VARCHAR(500),
    dados_extras JSON,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_usuario (id_usuario),
    INDEX idx_tipo (tipo_notificacao),
    INDEX idx_lida (lida)
);

-- Tabela de logs do sistema
CREATE TABLE logs_sistema (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NULL,
    acao VARCHAR(255) NOT NULL,
    tabela_afetada VARCHAR(100),
    id_registro_afetado INT,
    dados_anteriores JSON,
    dados_novos JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    nivel_log ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    INDEX idx_usuario (id_usuario),
    INDEX idx_acao (acao),
    INDEX idx_tabela (tabela_afetada),
    INDEX idx_data (data_criacao),
    INDEX idx_nivel (nivel_log)
);

-- Tabela de configurações do sistema
CREATE TABLE configuracoes_sistema (
    id_config INT PRIMARY KEY AUTO_INCREMENT,
    chave_config VARCHAR(255) NOT NULL UNIQUE,
    valor_config TEXT,
    tipo_valor ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    descricao TEXT,
    categoria VARCHAR(100) DEFAULT 'geral',
    editavel BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_categoria (categoria),
    INDEX idx_chave (chave_config)
);

-- =============================================
-- VIEWS ÚTEIS PARA RELATÓRIOS
-- =============================================

-- View de relatório de notas por turma
CREATE VIEW relatorio_notas_turma AS
SELECT 
    t.nome_turma,
    d.nome_disciplina,
    u.nome_completo as nome_aluno,
    p.titulo as titulo_prova,
    tp.nota_obtida,
    tp.percentual_acerto,
    tp.data_fim,
    tp.status,
    CASE 
        WHEN tp.nota_obtida >= p.nota_minima_aprovacao THEN 'Aprovado'
        ELSE 'Reprovado'
    END as situacao
FROM tentativas_prova tp
JOIN provas p ON tp.id_prova = p.id_prova
JOIN usuarios u ON tp.id_aluno = u.id_usuario
JOIN turmas t ON p.id_turma = t.id_turma
JOIN disciplinas d ON p.id_disciplina = d.id_disciplina
WHERE tp.status = 'finalizada';

-- View de estatísticas de questões
CREATE VIEW estatisticas_questoes AS
SELECT 
    q.id_questao,
    q.titulo,
    q.enunciado,
    d.nome_disciplina,
    COUNT(ra.id_resposta) as total_respostas,
    COUNT(CASE WHEN ra.correta = TRUE THEN 1 END) as respostas_corretas,
    ROUND((COUNT(CASE WHEN ra.correta = TRUE THEN 1 END) / COUNT(ra.id_resposta)) * 100, 2) as percentual_acerto,
    AVG(ra.tempo_resposta_segundos) as tempo_medio_resposta
FROM questoes q
LEFT JOIN prova_questoes pq ON q.id_questao = pq.id_questao
LEFT JOIN respostas_aluno ra ON pq.id_prova_questao = ra.id_prova_questao
JOIN disciplinas d ON q.id_disciplina = d.id_disciplina
GROUP BY q.id_questao;

-- View de desempenho por aluno
CREATE VIEW desempenho_aluno AS
SELECT 
    u.id_usuario,
    u.nome_completo,
    d.nome_disciplina,
    COUNT(tp.id_tentativa) as total_provas_realizadas,
    AVG(tp.percentual_acerto) as media_percentual,
    AVG(tp.nota_obtida) as media_notas,
    MAX(tp.nota_obtida) as melhor_nota,
    MIN(tp.nota_obtida) as pior_nota
FROM usuarios u
JOIN tentativas_prova tp ON u.id_usuario = tp.id_aluno
JOIN provas p ON tp.id_prova = p.id_prova
JOIN disciplinas d ON p.id_disciplina = d.id_disciplina
WHERE tp.status = 'finalizada'
GROUP BY u.id_usuario, d.id_disciplina;

-- =============================================
-- INSERÇÃO DE DADOS BÁSICOS
-- =============================================

-- Inserir tipos de usuário básicos
INSERT INTO tipos_usuario (nome_tipo, descricao, permissoes) VALUES
('administrador', 'Administrador do sistema com acesso completo', '{"all": true}'),
('professor', 'Professor com permissões de criação e gerenciamento de provas', '{"create_exam": true, "manage_students": true, "view_reports": true}'),
('estudante', 'Estudante com permissões básicas para realizar provas', '{"take_exam": true, "view_grades": true}');

-- Inserir configurações básicas do sistema
INSERT INTO configuracoes_sistema (chave_config, valor_config, tipo_valor, descricao, categoria) VALUES
('sistema_nome', 'Sistema de Provas SEICE', 'string', 'Nome do sistema', 'geral'),
('max_tentativas_login', '5', 'integer', 'Máximo de tentativas de login', 'seguranca'),
('tempo_bloqueio_login', '15', 'integer', 'Tempo de bloqueio após tentativas (minutos)', 'seguranca'),
('duracao_sessao', '480', 'integer', 'Duração da sessão em minutos', 'seguranca'),
('backup_automatico', 'true', 'boolean', 'Ativar backup automático', 'sistema'),
('manutencao_modo', 'false', 'boolean', 'Modo de manutenção', 'sistema'),
('email_smtp_host', '', 'string', 'Servidor SMTP para envio de emails', 'email'),
('email_smtp_port', '587', 'integer', 'Porta do servidor SMTP', 'email'),
('upload_max_size', '10485760', 'integer', 'Tamanho máximo de upload em bytes (10MB)', 'upload');

-- Inserir categorias básicas de questões
INSERT INTO categorias_questao (nome_categoria, descricao, cor_identificacao) VALUES
('Conceitos Básicos', 'Questões sobre conceitos fundamentais', '#3498db'),
('Aplicação Prática', 'Questões de aplicação prática do conhecimento', '#2ecc71'),
('Análise e Síntese', 'Questões que exigem análise e síntese', '#e74c3c'),
('Resolução de Problemas', 'Questões de resolução de problemas complexos', '#f39c12');

-- =============================================
-- TRIGGERS PARA AUDITORIA E VALIDAÇÃO
-- =============================================

-- Trigger para log de alterações em usuários
DELIMITER //
CREATE TRIGGER tr_usuarios_audit 
AFTER UPDATE ON usuarios
FOR EACH ROW
BEGIN
    INSERT INTO logs_sistema (id_usuario, acao, tabela_afetada, id_registro_afetado, dados_anteriores, dados_novos, nivel_log)
    VALUES (NEW.id_usuario, 'UPDATE', 'usuarios', NEW.id_usuario, 
            JSON_OBJECT('nome', OLD.nome_completo, 'email', OLD.email, 'status', OLD.status),
            JSON_OBJECT('nome', NEW.nome_completo, 'email', NEW.email, 'status', NEW.status),
            'info');
END//

-- Trigger para validar data de fechamento de prova
CREATE TRIGGER tr_prova_validacao
BEFORE INSERT ON provas
FOR EACH ROW
BEGIN
    IF NEW.data_fechamento <= NEW.data_abertura THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data de fechamento deve ser posterior à data de abertura';
    END IF;
    
    IF NEW.duracao_minutos <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Duração deve ser maior que zero';
    END IF;
END//

-- Trigger para calcular nota final automaticamente
CREATE TRIGGER tr_calcular_nota_tentativa
AFTER INSERT ON respostas_aluno
FOR EACH ROW
BEGIN
    DECLARE total_pontos DECIMAL(5,2) DEFAULT 0;
    DECLARE pontos_obtidos DECIMAL(5,2) DEFAULT 0;
    
    -- Calcular total de pontos da prova
    SELECT SUM(pq.pontuacao) INTO total_pontos
    FROM prova_questoes pq
    JOIN tentativas_prova tp ON pq.id_prova = tp.id_prova
    WHERE tp.id_tentativa = NEW.id_tentativa;
    
    -- Calcular pontos obtidos pelo aluno
    SELECT SUM(COALESCE(ra.pontuacao_obtida, 0)) INTO pontos_obtidos
    FROM respostas_aluno ra
    WHERE ra.id_tentativa = NEW.id_tentativa;
    
    -- Atualizar tentativa com a nova pontuação
    UPDATE tentativas_prova 
    SET nota_obtida = pontos_obtidos,
        nota_maxima = total_pontos,
        percentual_acerto = (pontos_obtidos / total_pontos) * 100
    WHERE id_tentativa = NEW.id_tentativa;
END//

DELIMITER ;

-- =============================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =============================================

-- Índices compostos para consultas frequentes
CREATE INDEX idx_prova_turma_status ON provas(id_turma, status);
CREATE INDEX idx_tentativa_aluno_data ON tentativas_prova(id_aluno, data_inicio);
CREATE INDEX idx_questao_disciplina_ativa ON questoes(id_disciplina, ativa);
CREATE INDEX idx_usuario_tipo_status ON usuarios(id_tipo_usuario, status);
CREATE INDEX idx_notificacao_usuario_lida ON notificacoes(id_usuario, lida);

-- =============================================
-- MÉTODO ÚTEIS
-- =============================================

-- método para limpeza de dados antigos
DELIMITER //
CREATE PROCEDURE sp_limpeza_dados_antigos()
BEGIN
    -- Limpar tokens de recuperação expirados
    DELETE FROM tokens_recuperacao 
    WHERE data_expiracao < NOW();
    
    -- Limpar sessões expiradas
    DELETE FROM sessoes_usuario 
    WHERE data_expiracao < NOW();
    
    -- Limpar logs antigos (mais de 1 ano)
    DELETE FROM logs_sistema 
    WHERE data_criacao < DATE_SUB(NOW(), INTERVAL 1 YEAR);
    
    -- Limpar notificações antigas lidas (mais de 6 meses)
    DELETE FROM notificacoes 
    WHERE lida = TRUE AND data_leitura < DATE_SUB(NOW(), INTERVAL 6 MONTH);
END//

-- método para relatório de desempenho de turma
CREATE PROCEDURE sp_relatorio_desempenho_turma(IN p_id_turma INT)
BEGIN
    SELECT 
        u.nome_completo,
        COUNT(tp.id_tentativa) as total_provas,
        AVG(tp.percentual_acerto) as media_percentual,
        AVG(tp.nota_obtida) as media_notas,
        SUM(CASE WHEN tp.percentual_acerto >= 70 THEN 1 ELSE 0 END) as provas_aprovadas
    FROM usuarios u
    JOIN matriculas m ON u.id_usuario = m.id_aluno
    LEFT JOIN tentativas_prova tp ON u.id_usuario = tp.id_aluno
    LEFT JOIN provas p ON tp.id_prova = p.id_prova AND p.id_turma = p_id_turma
    WHERE m.id_turma = p_id_turma AND m.status = 'ativo'
    GROUP BY u.id_usuario, u.nome_completo
    ORDER BY media_percentual DESC;
END//

DELIMITER ;

-- =============================================
-- COMENTÁRIOS FINAIS
-- =============================================

/*
Este banco de dados foi projetado para suportar:

1. GESTÃO DE USUÁRIOS:
   - Diferentes tipos de usuário com permissões específicas
   - Sistema de autenticação seguro com tokens
   - Controle de sessões e tentativas de login

2. ESTRUTURA ACADÊMICA:
   - Cursos, disciplinas e turmas
   - Sistema de matrículas
   - Relacionamentos entre professores e alunos

3. SISTEMA DE PROVAS:
   - Banco de questões categorizadas
   - Provas personalizáveis com múltiplas configurações
   - Suporte a diferentes tipos de questões
   - Sistema de tentativas e correção

4. SEGURANÇA E AUDITORIA:
   - Logs detalhados de ações
   - Triggers para validação
   - Controle de acesso baseado em perfis

5. RELATÓRIOS E ANÁLISES:
   - Views pré-configuradas para relatórios
   - métodos para análises específicas
   - Estatísticas de desempenho

6. PERFORMANCE:
   - Índices otimizados para consultas frequentes
   - Estrutura normalizada para evitar redundância
   - método para manutenção automática

O banco suporta crescimento e pode ser facilmente expandido
conforme novas funcionalidades sejam necessárias.
*/