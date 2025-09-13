// pages/api/questions/import.php
session_start();
require_once '../../../backend/config.php';
require_once '../../../backend/classes/Question.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

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

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado']);
    exit;
}

try {
    $file = $_FILES['file'];
    
    // Validar arquivo
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Erro no upload do arquivo']);
        exit;
    }
    
    // Verificar extensão
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['json', 'csv', 'xlsx'];
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode(['success' => false, 'message' => 'Formato de arquivo não suportado']);
        exit;
    }
    
    // Verificar tamanho (máximo 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Arquivo muito grande (máximo 10MB)']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    $question = new Question($db);
    
    $result = $question->importFromFile($file['tmp_name'], $fileExtension);
    
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
