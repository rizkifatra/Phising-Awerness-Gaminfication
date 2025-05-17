<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all MCQ questions
    $query = "SELECT id, question_text, options, correct_answer, level FROM mcq_questions ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $questions = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $questions[] = $row;
    }
    
    if (count($questions) > 0) {
        echo json_encode([
            'success' => true,
            'questions' => $questions
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'questions' => [],
            'message' => 'No MCQ questions found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve MCQ questions: ' . $e->getMessage()
    ]);
}
?>
