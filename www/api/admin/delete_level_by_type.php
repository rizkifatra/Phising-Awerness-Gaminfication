<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization');

include_once '../../config/database.php';

try {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data || !isset($data->level) || !isset($data->type)) {
        throw new Exception('Level and type are required');
    }
    
    $level = $data->level;
    $type = $data->type;
    
    $database = new Database();
    $db = $database->getConnection();
    
    $deletedCount = 0;
    $typeName = '';
    
    // Delete based on specified type
    if ($type === 'mcq') {
        $query = "DELETE FROM mcq_questions WHERE level = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$level]);
        $deletedCount = $stmt->rowCount();
        $typeName = 'Multiple Choice';
    } else if ($type === 'fill_blank') {
        $query = "DELETE FROM fill_blank_questions WHERE level = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$level]);
        $deletedCount = $stmt->rowCount();
        $typeName = 'Fill in the Blank';
    } else if ($type === 'puzzle') {
        $query = "DELETE FROM word_search_puzzles WHERE level = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$level]);
        $deletedCount = $stmt->rowCount();
        $typeName = 'Word Puzzle';
    } else {
        throw new Exception('Invalid question type');
    }
    
    echo json_encode([
        'success' => true,
        'message' => "$typeName questions for level $level deleted successfully",
        'deleted_count' => $deletedCount,
        'type_name' => $typeName
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete questions: ' . $e->getMessage()
    ]);
}
?>
 