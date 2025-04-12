<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON for all responses
header('Content-Type: application/json');

// Start session to get user ID
session_start();

// Check if the database config file exists
if (!file_exists('../config/database.php')) {
    echo json_encode(['error' => 'Database configuration file not found']);
    exit;
}

// Include database configuration
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

try {
    // Create database instance and get PDO connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception("Failed to establish database connection");
    }
    
    // Use PDO prepared statement instead of mysqli
    $query = "SELECT id, name, email, profile_picture as picture FROM users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    // Check if user exists
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'User not found in database']);
    }
    
} catch (Exception $e) {
    // Log the error server-side
    error_log("Database error in get_user_data.php: " . $e->getMessage());
    
    // Return error to client
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
