<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Validate ID parameter
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Puzzle ID is required');
    }
    
    $id = intval($_GET['id']);
    
    // Get the Word Puzzle by ID
    $query = "SELECT id, level, category, words, grid, answers, created_at FROM word_search_puzzles WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    
    $puzzle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($puzzle) {
        echo json_encode([
            'success' => true,
            'puzzle' => $puzzle
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Word Puzzle not found'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve Word Puzzle: ' . $e->getMessage()
    ]);
}
?>
