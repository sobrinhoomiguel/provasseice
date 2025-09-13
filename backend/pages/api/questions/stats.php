// pages/api/questions/stats.php
session_start();
require_once '../../../backend/config.php';
require_once '../../../backend/classes/Question.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    
    $result = $question->getStats();
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
});
    echo json_encode(['success' => false, 'message' => 'ID da questão é obrigatório']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $question = new Question($db);
    
    $result = $question->getById($_GET['id']);
    
    if (!$result['success']) {
        http_response_code(404);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

// ================================
