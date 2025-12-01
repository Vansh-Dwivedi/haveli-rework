<?php
/**
 * Reservation Confirmation API
 * Standalone endpoint for confirming reservations
 */

// Prevent any output until we are ready to send JSON
ob_start();

// Disable error display to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        // Clean any previous output
        if (ob_get_length()) ob_clean();
        
        echo json_encode([
            'success' => false,
            'message' => 'Fatal Error: ' . $error['message']
        ]);
    }
});

// Allow requests from your domain
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/queue_helpers.php';

try {
    $pdo = getDBConnection();
    
    // Get POST data
    $reservation_id = $_POST['reservation_id'] ?? 0;
    
    if (!$reservation_id) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'No reservation ID provided']);
        exit;
    }
    
    // Check if reservation exists and is in confirmable state
    $check_stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? AND status = 'pending'");
    $check_stmt->execute([$reservation_id]);
    $reservation = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'Reservation not found or already processed']);
        exit;
    }
    
    // Update reservation status
    $update_stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ?");
    $result = $update_stmt->execute([$reservation_id]);
    
    if ($result && $update_stmt->rowCount() > 0) {
        // Create confirmation email queue
        require_once __DIR__ . '/email_templates.php';
        
        $confirmation_email_queue = [
            'customer' => [
                'to' => $reservation['email'],
                'to_name' => $reservation['customer_name'],
                'subject' => '🎉 Congratulations! Your Haveli Booking is Confirmed',
                'template' => 'confirmation',
                'data' => [
                    'customer_name' => $reservation['customer_name'],
                    'reservation_date' => $reservation['reservation_date'],
                    'reservation_time' => $reservation['reservation_time'],
                    'num_guests' => $reservation['num_guests']
                ],
                'retry_count' => 0,
                'max_retries' => 3,
                'created_at' => date('Y-m-d H:i:s'),
                'priority' => 'high'
            ]
        ];
        
        // Create queue file
        $queue_file = __DIR__ . '/email_queue_confirm_' . $reservation_id . '_' . time() . '.json';
        write_queue_file($queue_file, $confirmation_email_queue);
        
        // Trigger email processing immediately
        if (function_exists('exec')) {
            $php_path = PHP_BINARY;
            $script_path = __DIR__ . '/process_email_queue.php';
            
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("start /B \"\" \"$php_path\" \"$script_path\" > nul 2>&1");
            } else {
                exec("$php_path \"$script_path\" > /dev/null 2>&1 &");
            }
        }
        
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Reservation confirmed! Confirmation email will be sent shortly.',
            'reservation_id' => $reservation_id
        ]);
    } else {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to confirm reservation']);
    }
    
} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>