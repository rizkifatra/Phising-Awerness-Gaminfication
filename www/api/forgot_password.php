<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require '../vendor/autoload.php';
require_once '../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->email)) {
        throw new Exception('Email is required');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Check if email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data->email]);
    if (!$stmt->fetch()) {
        throw new Exception('Email not found');
    }

    // Generate reset token
    $resetToken = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store reset token
    $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
    $stmt->execute([$resetToken, $expiry, $data->email]);

    // Send email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'rizkifatra31@gmail.com';
    $mail->Password = 'csgtjktolxvqnxyd';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $resetLink = "http://localhost/PAG/www/reset-password.html?token=" . $resetToken;

    $mail->setFrom('rizkifatra31@gmail.com', 'Your Game');
    $mail->addAddress($data->email);
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Request';
    $mail->Body = "Click the link below to reset your password:<br><br>
                   <a href='{$resetLink}'>{$resetLink}</a><br><br>
                   This link will expire in 1 hour.";

    $mail->send();

    echo json_encode([
        'success' => true,
        'message' => 'Reset link sent successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
