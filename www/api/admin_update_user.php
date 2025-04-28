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
    if (!isset($data->id) || !isset($data->name) || !isset($data->email)) {
        throw new Exception('Missing required fields');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user exists
    $checkStmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->execute([$data->id]);
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('User not found');
    }
    
    // Check if email is already used by another user
    $emailStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $emailStmt->execute([$data->email, $data->id]);
    
    if ($emailStmt->rowCount() > 0) {
        throw new Exception('Email already in use by another user');
    }
    
    // Prepare update query
    $query = "UPDATE users SET 
              name = ?, 
              email = ?, 
              score = ?, 
              is_admin = ?, 
              is_verified = ?, 
              updated_at = NOW() 
              WHERE id = ?";
    
    $stmt = $db->prepare($query);
    
    // Execute query
    $result = $stmt->execute([
        $data->name,
        $data->email,
        $data->score,
        isset($data->is_admin) ? $data->is_admin : 0,
        isset($data->is_verified) ? $data->is_verified : 0,
        $data->id
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update user');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
