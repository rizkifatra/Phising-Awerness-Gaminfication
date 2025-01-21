<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Database connection failed');
    }

    $level = isset($_GET['level']) ? (int)$_GET['level'] : 1;
    
    // Debug query
    $stmt = $db->prepare("SELECT * FROM fill_blank_questions WHERE level = ?");
    $stmt->execute([$level]);
    
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($questions)) {
        // Return sample questions if database is empty
        $sampleQuestions = [
            [
                'id' => 1,
                'text' => 'HTML stands for [blank] Text Markup Language.',
                'answers' => ['Hyper']
            ],
            [
                'id' => 2,
                'text' => 'CSS stands for Cascading [blank] Sheets.',
                'answers' => ['Style']
            ]
        ];
        echo json_encode($sampleQuestions);
        exit;
    }
    
    $formatted_questions = array_map(function($q) {
        return [
            'id' => $q['id'],
            'text' => $q['question_text'],
            'answers' => json_decode($q['answers'], true) // Added true for array
        ];
    }, $questions);
    
    echo json_encode($formatted_questions);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'debug' => [
            'level' => $level ?? null,
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
