<?php
// classes/User.php
require_once '../config/database.php';

class User {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $nome;
    public $email;
    public $senha;
    public $telefone;
    public $data_nascimento;
    public $status;
    public $email_verificado;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Registrar novo usuário
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nome=:nome, email=:email, senha=:senha, telefone=:telefone, 
                      data_nascimento=:data_nascimento, token_verificacao=:token";

        $stmt = $this->conn->prepare($query);

        // Sanitizar dados
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telefone = htmlspecialchars(strip_tags($this->telefone));
        
        // Hash da senha
        $password_hash = password_hash($this->senha, PASSWORD_DEFAULT);
        
        // Token de verificação
        $token = bin2hex(random_bytes(50));

        // Bind dos parâmetros
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":senha", $password_hash);
        $stmt->bindParam(":telefone", $this->telefone);
        $stmt->bindParam(":data_nascimento", $this->data_nascimento);
        $stmt->bindParam(":token", $token);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Login do usuário
    public function login($email, $senha) {
        $query = "SELECT id, nome, email, senha, status, email_verificado 
                  FROM " . $this->table_name . " 
                  WHERE email = :email AND status = 'ativo' LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($senha, $row['senha'])) {
                $this->id = $row['id'];
                $this->nome = $row['nome'];
                $this->email = $row['email'];
                $this->status = $row['status'];
                $this->email_verificado = $row['email_verificado'];
                
                return true;
            }
        }

        return false;
    }

    // Verificar se email já existe
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Buscar usuário por ID
    public function getUserById($id) {
        $query = "SELECT id, nome, email, telefone, data_nascimento, status, 
                         email_verificado, created_at 
                  FROM " . $this->table_name . " 
                  WHERE id = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->id = $row['id'];
            $this->nome = $row['nome'];
            $this->email = $row['email'];
            $this->telefone = $row['telefone'];
            $this->data_nascimento = $row['data_nascimento'];
            $this->status = $row['status'];
            $this->email_verificado = $row['email_verificado'];
            $this->created_at = $row['created_at'];
            
            return true;
        }

        return false;
    }

    // Atualizar dados do usuário
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome=:nome, telefone=:telefone, data_nascimento=:data_nascimento 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->telefone = htmlspecialchars(strip_tags($this->telefone));

        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':telefone', $this->telefone);
        $stmt->bindParam(':data_nascimento', $this->data_nascimento);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    // Alterar senha
    public function changePassword($nova_senha) {
        $query = "UPDATE " . $this->table_name . " SET senha=:senha WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        $password_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        $stmt->bindParam(':senha', $password_hash);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    // Gerar token para reset de senha
    public function generateResetToken($email) {
        $token = bin2hex(random_bytes(50));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $query = "UPDATE " . $this->table_name . " 
                  SET token_reset_senha=:token, token_reset_expira=:expires 
                  WHERE email=:email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires', $expires);
        $stmt->bindParam(':email', $email);
        
        if($stmt->execute()) {
            return $token;
        }
        
        return false;
    }

    // Validar token de reset
    public function validateResetToken($token) {
        $query = "SELECT id, email FROM " . $this->table_name . " 
                  WHERE token_reset_senha=:token 
                  AND token_reset_expira > NOW() LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->email = $row['email'];
            return true;
        }
        
        return false;
    }

    // Limpar token de reset após uso
    public function clearResetToken() {
        $query = "UPDATE " . $this->table_name . " 
                  SET token_reset_senha=NULL, token_reset_expira=NULL 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
}
?>