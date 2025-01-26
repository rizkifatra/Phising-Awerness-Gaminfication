<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $level = isset($_GET['level']) ? (int)$_GET['level'] : 1;
    
    // Debug logging
    error_log("Fetching questions for level: " . $level);
    
    $stmt = $db->prepare("SELECT * FROM fill_blank_questions WHERE level = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . print_r($db->errorInfo(), true));
        throw new Exception('Database query preparation failed');
    }

    $success = $stmt->execute([$level]);
    if (!$success) {
        error_log("Execute failed: " . print_r($stmt->errorInfo(), true));
        throw new Exception('Failed to execute query');
    }

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Raw questions data: " . print_r($questions, true));

    if (empty($questions)) {
        error_log("No questions found for level $level");
        throw new Exception("No questions available for level $level");
    }

    $formatted_questions = array_map(function($q) {
        $answers = json_decode($q['answers'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            error_log("Problem answers string: " . $q['answers']);
        }
        
        return [
            'id' => $q['id'],
            'text' => $q['question_text'],
            'answers' => $answers
        ];
    }, $questions);

    error_log("Sending formatted questions: " . json_encode($formatted_questions));
    echo json_encode($formatted_questions);

} catch (Exception $e) {
    error_log("Error in get_fill_questions: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
