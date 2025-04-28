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
    
    // Validate required field
    if (!isset($data->id)) {
        throw new Exception('Missing user ID');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user exists
    $checkStmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $checkStmt->execute([$data->id]);
    
    if ($checkStmt->rowCount() === 0) {
        throw new Exception('User not found');
    }
    
    // Don't allow deleting the current admin user
    if ($data->id == $_SESSION['user_id']) {
        throw new Exception('Cannot delete your own account');
    }
    
    // Prepare delete query
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    
    // Execute query
    $result = $stmt->execute([$data->id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete user');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
