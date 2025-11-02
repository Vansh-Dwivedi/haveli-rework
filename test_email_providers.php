<?php
/**
 * Test Email Sender
 * Usage (browser): /test_email_providers.php?to=someone@example.com
 * Usage (CLI): php test_email_providers.php someone@example.com
 */

header('Content-Type: application/json');

$defaultTo = 'kalakaarstudios@gmail.com';
$to = isset($_GET['to']) ? $_GET['to'] : (isset($_POST['to']) ? $_POST['to'] : $defaultTo);

if (php_sapi_name() === 'cli' && isset($argv) && isset($argv[1]) && filter_var($argv[1], FILTER_VALIDATE_EMAIL)) {
    $to = $argv[1];
}

// SMTP configuration (Zoho)
$SMTP_HOST = 'smtppro.zoho.eu';
$SMTP_PORT = 465;
$SMTP_SECURE = 'ssl';
$SMTP_USER = 'info@haveli.co.uk';

// Retrieve SMTP password securely
$SMTP_PASS = getenv('HAVELI_SMTP_PASS');
if (!$SMTP_PASS && file_exists(__DIR__ . '/smtp_password.php')) {
    include __DIR__ . '/smtp_password.php';
    if (isset($SMTP_PASSWORD) && $SMTP_PASSWORD) {
        $SMTP_PASS = $SMTP_PASSWORD;
    }
}

require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing recipient email']);
    exit;
}

if (!$SMTP_PASS) {
    echo json_encode([
        'success' => false,
        'message' => 'SMTP password not configured. Set HAVELI_SMTP_PASS environment variable or create smtp_password.php with $SMTP_PASSWORD.'
    ]);
    exit;
}

try {
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host = $SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = $SMTP_USER;
    $mail->Password = $SMTP_PASS;
    $mail->SMTPSecure = $SMTP_SECURE;
    $mail->Port = $SMTP_PORT;
    $mail->CharSet = 'UTF-8';

    // Recipients
    $mail->setFrom($SMTP_USER, 'Haveli Restaurant');
    $mail->addAddress($to);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Haveli Test Email (' . date('Y-m-d H:i:s') . ')';
    $mail->Body = '<p>This is a test email from Haveli Admin.</p>' .
                  '<p>Timestamp: <strong>' . date('c') . '</strong></p>' .
                  '<p>If you received this, SMTP is working.</p>';
    $mail->AltBody = 'This is a test email from Haveli Admin. Timestamp: ' . date('c');

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Test email sent to ' . $to]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $e->getMessage()]);
}
