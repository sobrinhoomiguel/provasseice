// config/config.php
<?php
session_start();

// config gerais
define('BASE_URL', 'http://localhost/seice-provas/');
define('SITE_NAME', 'SEICE Provas');

// config de email (para futura implementação)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu@email.com');
define('SMTP_PASS', 'sua_senha');

// config de segurança
define('JWT_SECRET', 'sua_chave_secreta_muito_forte_aqui_2024');
define('PASSWORD_MIN_LENGTH', 6);


function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
?>