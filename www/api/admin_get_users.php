<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get query parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $role = isset($_GET['role']) ? $_GET['role'] : '';
    $verified = isset($_GET['verified']) ? $_GET['verified'] : '';
    
    // Calculate offset
    $offset = ($page - 1) * $limit;
    
    // Build query conditions
    $conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $conditions[] = "(name LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($role !== '') {
        $conditions[] = "is_admin = ?";
        $params[] = $role;
    }
    
    if ($verified !== '') {
        $conditions[] = "is_verified = ?";
        $params[] = $verified;
    }
    
    $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Query to get total users count
    $countQuery = "SELECT COUNT(*) AS total FROM users $whereClause";
    $stmt = $db->prepare($countQuery);
    
    // Bind parameters for count query
    if (!empty($params)) {
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
    }
    
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Query to get users with pagination
    $query = "SELECT id, name, email, score, is_admin, is_verified, created_at, updated_at 
              FROM users 
              $whereClause
              ORDER BY created_at DESC 
              LIMIT $limit OFFSET $offset";
    
    $stmt = $db->prepare($query);
    
    // Bind parameters for main query
    if (!empty($params)) {
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
    }
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'users' => $users
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
