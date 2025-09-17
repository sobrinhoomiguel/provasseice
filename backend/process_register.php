<?php
// process_register.php - Processamento do cadastro de usuários
require_once 'config.php';

// Definir cabeçalho JSON
header('Content-Type: application/json; charset=utf-8');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
    exit;
}

try {
    // Obter dados do formulário
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $data_nascimento = sanitizeInput($_POST['data_nascimento'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    $errors = [];
    
    // Validação do nome
    if (empty($nome)) {
        $errors['nome'] = 'Nome é obrigatório';
    } elseif (strlen($nome) < 2) {
        $errors['nome'] = 'Nome deve ter pelo menos 2 caracteres';
    } elseif (strlen($nome) > 100) {
        $errors['nome'] = 'Nome muito longo (máximo 100 caracteres)';
    }
    
    // Validação do email
    if (empty($email)) {
        $errors['email'] = 'Email é obrigatório';
    } elseif (!validateEmail($email)) {
        $errors['email'] = 'Email inválido';
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'Email muito longo (máximo 100 caracteres)';
    }
    
    // Validação do telefone (opcional)
    if (!empty($telefone)) {
        // Remover formatação para validação
        $telefone_limpo = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($telefone_limpo) < 10 || strlen($telefone_limpo) > 11) {
            $errors['telefone'] = 'Telefone deve ter 10 ou 11 dígitos';
        }
    }
    
    // Validação da data de nascimento (opcional)
    if (!empty($data_nascimento)) {
        $data_obj = DateTime::createFromFormat('Y-m-d', $data_nascimento);
        if (!$data_obj) {
            $errors['data'] = 'Data de nascimento inválida';
        } else {
            $hoje = new DateTime();
            $idade = $hoje->diff($data_obj)->y;
            if ($idade < 13 || $idade > 120) {
                $errors['data'] = 'Idade deve estar entre 13 e 120 anos';
            }
        }
    }
    
    // Validação da senha
    if (empty($senha)) {
        $errors['senha'] = 'Senha é obrigatória';
    } elseif (strlen($senha) < 6) {
        $errors['senha'] = 'Senha deve ter pelo menos 6 caracteres';
    } elseif (strlen($senha) > 255) {
        $errors['senha'] = 'Senha muito longa';
    }
    
    // Validação da confirmação de senha
    if (empty($confirmar_senha)) {
        $errors['confirmar_senha'] = 'Confirmação de senha é obrigatória';
    } elseif ($senha !== $confirmar_senha) {
        $errors['confirmar_senha'] = 'As senhas não coincidem';
    }
    
    // Se houver erros de validação, retornar
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Dados inválidos',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Conectar ao banco de dados
    $db = Database::getInstance()->getConnection();
    
    // Verificar se o email já existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Este email já está cadastrado',
            'errors' => ['email' => 'Este email já está em uso']
        ]);
        exit;
    }
    
    // Hash da senha
    $senha_hash = hashPassword($senha);
    
    // Inserir usuário no banco
    $sql = "INSERT INTO usuarios (nome, email, telefone, data_nascimento, senha) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        $nome,
        $email,
        !empty($telefone) ? $telefone : null,
        !empty($data_nascimento) ? $data_nascimento : null,
        $senha_hash
    ]);
    
    if ($result) {
        $userId = $db->lastInsertId();
        
        // Log da atividade
        logActivity($userId, 'CADASTRO', 'Usuário cadastrado com sucesso');
        
        // Resposta de sucesso
        echo json_encode([
            'success' => true,
            'message' => 'Cadastro realizado com sucesso! Você será redirecionado para a página de login.'
        ]);
    } else {
        throw new Exception('Erro ao salvar usuário no banco de dados');
    }
    
} catch (Exception $e) {
    error_log("Erro no cadastro: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor. Tente novamente.'
    ]);
}
?>