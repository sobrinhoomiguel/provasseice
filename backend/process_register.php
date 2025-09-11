<?php
// process_register.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/User.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$response = ['success' => false, 'message' => '', 'errors' => []];

try {
    // Conectar ao banco de dados
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Erro de conexão com o banco de dados');
    }
    
    // Instanciar classe User
    $user = new User($db);
    
    
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $data_nascimento = $_POST['data_nascimento'] ?? null;
    
   
    $errors = [];
    
    
    if (empty($nome)) {
        $errors['nome'] = 'Nome é obrigatório';
    } elseif (strlen($nome) < 2) {
        $errors['nome'] = 'Nome deve ter pelo menos 2 caracteres';
    } elseif (strlen($nome) > 100) {
        $errors['nome'] = 'Nome muito longo (máximo 100 caracteres)';
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $nome)) {
        $errors['nome'] = 'Nome deve conter apenas letras e espaços';
    }
    
    
    if (empty($email)) {
        $errors['email'] = 'Email é obrigatório';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email inválido';
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'Email muito longo';
    } else {
        
        $user->email = $email;
        if ($user->emailExists()) {
            $errors['email'] = 'Este email já está cadastrado';
        }
    }
    
    
    if (!empty($telefone)) {
        $telefone_limpo = preg_replace('/\D/', '', $telefone);
        if (strlen($telefone_limpo) < 10 || strlen($telefone_limpo) > 11) {
            $errors['telefone'] = 'Telefone inválido';
        }
    }
    
    
    if (empty($senha)) {
        $errors['senha'] = 'Senha é obrigatória';
    } elseif (strlen($senha) < PASSWORD_MIN_LENGTH) {
        $errors['senha'] = 'Senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres';
    } elseif (strlen($senha) > 255) {
        $errors['senha'] = 'Senha muito longa';
    }
    
    
    if (empty($confirmar_senha)) {
        $errors['confirmar_senha'] = 'Confirmação de senha é obrigatória';
    } elseif ($senha !== $confirmar_senha) {
        $errors['confirmar_senha'] = 'As senhas não coincidem';
    }
    
 
    if (!empty($data_nascimento)) {
        $data = DateTime::createFromFormat('Y-m-d', $data_nascimento);
        if (!$data) {
            $errors['data'] = 'Data de nascimento inválida';
        } else {
            $hoje = new DateTime();
            $idade = $hoje->diff($data)->y;
            if ($idade < 13) {
                $errors['data'] = 'Você deve ter pelo menos 13 anos';
            } elseif ($idade > 120) {
                $errors['data'] = 'Data de nascimento inválida';
            }
        }
    }
    
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
        $response['message'] = 'Dados inválidos';
        echo json_encode($response);
        exit;
    }
    
    
    $user->nome = sanitizeInput($nome);
    $user->email = sanitizeInput($email);
    $user->senha = $senha;
    $user->telefone = sanitizeInput($telefone);
    $user->data_nascimento = !empty($data_nascimento) ? $data_nascimento : null;
    
    
    if ($user->register()) {
        $response['success'] = true;
        $response['message'] = 'Cadastro realizado com sucesso! Redirecionando...';
        
        
        error_log("Novo usuário cadastrado: " . $user->email . " (ID: " . $user->id . ")");
        
    } else {
        throw new Exception('Erro ao processar cadastro. Tente novamente.');
    }
    
} catch (PDOException $e) {
    error_log("Erro de banco de dados no cadastro: " . $e->getMessage());
    
   
    if ($e->getCode() == 23000 && strpos($e->getMessage(), 'email') !== false) {
        $response['errors']['email'] = 'Este email já está cadastrado';
        $response['message'] = 'Email já cadastrado';
    } else {
        $response['message'] = 'Erro interno do servidor. Tente novamente.';
    }
    
} catch (Exception $e) {
    error_log("Erro no cadastro: " . $e->getMessage());
    $response['message'] = $e->getMessage();
    
} finally {
    echo json_encode($response);
}
?>