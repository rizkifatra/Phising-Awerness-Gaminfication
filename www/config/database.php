<?php
class Database {
    private $host = "localhost";
    private $db_name = "PAG";
    private $username = "root";
    private $password = "";
    private $conn;

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
