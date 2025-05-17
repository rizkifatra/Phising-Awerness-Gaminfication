<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization');

include_once '../../config/database.php';

try {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data || !isset($data->id) || !isset($data->question_text) || !isset($data->options) || 
        !isset($data->correct_answer) || !isset($data->level)) {
        throw new Exception('Invalid or missing data. Required fields: id, question_text, options, correct_answer, level');
    }
    
    // Validate data
    if (empty($data->question_text) || !is_array($data->options) || count($data->options) < 2) {
        throw new Exception('Question text and at least 2 options are required');
    }
    
    // Convert options to JSON for storage
    $options_json = json_encode($data->options);
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Update MCQ question
    $query = "UPDATE mcq_questions SET question_text = ?, options = ?, correct_answer = ?, level = ? WHERE id = ?";
    $stmt = $db->prepare($query);
    
    $result = $stmt->execute([
        $data->question_text,
        $options_json,
        $data->correct_answer,
        $data->level,
        $data->id
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'MCQ question updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update MCQ question');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
