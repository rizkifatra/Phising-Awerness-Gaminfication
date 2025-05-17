<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization');

include_once '../../config/database.php';

try {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data || !isset($data->id)) {
        throw new Exception('Question ID is required');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Delete MCQ question
    $query = "DELETE FROM mcq_questions WHERE id = ?";
    $stmt = $db->prepare($query);
    
    $result = $stmt->execute([$data->id]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'MCQ question deleted successfully'
        ]);
    } else {
        throw new Exception('Question not found or already deleted');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
