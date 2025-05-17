<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all Word Search Puzzles
    $query = "SELECT id, level, created_at, category, words FROM word_search_puzzles ORDER BY id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $puzzles = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $puzzles[] = $row;
    }
    
    if (count($puzzles) > 0) {
        echo json_encode([
            'success' => true,
            'puzzles' => $puzzles
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'puzzles' => [],
            'message' => 'No Word Puzzles found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve Word Puzzles: ' . $e->getMessage()
    ]);
}
?>
