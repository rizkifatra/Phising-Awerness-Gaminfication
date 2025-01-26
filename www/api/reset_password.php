<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    // Debug input data
    $input = file_get_contents("php://input");
    error_log("Received input: " . $input);
    
    $data = json_decode($input);
    
    // Debug decoded data
    error_log("Decoded data: " . print_r($data, true));

    // Strict validation
    if (!isset($data->token) || trim($data->token) === '') {
        throw new Exception('Reset token is required');
    }

    if (!isset($data->password) || trim($data->password) === '') {
        throw new Exception('Password is required');
    }

    // Validate password length
    if (strlen($data->password) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    $database = new Database();
    $db = $database->getConnection();

    // First verify the token exists and is valid
    $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ?");
    $stmt->execute([$data->token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Invalid reset token');
    }

    // Update password
    $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET 
                         password = ?, 
                         reset_token = NULL, 
                         reset_token_expiry = NULL 
                         WHERE id = ?");
    
    if (!$stmt->execute([$hashedPassword, $user['id']])) {
        throw new Exception('Failed to update password');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Password reset successfully'
    ]);

} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
