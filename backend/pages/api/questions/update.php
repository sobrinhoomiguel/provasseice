// pages/api/questions/update.php
session_start();
require_once '../../../backend/config.php';
require_once '../../../backend/classes/Question.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da questão é obrigatório']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $question = new Question($db);
    
    // Receber dados JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validação básica
    $requiredFields = ['questionText', 'questionSubject', 'questionDifficulty', 'questionGrade', 'questionType'];
    foreach ($requiredFields as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'message' => "Campo $field é obrigatório"]);
            exit;
        }
    }
    
    // Preparar dados (mesmo processamento do create.php)
    $data = [
        'question_text' => trim($input['questionText']),
        'subject' => $input['questionSubject'],
        'difficulty' => $input['questionDifficulty'],
        'grade' => $input['questionGrade'],
        'question_type' => $input['questionType'],
        'tags' => $input['questionTags'] ?? null
    ];
    
    // Processar resposta baseada no tipo (mesmo logic do create.php)
    // ... (código similar ao create.php)
    
    $result = $question->update($_GET['id'], $data);
    
    if (!$result['success']) {
        http_response_code(400);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}