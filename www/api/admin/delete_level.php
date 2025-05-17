<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization');

include_once '../../config/database.php';

try {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data || !isset($data->level)) {
        throw new Exception('Level is required');
    }
    
    $level = $data->level;
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Begin transaction to ensure atomicity
    $db->beginTransaction();
    
    try {
        // Delete MCQ questions for this level
        $query = "DELETE FROM mcq_questions WHERE level = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$level]);
        $mcqDeleted = $stmt->rowCount();
        
        // Delete Fill in the Blank questions for this level
        $query = "DELETE FROM fill_blank_questions WHERE level = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$level]);
        $fillBlankDeleted = $stmt->rowCount();
        
        // Delete Word Puzzles for this level
        $query = "DELETE FROM word_search_puzzles WHERE level = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$level]);
        $puzzleDeleted = $stmt->rowCount();
        
        // Commit the transaction
        $db->commit();
        
        $totalDeleted = $mcqDeleted + $fillBlankDeleted + $puzzleDeleted;
        
        echo json_encode([
            'success' => true,
            'message' => "Level $level deleted successfully",
            'deleted_count' => [
                'mcq' => $mcqDeleted,
                'fill_blank' => $fillBlankDeleted,
                'puzzle' => $puzzleDeleted,
                'total' => $totalDeleted
            ]
        ]);
    } catch (Exception $e) {
        // Roll back the transaction on error
        $db->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete level: ' . $e->getMessage()
    ]);
}
?>
