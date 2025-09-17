<?php
// forgot_password.php - Sistema de recuperação de senha
require_once 'config.php';

// Se já estiver logado, redirecionar para o dashboard
if (validateSession()) {
    header('Location: index.html');
    exit;
}

// Processar formulário se for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $email = sanitizeInput($_POST['email'] ?? '');
        
        // Validação básica
        if (empty($email)) {
            echo json_encode([
                'success' => false,
                'message' => 'Email é obrigatório'
            ]);
            exit;
        }
        
        if (!validateEmail($email)) {
            echo json_encode([
                'success' => false,
                'message' => 'Email inválido'
            ]);
            exit;
        }
        
        $db = Database::getInstance()->getConnection();
        
        // Buscar usuário por email
        $stmt = $db->prepare("SELECT id, nome, email FROM usuarios WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
        
        // Por segurança, sempre retornamos sucesso, mesmo se o email não existir
        if ($usuario) {
            // Verificar se já existe um token válido recente (últimos 5 minutos)
            $stmt = $db->prepare("SELECT id FROM tokens_recuperacao 
                                 WHERE usuario_id = ? AND usado = 0 AND data_criacao > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
            $stmt->execute([$usuario['id']]);
            
            if ($stmt->rowCount() == 0) {
                // Gerar token de recuperação
                $token = generateSecureToken(32);
                $expiracao = date('Y-m-d H:i:s', time() + 3600); // 1 hora
                
                // Salvar token no banco
                $stmt = $db->prepare("INSERT INTO tokens_recuperacao (usuario_id, token, data_expiracao) VALUES (?, ?, ?)");
                $stmt->execute([$usuario['id'], $token, $expiracao]);
                
                // Aqui você pode enviar o email de recuperação
                // Para este exemplo, vamos apenas logar o token
                error_log("Token de recuperação para {$email}: {$token}");
                
                // Log da atividade
                logActivity($usuario['id'], 'RECUPERACAO_SOLICITADA', 'Solicitação de recuperação de senha');
                
                // Em produção, você enviaria um email com o link:
                // $resetLink = SITE_URL . "/reset_password.php?token=" . $token;
                // sendPasswordResetEmail($usuario['email'], $usuario['nome'], $resetLink);
            }
        }
        
        // Sempre retornar sucesso por segurança
        echo json_encode([
            'success' => true,
            'message' => 'Se o email existir em nossa base, você receberá instruções para recuperar sua senha.'
        ]);
        
    } catch (Exception $e) {
        error_log("Erro na recuperação de senha: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'Erro interno. Tente novamente mais tarde.'
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - SEICE</title>
    <style>
        :root {
            --primary-blue: #2D4B73;
            --secondary-blue: #3A5F87;
            --light-blue: #4A6FA5;
            --dark-blue: #253C59;
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --medium-gray: #e9ecef;
            --text-dark: #333333;
            --error-color: #dc3545;
            --success-color: #28a745;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--light-gray) 0%, var(--medium-gray) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .back-btn {
            position: absolute;
            top: 24px;
            right: 24px;
            background: #2D4B73;
            border: none;
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .back-btn:hover {
            background: #3165a8;
            transform: translateY(-2px);
        }

        .back-btn::before {
            content: "←";
            font-size: 16px;
            font-weight: 600;
        }

        .recovery-container {
            background: white;
            border-radius: 16px;
            padding: 40px 36px;
            width: 100%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.08);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .seice-logo {
            height: 75px;
            width: auto;
            margin-bottom: 20px;
        }

        .recovery-title {
            font-size: 24px;
            color: #1e293b;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .recovery-subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 32px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: #475569;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            color: #1e293b;
            background: white;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            transform: translateY(-1px);
        }

        .form-group input.error {
            border-color: var(--error-color);
        }

        .success-message, .error-message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .recovery-btn {
            width: 100%;
            padding: 15px;
            background: #2D4B73;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }

        .recovery-btn:hover:not(:disabled) {
            background: #265ba1;
            transform: translateY(-2px);
        }

        .recovery-btn:disabled {
            background: #64748b;
            cursor: not-allowed;
        }

        .login-link {
            color: #64748b;
            font-size: 14px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .login-link a {
            color: #3A5F87;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <a href="login.php" class="back-btn">Voltar ao Login</a>
    
    <div class="recovery-container">
        <img src="../img/image.png" alt="Logo SEICE" class="seice-logo">
        
        <h2 class="recovery-title">Recuperar Senha</h2>
        <p class="recovery-subtitle">Digite seu email para receber instruções de recuperação de senha</p>
        
        <div class="success-message" id="successMessage"></div>
        <div class="error-message" id="errorMessage"></div>
        
        <form id="recoveryForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required>
            </div>
            
            <button type="submit" class="recovery-btn" id="recoveryBtn">
                Enviar Instruções
            </button>
        </form>
        
        <div class="login-link">
            Lembrou da senha? <a href="login.php">Fazer login</a>
        </div>
    </div>

    <script>
        const form = document.getElementById('recoveryForm');
        const submitBtn = document.getElementById('recoveryBtn');
        const emailInput = document.getElementById('email');

        function showMessage(message, type) {
            const successDiv = document.getElementById('successMessage');
            const errorDiv = document.getElementById('errorMessage');
            
     
            successDiv.style.display = 'none';
            errorDiv.style.display = 'none';
            
            if (type === 'success') {
                successDiv.textContent = message;
                successDiv.style.display = 'block';
            } else {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = emailInput.value.trim();
            
            if (!email) {
                showMessage('Email é obrigatório', 'error');
                return;
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showMessage('Email inválido', 'error');
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando...';
            
            const formData = new FormData(form);
            
            fetch('forgot_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    form.reset();
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showMessage('Erro de conexão. Tente novamente.', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar Instruções';
            });
        });
    </script>
</body>
</html>