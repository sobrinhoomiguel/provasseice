<?php
// config/database.php
class Database {
    private $host = 'localhost';
    private $db_name = 'seice_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Erro de conexão com o banco de dados");
        }
        
        return $this->conn;
    }
}

// models/Student.php
class Student {
    private $conn;
    private $table_name = "students";

    public $id;
    public $nome;
    public $email;
    public $turma;
    public $serie;
    public $telefone;
    public $matricula;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar tabela se não existir
    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            turma VARCHAR(50),
            serie VARCHAR(50),
            telefone VARCHAR(20),
            matricula VARCHAR(20) UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_matricula (matricula),
            INDEX idx_turma_serie (turma, serie)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    // Verificar se email já existe
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Verificar se matrícula já existe
    public function matriculaExists($matricula) {
        if (empty($matricula)) return false;
        
        $query = "SELECT id FROM " . $this->table_name . " WHERE matricula = :matricula LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":matricula", $matricula);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Criar novo aluno
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                (nome, email, turma, serie, telefone, matricula) 
                VALUES (:nome, :email, :turma, :serie, :telefone, :matricula)";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = filter_var($this->email, FILTER_SANITIZE_EMAIL);
        $this->turma = htmlspecialchars(strip_tags($this->turma));
        $this->serie = htmlspecialchars(strip_tags($this->serie));
        $this->telefone = htmlspecialchars(strip_tags($this->telefone));
        $this->matricula = htmlspecialchars(strip_tags($this->matricula));

        // Bind dos parâmetros
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":turma", $this->turma);
        $stmt->bindParam(":serie", $this->serie);
        $stmt->bindParam(":telefone", $this->telefone);
        $stmt->bindParam(":matricula", $this->matricula);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Atualizar aluno existente
    public function update($id) {
        $query = "UPDATE " . $this->table_name . " 
                SET nome = :nome, turma = :turma, serie = :serie, 
                    telefone = :telefone, matricula = :matricula, 
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->turma = htmlspecialchars(strip_tags($this->turma));
        $this->serie = htmlspecialchars(strip_tags($this->serie));
        $this->telefone = htmlspecialchars(strip_tags($this->telefone));
        $this->matricula = htmlspecialchars(strip_tags($this->matricula));

        // Bind dos parâmetros
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":turma", $this->turma);
        $stmt->bindParam(":serie", $this->serie);
        $stmt->bindParam(":telefone", $this->telefone);
        $stmt->bindParam(":matricula", $this->matricula);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    // Buscar aluno por email
    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nome = $row['nome'];
            $this->email = $row['email'];
            $this->turma = $row['turma'];
            $this->serie = $row['serie'];
            $this->telefone = $row['telefone'];
            $this->matricula = $row['matricula'];
            return true;
        }

        return false;
    }

    // Buscar todos os alunos
    public function readAll($limit = 100, $offset = 0) {
        $query = "SELECT * FROM " . $this->table_name . " 
                ORDER BY nome ASC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// services/FileUploadService.php
class FileUploadService {
    private $allowedTypes = [
        'text/csv',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'text/plain'
    ];
    
    private $maxFileSize = 10485760; // 10MB
    private $uploadDir = 'uploads/';

    public function __construct() {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function validateFile($file) {
        $errors = [];

        // Verificar se arquivo foi enviado
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Erro no upload do arquivo';
            return $errors;
        }

        // Verificar tamanho
        if ($file['size'] > $this->maxFileSize) {
            $errors[] = 'Arquivo muito grande. Máximo permitido: 10MB';
        }

        // Verificar tipo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($mimeType, $this->allowedTypes) && !in_array($extension, ['csv', 'xlsx', 'xls'])) {
            $errors[] = 'Tipo de arquivo não permitido. Use CSV, XLS ou XLSX';
        }

        return $errors;
    }

    public function saveFile($file) {
        $filename = uniqid() . '_' . basename($file['name']);
        $filepath = $this->uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filepath;
        }

        return false;
    }

    public function deleteFile($filepath) {
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}

// services/ImportService.php
require_once 'vendor/autoload.php'; // Para PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService {
    private $db;
    private $student;
    
    public function __construct($database) {
        $this->db = $database;
        $this->student = new Student($this->db);
    }

    public function processFile($filepath) {
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'csv':
                return $this->processCSV($filepath);
            case 'xlsx':
            case 'xls':
                return $this->processExcel($filepath);
            default:
                throw new Exception('Formato de arquivo não suportado');
        }
    }

    private function processCSV($filepath) {
        $data = [];
        
        if (($handle = fopen($filepath, "r")) !== FALSE) {
            $headers = array_map('trim', fgetcsv($handle, 1000, ","));
            $headers = array_map('strtolower', $headers);
            
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($row) === count($headers)) {
                    $studentData = array_combine($headers, array_map('trim', $row));
                    if (!empty($studentData['nome']) || !empty($studentData['email'])) {
                        $data[] = $studentData;
                    }
                }
            }
            fclose($handle);
        }
        
        return $data;
    }

    private function processExcel($filepath) {
        try {
            $spreadsheet = IOFactory::load($filepath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = [];
            
            $headers = [];
            $headerRow = $worksheet->getRowIterator(1, 1)->current();
            
            foreach ($headerRow->getCellIterator() as $cell) {
                $headers[] = strtolower(trim($cell->getValue()));
            }
            
            foreach ($worksheet->getRowIterator(2) as $row) {
                $rowData = [];
                $cellIterator = $row->getCellIterator();
                $cellIndex = 0;
                
                foreach ($cellIterator as $cell) {
                    if (isset($headers[$cellIndex])) {
                        $rowData[$headers[$cellIndex]] = trim($cell->getCalculatedValue());
                    }
                    $cellIndex++;
                }
                
                if (!empty($rowData['nome']) || !empty($rowData['email'])) {
                    $data[] = $rowData;
                }
            }
            
            return $data;
            
        } catch (Exception $e) {
            throw new Exception('Erro ao processar arquivo Excel: ' . $e->getMessage());
        }
    }

    public function validateData($data) {
        $validData = [];
        $errors = [];
        
        foreach ($data as $index => $row) {
            $lineNumber = $index + 2; // +2 porque começamos da linha 2 e arrays começam em 0
            $validation = $this->validateStudent($row, $lineNumber);
            
            if ($validation['valid']) {
                $validData[] = $validation['data'];
            } else {
                $errors = array_merge($errors, $validation['errors']);
            }
        }
        
        return [
            'valid_data' => $validData,
            'errors' => $errors
        ];
    }

    private function validateStudent($data, $lineNumber) {
        $errors = [];
        $cleanData = [];
        
        // Nome obrigatório
        if (empty(trim($data['nome'] ?? ''))) {
            $errors[] = "Linha $lineNumber: Nome é obrigatório";
        } else {
            $cleanData['nome'] = trim($data['nome']);
        }
        
        // Email obrigatório e válido
        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $errors[] = "Linha $lineNumber: Email é obrigatório";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Linha $lineNumber: Email inválido";
        } else {
            $cleanData['email'] = strtolower($email);
        }
        
        // Campos opcionais
        $cleanData['turma'] = trim($data['turma'] ?? '');
        $cleanData['serie'] = trim($data['serie'] ?? '');
        $cleanData['telefone'] = trim($data['telefone'] ?? '');
        $cleanData['matricula'] = trim($data['matricula'] ?? '');
        
        return [
            'valid' => empty($errors),
            'data' => $cleanData,
            'errors' => $errors
        ];
    }

    public function importStudents($validData) {
        $this->db->beginTransaction();
        
        try {
            $results = [
                'imported' => 0,
                'updated' => 0,
                'errors' => 0,
                'error_details' => []
            ];
            
            foreach ($validData as $studentData) {
                try {
                    // Verificar se aluno já existe por email
                    if ($this->student->emailExists($studentData['email'])) {
                        // Atualizar aluno existente
                        $this->student->findByEmail($studentData['email']);
                        
                        $this->student->nome = $studentData['nome'];
                        $this->student->turma = $studentData['turma'];
                        $this->student->serie = $studentData['serie'];
                        $this->student->telefone = $studentData['telefone'];
                        
                        // Verificar matrícula duplicada (se diferente da atual)
                        if (!empty($studentData['matricula']) && 
                            $studentData['matricula'] !== $this->student->matricula &&
                            $this->student->matriculaExists($studentData['matricula'])) {
                            $results['errors']++;
                            $results['error_details'][] = "Matrícula {$studentData['matricula']} já existe para outro aluno";
                            continue;
                        }
                        
                        $this->student->matricula = $studentData['matricula'];
                        
                        if ($this->student->update($this->student->id)) {
                            $results['updated']++;
                        } else {
                            $results['errors']++;
                            $results['error_details'][] = "Erro ao atualizar aluno: {$studentData['email']}";
                        }
                        
                    } else {
                        // Verificar matrícula duplicada
                        if (!empty($studentData['matricula']) && 
                            $this->student->matriculaExists($studentData['matricula'])) {
                            $results['errors']++;
                            $results['error_details'][] = "Matrícula {$studentData['matricula']} já existe";
                            continue;
                        }
                        
                        // Criar novo aluno
                        $this->student->nome = $studentData['nome'];
                        $this->student->email = $studentData['email'];
                        $this->student->turma = $studentData['turma'];
                        $this->student->serie = $studentData['serie'];
                        $this->student->telefone = $studentData['telefone'];
                        $this->student->matricula = $studentData['matricula'];
                        
                        if ($this->student->create()) {
                            $results['imported']++;
                        } else {
                            $results['errors']++;
                            $results['error_details'][] = "Erro ao criar aluno: {$studentData['email']}";
                        }
                    }
                    
                } catch (Exception $e) {
                    $results['errors']++;
                    $results['error_details'][] = "Erro ao processar {$studentData['email']}: " . $e->getMessage();
                }
            }
            
            $this->db->commit();
            return $results;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Erro na importação: ' . $e->getMessage());
        }
    }
}

// api/import.php
<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir dependências
require_once '../config/database.php';
require_once '../models/Student.php';
require_once '../services/FileUploadService.php';
require_once '../services/ImportService.php';

// Função para retornar resposta JSON
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método não permitido'], 405);
}

try {
    // Conectar ao banco
    $database = new Database();
    $db = $database->getConnection();
    
    // Criar tabela se não existir
    $student = new Student($db);
    $student->createTable();
    
    // Verificar se arquivo foi enviado
    if (!isset($_FILES['file'])) {
        jsonResponse(['error' => 'Nenhum arquivo enviado'], 400);
    }
    
    // Validar arquivo
    $fileService = new FileUploadService();
    $validationErrors = $fileService->validateFile($_FILES['file']);
    
    if (!empty($validationErrors)) {
        jsonResponse(['error' => implode(', ', $validationErrors)], 400);
    }
    
    // Salvar arquivo
    $filepath = $fileService->saveFile($_FILES['file']);
    if (!$filepath) {
        jsonResponse(['error' => 'Erro ao salvar arquivo'], 500);
    }
    
    try {
        // Processar arquivo
        $importService = new ImportService($db);
        $rawData = $importService->processFile($filepath);
        
        if (empty($rawData)) {
            jsonResponse(['error' => 'Arquivo vazio ou sem dados válidos'], 400);
        }
        
        // Validar dados
        $validation = $importService->validateData($rawData);
        
        if (empty($validation['valid_data'])) {
            jsonResponse([
                'error' => 'Nenhum dado válido encontrado',
                'validation_errors' => $validation['errors']
            ], 400);
        }
        
        // Se há apenas validação (preview)
        if (isset($_POST['preview_only']) && $_POST['preview_only'] === 'true') {
            jsonResponse([
                'success' => true,
                'preview_data' => array_slice($validation['valid_data'], 0, 5),
                'total_valid' => count($validation['valid_data']),
                'validation_errors' => $validation['errors']
            ]);
        }
        
        // Importar dados
        $results = $importService->importStudents($validation['valid_data']);
        
        // Resposta de sucesso
        jsonResponse([
            'success' => true,
            'message' => 'Importação concluída',
            'imported' => $results['imported'],
            'updated' => $results['updated'],
            'errors' => $results['errors'],
            'validation_errors' => $validation['errors'],
            'import_errors' => $results['error_details']
        ]);
        
    } finally {
        // Limpar arquivo temporário
        $fileService->deleteFile($filepath);
    }
    
} catch (Exception $e) {
    error_log('Erro na importação: ' . $e->getMessage());
    jsonResponse(['error' => $e->getMessage()], 500);
}

// api/students.php - Para listar alunos
<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/database.php';
require_once '../models/Student.php';

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Método não permitido'], 405);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $student = new Student($db);
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $limit;
    
    $students = $student->readAll($limit, $offset);
    
    jsonResponse([
        'success' => true,
        'data' => $students,
        'page' => $page,
        'limit' => $limit
    ]);
    
} catch (Exception $e) {
    error_log('Erro ao listar alunos: ' . $e->getMessage());
    jsonResponse(['error' => 'Erro interno do servidor'], 500);
}

// download-template.php - Para baixar modelo
<?php
// Definir cabeçalhos para download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="modelo_importacao_alunos.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Conteúdo do CSV modelo
$csvContent = "nome,email,turma,serie,telefone,matricula\n";
$csvContent .= "João da Silva,joao.silva@email.com,A,1º Ano,(21) 99999-9999,2024001\n";
$csvContent .= "Maria Santos,maria.santos@email.com,B,1º Ano,(21) 88888-8888,2024002\n";
$csvContent .= "Pedro Oliveira,pedro.oliveira@email.com,C,2º Ano,(21) 77777-7777,2024003\n";

echo $csvContent;
exit;

// .htaccess - Para configuração do Apache
/*
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1.php [QSA,L]

# Configurações de segurança
<Files "*.php">
    Order Allow,Deny
    Allow from all
</Files>

<Files "config/*">
    Order Deny,Allow
    Deny from all
</Files>

# Configurações de upload
php_value upload_max_filesize 10M
php_value post_max_size 12M
php_value memory_limit 256M
php_value max_execution_time 300
*/

// composer.json - Para dependências
/*
{
    "require": {
        "phpoffice/phpspreadsheet": "^1.29"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
*/
?>