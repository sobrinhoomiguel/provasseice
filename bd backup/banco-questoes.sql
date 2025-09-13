-- banco-questoes.sql
-- Estrutura da tabela de questões

CREATE TABLE IF NOT EXISTS `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_text` text NOT NULL COMMENT 'Texto da questão',
  `subject` varchar(50) NOT NULL COMMENT 'Disciplina (matematica, portugues, ciencias, etc)',
  `difficulty` enum('facil','medio','dificil') NOT NULL COMMENT 'Nível de dificuldade',
  `grade` varchar(20) NOT NULL COMMENT 'Série (6ano, 7ano, 8ano, 9ano, 1medio, 2medio, 3medio)',
  `question_type` enum('multipla_escolha','verdadeiro_falso','dissertativa') NOT NULL COMMENT 'Tipo de questão',
  `alternatives` json DEFAULT NULL COMMENT 'Alternativas para múltipla escolha (JSON)',
  `correct_answer` varchar(10) DEFAULT NULL COMMENT 'Resposta correta',
  `essay_guidelines` text DEFAULT NULL COMMENT 'Critérios de avaliação para questões dissertativas',
  `max_score` decimal(3,1) DEFAULT 1.0 COMMENT 'Pontuação máxima da questão',
  `tags` varchar(255) DEFAULT NULL COMMENT 'Tags separadas por vírgula',
  `created_by` int(11) NOT NULL COMMENT 'ID do usuário que criou',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete',
  PRIMARY KEY (`id`),
  KEY `idx_subject` (`subject`),
  KEY `idx_difficulty` (`difficulty`),
  KEY `idx_grade` (`grade`),
  KEY `idx_question_type` (`question_type`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_questions_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Banco de questões para avaliações';

-- ================================

-- indexs para otimização de consultas
CREATE INDEX idx_questions_search ON questions (subject, difficulty, grade, deleted_at);
CREATE INDEX idx_questions_fulltext ON questions (question_text, tags);

-- ================================

-- tabela para histórico de uso das questões (opcional)
CREATE TABLE IF NOT EXISTS `question_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `evaluation_id` int(11) DEFAULT NULL COMMENT 'ID da avaliação onde foi usada',
  `used_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `used_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_question_id` (`question_id`),
  KEY `idx_evaluation_id` (`evaluation_id`),
  KEY `idx_used_by` (`used_by`),
  CONSTRAINT `fk_usage_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usage_user` FOREIGN KEY (`used_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================

-- Inserir dados de exemplo (opcional para testes)
INSERT INTO `questions` (`question_text`, `subject`, `difficulty`, `grade`, `question_type`, `alternatives`, `correct_answer`, `tags`, `created_by`) VALUES
('Qual é o resultado de 2 + 2?', 'matematica', 'facil', '6ano', 'multipla_escolha', 
 '{"a": "3", "b": "4", "c": "5", "d": "6"}', 'b', 'soma, aritmetica, basico', 1),

('A fotossíntese é o processo pelo qual as plantas produzem seu próprio alimento usando luz solar.', 'ciencias', 'medio', '7ano', 'verdadeiro_falso', 
 NULL, 'true', 'fotossintese, plantas, biologia', 1),

('Explique o que é globalização e cite dois aspectos positivos e dois negativos desse processo.', 'geografia', 'dificil', '9ano', 'dissertativa', 
 NULL, NULL, 'globalização, economia, sociedade', 1);

-- ================================

-- views uteis para relatórios
CREATE VIEW `v_questions_stats` AS
SELECT 
    subject,
    difficulty,
    grade,
    question_type,
    COUNT(*) as total_questions,
    COUNT(CASE WHEN deleted_at IS NULL THEN 1 END) as active_questions
FROM questions 
GROUP BY subject, difficulty, grade, question_type;

-- ================================

-- metodo para limpeza de questoes antigas (opcional)
DELIMITER //
CREATE PROCEDURE CleanupOldQuestions(IN days_old INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE question_id INT;
    DECLARE cur CURSOR FOR 
        SELECT id FROM questions 
        WHERE deleted_at IS NOT NULL 
        AND deleted_at < DATE_SUB(NOW(), INTERVAL days_old DAY);
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO question_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Remove referências na tabela de uso
        DELETE FROM question_usage WHERE question_id = question_id;
        
        -- Remove a questão permanentemente
        DELETE FROM questions WHERE id = question_id;
    END LOOP;
    
    CLOSE cur;
    
    SELECT CONCAT('Limpeza concluída. Questões removidas definitivamente após ', days_old, ' dias.') as message;
END //
DELIMITER ;
