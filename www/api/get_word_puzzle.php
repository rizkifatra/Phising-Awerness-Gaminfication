<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../config/database.php';

function generateGrid($words, $answers, $size = 10) {
    $grid = array_fill(0, $size, array_fill(0, $size, ''));
    
    // Place words according to their answers
    foreach ($answers as $answer) {
        $word = strtoupper($answer->word);
        $row = $answer->row;
        $col = $answer->col;
        $direction = $answer->direction;
        $length = strlen($word);
        
        for ($i = 0; $i < $length; $i++) {
            switch ($direction) {
                case 'horizontal':
                    $grid[$row][$col + $i] = $word[$i];
                    break;
                case 'vertical':
                    $grid[$row + $i][$col] = $word[$i];
                    break;
                case 'diagonal':
                    $grid[$row + $i][$col + $i] = $word[$i];
                    break;
            }
        }
    }
    
    // Fill empty spaces with random letters
    for ($i = 0; $i < $size; $i++) {
        for ($j = 0; $j < $size; $j++) {
            if ($grid[$i][$j] === '') {
                $grid[$i][$j] = chr(rand(65, 90));
            }
        }
    }
    
    return $grid;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    $level = isset($_GET['level']) ? (int)$_GET['level'] : 1;
    
    // Add debug logging
    error_log("Fetching word puzzle for level: " . $level);
    
    // Test query to check if table exists
    try {
        $test = $db->query("SHOW TABLES LIKE 'word_search_puzzles'");
        if ($test->rowCount() === 0) {
            throw new Exception('Table word_search_puzzles does not exist');
        }
    } catch (PDOException $e) {
        throw new Exception('Database structure error: ' . $e->getMessage());
    }
    
    $stmt = $db->prepare("SELECT * FROM word_search_puzzles WHERE level = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . print_r($db->errorInfo(), true));
        throw new Exception('Database query preparation failed');
    }

    $success = $stmt->execute([$level]);
    if (!$success) {
        error_log("Execute failed: " . print_r($stmt->errorInfo(), true));
        throw new Exception('Failed to execute query');
    }

    $puzzle = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Raw puzzle data: " . print_r($puzzle, true));

    if (!$puzzle) {
        // Return sample puzzle with proper structure
        $samplePuzzle = [
            'category' => 'Programming',
            'words' => ['PHP', 'HTML', 'CSS', 'JAVASCRIPT'],
            'grid' => generateGrid(['PHP', 'HTML', 'CSS', 'JAVASCRIPT']),
            'answers' => [
                ['word' => 'PHP', 'row' => 0, 'col' => 0, 'direction' => 'horizontal'],
                ['word' => 'HTML', 'row' => 1, 'col' => 0, 'direction' => 'horizontal'],
                ['word' => 'CSS', 'row' => 2, 'col' => 0, 'direction' => 'horizontal'],
                ['word' => 'JAVASCRIPT', 'row' => 3, 'col' => 0, 'direction' => 'horizontal']
            ]
        ];
        echo json_encode($samplePuzzle);
        exit;
    }
    
    echo json_encode([
        'category' => $puzzle['category'],
        'words' => json_decode($puzzle['words']),
        'grid' => json_decode($puzzle['grid']),
        'answers' => json_decode($puzzle['answers'])
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'debug' => [
            'database' => $db_name ?? 'not set',
            'level' => $level ?? 'not set',
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
