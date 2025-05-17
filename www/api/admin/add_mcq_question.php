<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization');

include_once '../../config/database.php';

try {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data || !isset($data->question_text) || !isset($data->options) || !isset($data->correct_answer) || !isset($data->level)) {
        throw new Exception('Invalid or missing data. Required fields: question_text, options, correct_answer, level');
    }
    
    // Validate data
    if (empty($data->question_text) || !is_array($data->options) || count($data->options) < 2) {
        throw new Exception('Question text and at least 2 options are required');
    }
    
    // Convert options to JSON for storage
    $options_json = json_encode($data->options);
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Insert new MCQ question
    $query = "INSERT INTO mcq_questions (question_text, options, correct_answer, level) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    $stmt->execute([
        $data->question_text,
        $options_json,
        $data->correct_answer,
        $data->level
    ]);
    
    $lastId = $db->lastInsertId();
    
    if ($lastId) {
        echo json_encode([
            'success' => true,
            'message' => 'MCQ question added successfully',
            'id' => $lastId
        ]);
    } else {
        throw new Exception('Failed to add MCQ question');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
