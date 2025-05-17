<?php
class Database {
    private $host = "localhost";
    private $db_name = "PAG";
    private $username = "admin";
    private $password = "ZYGR9TYQAf3U";
    private $conn;

    public function __construct() {
        // If db_credentials.php exists, use those credentials instead
        $credFile = $_SERVER['DOCUMENT_ROOT'] . '/db_credentials.php';
        if (file_exists($credFile)) {
            include $credFile;
            $this->host = $DB_HOST ?? $this->host;
            $this->db_name = $DB_NAME ?? $this->db_name;
            $this->username = $DB_USER ?? $this->username;
            $this->password = $DB_PASS ?? $this->password;
        }
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
            return $this->conn;
        } catch(PDOException $e) {
            // Don't expose connection details in error message
            throw new Exception("Database connection failed");
        }
    }

    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query('SELECT 1');
            return true;
        } catch(Exception $e) {
            return false;
        }
    }
}
