<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all Fill in the Blank questions
    $query = "SELECT id, level, question_text, answers FROM fill_blank_questions ORDER BY id DESC";
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
            'message' => 'No Fill in the Blank questions found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve Fill in the Blank questions: ' . $e->getMessage()
    ]);
}
?>
