<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

include_once '../config/database.php';

try {
    // Get JSON data
    $data = json_decode(file_get_contents("php://input"));
    
    // Validate required fields
    if (!isset($data->name) || !isset($data->email) || !isset($data->password)) {
        throw new Exception('Missing required fields');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if email already exists
    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$data->email]);
    
    if ($checkStmt->rowCount() > 0) {
        throw new Exception('Email already exists');
    }
    
    // Hash password
    $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT);
    
    // Prepare insert query
    $query = "INSERT INTO users (name, email, password, is_admin, is_verified, created_at, updated_at) 
              VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $db->prepare($query);
    
    // Execute query
    $result = $stmt->execute([
        $data->name,
        $data->email,
        $hashedPassword,
        isset($data->is_admin) ? $data->is_admin : 0,
        isset($data->is_verified) ? $data->is_verified : 0
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'User added successfully',
            'user_id' => $db->lastInsertId()
        ]);
    } else {
        throw new Exception('Failed to add user');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
