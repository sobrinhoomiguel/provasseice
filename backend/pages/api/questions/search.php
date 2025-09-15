<?php

// pages/api/questions/search.php

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
    
    // Receber filtros JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    $filters = [
        'search_term' => $input['searchTerm'] ?? null,
        'subject' => $input['subject'] ?? null,
        'difficulty' => $input['difficulty'] ?? null,
        'grade' => $input['grade'] ?? null
    ];
    
    // Remover filtros vazios
    $filters = array_filter($filters, function($value) {
        return !empty($value);
    });
    
    // Parâmetros de paginação
    $page = isset($input['page']) ? max(1, (int)$input['page']) : 1;
    $limit = isset($input['limit']) ? max(1, min(100, (int)$input['limit'])) : 20;
    
    $result = $question->search($filters, $page, $limit);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

// ================================
