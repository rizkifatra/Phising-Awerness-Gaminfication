<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Create direct database connection
try {
    $host = 'localhost';
    $dbname = 'PAG'; // Changed to lowercase to match error message.PAG is the database name
    $username = 'root';
    $password = '';
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_id = $_SESSION['user_id'];
    
    // Get user profile data
    $stmt = $conn->prepare("SELECT id, name, email, score, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    // Default to user table score
    $totalScore = $user['score'] ?? 0;
    $levelsCompleted = 0;
    
    // Try to get score from user_scores table (note the plural form)
    try {
        $stmt = $conn->prepare("
            SELECT 
                SUM(score) as total_score, 
                COUNT(DISTINCT level) as levels_completed
            FROM 
                user_scores 
            WHERE 
                user_id = ?
        ");
        $stmt->execute([$user_id]);
        $scoreData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($scoreData && $scoreData['total_score'] !== null) {
            $totalScore = $scoreData['total_score'];
            $levelsCompleted = $scoreData['levels_completed'] ?? 0;
        }
    } catch (PDOException $e) {
        // If there's an error with the user_scores query, we'll just use the user table score
        error_log('Error fetching from user_scores: ' . $e->getMessage());
    }
    
    // Format data for response
    $profileData = [
        'username' => $user['name'],
        'dateJoined' => date('F Y', strtotime($user['created_at'])),
        'totalScore' => (int)$totalScore,
        'levelsCompleted' => (int)$levelsCompleted,
        'rank' => 1 // Default rank
    ];
    
    // Calculate rank based on other users' scores from user_scores table
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) + 1 as rank FROM (
                SELECT user_id, SUM(score) as total 
                FROM user_scores 
                GROUP BY user_id 
                HAVING SUM(score) > ?
            ) as rankings
        ");
        $stmt->execute([$totalScore]);
        $rankData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rankData) {
            $profileData['rank'] = (int)$rankData['rank'];
        }
    } catch (PDOException $e) {
        // If this query fails, fall back to users table ranking
        $stmt = $conn->prepare("SELECT COUNT(*) + 1 as rank FROM users WHERE score > ?");
        $stmt->execute([$user['score']]);
        $rankData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($rankData) {
            $profileData['rank'] = (int)$rankData['rank'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'profile' => $profileData
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
