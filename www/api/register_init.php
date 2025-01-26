<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

include_once '../config/database.php';

require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->name) || !isset($data->email) || !isset($data->password)) {
        throw new Exception('Missing required fields');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Check if email already exists in users table
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data->email]);
    if ($stmt->fetch()) {
        throw new Exception('Email already registered');
    }

    // Generate 6-digit verification code
    $verificationCode = sprintf("%06d", mt_rand(0, 999999));

    // Store in temporary registrations
    $stmt = $db->prepare("INSERT INTO temp_registrations (name, email, password, verification_code) 
                         VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $data->name,
        $data->email,
        password_hash($data->password, PASSWORD_DEFAULT),
        $verificationCode
    ]);

    $tempId = $db->lastInsertId();

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rizkifatra31@gmail.com' ;
        $mail->Password = 'csgtjktolxvqnxyd'; // Update this
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('your-email@gmail.com', 'Your Game');
        $mail->addAddress($data->email);
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Registration';
        $mail->Body = "Your verification code is: <b>$verificationCode</b>";

        $mail->send();
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification code sent',
            'temp_id' => $tempId
        ]);
    } catch (Exception $e) {
        throw new Exception('Failed to send verification email: ' . $mail->ErrorInfo);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
