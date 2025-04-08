<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get the top 10 users with highest total scores across all game types
    // Using the 'name' column directly as confirmed by the user
    $query = "SELECT 
                u.id, 
                u.name as username, 
                SUM(us.score) as total_score 
              FROM 
                users u 
              INNER JOIN 
                user_scores us ON u.id = us.user_id 
              GROUP BY 
                u.id, u.name 
              ORDER BY 
                total_score DESC 
              LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $leaderboard = [];
    $rank = 1;
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $player = [
            'rank' => $rank,
            'id' => $row['id'],
            'username' => $row['username'],
            'score' => $row['total_score']
        ];
        
        $leaderboard[] = $player;
        $rank++;
    }
    
    echo json_encode(['success' => true, 'leaderboard' => $leaderboard]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
