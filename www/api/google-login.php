<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->token)) {
        throw new Exception('Missing Google token');
    }

    // Verify Google token with Google's API
    $google_client_id = "187258324052-l53dk7e9d26sbvtshf11ijca5t3kgdtd.apps.googleusercontent.com"; // Replace with your Google Client ID
    $google_token = $data->token;
    
    // Get Google user data from token
    $google_user_data = verifyGoogleToken($google_token, $google_client_id);
    
    if (!$google_user_data) {
        throw new Exception('Invalid Google token');
    }
    
    // Check if user with this Google ID exists
    $stmt = $db->prepare("SELECT * FROM users WHERE google_id = ?");
    $stmt->execute([$google_user_data->sub]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Check if user with this email exists
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$google_user_data->email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Update existing user with Google ID
            $stmt = $db->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            $stmt->execute([$google_user_data->sub, $user['id']]);
        } else {
            // Create new user with a random password placeholder
            $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, google_id, is_admin, password) VALUES (?, ?, ?, 0, ?)");
            $stmt->execute([
                $google_user_data->name,
                $google_user_data->email,
                $google_user_data->sub,
                $random_password
            ]);
            
            $userId = $db->lastInsertId();
            
            // Get new user data
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    // Set session data
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['score'] = $user['score'] ?? 0;
    $_SESSION['high_score'] = $user['high_score'] ?? 0;
    $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'message' => 'Google login successful',
        'isAdmin' => (bool)($user['is_admin'] ?? 0),
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'score' => $user['score'] ?? 0,
            'high_score' => $user['high_score'] ?? 0
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Google login error: " . $e->getMessage());
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Verify Google ID token
 * 
 * @param string $id_token The token to verify
 * @param string $client_id Your Google Client ID
 * @return object|false User data if token is valid, false otherwise
 */
function verifyGoogleToken($id_token, $client_id) {
    // Google's token verification URL
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $id_token;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $payload = json_decode($response);
    
    if (!$payload) {
        return false;
    }
    
    // Verify the token
    if (!isset($payload->aud) || $payload->aud != $client_id) {
        return false;
    }
    
    return $payload;
}
