<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include_once '../config/database.php';
session_start();

try {
    // Get JSON data and log it
    $raw_data = file_get_contents("php://input");
    error_log("Raw score data received: " . $raw_data);
    $data = json_decode($raw_data);
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        error_log("User not logged in when trying to save score");
        throw new Exception('User not logged in');
    }
    
    // Validate required fields
    if (!isset($data->game_type) || !isset($data->level) || !isset($data->score)) {
        error_log("Missing required fields in score data");
        throw new Exception('Missing required fields');
    }
    
    $userId = $_SESSION['user_id'];
    $gameType = $data->game_type;
    $level = (int)$data->level;
    $score = (int)$data->score;
    $timeTaken = isset($data->time_taken) ? (int)$data->time_taken : null;
    
    error_log("Processing score: User ID: $userId, Game: $gameType, Level: $level, Score: $score");
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if user already has a score for this game and level
    $stmt = $db->prepare("SELECT score FROM user_scores WHERE user_id = ? AND game_type = ? AND level = ?");
    $stmt->execute([$userId, $gameType, $level]);
    $existingScore = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingScore) {
        error_log("Existing score found: " . $existingScore['score']);
        // Only update if new score is higher
        if ($score > $existingScore['score']) {
            error_log("New score is higher, updating");
            $stmt = $db->prepare("UPDATE user_scores SET score = ?, time_taken = ?, updated_at = NOW() 
                                 WHERE user_id = ? AND game_type = ? AND level = ?");
            $stmt->execute([$score, $timeTaken, $userId, $gameType, $level]);
            echo json_encode(['success' => true, 'message' => 'Score updated successfully']);
        } else {
            error_log("Existing score is higher, not updating");
            echo json_encode(['success' => true, 'message' => 'Existing score is higher']);
        }
    } else {
        error_log("No existing score, inserting new record");
        // Insert new score
        $stmt = $db->prepare("INSERT INTO user_scores (user_id, game_type, level, score, time_taken, created_at) 
                             VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $gameType, $level, $score, $timeTaken]);
        echo json_encode(['success' => true, 'message' => 'Score saved successfully']);
    }

} catch (Exception $e) {
    error_log("Error in save_score.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
