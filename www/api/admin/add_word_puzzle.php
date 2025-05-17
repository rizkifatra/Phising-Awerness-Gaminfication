<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization');

include_once '../../config/database.php';

// Function to generate a word search grid
function generateWordSearchGrid($words) {
    $size = 10; // 10x10 grid
    $grid = array_fill(0, $size, array_fill(0, $size, ''));
    $answers = [];
    
    // Directions for word placement
    $directions = ['horizontal', 'vertical', 'diagonal'];
    
    foreach ($words as $word) {
        $placed = false;
        $word = strtoupper($word);
        $length = strlen($word);
        
        // Try to place the word up to 100 times
        for ($attempts = 0; $attempts < 100 && !$placed; $attempts++) {
            $direction = $directions[array_rand($directions)];
            
            // Choose random starting position based on direction and word length
            switch ($direction) {
                case 'horizontal':
                    $row = rand(0, $size - 1);
                    $col = rand(0, $size - $length);
                    break;
                case 'vertical':
                    $row = rand(0, $size - $length);
                    $col = rand(0, $size - 1);
                    break;
                case 'diagonal':
                    $row = rand(0, $size - $length);
                    $col = rand(0, $size - $length);
                    break;
            }
            
            // Check if the word fits in the chosen position
            $canPlace = true;
            for ($i = 0; $i < $length; $i++) {
                $r = $row;
                $c = $col;
                
                if ($direction == 'horizontal') {
                    $c += $i;
                } elseif ($direction == 'vertical') {
                    $r += $i;
                } else { // diagonal
                    $r += $i;
                    $c += $i;
                }
                
                // If position is already filled with a different letter, can't place
                if ($grid[$r][$c] !== '' && $grid[$r][$c] !== $word[$i]) {
                    $canPlace = false;
                    break;
                }
            }
            
            // Place the word if it fits
            if ($canPlace) {
                for ($i = 0; $i < $length; $i++) {
                    $r = $row;
                    $c = $col;
                    
                    if ($direction == 'horizontal') {
                        $c += $i;
                    } elseif ($direction == 'vertical') {
                        $r += $i;
                    } else { // diagonal
                        $r += $i;
                        $c += $i;
                    }
                    
                    $grid[$r][$c] = $word[$i];
                }
                
                // Add to answers
                $answers[] = [
                    'word' => $word,
                    'row' => $row,
                    'col' => $col,
                    'direction' => $direction
                ];
                
                $placed = true;
            }
        }
        
        // If word couldn't be placed after many attempts
        if (!$placed) {
            // Place it horizontally at a fixed position as fallback
            $row = count($answers) % $size;
            $col = 0;
            
            for ($i = 0; $i < $length && $i < $size; $i++) {
                $grid[$row][$col + $i] = $word[$i];
            }
            
            $answers[] = [
                'word' => $word,
                'row' => $row,
                'col' => $col,
                'direction' => 'horizontal'
            ];
        }
    }
    
    // Fill empty cells with random letters
    for ($i = 0; $i < $size; $i++) {
        for ($j = 0; $j < $size; $j++) {
            if ($grid[$i][$j] === '') {
                $grid[$i][$j] = chr(rand(65, 90)); // Random uppercase letter
            }
        }
    }
    
    return [
        'grid' => $grid,
        'answers' => $answers
    ];
}

try {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"));
    
    if (!$data || !isset($data->words) || !isset($data->level) || !isset($data->category)) {
        throw new Exception('Invalid or missing data. Required fields: words, level, category');
    }
    
    // Validate data
    if (!is_array($data->words) || count($data->words) < 3) {
        throw new Exception('At least 3 words are required');
    }
    
    // Generate grid and answers
    $puzzle = generateWordSearchGrid($data->words);
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Insert new Word Puzzle
    $query = "INSERT INTO word_search_puzzles (level, category, words, grid, answers) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    $stmt->execute([
        $data->level,
        $data->category,
        json_encode($data->words),
        json_encode($puzzle['grid']),
        json_encode($puzzle['answers'])
    ]);
    
    $lastId = $db->lastInsertId();
    
    if ($lastId) {
        echo json_encode([
            'success' => true,
            'message' => 'Word Puzzle added successfully',
            'id' => $lastId
        ]);
    } else {
        throw new Exception('Failed to add Word Puzzle');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
