<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../config/database.php';

try {
    // Get game type from query parameter
    $gameType = isset($_GET['type']) ? $_GET['type'] : '';
    
    if (empty($gameType)) {
        throw new Exception('Game type is required');
    }

    // Map game types to their respective tables
    $tableMap = [
        'mcq' => 'mcq_questions',
        'fill' => 'fill_blank_questions',
        'puzzle' => 'word_search_puzzles'
    ];

    if (!isset($tableMap[$gameType])) {
        throw new Exception('Invalid game type');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Check database connection
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Get distinct levels from the appropriate table
    $query = "SELECT DISTINCT level FROM " . $tableMap[$gameType] . " ORDER BY level ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();

    $levels = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $levels[] = (int)$row['level'];
    }

    // Return default level 1 if no levels found
    if (empty($levels)) {
        echo json_encode([1]);
    } else {
        echo json_encode($levels);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch levels',
        'message' => $e->getMessage()
    ]);
}
