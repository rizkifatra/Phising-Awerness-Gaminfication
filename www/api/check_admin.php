<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set response header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in',
        'isAdmin' => false
    ]);
    exit;
}

// Include database connection
include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get user from database to check admin status
    $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found',
            'isAdmin' => false
        ]);
        exit;
    }
    
    // Check if user is admin
    $isAdmin = (bool)($user['is_admin'] ?? 0);
    
    echo json_encode([
        'success' => true,
        'message' => $isAdmin ? 'User is admin' : 'User is not admin',
        'isAdmin' => $isAdmin
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'isAdmin' => false
    ]);
}
?>
