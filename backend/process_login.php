<?php
// process_login.php - Processamento do login de usuários
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
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $fieldErrors = [];
    
    // Validação básica
    if (empty($email)) {
        $fieldErrors['email'] = 'Email é obrigatório';
    } elseif (!validateEmail($email)) {
        $fieldErrors['email'] = 'Email inválido';
    }
    
    if (empty($password)) {
        $fieldErrors['password'] = 'Senha é obrigatória';
    }
    
    // Se houver erros de validação, retornar
    if (!empty($fieldErrors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Dados inválidos',
            'field_errors' => $fieldErrors
        ]);
        exit;
    }
    
    // Conectar ao banco de dados
    $db = Database::getInstance()->getConnection();
    
    // Buscar usuário por email
    $stmt = $db->prepare("SELECT id, nome, email, senha, ativo, tentativas_login, bloqueado_ate 
                          FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        // Log da tentativa de login com email inexistente
        logActivity(null, 'LOGIN_FALHOU', "Tentativa de login com email inexistente: $email");
        
        echo json_encode([
            'success' => false,
            'message' => 'Email ou senha incorretos'
        ]);
        exit;
    }
    
    // Verificar se a conta está ativa
    if (!$usuario['ativo']) {
        logActivity($usuario['id'], 'LOGIN_FALHOU', 'Tentativa de login em conta inativa');
        
        echo json_encode([
            'success' => false,
            'message' => 'Conta inativa. Entre em contato com o suporte.'
        ]);
        exit;
    }
    
    // Verificar se a conta está bloqueada
    if ($usuario['bloqueado_ate'] && new DateTime() < new DateTime($usuario['bloqueado_ate'])) {
        $bloqueadoAte = new DateTime($usuario['bloqueado_ate']);
        $agora = new DateTime();
        $diff = $agora->diff($bloqueadoAte);
        $minutosRestantes = $diff->i + ($diff->h * 60);
        
        logActivity($usuario['id'], 'LOGIN_BLOQUEADO', 'Tentativa de login em conta bloqueada');
        
        echo json_encode([
            'success' => false,
            'message' => "Conta temporariamente bloqueada. Tente novamente em {$minutosRestantes} minutos."
        ]);
        exit;
    }
    
    // Verificar a senha
    if (!verifyPassword($password, $usuario['senha'])) {
        // Incrementar tentativas de login
        $novasTentativas = $usuario['tentativas_login'] + 1;
        $bloqueadoAte = null;
        
        // Bloquear conta após muitas tentativas
        if ($novasTentativas >= MAX_LOGIN_ATTEMPTS) {
            $bloqueadoAte = date('Y-m-d H:i:s', time() + LOCKOUT_TIME);
        }
        
        // Atualizar tentativas no banco
        $updateStmt = $db->prepare("UPDATE usuarios SET tentativas_login = ?, bloqueado_ate = ? WHERE id = ?");
        $updateStmt->execute([$novasTentativas, $bloqueadoAte, $usuario['id']]);
        
        logActivity($usuario['id'], 'LOGIN_FALHOU', "Senha incorreta - Tentativa {$novasTentativas}");
        
        $message = 'Email ou senha incorretos';
        if ($novasTentativas >= MAX_LOGIN_ATTEMPTS) {
            $message .= '. Conta bloqueada temporariamente devido a muitas tentativas.';
        } elseif ($novasTentativas >= 3) {
            $tentativasRestantes = MAX_LOGIN_ATTEMPTS - $novasTentativas;
            $message .= " ({$tentativasRestantes} tentativas restantes)";
        }
        
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
    
    // Login bem-sucedido - limpar tentativas e bloqueios
    $stmt = $db->prepare("UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL, ultimo_login = NOW() WHERE id = ?");
    $stmt->execute([$usuario['id']]);
    
    // Criar sessão
    $sessionId = generateSecureToken(64);
    $expirationTime = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    // Salvar sessão no banco
    $sessionStmt = $db->prepare("INSERT INTO sessoes (id, usuario_id, ip_address, user_agent, data_expiracao) 
                                 VALUES (?, ?, ?, ?, ?)");
    $sessionStmt->execute([
        $sessionId,
        $usuario['id'],
        getUserIP(),
        getUserAgent(),
        $expirationTime
    ]);
    
    // Definir variáveis de sessão
    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['session_id'] = $sessionId;
    $_SESSION['user_name'] = $usuario['nome'];
    $_SESSION['user_email'] = $usuario['email'];
    $_SESSION['login_time'] = time();
    
    // Log do login bem-sucedido
    logActivity($usuario['id'], 'LOGIN_SUCESSO', 'Login realizado com sucesso');
    
    // Limpar sessões antigas do usuário (manter apenas as 5 mais recentes)
    $cleanupStmt = $db->prepare("DELETE FROM sessoes 
                                 WHERE usuario_id = ? AND id != ? AND id NOT IN (
                                     SELECT t.id FROM (
                                         SELECT id FROM sessoes 
                                         WHERE usuario_id = ? 
                                         ORDER BY data_criacao DESC 
                                         LIMIT 5
                                     ) as t
                                 )");
    $cleanupStmt->execute([$usuario['id'], $sessionId, $usuario['id']]);
    
    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso!',
        'redirect' => 'index.html',
        'user' => [
            'id' => $usuario['id'],
            'name' => $usuario['nome'],
            'email' => $usuario['email']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Erro PDO no login: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro de banco de dados. Verifique a conexão.'
    ]);
    
} catch (Exception $e) {
    error_log("Erro no login: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor. Tente novamente.'
    ]);
}
?>