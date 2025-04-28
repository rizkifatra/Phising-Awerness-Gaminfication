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

// Initialize database connection
try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error: ' . $e->getMessage()
    ]);
    exit;
}

// Get the action from request
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Route the request based on the action
switch ($action) {
    // === USER MANAGEMENT ===
    case 'get_users':
        getUsers($db);
        break;
    case 'add_user':
        addUser($db);
        break;
    case 'update_user':
        updateUser($db);
        break;
    case 'delete_user':
        deleteUser($db);
        break;
    case 'get_user':
        getUser($db);
        break;
        
    // === QUESTION MANAGEMENT ===
    case 'get_questions':
        getQuestions($db);
        break;
    case 'add_question':
        addQuestion($db);
        break;
    case 'update_question':
        updateQuestion($db);
        break;
    case 'delete_question':
        deleteQuestion($db);
        break;
    case 'get_question':
        getQuestion($db);
        break;
        
    // === DASHBOARD DATA ===
    case 'get_dashboard_stats':
        getDashboardStats($db);
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action specified'
        ]);
        break;
}

// === USER MANAGEMENT FUNCTIONS ===

/**
 * Get users with pagination and filters
 */
function getUsers($db) {
    try {
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
}

/**
 * Get a single user by ID
 */
function getUser($db) {
    try {
        // Get user ID
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$id) {
            throw new Exception('Invalid user ID');
        }
        
        // Query to get user
        $query = "SELECT id, name, email, score, is_admin, is_verified, created_at, updated_at 
                  FROM users 
                  WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Add a new user
 */
function addUser($db) {
    try {
        // Get JSON data
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate required fields
        if (!isset($data->name) || !isset($data->email) || !isset($data->password)) {
            throw new Exception('Missing required fields');
        }
        
        // Check if email already exists
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$data->email]);
        
        if ($checkStmt->rowCount() > 0) {
            throw new Exception('Email already exists');
        }
        
        // Hash password
        $hashedPassword = password_hash($data->password, PASSWORD_DEFAULT);
        
        // Prepare insert query
        $query = "INSERT INTO users (name, email, password, is_admin, is_verified, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $db->prepare($query);
        
        // Execute query
        $result = $stmt->execute([
            $data->name,
            $data->email,
            $hashedPassword,
            isset($data->is_admin) ? $data->is_admin : 0,
            isset($data->is_verified) ? $data->is_verified : 0
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'User added successfully',
                'user_id' => $db->lastInsertId()
            ]);
        } else {
            throw new Exception('Failed to add user');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Update an existing user
 */
function updateUser($db) {
    try {
        // Get JSON data
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate required fields
        if (!isset($data->id) || !isset($data->name) || !isset($data->email)) {
            throw new Exception('Missing required fields');
        }
        
        // Check if user exists
        $checkStmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $checkStmt->execute([$data->id]);
        
        if ($checkStmt->rowCount() === 0) {
            throw new Exception('User not found');
        }
        
        // Check if email is already used by another user
        $emailStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $emailStmt->execute([$data->email, $data->id]);
        
        if ($emailStmt->rowCount() > 0) {
            throw new Exception('Email already in use by another user');
        }
        
        // Prepare update query
        $query = "UPDATE users SET 
                  name = ?, 
                  email = ?, 
                  score = ?, 
                  is_admin = ?, 
                  is_verified = ?, 
                  updated_at = NOW() 
                  WHERE id = ?";
        
        $stmt = $db->prepare($query);
        
        // Execute query
        $result = $stmt->execute([
            $data->name,
            $data->email,
            $data->score,
            isset($data->is_admin) ? $data->is_admin : 0,
            isset($data->is_verified) ? $data->is_verified : 0,
            $data->id
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update user');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Delete a user
 */
function deleteUser($db) {
    try {
        // Get JSON data
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate required field
        if (!isset($data->id)) {
            throw new Exception('Missing user ID');
        }
        
        // Check if user exists
        $checkStmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $checkStmt->execute([$data->id]);
        
        if ($checkStmt->rowCount() === 0) {
            throw new Exception('User not found');
        }
        
        // Don't allow deleting the current admin user
        if ($data->id == $_SESSION['user_id']) {
            throw new Exception('Cannot delete your own account');
        }
        
        // Prepare delete query
        $query = "DELETE FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        
        // Execute query
        $result = $stmt->execute([$data->id]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete user');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

// === QUESTION MANAGEMENT FUNCTIONS ===

/**
 * Get questions with pagination and filters
 */
function getQuestions($db) {
    try {
        // Get query parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        $level = isset($_GET['level']) ? $_GET['level'] : '';
        
        // Calculate offset
        $offset = ($page - 1) * $limit;
        
        // Build query conditions
        $conditions = [];
        $params = [];
        
        if (!empty($search)) {
            $conditions[] = "question_text LIKE ?";
            $params[] = "%$search%";
        }
        
        if (!empty($type)) {
            $conditions[] = "type = ?";
            $params[] = $type;
        }
        
        if (!empty($level)) {
            $conditions[] = "level = ?";
            $params[] = $level;
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        // Query to get total questions count
        $countQuery = "SELECT COUNT(*) AS total FROM questions $whereClause";
        $stmt = $db->prepare($countQuery);
        
        // Bind parameters for count query
        if (!empty($params)) {
            for ($i = 0; $i < count($params); $i++) {
                $stmt->bindValue($i + 1, $params[$i]);
            }
        }
        
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Query to get questions with pagination
        $query = "SELECT id, question_text, type, level, created_at, updated_at 
                  FROM questions 
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
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'questions' => $questions
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get a single question by ID
 */
function getQuestion($db) {
    try {
        // Get question ID
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$id) {
            throw new Exception('Invalid question ID');
        }
        
        // Query to get question
        $query = "SELECT * FROM questions WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        $question = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$question) {
            throw new Exception('Question not found');
        }
        
        echo json_encode([
            'success' => true,
            'question' => $question
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Add a new question
 */
function addQuestion($db) {
    try {
        // Get JSON data
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate required fields
        if (!isset($data->question_text) || !isset($data->type) || !isset($data->level)) {
            throw new Exception('Missing required fields');
        }
        
        // Start transaction
        $db->beginTransaction();
        
        // Insert question
        $query = "INSERT INTO questions (question_text, type, level, created_at, updated_at) 
                  VALUES (?, ?, ?, NOW(), NOW())";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $data->question_text,
            $data->type,
            $data->level
        ]);
        
        if (!$result) {
            throw new Exception('Failed to add question');
        }
        
        $questionId = $db->lastInsertId();
        
        // Add type-specific data
        if ($data->type === 'mcq' && isset($data->options) && isset($data->correct_option)) {
            // For multiple choice questions
            $optionsJson = json_encode($data->options);
            
            $updateQuery = "UPDATE questions SET 
                           options = ?, 
                           correct_option = ? 
                           WHERE id = ?";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateResult = $updateStmt->execute([
                $optionsJson,
                $data->correct_option,
                $questionId
            ]);
            
            if (!$updateResult) {
                throw new Exception('Failed to add question options');
            }
        } 
        else if ($data->type === 'fill' && isset($data->answer)) {
            // For fill in the blank questions
            $updateQuery = "UPDATE questions SET 
                           answer = ? 
                           WHERE id = ?";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateResult = $updateStmt->execute([
                $data->answer,
                $questionId
            ]);
            
            if (!$updateResult) {
                throw new Exception('Failed to add question answer');
            }
        } 
        else if ($data->type === 'puzzle' && isset($data->solution)) {
            // For puzzle questions
            $updateQuery = "UPDATE questions SET 
                           solution = ?, 
                           hint = ? 
                           WHERE id = ?";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateResult = $updateStmt->execute([
                $data->solution,
                isset($data->hint) ? $data->hint : null,
                $questionId
            ]);
            
            if (!$updateResult) {
                throw new Exception('Failed to add question solution');
            }
        } 
        else {
            throw new Exception('Invalid question type or missing type-specific data');
        }
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Question added successfully',
            'question_id' => $questionId
        ]);
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollback();
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Update an existing question
 */
function updateQuestion($db) {
    try {
        // Get JSON data
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate required fields
        if (!isset($data->id) || !isset($data->question_text) || !isset($data->type) || !isset($data->level)) {
            throw new Exception('Missing required fields');
        }
        
        // Check if question exists
        $checkStmt = $db->prepare("SELECT id, type FROM questions WHERE id = ?");
        $checkStmt->execute([$data->id]);
        $question = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$question) {
            throw new Exception('Question not found');
        }
        
        // Start transaction
        $db->beginTransaction();
        
        // Update question
        $query = "UPDATE questions SET 
                  question_text = ?, 
                  level = ?, 
                  updated_at = NOW() 
                  WHERE id = ?";
        
        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            $data->question_text,
            $data->level,
            $data->id
        ]);
        
        if (!$result) {
            throw new Exception('Failed to update question');
        }
        
        // Update type-specific data
        if ($data->type === 'mcq' && isset($data->options) && isset($data->correct_option)) {
            // For multiple choice questions
            $optionsJson = json_encode($data->options);
            
            $updateQuery = "UPDATE questions SET 
                           options = ?, 
                           correct_option = ? 
                           WHERE id = ?";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateResult = $updateStmt->execute([
                $optionsJson,
                $data->correct_option,
                $data->id
            ]);
            
            if (!$updateResult) {
                throw new Exception('Failed to update question options');
            }
        } 
        else if ($data->type === 'fill' && isset($data->answer)) {
            // For fill in the blank questions
            $updateQuery = "UPDATE questions SET 
                           answer = ? 
                           WHERE id = ?";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateResult = $updateStmt->execute([
                $data->answer,
                $data->id
            ]);
            
            if (!$updateResult) {
                throw new Exception('Failed to update question answer');
            }
        } 
        else if ($data->type === 'puzzle' && isset($data->solution)) {
            // For puzzle questions
            $updateQuery = "UPDATE questions SET 
                           solution = ?, 
                           hint = ? 
                           WHERE id = ?";
            
            $updateStmt = $db->prepare($updateQuery);
            $updateResult = $updateStmt->execute([
                $data->solution,
                isset($data->hint) ? $data->hint : null,
                $data->id
            ]);
            
            if (!$updateResult) {
                throw new Exception('Failed to update question solution');
            }
        } 
        else {
            throw new Exception('Invalid question type or missing type-specific data');
        }
        
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Question updated successfully'
        ]);
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollback();
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Delete a question
 */
function deleteQuestion($db) {
    try {
        // Get JSON data
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate required field
        if (!isset($data->id)) {
            throw new Exception('Missing question ID');
        }
        
        // Check if question exists
        $checkStmt = $db->prepare("SELECT id FROM questions WHERE id = ?");
        $checkStmt->execute([$data->id]);
        
        if ($checkStmt->rowCount() === 0) {
            throw new Exception('Question not found');
        }
        
        // Prepare delete query
        $query = "DELETE FROM questions WHERE id = ?";
        $stmt = $db->prepare($query);
        
        // Execute query
        $result = $stmt->execute([$data->id]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Question deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete question');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get dashboard statistics
 */
function getDashboardStats($db) {
    try {
        // Get total users
        $userQuery = "SELECT COUNT(*) as total FROM users";
        $userStmt = $db->query($userQuery);
        $totalUsers = $userStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get total questions
        $questionQuery = "SELECT COUNT(*) as total FROM questions";
        $questionStmt = $db->query($questionQuery);
        $totalQuestions = $questionStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get total games played
        $gamesQuery = "SELECT COUNT(*) as total FROM game_sessions";
        $gamesStmt = $db->query($gamesQuery);
        $totalGames = $gamesStmt->fetchColumn();
        
        // Get active sessions (approximate)
        $activeSessionsQuery = "SELECT COUNT(DISTINCT user_id) as total FROM sessions 
                               WHERE last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
        $activeStmt = $db->query($activeSessionsQuery);
        $activeSessions = $activeStmt->fetchColumn();
        
        // Get recent users
        $recentUsersQuery = "SELECT id, name, email, score, created_at 
                            FROM users 
                            ORDER BY created_at DESC 
                            LIMIT 5";
        $recentStmt = $db->query($recentUsersQuery);
        $recentUsers = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'totalUsers' => $totalUsers,
                'totalQuestions' => $totalQuestions,
                'totalGames' => $totalGames,
                'activeSessions' => $activeSessions,
                'recentUsers' => $recentUsers
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
?>
