<?php
// filepath: /Applications/XAMPP/xamppfiles/htdocs/PAG/www/api/test/GoogleLoginTest.php


// Mock the database class
class MockDatabase {
    public function getConnection() {
        return new MockPDO();
    }
}

// Mock PDO class
class MockPDO {
    private $mockResults = [];
    private $mockLastInsertId = null;
    
    public function prepare($query) {
        return new MockStatement($this, $query);
    }
    
    public function setMockResults($query, $results) {
        $this->mockResults[$query] = $results;
    }
    
    public function getMockResults($query) {
        return $this->mockResults[$query] ?? [];
    }
    
    public function setLastInsertId($id) {
        $this->mockLastInsertId = $id;
    }
    
    public function lastInsertId() {
        return $this->mockLastInsertId;
    }
}

// Mock PDOStatement
class MockStatement {
    private $pdo;
    private $query;