// pages/api/questions/bulk-delete.php
session_start();
require_once '../../../backend/config.php';
require_once '../../../backend/classes/Question.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

try {
    $database = new Database();
    $db = $database->getConnection();
    $question = new Question($db);
    
    // Receber IDs das questões
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['questionIds']) || !is_array($input['questionIds'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'IDs das questões são obrigatórios']);
        exit;
    }
    
    $result = $question->bulkDelete($input['questionIds']);
    
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

