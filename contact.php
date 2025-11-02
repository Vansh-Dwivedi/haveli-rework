<?php
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/SMTP.php';

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';

    if ($name && $email && $message) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'sloughhaveli@gmail.com';
            $mail->Password = 'qxzemmditlemgqph';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('sloughhaveli@gmail.com', 'Haveli Contact Form');
            $mail->addReplyTo($email, $name); // User's info for easy replies
            $mail->addAddress("sloughhaveli@gmail.com", "Haveli Restaurant"); // To your business inbox

            $mail->isHTML(true);
            $mail->Subject = 'New Contact Form Submission';
            $mail->Body = "
                <strong>Name:</strong> {$name}<br>
                <strong>Email:</strong> {$email}<br>
                <strong>Message:</strong><br>" . nl2br(htmlspecialchars($message));

            $mail->send();

            $response['success'] = true;
            $response['message'] = 'Message sent successfully!';
        } catch (Exception $e) {
            $response['message'] = 'Mailer Error: ' . $mail->ErrorInfo;
        }
    } else {
        $response['message'] = 'Missing required fields.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
