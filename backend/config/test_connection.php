<?php
// test_connection.php - Script para testar a conexão com o banco
require_once 'config.php';

echo "<h2>Teste de Conexão - SEICE</h2>";

try {
    // Testar conexão
    $db = Database::getInstance()->getConnection();
    echo "<p style='color: green;'>✓ Conexão com banco de dados estabelecida com sucesso!</p>";
    
    // Testar se as tabelas existem
    $tables = ['usuarios', 'sessoes', 'log_atividades'];
    
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Tabela '$table' encontrada</p>";
        } else {
            echo "<p style='color: red;'>✗ Tabela '$table' NÃO encontrada</p>";
        }
    }
    
    // Verificar se há usuários cadastrados
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    echo "<p>Total de usuários cadastrados: " . $result['total'] . "</p>";
    
    // Listar usuários (apenas para teste)
    $stmt = $db->query("SELECT id, nome, email, ativo FROM usuarios LIMIT 5");
    $usuarios = $stmt->fetchAll();
    
    if (count($usuarios) > 0) {
        echo "<h3>Usuários cadastrados:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Ativo</th></tr>";
        foreach ($usuarios as $user) {
            $ativo = $user['ativo'] ? 'Sim' : 'Não';
            echo "<tr><td>{$user['id']}</td><td>{$user['nome']}</td><td>{$user['email']}</td><td>$ativo</td></tr>";
        }
        echo "</table>";
    }
    
    // Verificar configurações de sessão
    echo "<h3>Configurações de Sessão:</h3>";
    echo "<p>Status da sessão: " . session_status() . " (1=desabilitada, 2=ativa)</p>";
    echo "<p>ID da sessão atual: " . session_id() . "</p>";
    
    // Verificar configurações do PHP
    echo "<h3>Configurações PHP:</h3>";
    echo "<p>Versão PHP: " . PHP_VERSION . "</p>";
    echo "<p>PDO disponível: " . (extension_loaded('pdo') ? 'Sim' : 'Não') . "</p>";
    echo "<p>PDO MySQL disponível: " . (extension_loaded('pdo_mysql') ? 'Sim' : 'Não') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erro: " . $e->getMessage() . "</p>";
    echo "<p>Verifique:</p>";
    echo "<ul>";
    echo "<li>Se o MySQL/MariaDB está rodando</li>";
    echo "<li>Se as credenciais do banco estão corretas no config.php</li>";
    echo "<li>Se o banco 'seice_db' foi criado</li>";
    echo "<li>Se as tabelas foram criadas</li>";
    echo "</ul>";
}
?>