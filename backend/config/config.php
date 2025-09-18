<?php
// config.php - configuração do banco de dados e sistema

define('DB_HOST', 'localhost');
define('DB_NAME', 'seice_simulado');
define('DB_USER', 'root');         // altere se necessário
define('DB_PASS', '');             // altere se a senha não for vazia
define('DB_CHARSET', 'utf8mb4');

// configurações de segurança
define('SECRET_KEY', '41b474d2c28d6646687d7307db16036c6e03c9f39d89ebeebc423d9fbf2ddf5e');
define('SESSION_LIFETIME', 7200);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);

// configurações do sistema
define('SITE_URL', 'http://localhost/seice');
define('SITE_NAME', 'SEICE - Sistema de Ensino Integrado de Campos Elíseos');

date_default_timezone_set('America/Sao_Paulo');

// iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// classe de conexão com o banco de dados
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            error_log("Erro de conexão com banco de dados: " . $e->getMessage());
            die("Erro de conexão com banco de dados. Verifique as configurações.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}

function sanitizeInput($data) {
    if (is_null($data)) return null;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

function logActivity($usuarioId, $acao, $detalhes = null) {
    try {
        $db = Database::getInstance()->getConnection();

        $sql = "INSERT INTO log_atividades (usuario_id, acao, detalhes, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            $usuarioId,
            $acao,
            $detalhes,
            getUserIP(),
            getUserAgent()
        ]);
    } catch (Exception $e) {
        error_log("Erro ao registrar log de atividade: " . $e->getMessage());
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['session_id']);
}

function validateSession() {
    if (!isLoggedIn()) {
        return false;
    }

    try {
        $db = Database::getInstance()->getConnection();

        $sql = "SELECT usuario_id FROM sessoes 
                WHERE id = ? AND usuario_id = ? AND ativo = 1 AND data_expiracao > NOW()";

        $stmt = $db->prepare($sql);
        $stmt->execute([$_SESSION['session_id'], $_SESSION['user_id']]);

        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Erro ao validar sessão: " . $e->getMessage());
        return false;
    }
}

function requireLogin() {
    if (!validateSession()) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

function logout() {
    if (isset($_SESSION['session_id'])) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "UPDATE sessoes SET ativo = 0 WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$_SESSION['session_id']]);
        } catch (Exception $e) {
            error_log("Erro ao fazer logout: " . $e->getMessage());
        }
    }

    session_unset();
    session_destroy();
}

// logs e debug
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// cria diretório de logs, se necessário
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
?>
