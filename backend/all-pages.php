<?php
// config/database.php
class Database {
    private $host = "localhost";
    private $db_name = "seice_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

// models/Turma.php
class Turma {
    private $conn;
    private $table_name = "turmas";

    public $id;
    public $nome;
    public $serie;
    public $turno;
    public $ano_letivo;
    public $capacidade;
    public $descricao;
    public $unidade;
    public $alunos_count;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Criar tabela se não existir
    public function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            serie VARCHAR(10) NOT NULL,
            turno ENUM('manha', 'tarde', 'noite') NOT NULL,
            ano_letivo INT NOT NULL,
            capacidade INT DEFAULT NULL,
            descricao TEXT DEFAULT NULL,
            unidade ENUM('campos-eliseos', 'jardim-primavera') DEFAULT NULL,
            alunos_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_serie (serie),
            INDEX idx_turno (turno),
            INDEX idx_unidade (unidade)
        )";

        try {
            $this->conn->exec($query);
            return true;
        } catch(PDOException $exception) {
            echo "Error creating table: " . $exception->getMessage();
            return false;
        }
    }

    // Listar todas as turmas
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Listar turmas com filtros
    public function readWithFilters($search = '', $serie = '', $turno = '', $unidade = '') {
        $query = "SELECT * FROM " . $this->table_name . " WHERE 1=1";
        
        if (!empty($search)) {
            $query .= " AND (nome LIKE :search OR serie LIKE :search)";
        }
        
        if (!empty($serie)) {
            $query .= " AND serie = :serie";
        }
        
        if (!empty($turno)) {
            $query .= " AND turno = :turno";
        }
        
        if (!empty($unidade)) {
            if ($unidade === 'sem-unidade') {
                $query .= " AND unidade IS NULL";
            } else {
                $query .= " AND unidade = :unidade";
            }
        }
        
        $query .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($search)) {
            $searchParam = "%{$search}%";
            $stmt->bindParam(":search", $searchParam);
        }
        
        if (!empty($serie)) {
            $stmt->bindParam(":serie", $serie);
        }
        
        if (!empty($turno)) {
            $stmt->bindParam(":turno", $turno);
        }
        
        if (!empty($unidade) && $unidade !== 'sem-unidade') {
            $stmt->bindParam(":unidade", $unidade);
        }
        
        $stmt->execute();
        return $stmt;
    }

    // Buscar turma por ID
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->nome = $row['nome'];
            $this->serie = $row['serie'];
            $this->turno = $row['turno'];
            $this->ano_letivo = $row['ano_letivo'];
            $this->capacidade = $row['capacidade'];
            $this->descricao = $row['descricao'];
            $this->unidade = $row['unidade'];
            $this->alunos_count = $row['alunos_count'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }

        return false;
    }

    // Criar nova turma
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET nome=:nome, serie=:serie, turno=:turno, ano_letivo=:ano_letivo, 
                    capacidade=:capacidade, descricao=:descricao, unidade=:unidade";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->serie = htmlspecialchars(strip_tags($this->serie));
        $this->turno = htmlspecialchars(strip_tags($this->turno));
        $this->ano_letivo = htmlspecialchars(strip_tags($this->ano_letivo));
        $this->capacidade = $this->capacidade ? htmlspecialchars(strip_tags($this->capacidade)) : null;
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->unidade = $this->unidade ? htmlspecialchars(strip_tags($this->unidade)) : null;

        // Bind dos parâmetros
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":serie", $this->serie);
        $stmt->bindParam(":turno", $this->turno);
        $stmt->bindParam(":ano_letivo", $this->ano_letivo);
        $stmt->bindParam(":capacidade", $this->capacidade);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":unidade", $this->unidade);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Atualizar turma
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                SET nome=:nome, serie=:serie, turno=:turno, ano_letivo=:ano_letivo, 
                    capacidade=:capacidade, descricao=:descricao, unidade=:unidade
                WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->serie = htmlspecialchars(strip_tags($this->serie));
        $this->turno = htmlspecialchars(strip_tags($this->turno));
        $this->ano_letivo = htmlspecialchars(strip_tags($this->ano_letivo));
        $this->capacidade = $this->capacidade ? htmlspecialchars(strip_tags($this->capacidade)) : null;
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->unidade = $this->unidade ? htmlspecialchars(strip_tags($this->unidade)) : null;
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind dos parâmetros
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":serie", $this->serie);
        $stmt->bindParam(":turno", $this->turno);
        $stmt->bindParam(":ano_letivo", $this->ano_letivo);
        $stmt->bindParam(":capacidade", $this->capacidade);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":unidade", $this->unidade);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Atualizar apenas a unidade
    public function updateUnidade() {
        $query = "UPDATE " . $this->table_name . " SET unidade=:unidade WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->unidade = htmlspecialchars(strip_tags($this->unidade));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":unidade", $this->unidade);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Deletar turma
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";

        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Verificar se nome da turma já existe
    public function nameExists() {
        $query = "SELECT id FROM " . $this->table_name . " 
                WHERE nome = :nome AND serie = :serie AND turno = :turno AND ano_letivo = :ano_letivo";
        
        if ($this->id) {
            $query .= " AND id != :id";
        }

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":serie", $this->serie);
        $stmt->bindParam(":turno", $this->turno);
        $stmt->bindParam(":ano_letivo", $this->ano_letivo);
        
        if ($this->id) {
            $stmt->bindParam(":id", $this->id);
        }

        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }

        return false;
    }

    // Contar total de turmas
    public function countTotal() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Contar turmas por unidade
    public function countByUnidade() {
        $query = "SELECT 
                    unidade,
                    COUNT(*) as total 
                  FROM " . $this->table_name . " 
                  GROUP BY unidade";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}

// api/turmas/read.php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Turma.php';

$database = new Database();
$db = $database->getConnection();

$turma = new Turma($db);

// Verificar se a tabela existe, senão criar
$turma->createTable();

// Pegar parâmetros de filtro
$search = isset($_GET['search']) ? $_GET['search'] : '';
$serie = isset($_GET['serie']) ? $_GET['serie'] : '';
$turno = isset($_GET['turno']) ? $_GET['turno'] : '';
$unidade = isset($_GET['unidade']) ? $_GET['unidade'] : '';

try {
    // Buscar turmas com filtros
    $stmt = $turma->readWithFilters($search, $serie, $turno, $unidade);
    $num = $stmt->rowCount();

    if ($num > 0) {
        $turmas_arr = array();
        $turmas_arr["records"] = array();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);

            $turma_item = array(
                "id" => $id,
                "nome" => $nome,
                "serie" => $serie,
                "turno" => $turno,
                "anoLetivo" => intval($ano_letivo),
                "capacidade" => $capacidade ? intval($capacidade) : null,
                "descricao" => $descricao,
                "unidade" => $unidade,
                "alunosCount" => intval($alunos_count),
                "createdAt" => $created_at,
                "updatedAt" => $updated_at
            );

            array_push($turmas_arr["records"], $turma_item);
        }

        http_response_code(200);
        echo json_encode($turmas_arr);
    } else {
        http_response_code(200);
        echo json_encode(array("records" => array()));
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Erro interno do servidor.", "error" => $e->getMessage()));
}
?>

// api/turmas/create.php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Turma.php';

$database = new Database();
$db = $database->getConnection();

$turma = new Turma($db);

// Verificar se a tabela existe, senão criar
$turma->createTable();

$data = json_decode(file_get_contents("php://input"));

// Validar dados obrigatórios
if (empty($data->nome) || empty($data->serie) || empty($data->turno) || empty($data->anoLetivo)) {
    http_response_code(400);
    echo json_encode(array("message" => "Dados obrigatórios não informados."));
    exit;
}

// Definir propriedades da turma
$turma->nome = $data->nome;
$turma->serie = $data->serie;
$turma->turno = $data->turno;
$turma->ano_letivo = $data->anoLetivo;
$turma->capacidade = isset($data->capacidade) ? $data->capacidade : null;
$turma->descricao = isset($data->descricao) ? $data->descricao : null;
$turma->unidade = null; // Será definida depois

try {
    // Verificar se já existe turma com mesmo nome, série, turno e ano
    if ($turma->nameExists()) {
        http_response_code(400);
        echo json_encode(array("message" => "Já existe uma turma com essas características."));
        exit;
    }

    // Criar turma
    if ($turma->create()) {
        http_response_code(201);
        echo json_encode(array(
            "message" => "Turma criada com sucesso.",
            "id" => $turma->id
        ));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Não foi possível criar a turma."));
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Erro interno do servidor.", "error" => $e->getMessage()));
}
?>

// api/turmas/update.php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Turma.php';

$database = new Database();
$db = $database->getConnection();

$turma = new Turma($db);

$data = json_decode(file_get_contents("php://input"));

// Validar dados obrigatórios
if (empty($data->id) || empty($data->nome) || empty($data->serie) || empty($data->turno) || empty($data->anoLetivo)) {
    http_response_code(400);
    echo json_encode(array("message" => "Dados obrigatórios não informados."));
    exit;
}

// Definir propriedades da turma
$turma->id = $data->id;

// Verificar se turma existe
if (!$turma->readOne()) {
    http_response_code(404);
    echo json_encode(array("message" => "Turma não encontrada."));
    exit;
}

// Atualizar propriedades
$turma->nome = $data->nome;
$turma->serie = $data->serie;
$turma->turno = $data->turno;
$turma->ano_letivo = $data->anoLetivo;
$turma->capacidade = isset($data->capacidade) ? $data->capacidade : null;
$turma->descricao = isset($data->descricao) ? $data->descricao : null;
// Manter unidade existente se não for informada
if (isset($data->unidade)) {
    $turma->unidade = $data->unidade;
}

try {
    // Verificar se já existe outra turma com mesmo nome, série, turno e ano
    if ($turma->nameExists()) {
        http_response_code(400);
        echo json_encode(array("message" => "Já existe outra turma com essas características."));
        exit;
    }

    // Atualizar turma
    if ($turma->update()) {
        http_response_code(200);
        echo json_encode(array("message" => "Turma atualizada com sucesso."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Não foi possível atualizar a turma."));
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Erro interno do servidor.", "error" => $e->getMessage()));
}
?>

// api/turmas/update_unidade.php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Turma.php';

$database = new Database();
$db = $database->getConnection();

$turma = new Turma($db);

$data = json_decode(file_get_contents("php://input"));

// Validar dados obrigatórios
if (empty($data->id) || empty($data->unidade)) {
    http_response_code(400);
    echo json_encode(array("message" => "ID da turma e unidade são obrigatórios."));
    exit;
}

// Validar unidade
if (!in_array($data->unidade, ['campos-eliseos', 'jardim-primavera'])) {
    http_response_code(400);
    echo json_encode(array("message" => "Unidade inválida."));
    exit;
}

// Definir propriedades
$turma->id = $data->id;
$turma->unidade = $data->unidade;

// Verificar se turma existe
if (!$turma->readOne()) {
    http_response_code(404);
    echo json_encode(array("message" => "Turma não encontrada."));
    exit;
}

try {
    // Atualizar unidade
    if ($turma->updateUnidade()) {
        http_response_code(200);
        echo json_encode(array("message" => "Unidade da turma atualizada com sucesso."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Não foi possível atualizar a unidade da turma."));
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Erro interno do servidor.", "error" => $e->getMessage()));
}
?>

// api/turmas/delete.php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Turma.php';

$database = new Database();
$db = $database->getConnection();

$turma = new Turma($db);

$data = json_decode(file_get_contents("php://input"));

// Validar ID
if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(array("message" => "ID da turma é obrigatório."));
    exit;
}

$turma->id = $data->id;

// Verificar se turma existe
if (!$turma->readOne()) {
    http_response_code(404);
    echo json_encode(array("message" => "Turma não encontrada."));
    exit;
}

try {
    // Deletar turma
    if ($turma->delete()) {
        http_response_code(200);
        echo json_encode(array("message" => "Turma excluída com sucesso."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Não foi possível excluir a turma."));
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Erro interno do servidor.", "error" => $e->getMessage()));
}
?>

// api/turmas/read_one.php
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Turma.php';

$database = new Database();
$db = $database->getConnection();

$turma = new Turma($db);

// Pegar ID da URL
$turma->id = isset($_GET['id']) ? $_GET['id'] : die();

try {
    // Buscar turma
    if ($turma->readOne()) {
        $turma_arr = array(
            "id" => $turma->id,
            "nome" => $turma->nome,
            "serie" => $turma->serie,
            "turno" => $turma->turno,
            "anoLetivo" => intval($turma->ano_letivo),
            "capacidade" => $turma->capacidade ? intval($turma->capacidade) : null,
            "descricao" => $turma->descricao,
            "unidade" => $turma->unidade,
            "alunosCount" => intval($turma->alunos_count),
            "createdAt" => $turma->created_at,
            "updatedAt" => $turma->updated_at
        );

        http_response_code(200);
        echo json_encode($turma_arr);
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Turma não encontrada."));
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Erro interno do servidor.", "error" => $e->getMessage()));
}
?>

// install.php - Script para criar banco e tabelas
<?php
include_once 'config/database.php';
include_once 'models/Turma.php';

echo "<h2>SEICE - Instalação do Banco de Dados</h2>";

try {
    // Conectar ao banco
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p style='color: green;'>✅ Conexão com o banco estabelecida com sucesso!</p>";
        
        // Criar tabela de turmas
        $turma = new Turma($db);
        if ($turma->createTable()) {
            echo "<p style='color: green;'>✅ Tabela 'turmas' criada/verificada com sucesso!</p>";
        } else {
            echo "<p style='color: red;'>❌ Erro ao criar tabela 'turmas'</p>";
        }
        
        echo "<h3>Estrutura da Tabela 'turmas':</h3>";
        echo "<ul>";
        echo "<li>id (INT, AUTO_INCREMENT, PRIMARY KEY)</li>";
        echo "<li>nome (VARCHAR(100), NOT NULL)</li>";
        echo "<li>serie (VARCHAR(10), NOT NULL)</li>";
        echo "<li>turno (ENUM: 'manha', 'tarde', 'noite')</li>";
        echo "<li>ano_letivo (INT, NOT NULL)</li>";
        echo "<li>capacidade (INT, NULLABLE)</li>";
        echo "<li>descricao (TEXT, NULLABLE)</li>";
        echo "<li>unidade (ENUM: 'campos-eliseos', 'jardim-primavera', NULLABLE)</li>";
        echo "<li>alunos_count (INT, DEFAULT 0)</li>";
        echo "<li>created_at (TIMESTAMP)</li>";
        echo "<li>updated_at (TIMESTAMP)</li>";
        echo "</ul>";
        
        echo "<p style='color: blue;'>🚀 Sistema pronto para uso!</p>";
        echo "<p><strong>Endpoints da API:</strong></p>";
        echo "<ul>";
        echo "<li>GET /api/turmas/read.php - Listar turmas</li>";
        echo "<li>POST /api/turmas/create.php - Criar turma</li>";
        echo "<li>PUT /api/turmas/update.php - Atualizar turma</li>";
        echo "<li>PUT /api/turmas/update_unidade.php - Definir unidade</li>";
        echo "<li>DELETE /api/turmas/delete.php - Excluir turma</li>";
        echo "<li>GET /api/turmas/read_one.php?id=X - Buscar uma turma</li>";
        echo "</ul>";
        
    } else {
        echo "<p style='color: red;'>❌ Erro ao conectar com o banco de dados!</p>";
        echo "<p>Verifique as configurações em config/database.php</p>";
    }
    
} catch(Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>