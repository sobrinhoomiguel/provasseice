<?php
// pages/api/questions/create.php
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
    
    // Preparar dados
    $data = [
        'question_text' => trim($input['questionText']),
        'subject' => $input['questionSubject'],
        'difficulty' => $input['questionDifficulty'],
        'grade' => $input['questionGrade'],
        'question_type' => $input['questionType'],
        'tags' => $input['questionTags'] ?? null
    ];
    
    // Processar resposta baseada no tipo de questão
    switch ($input['questionType']) {
        case 'multipla_escolha':
            if (empty($input['correctAnswer'])) {
                echo json_encode(['success' => false, 'message' => 'Selecione a resposta correta']);
                exit;
            }
            
            // Coletar alternativas do input
            $alternatives = [];
            $options = ['a', 'b', 'c', 'd', 'e'];
            foreach ($options as $option) {
                if (!empty($input['alternative_' . $option])) {
                    $alternatives[$option] = trim($input['alternative_' . $option]);
                }
            }
            
            if (count($alternatives) < 2) {
                echo json_encode(['success' => false, 'message' => 'Adicione pelo menos 2 alternativas']);
                exit;
            }
            
            $data['alternatives'] = $alternatives;
            $data['correct_answer'] = $input['correctAnswer'];
            break;
            
        case 'verdadeiro_falso':
            if (empty($input['trueFalseAnswer'])) {
                echo json_encode(['success' => false, 'message' => 'Selecione a resposta correta']);
                exit;
            }
            $data['correct_answer'] = $input['trueFalseAnswer'];
            break;
            
        case 'dissertativa':
            $data['essay_guidelines'] = $input['essayGuidelines'] ?? null;
            $data['max_score'] = $input['maxScore'] ?? 2;
            $data['correct_answer'] = null; // Dissertativas não têm resposta única
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Tipo de questão inválido']);
            exit;
    }
    
    $result = $question->create($data);
    
    if ($result['success']) {
        http_response_code(201);
    } else {
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
