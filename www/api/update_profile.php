<?php
// Start session to get user ID
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'your_username', 'your_password', 'your_database');

// Check connection
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Get form data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$currentPassword = $_POST['currentPassword'] ?? '';
$newPassword = $_POST['newPassword'] ?? '';

// Handle profile picture upload
$pictureFileName = null;
if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $fileExtension = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
    $pictureFileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
    $uploadFile = $uploadDir . $pictureFileName;
    
    // Move uploaded file
    if (!move_uploaded_file($_FILES['picture']['tmp_name'], $uploadFile)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to upload picture']);
        exit;
    }
}

// Start transaction
$conn->begin_transaction();

try {
    // Update user information
    $updateQuery = "UPDATE users SET name = ?, email = ?, updated_at = NOW()";
    $params = [$name, $email];
    $types = "ss";
    
    // Add picture to update if uploaded
    if ($pictureFileName) {
        $updateQuery .= ", picture = ?";
        $params[] = $pictureFileName;
        $types .= "s";
    }
    
    // Check if password change is requested
    if ($newPassword) {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $conn->rollback();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }
        
        // Add password to update
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateQuery .= ", password = ?";
        $params[] = $hashedPassword;
        $types .= "s";
    }
    
    // Complete the query
    $updateQuery .= " WHERE id = ?";
    $params[] = $userId;
    $types .= "i";
    
    // Execute update
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0 || $stmt->errno === 0) {
        $conn->commit();
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Update failed");
    }
    
} catch (Exception $e) {
    $conn->rollback();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Profile update failed: ' . $e->getMessage()]);
}

$conn->close();
?>
