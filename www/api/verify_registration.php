<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

include_once '../config/database.php';

try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->email) || !isset($data->code) || !isset($data->temp_id)) {
        throw new Exception('Missing required fields');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Verify code
    $stmt = $db->prepare("SELECT * FROM temp_registrations WHERE id = ? AND email = ? AND verification_code = ?");
    $stmt->execute([$data->temp_id, $data->email, $data->code]);
    $temp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$temp) {
        throw new Exception('Invalid verification code');
    }

    // Move user to permanent users table
    $stmt = $db->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([
        $temp['name'],
        $temp['email'],
        $temp['password']
    ]);

    // Delete temporary registration
    $stmt = $db->prepare("DELETE FROM temp_registrations WHERE id = ?");
    $stmt->execute([$data->temp_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Registration completed successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
