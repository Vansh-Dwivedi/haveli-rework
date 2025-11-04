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

// Validate phone (require 10-15 digits after removing non-digit chars)
$raw_phone = $_POST['phone'] ?? '';
$digits = preg_replace('/\D+/', '', $raw_phone);
if (strlen($digits) < 10 || strlen($digits) > 15) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid phone number (include country code if outside UK).']);
    exit;
}

// Parse reservation date/time and enforce 2-hour lead time (UK time) and opening hours
$reservation_date = $_POST['date'];
$reservation_time = $_POST['time'];

// Use Europe/London timezone to correctly handle UK GMT/BST
$ukTz = new DateTimeZone('Europe/London');
$dt = DateTime::createFromFormat('Y-m-d H:i', $reservation_date . ' ' . $reservation_time, $ukTz);
if (!$dt) {
    // fallback to generic parser but force UK timezone
    try {
        $dt = new DateTime($reservation_date . ' ' . $reservation_time, $ukTz);
    } catch (Exception $e) {
        $dt = false;
    }
}
if (!$dt) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation date or time.']);
    exit;
}

// Ensure reservation is at least 2 hours from now (using UK time)
$nowUk = new DateTime('now', $ukTz);
$minAllowed = (clone $nowUk)->modify('+2 hours');
if ($dt < $minAllowed) {
    echo json_encode(['success' => false, 'message' => 'Reservations must be made at least 2 hours in advance (UK time).']);
    exit;
}

// Opening hours (Mon-Fri 17:00-23:00, Sat-Sun 12:00-23:00) evaluated in UK time
$day = (int)$dt->format('N'); // 1 (Mon) - 7 (Sun)
if ($day >=1 && $day <=5) {
    $opens = '17:00';
    $closes = '23:00';
} else {
    $opens = '12:00';
    $closes = '23:00';
}

// Build full DateTime instances for opening/closing on the reservation date
$openT = DateTime::createFromFormat('Y-m-d H:i', $dt->format('Y-m-d') . ' ' . $opens, $ukTz);
$closeT = DateTime::createFromFormat('Y-m-d H:i', $dt->format('Y-m-d') . ' ' . $closes, $ukTz);
if (!$openT || !$closeT) {
    echo json_encode(['success' => false, 'message' => 'Server error validating opening hours.']);
    exit;
}

if ($dt < $openT || $dt >= $closeT) {
    echo json_encode(['success' => false, 'message' => 'Selected time is outside of our opening hours (UK time). Please choose a different time.']);
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

    // Also queue an admin notification to info@haveli.co.uk
    $admin_email = 'info@haveli.co.uk';
    $admin_email_queue = [
        'customer' => [
            'to' => $admin_email,
            'to_name' => 'Haveli Reservations',
            'subject' => 'New Reservation Received',
            'template' => 'request',
            'data' => [
                'customer_name' => $customer_name,
                'reservation_date' => $reservation_date,
                'reservation_time' => $reservation_time,
                'num_guests' => $num_guests,
                'customer_phone' => $customer_phone,
                'customer_email' => $customer_email
            ],
            'retry_count' => 0,
            'max_retries' => 3,
            'created_at' => date('Y-m-d H:i:s'),
            'priority' => 'high'
        ]
    ];
    $queue_file_admin = __DIR__ . '/email_queue_admin_' . $db->lastInsertId() . '_' . time() . '.json';
    @file_put_contents($queue_file_admin, json_encode($admin_email_queue, JSON_PRETTY_PRINT));

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
        'queued_customer' => file_exists($queue_file_customer) ? 1 : 0,
        'queued_admin' => file_exists($queue_file_admin) ? 1 : 0
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again later.']);
    exit;
}
?>