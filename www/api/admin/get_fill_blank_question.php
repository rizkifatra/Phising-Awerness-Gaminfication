<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Validate ID parameter
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Question ID is required');
    }
    
    $id = intval($_GET['id']);
    
    // Get the Fill in the Blank question by ID
    $query = "SELECT id, level, question_text, answers FROM fill_blank_questions WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    
    $question = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($question) {
        echo json_encode([
            'success' => true,
            'question' => $question
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Fill in the Blank question not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve Fill in the Blank question: ' . $e->getMessage()
    ]);
}
?>
