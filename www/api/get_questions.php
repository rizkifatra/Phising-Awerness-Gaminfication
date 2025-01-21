<?php
// Prevent any output before headers
ob_start();

// Disable error display but log them
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../config/database.php';

// Clear any previous output
ob_clean();

try {
    $database = new Database();
    
    if (!$database->testConnection()) {
        throw new Exception('Database connection failed');
    }
    
    $db = $database->getConnection();

    $level = isset($_GET['level']) ? (int)$_GET['level'] : 1;
    
    // Add debug information
    $debug = ['level' => $level];
    
    // Verify table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'mcq_questions'");
    if ($tableCheck->rowCount() === 0) {
        $debug['error'] = 'Table does not exist';
        throw new Exception("Table 'mcq_questions' does not exist");
    }

    // Check table structure
    $structure = $db->query("DESCRIBE mcq_questions");
    $debug['table_structure'] = $structure->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT id, question_text, options, correct_answer FROM mcq_questions WHERE level = ? ORDER BY RAND() LIMIT 5");
    $stmt->execute([$level]);
    
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug['query_result'] = $questions;
    
    if (empty($questions)) {
        $debug['message'] = 'No questions found for level ' . $level;
        // Return sample question if no questions found
        echo json_encode([
            [
                'id' => 1,
                'question_text' => 'What does HTML stand for?',
                'options' => [
                    'Hyper Text Markup Language',
                    'High Tech Modern Language',
                    'Hyper Transfer Markup Language',
                    'Home Tool Markup Language'
                ],
                'correct_answer' => 0
            ]
        ]);
        exit;
    }
    
    // Format questions for frontend
    $formatted_questions = array_map(function($q) {
        return [
            'id' => $q['id'],
            'question_text' => $q['question_text'], // Changed from 'question' to 'question_text'
            'options' => json_decode($q['options']),
            'correct_answer' => (int)$q['correct_answer']
        ];
    }, $questions);
    
    $debug['formatted_questions'] = $formatted_questions;
    
    // Add debug parameter to see full debug info
    if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
        echo json_encode(['data' => $formatted_questions, 'debug' => $debug]);
    } else {
        echo json_encode($formatted_questions ?: []);
    }

} catch (Exception $e) {
    ob_clean(); // Clear any previous output
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'status' => 'error',
        'debug' => $debug ?? [],
        'trace' => $e->getTraceAsString()
    ]);
}
// Flush and end output
ob_end_flush();
