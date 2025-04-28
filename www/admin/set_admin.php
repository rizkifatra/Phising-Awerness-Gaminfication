<?php
// Include database connection
include_once '../config/database.php';

// This script is for one-time setup - restrict access in production
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied. This script can only be run locally.');
}

// Get email from URL parameter
$email = isset($_GET['email']) ? $_GET['email'] : null;

if (!$email) {
    die('Please provide an email address via the URL parameter. Example: set_admin.php?email=admin@example.com');
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Check if user exists
    $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("No user found with email: $email");
    }

    // Update user as admin
    $stmt = $db->prepare("UPDATE users SET is_admin = 1 WHERE email = ?");
    $result = $stmt->execute([$email]);

    if ($result) {
        echo "SUCCESS: User {$user['name']} (ID: {$user['id']}) with email {$email} has been set as admin.";
    } else {
        echo "ERROR: Failed to update user.";
    }

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>
