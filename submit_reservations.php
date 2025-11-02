<?php
// No need for the debug lines anymore unless an issue persists
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

header('Content-Type: application/json');

// --- Validation ---
if (
    empty($_POST['name']) || 
    empty($_POST['phone']) || 
    empty($_POST['email']) ||
    empty($_POST['date']) || 
    empty($_POST['time']) || 
    empty($_POST['guests'])
) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

try {
    require_once 'db_config.php';

    $db = getDBConnection();
    
    $num_guests = intval($_POST['guests']);
    $reservation_date = $_POST['date'];
    $reservation_time = $_POST['time'];
    $customer_name = htmlspecialchars($_POST['name']);
    $customer_email = htmlspecialchars($_POST['email']);
    $customer_phone = htmlspecialchars($_POST['phone']);

    // Insert the reservation as "Pending"
    $stmt = $db->prepare(
        "INSERT INTO reservations (customer_name, phone_number, email, num_guests, reservation_date, reservation_time, status) 
         VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
    );
    
    $stmt->execute([
        $customer_name,
        $customer_phone,
        $customer_email,
        $num_guests,
        $reservation_date,
        $reservation_time
    ]);

    // Queue customer email for background processing
    // IMPORTANT: place in project root and use naming/template expected by process_email_queue.php
    $customer_email_queue = [
        'customer' => [
            'to' => $customer_email,
            'to_name' => $customer_name,
            'subject' => 'Reservation Request Received - Haveli Restaurant',
            'template' => 'request', // matches getRequestReceivedTemplate
            'data' => [
                'customer_name' => $customer_name,
                'reservation_date' => $reservation_date,
                'reservation_time' => $reservation_time,
                'num_guests' => $num_guests
            ],
            'retry_count' => 0,
            'max_retries' => 3,
            'created_at' => date('Y-m-d H:i:s'),
            'priority' => 'normal'
        ]
    ];
    
    // Save to root with pattern email_queue_*.json so the processor and dashboard can see it
    $queue_file_customer = __DIR__ . '/email_queue_request_' . $db->lastInsertId() . '_' . time() . '.json';
    @file_put_contents($queue_file_customer, json_encode($customer_email_queue, JSON_PRETTY_PRINT));

    // Optionally trigger processing in the background (best‑effort, non‑blocking)
    if (function_exists('exec')) {
        $php_path = PHP_BINARY;
        $script_path = __DIR__ . '/process_email_queue.php';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            @exec("start /B \"\" \"$php_path\" \"$script_path\" > nul 2>&1");
        } else {
            @exec("$php_path \"$script_path\" > /dev/null 2>&1 &");
        }
    }

    // Trigger robust email processing in background (non-blocking)
    if (function_exists('exec')) {
        $php_path = PHP_BINARY;
        $script_path = __DIR__ . '/admin/robust_email_processor.php';
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - run in background without blocking
            exec("start /B \"\" \"$php_path\" \"$script_path\" > nul 2>&1", $output, $return_var);
        } else {
            // Linux/Mac - run in background
            exec("$php_path \"$script_path\" > /dev/null 2>&1 &", $output, $return_var);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Reservation request saved! A confirmation email will arrive shortly.',
        'queued' => file_exists($queue_file_customer) ? 1 : 0
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again later.']);
    exit;
}
?>