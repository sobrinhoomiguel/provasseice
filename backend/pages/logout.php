<?php
// logout.php - Sistema de logout
require_once 'config.php';

try {
    // Verificar se há uma sessão ativa
    if (isset($_SESSION['user_id']) && isset($_SESSION['session_id'])) {
        $userId = $_SESSION['user_id'];
        
        // Log da atividade de logout
        logActivity($userId, 'LOGOUT', 'Usuário fez logout');
        
        // Desativar sessão no banco de dados
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE sessoes SET ativo = 0 WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$_SESSION['session_id'], $userId]);
    }
    
    // Limpar todas as variáveis de sessão
    session_unset();
    
    // Destruir a sessão
    session_destroy();
    
    // Verificar se é uma requisição AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => 'Logout realizado com sucesso',
            'redirect' => 'login.php'
        ]);
    } else {
        // Redirecionar para a página de login
        header('Location: login.php?logout=1');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Erro no logout: " . $e->getMessage());
    
    // Se for AJAX, retornar erro em JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao fazer logout',
            'redirect' => 'login.php'
        ]);
    } else {
        // Mesmo com erro, redirecionar para login
        header('Location: login.php');
        exit;
    }
}
?>