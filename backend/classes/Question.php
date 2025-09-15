<?php
// backend/classes/Question.php
class Question {
    private $conn;
    private $table = 'questions';

    public function __construct($db) {
        $this->conn = $db;
    }

   
    public function create($data) {
        $query = "INSERT INTO " . $this->table . " 
                  (question_text, subject, difficulty, grade, question_type, alternatives, correct_answer, 
                   essay_guidelines, max_score, tags, created_by, created_at) 
                  VALUES (:question_text, :subject, :difficulty, :grade, :question_type, :alternatives, 
                          :correct_answer, :essay_guidelines, :max_score, :tags, :created_by, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
       
        $alternatives = null;
        if ($data['question_type'] === 'multipla_escolha' && isset($data['alternatives'])) {
            $alternatives = json_encode($data['alternatives']);
        }
        
      
        $stmt->bindParam(':question_text', $data['question_text']);
        $stmt->bindParam(':subject', $data['subject']);
        $stmt->bindParam(':difficulty', $data['difficulty']);
        $stmt->bindParam(':grade', $data['grade']);
        $stmt->bindParam(':question_type', $data['question_type']);
        $stmt->bindParam(':alternatives', $alternatives);
        $stmt->bindParam(':correct_answer', $data['correct_answer']);
        $stmt->bindParam(':essay_guidelines', $data['essay_guidelines'] ?? null);
        $stmt->bindParam(':max_score', $data['max_score'] ?? 1);
        $stmt->bindParam(':tags', $data['tags'] ?? null);
        $stmt->bindParam(':created_by', $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Questão criada com sucesso',
                'question_id' => $this->conn->lastInsertId()
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Erro ao criar questão: ' . $stmt->errorInfo()[2]
        ];
    }

    // Buscar questões com filtros
    public function search($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT q.*, u.name as created_by_name 
                  FROM " . $this->table . " q 
                  LEFT JOIN users u ON q.created_by = u.id 
                  WHERE q.deleted_at IS NULL";
        
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters['search_term'])) {
            $query .= " AND q.question_text LIKE :search_term";
            $params['search_term'] = '%' . $filters['search_term'] . '%';
        }
        
        if (!empty($filters['subject'])) {
            $query .= " AND q.subject = :subject";
            $params['subject'] = $filters['subject'];
        }
        
        if (!empty($filters['difficulty'])) {
            $query .= " AND q.difficulty = :difficulty";
            $params['difficulty'] = $filters['difficulty'];
        }
        
        if (!empty($filters['grade'])) {
            $query .= " AND q.grade = :grade";
            $params['grade'] = $filters['grade'];
        }
        
        // Ordenação
        $query .= " ORDER BY q.created_at DESC";
        
        // Contar total de registros
        $countQuery = "SELECT COUNT(*) as total FROM (" . $query . ") as count_table";
        $countStmt = $this->conn->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindParam(':' . $key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Aplicar paginação
        $query .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindParam(':' . $key, $value);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Processar alternativas JSON
        foreach ($questions as &$question) {
            if ($question['alternatives']) {
                $question['alternatives'] = json_decode($question['alternatives'], true);
            }
        }
        
        return [
            'success' => true,
            'data' => [
                'questions' => $questions,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'items_per_page' => $limit
                ]
            ]
        ];
    }

    // Buscar questão por ID
    public function getById($id) {
        $query = "SELECT q.*, u.name as created_by_name 
                  FROM " . $this->table . " q 
                  LEFT JOIN users u ON q.created_by = u.id 
                  WHERE q.id = :id AND q.deleted_at IS NULL";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $question = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($question) {
            if ($question['alternatives']) {
                $question['alternatives'] = json_decode($question['alternatives'], true);
            }
            return [
                'success' => true,
                'data' => $question
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Questão não encontrada'
        ];
    }

    // Atualizar questão
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET question_text = :question_text, 
                      subject = :subject, 
                      difficulty = :difficulty, 
                      grade = :grade, 
                      question_type = :question_type,
                      alternatives = :alternatives,
                      correct_answer = :correct_answer,
                      essay_guidelines = :essay_guidelines,
                      max_score = :max_score,
                      tags = :tags,
                      updated_at = NOW()
                  WHERE id = :id AND deleted_at IS NULL";
        
        $stmt = $this->conn->prepare($query);
        
        // Preparar alternativas
        $alternatives = null;
        if ($data['question_type'] === 'multipla_escolha' && isset($data['alternatives'])) {
            $alternatives = json_encode($data['alternatives']);
        }
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':question_text', $data['question_text']);
        $stmt->bindParam(':subject', $data['subject']);
        $stmt->bindParam(':difficulty', $data['difficulty']);
        $stmt->bindParam(':grade', $data['grade']);
        $stmt->bindParam(':question_type', $data['question_type']);
        $stmt->bindParam(':alternatives', $alternatives);
        $stmt->bindParam(':correct_answer', $data['correct_answer']);
        $stmt->bindParam(':essay_guidelines', $data['essay_guidelines'] ?? null);
        $stmt->bindParam(':max_score', $data['max_score'] ?? 1);
        $stmt->bindParam(':tags', $data['tags'] ?? null);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Questão atualizada com sucesso'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Erro ao atualizar questão'
        ];
    }

    // Excluir questão (soft delete)
    public function delete($id) {
        $query = "UPDATE " . $this->table . " 
                  SET deleted_at = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            return [
                'success' => true,
                'message' => 'Questão excluída com sucesso'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Erro ao excluir questão ou questão não encontrada'
        ];     
    }

    // Excluir múltiplas questões
    public function bulkDelete($ids) {
        if (empty($ids)) {
            return [
                'success' => false,
                'message' => 'Nenhuma questão selecionada'
            ];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $query = "UPDATE " . $this->table . " 
                  SET deleted_at = NOW() 
                  WHERE id IN ($placeholders)";
        
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute($ids)) {
            return [
                'success' => true,
                'message' => count($ids) . ' questão(ões) excluída(s) com sucesso'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Erro ao excluir questões'
        ];
    }

    // Importar questões de arquivo
    public function importFromFile($filePath, $fileType) {
        $questions = [];
        
        try {
            switch ($fileType) {
                case 'json':
                    $jsonData = file_get_contents($filePath);
                    $questions = json_decode($jsonData, true);
                    break;
                    
                case 'csv':
                    $questions = $this->parseCsvFile($filePath);
                    break;
                    
                case 'xlsx':
                    // Requires PhpSpreadsheet library
                    $questions = $this->parseExcelFile($filePath);
                    break;
                    
                default:
                    return [
                        'success' => false,
                        'message' => 'Formato de arquivo não suportado'
                    ];
            }
            
            $imported = 0;
            $errors = [];
            
            foreach ($questions as $index => $questionData) {
                $result = $this->create($questionData);
                if ($result['success']) {
                    $imported++;
                } else {
                    $errors[] = "Linha " . ($index + 1) . ": " . $result['message'];
                }
            }
            
            return [
                'success' => true,
                'message' => "$imported questão(ões) importada(s) com sucesso",
                'imported_count' => $imported,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao processar arquivo: ' . $e->getMessage()
            ];
        }
    }

    // Exportar questões selecionadas
    public function exportQuestions($ids, $format = 'json') {
        if (empty($ids)) {
            return [
                'success' => false,
                'message' => 'Nenhuma questão selecionada'
            ];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id IN ($placeholders) AND deleted_at IS NULL";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($ids);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Processar alternativas JSON
        foreach ($questions as &$question) {
            if ($question['alternatives']) {
                $question['alternatives'] = json_decode($question['alternatives'], true);
            }
            // Remove campos internos
            unset($question['created_by'], $question['deleted_at']);
        }
        
        switch ($format) {
            case 'json':
                $filename = 'questoes_' . date('Y-m-d_H-i-s') . '.json';
                $content = json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $mimeType = 'application/json';
                break;
                
            case 'csv':
                $filename = 'questoes_' . date('Y-m-d_H-i-s') . '.csv';
                $content = $this->generateCsv($questions);
                $mimeType = 'text/csv';
                break;
                
            default:
                return [
                    'success' => false,
                    'message' => 'Formato de exportação não suportado'
                ];
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'content' => $content,
            'mime_type' => $mimeType
        ];
    }

    private function parseCsvFile($filePath) {
        $questions = [];
        $file = fopen($filePath, 'r');
        
        // Skip header
        $headers = fgetcsv($file);
        
        while (($data = fgetcsv($file)) !== FALSE) {
            $questions[] = [
                'question_text' => $data[0],
                'subject' => $data[1],
                'difficulty' => $data[2],
                'grade' => $data[3],
                'question_type' => $data[4],
                'correct_answer' => $data[5],
                'alternatives' => isset($data[6]) ? json_decode($data[6], true) : null,
                'essay_guidelines' => isset($data[7]) ? $data[7] : null,
                'max_score' => isset($data[8]) ? $data[8] : 1,
                'tags' => isset($data[9]) ? $data[9] : null
            ];
        }
        
        fclose($file);
        return $questions;
    }

    private function generateCsv($questions) {
        $output = fopen('php://temp', 'r+');
        
        // Headers
        fputcsv($output, [
            'Questão', 'Disciplina', 'Dificuldade', 'Série', 'Tipo', 
            'Resposta Correta', 'Alternativas', 'Critérios', 'Pontuação', 'Tags'
        ]);
        
        foreach ($questions as $question) {
            fputcsv($output, [
                $question['question_text'],
                $question['subject'],
                $question['difficulty'],
                $question['grade'],
                $question['question_type'],
                $question['correct_answer'],
                $question['alternatives'] ? json_encode($question['alternatives']) : '',
                $question['essay_guidelines'] ?? '',
                $question['max_score'] ?? 1,
                $question['tags'] ?? ''
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    // Obter estatísticas
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN difficulty = 'facil' THEN 1 END) as facil,
                    COUNT(CASE WHEN difficulty = 'medio' THEN 1 END) as medio,
                    COUNT(CASE WHEN difficulty = 'dificil' THEN 1 END) as dificil,
                    COUNT(CASE WHEN subject = 'matematica' THEN 1 END) as matematica,
                    COUNT(CASE WHEN subject = 'portugues' THEN 1 END) as portugues,
                    COUNT(CASE WHEN subject = 'ciencias' THEN 1 END) as ciencias,
                    COUNT(CASE WHEN subject = 'historia' THEN 1 END) as historia,
                    COUNT(CASE WHEN subject = 'geografia' THEN 1 END) as geografia,
                    COUNT(CASE WHEN subject = 'ingles' THEN 1 END) as ingles
                  FROM " . $this->table . " 
                  WHERE deleted_at IS NULL";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
            
        return [
            'success' => true,
            'data' => $stmt->fetch(PDO::FETCH_ASSOC)
        ];
    }
}