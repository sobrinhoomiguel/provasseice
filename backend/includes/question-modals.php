<?php
// includes/question-modals.php
?>

<div class="modal" id="createQuestionModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Nova Questão</h3>
            <button class="modal-close" onclick="closeModal('createQuestionModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="createQuestionForm" onsubmit="createQuestion(event)">
            <div class="form-group">
                <label for="questionText">Enunciado da Questão *</label>
                <textarea id="questionText" name="questionText" class="form-control" rows="4" 
                          placeholder="Digite o enunciado da questão..." required></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="questionSubject">Disciplina *</label>
                    <select id="questionSubject" name="questionSubject" class="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="matematica">Matemática</option>
                        <option value="portugues">Português</option>
                        <option value="ciencias">Ciências</option>
                        <option value="historia">História</option>
                        <option value="geografia">Geografia</option>
                        <option value="ingles">Inglês</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="questionDifficulty">Dificuldade *</label>
                    <select id="questionDifficulty" name="questionDifficulty" class="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="facil">Fácil</option>
                        <option value="medio">Médio</option>
                        <option value="dificil">Difícil</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="questionGrade">Série *</label>
                    <select id="questionGrade" name="questionGrade" class="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="6ano">6º Ano</option>
                        <option value="7ano">7º Ano</option>
                        <option value="8ano">8º Ano</option>
                        <option value="9ano">9º Ano</option>
                        <option value="1medio">1º Médio</option>
                        <option value="2medio">2º Médio</option>
                        <option value="3medio">3º Médio</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="questionType">Tipo de Questão *</label>
                <select id="questionType" name="questionType" class="form-control" required onchange="toggleAnswerOptions()">
                    <option value="">Selecione...</option>
                    <option value="multipla_escolha">Múltipla Escolha</option>
                    <option value="verdadeiro_falso">Verdadeiro ou Falso</option>
                    <option value="dissertativa">Dissertativa</option>
                </select>
            </div>

            
            <div id="multipleChoiceOptions" style="display: none;">
                <h4 style="margin: 20px 0 15px 0; color: #1e3a5f;">Alternativas</h4>
                <div class="form-group">
                    <label>
                        <input type="radio" name="correctAnswer" value="a"> 
                        A) <input type="text" name="alternative_a" placeholder="Digite a alternativa A..." style="width: calc(100% - 60px); margin-left: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="radio" name="correctAnswer" value="b"> 
                        B) <input type="text" name="alternative_b" placeholder="Digite a alternativa B..." style="width: calc(100% - 60px); margin-left: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="radio" name="correctAnswer" value="c"> 
                        C) <input type="text" name="alternative_c" placeholder="Digite a alternativa C..." style="width: calc(100% - 60px); margin-left: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="radio" name="correctAnswer" value="d"> 
                        D) <input type="text" name="alternative_d" placeholder="Digite a alternativa D..." style="width: calc(100% - 60px); margin-left: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="radio" name="correctAnswer" value="e"> 
                        E) <input type="text" name="alternative_e" placeholder="Digite a alternativa E..." style="width: calc(100% - 60px); margin-left: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </label>
                </div>
            </div>

            
            <div id="trueFalseOptions" style="display: none;">
                <h4 style="margin: 20px 0 15px 0; color: #1e3a5f;">Resposta Correta</h4>
                <div class="form-group">
                    <label>
                        <input type="radio" name="trueFalseAnswer" value="true"> Verdadeiro
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="radio" name="trueFalseAnswer" value="false"> Falso
                    </label>
                </div>
            </div>

          
            <div id="essayOptions" style="display: none;">
                <div class="form-group">
                    <label for="essayGuidelines">Critérios de Avaliação</label>
                    <textarea id="essayGuidelines" name="essayGuidelines" class="form-control" rows="3"
                              placeholder="Digite os critérios que devem ser considerados na correção desta questão dissertativa..."></textarea>
                </div>
                <div class="form-group">
                    <label for="maxScore">Pontuação Máxima</label>
                    <input type="number" id="maxScore" name="maxScore" class="form-control" min="1" max="10" value="2">
                </div>
            </div>

            <div class="form-group">
                <label for="questionTags">Tags (opcional)</label>
                <input type="text" id="questionTags" name="questionTags" class="form-control" 
                       placeholder="Digite tags separadas por vírgula (ex: álgebra, equações, fundamental)">
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" class="secondary-btn" onclick="closeModal('createQuestionModal')">
                    Cancelar
                </button>
                <button type="submit" class="primary-btn">
                    <i class="fas fa-save"></i>
                    Salvar Questão
                </button>
            </div>
        </form>
    </div>
</div>


<div class="modal" id="importModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Importar Questões</h3>
            <button class="modal-close" onclick="closeModal('importModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div style="text-align: center; padding: 20px 0;">
            <div style="border: 2px dashed #4a90e2; border-radius: 12px; padding: 40px; margin-bottom: 20px;">
                <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #4a90e2; margin-bottom: 20px;"></i>
                <h4 style="color: #1e3a5f; margin-bottom: 10px;">Selecione o arquivo para importar</h4>
                <p style="color: #666; margin-bottom: 20px;">Formatos aceitos: .xlsx, .csv, .json</p>
                <input type="file" id="importFile" accept=".xlsx,.csv,.json" style="display: none;" onchange="handleFileSelect(event)">
                <button type="button" class="primary-btn" onclick="document.getElementById('importFile').click()">
                    <i class="fas fa-folder-open"></i>
                    Escolher Arquivo
                </button>
            </div>
            <div id="fileInfo" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <p id="fileName" style="margin: 0; color: #333;"></p>
            </div>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button type="button" class="secondary-btn" onclick="closeModal('importModal')">
                    Cancelar
                </button>
                <button type="button" class="primary-btn" id="importBtn" onclick="importQuestions()" disabled>
                    <i class="fas fa-upload"></i>
                    Importar
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// backend/helpers/FileUpload.php
class FileUpload {
    private $uploadDir;
    private $allowedTypes;
    private $maxSize;

    public function __construct($uploadDir = '../uploads/questions/', $maxSize = 10485760) { // 10MB
        $this->uploadDir = $uploadDir;
        $this->maxSize = $maxSize;
        $this->allowedTypes = [
            'application/json' => 'json',
            'text/csv' => 'csv',
            'application/vnd.ms-excel' => 'csv',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'
        ];
        
        // Criar diretório se não existir
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function uploadFile($file) {
        try {
            // Validar erro de upload
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception($this->getUploadErrorMessage($file['error']));
            }

            // Validar tamanho
            if ($file['size'] > $this->maxSize) {
                throw new Exception('Arquivo muito grande. Máximo: ' . $this->formatBytes($this->maxSize));
            }

            // Validar tipo MIME
            $mimeType = mime_content_type($file['tmp_name']);
            if (!isset($this->allowedTypes[$mimeType])) {
                throw new Exception('Tipo de arquivo não permitido: ' . $mimeType);
            }

            // Gerar nome único
            $extension = $this->allowedTypes[$mimeType];
            $filename = uniqid('import_') . '_' . time() . '.' . $extension;
            $filepath = $this->uploadDir . $filename;

            // Mover arquivo
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Falha ao salvar arquivo');
            }

            return [
                'success' => true,
                'filepath' => $filepath,
                'filename' => $filename,
                'extension' => $extension,
                'size' => $file['size']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Arquivo excede o tamanho máximo permitido pelo servidor';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Arquivo excede o tamanho máximo permitido pelo formulário';
            case UPLOAD_ERR_PARTIAL:
                return 'Upload foi interrompido';
            case UPLOAD_ERR_NO_FILE:
                return 'Nenhum arquivo foi enviado';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Diretório temporário não encontrado';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Falha ao escrever arquivo no disco';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload bloqueado por extensão';
            default:
                return 'Erro desconhecido no upload';
        }
    }

    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function cleanupFile($filepath) {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}

// backend/helpers/QuestionValidator.php
class QuestionValidator {
    private $subjects = ['matematica', 'portugues', 'ciencias', 'historia', 'geografia', 'ingles'];
    private $difficulties = ['facil', 'medio', 'dificil'];
    private $grades = ['6ano', '7ano', '8ano', '9ano', '1medio', '2medio', '3medio'];
    private $questionTypes = ['multipla_escolha', 'verdadeiro_falso', 'dissertativa'];

    public function validate($data) {
        $errors = [];

        // Validar campos obrigatórios
        if (empty($data['question_text'])) {
            $errors[] = 'Texto da questão é obrigatório';
        }

        if (empty($data['subject']) || !in_array($data['subject'], $this->subjects)) {
            $errors[] = 'Disciplina inválida';
        }

        if (empty($data['difficulty']) || !in_array($data['difficulty'], $this->difficulties)) {
            $errors[] = 'Dificuldade inválida';
        }

        if (empty($data['grade']) || !in_array($data['grade'], $this->grades)) {
            $errors[] = 'Série inválida';
        }

        if (empty($data['question_type']) || !in_array($data['question_type'], $this->questionTypes)) {
            $errors[] = 'Tipo de questão inválido';
        }

        // Validações específicas por tipo
        switch ($data['question_type']) {
            case 'multipla_escolha':
                if (empty($data['alternatives']) || !is_array($data['alternatives'])) {
                    $errors[] = 'Alternativas são obrigatórias para questões de múltipla escolha';
                } elseif (count($data['alternatives']) < 2) {
                    $errors[] = 'Questões de múltipla escolha devem ter pelo menos 2 alternativas';
                }

                if (empty($data['correct_answer'])) {
                    $errors[] = 'Resposta correta é obrigatória para questões de múltipla escolha';
                } elseif (!isset($data['alternatives'][$data['correct_answer']])) {
                    $errors[] = 'Resposta correta deve corresponder a uma alternativa válida';
                }
                break;

            case 'verdadeiro_falso':
                if (empty($data['correct_answer']) || !in_array($data['correct_answer'], ['true', 'false'])) {
                    $errors[] = 'Resposta correta deve ser "true" ou "false" para questões verdadeiro/falso';
                }
                break;

            case 'dissertativa':
                if (isset($data['max_score']) && ($data['max_score'] < 1 || $data['max_score'] > 10)) {
                    $errors[] = 'Pontuação máxima deve estar entre 1 e 10';
                }
                break;
        }

        // Validar tamanho dos textos
        if (strlen($data['question_text']) > 2000) {
            $errors[] = 'Texto da questão muito longo (máximo 2000 caracteres)';
        }

        if (!empty($data['tags']) && strlen($data['tags']) > 255) {
            $errors[] = 'Tags muito longas (máximo 255 caracteres)';
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }

    public function sanitize($data) {
        return [
            'question_text' => trim(strip_tags($data['question_text'])),
            'subject' => $data['subject'],
            'difficulty' => $data['difficulty'],
            'grade' => $data['grade'],
            'question_type' => $data['question_type'],
            'alternatives' => $data['alternatives'] ?? null,
            'correct_answer' => $data['correct_answer'] ?? null,
            'essay_guidelines' => !empty($data['essay_guidelines']) ? trim(strip_tags($data['essay_guidelines'])) : null,
            'max_score' => isset($data['max_score']) ? (float)$data['max_score'] : 1.0,
            'tags' => !empty($data['tags']) ? trim(strip_tags($data['tags'])) : null
        ];
    }
}

// backend/helpers/ResponseHelper.php
class ResponseHelper {
    public static function success($data = null, $message = null, $code = 200) {
        http_response_code($code);
        echo json_encode([   
            'success' => true,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function error($message, $code = 400, $errors = null) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function unauthorized($message = 'Não autorizado') {
        self::error($message, 401);
    }

    public static function forbidden($message = 'Acesso negado') {
        self::error($message, 403);
    }

    public static function notFound($message = 'Recurso não encontrado') {
        self::error($message, 404);
    }

    public static function serverError($message = 'Erro interno do servidor') {
        error_log($message);
        self::error('Erro interno do servidor', 500);
    }
}

// backend/config.php (arquivo principal de configuração)
<?php
// Configurações de erro
error_reporting(E_ALL);
ini_set('display_errors', 0); // Desabilitar em produção
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Criar diretório de logs se não existir
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Habilitar apenas em HTTPS
ini_set('session.use_strict_mode', 1);

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Autoload de classes
spl_autoload_register(function ($className) {
    $directories = [
        __DIR__ . '/classes/',
        __DIR__ . '/helpers/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }
});

// Função para verificar autenticação
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        ResponseHelper::unauthorized('Usuário não autenticado');
    }
}

// Função para log de ações
function logAction($action, $details = null) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $_SESSION['user_id'] ?? null,
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    file_put_contents(
        __DIR__ . '/logs/actions.log',
        json_encode($log) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}