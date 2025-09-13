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



