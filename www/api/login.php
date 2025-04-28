<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->email) || !isset($data->password)) {
        throw new Exception('Missing email or password');
    }

    // Debug: Log incoming data
    error_log("Login attempt - Email: {$data->email}");

    // First, check if user exists
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$data->email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug: Log user data
    error_log("User found: " . json_encode($user));

    // Use password_verify to check hashed password
    if ($user && password_verify($data->password, $user['password'])) {
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['score'] = $user['score'] ?? 0;
        $_SESSION['high_score'] = $user['high_score'] ?? 0;
        $_SESSION['is_admin'] = $user['is_admin'] ?? 0;

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'isAdmin' => (bool)($user['is_admin'] ?? 0),
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'score' => $user['score'] ?? 0,
                'high_score' => $user['high_score'] ?? 0
            ]
        ]);
    } else {
        // Debug: Log failed attempt
        error_log("Login failed - Password verification failed");
        throw new Exception('Invalid credentials');
    }

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
