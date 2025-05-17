<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get distinct levels from all question types
    $levels = [];
    
    // Get levels from MCQ questions
    $query = "SELECT DISTINCT level FROM mcq_questions ORDER BY level ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!in_array($row['level'], $levels)) {
            $levels[] = $row['level'];
        }
    }
    
    // Get levels from fill in the blank questions
    $query = "SELECT DISTINCT level FROM fill_blank_questions ORDER BY level ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!in_array($row['level'], $levels)) {
            $levels[] = $row['level'];
        }
    }
    
    // Get levels from word puzzles
    $query = "SELECT DISTINCT level FROM word_search_puzzles ORDER BY level ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!in_array($row['level'], $levels)) {
            $levels[] = $row['level'];
        }
    }
    
    // Sort levels numerically
    sort($levels, SORT_NUMERIC);
    
    // Count questions for each level
    $levelStats = [];
    foreach ($levels as $level) {
        // Count MCQ questions
        $query = "SELECT COUNT(*) as count FROM mcq_questions WHERE level = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$level]);
        $mcqCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Count fill in the blank questions
        $query = "SELECT COUNT(*) as count FROM fill_blank_questions WHERE level = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$level]);
        $fillBlankCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Count word puzzles
        $query = "SELECT COUNT(*) as count FROM word_search_puzzles WHERE level = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$level]);
        $puzzleCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $totalCount = $mcqCount + $fillBlankCount + $puzzleCount;
        
        $levelStats[] = [
            'level' => $level,
            'mcq_count' => $mcqCount,
            'fill_blank_count' => $fillBlankCount,
            'puzzle_count' => $puzzleCount,
            'total_count' => $totalCount
        ];
    }
    
    echo json_encode([
        'success' => true,
        'levels' => $levelStats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve levels: ' . $e->getMessage()
    ]);
}
?>
