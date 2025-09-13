<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'provaseice'; // Ajuste conforme seu banco
    private $username = 'root'; // Ajuste conforme sua configuração
    private $password = ''; // Ajuste conforme sua configuração
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Erro de conexão com o banco de dados");
        }

        return $this->conn;
    }
}

-- ================================

-- pages/banco-questoes.php (Página principal integrada)
<?php
session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Incluir configurações
require_once '../backend/config/database.php';
require_once '../backend/classes/Question.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $question = new Question($db);
    
    // Buscar estatísticas para exibir no painel
    $stats = $question->getStats();
    $statsData = $stats['success'] ? $stats['data'] : [];
    
} catch (Exception $e) {
    error_log("Error loading questions page: " . $e->getMessage());
    $statsData = [];
}

$pageTitle = "Banco de Questões";
$currentPage = "banco-questoes";
?>