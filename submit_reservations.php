<?php
header('Content-Type: application/json');

// ✅ Enable full PHP error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * ========================================
 * RESERVATION SYSTEM - COMPLETE REBUILD
 * ========================================
 * Handles: Validation, Opening Hours, Advance Booking, Database Storage
 * Does NOT handle: Email sending (separate system)
 */

require_once __DIR__ . '/queue_helpers.php';

// ========== CONFIGURATION ==========
const TIMEZONE = 'Europe/London';
const MIN_ADVANCE_HOURS = 2;
const ADMIN_EMAIL = 'info@haveli.co.uk';

// Operating Hours: Mon 8-5PM, Tue-Fri 8-10PM, Sat 9-11PM, Sun 9-9PM
const OPERATING_HOURS = [
    0 => ['opens' => '09:00', 'closes' => '21:00', 'display' => '9:00 AM - 9:00 PM'],     // Sunday
    1 => ['opens' => '08:00', 'closes' => '17:00', 'display' => '8:00 AM - 5:00 PM'],     // Monday
    2 => ['opens' => '08:00', 'closes' => '22:00', 'display' => '8:00 AM - 10:00 PM'],    // Tuesday
    3 => ['opens' => '08:00', 'closes' => '22:00', 'display' => '8:00 AM - 10:00 PM'],    // Wednesday
    4 => ['opens' => '08:00', 'closes' => '22:00', 'display' => '8:00 AM - 10:00 PM'],    // Thursday
    5 => ['opens' => '08:00', 'closes' => '22:00', 'display' => '8:00 AM - 10:00 PM'],    // Friday
    6 => ['opens' => '09:00', 'closes' => '23:00', 'display' => '9:00 AM - 11:00 PM'],    // Saturday
];

const DAYS_OF_WEEK = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

/**
 * Trigger the email processor asynchronously so customer emails send immediately.
 */
function triggerEmailProcessing() {
    if (function_exists('exec')) {
        $phpPath = PHP_BINARY;
        $scriptPath = __DIR__ . '/process_email_queue.php';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("start /B \"\" \"$phpPath\" \"$scriptPath\" > nul 2>&1");
        } else {
            exec("$phpPath \"$scriptPath\" > /dev/null 2>&1 &");
        }
    }

    $triggerFile = __DIR__ . '/email_trigger_' . time() . '.flag';
    @file_put_contents($triggerFile, 'process_now');
}

// ========== VALIDATION FUNCTIONS ==========

/**
 * Validates required fields
 */
function validateRequiredFields() {
    $required = ['name', 'phone', 'email', 'date', 'time', 'guests'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            return ['valid' => false, 'message' => "Missing required field: $field"];
        }
    }
    return ['valid' => true];
}

/**
 * Validates email format
 */
function validateEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'Invalid email address format.'];
    }
    return ['valid' => true];
}

/**
 * Validates phone number (10-15 digits)
 */
function validatePhone($phone) {
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) < 10 || strlen($digits) > 15) {
        return ['valid' => false, 'message' => 'Phone number must contain 10-15 digits.'];
    }
    return ['valid' => true];
}

/**
 * Parses and validates date/time format
 */
function validateDateTime($date, $time, $timezone) {
    try {
        $tz = new DateTimeZone($timezone);
        $dt = DateTime::createFromFormat('Y-m-d H:i', "$date $time", $tz);
        
        if (!$dt) {
            return [
                'valid' => false,
                'message' => 'Invalid date or time format. Expected YYYY-MM-DD and HH:MM.'
            ];
        }
        
        return ['valid' => true, 'datetime' => $dt];
    } catch (Exception $e) {
        return ['valid' => false, 'message' => 'Error parsing date/time.'];
    }
}

/**
 * Validates that reservation is not in the past
 */
function validateNotInPast($reservationTime, $currentTime) {
    if ($reservationTime < $currentTime) {
        return ['valid' => false, 'message' => 'Reservations cannot be made in the past.'];
    }
    return ['valid' => true];
}

/**
 * Validates advance booking requirement (20+ hours)
 */
function validateAdvanceBooking($reservationTime, $currentTime, $minHours = MIN_ADVANCE_HOURS) {
    $minAllowed = (clone $currentTime)->modify("+$minHours hours");
    
    if ($reservationTime < $minAllowed) {
        $message = "Reservations must be made at least <strong>$minHours hours</strong> in advance.";
        return ['valid' => false, 'message' => $message];
    }
    return ['valid' => true];
}

/**
 * Validates opening hours and returns full debugging info
 */
function validateOpeningHours($reservationTime, $currentTime) {
    $dayOfWeek = (int)$reservationTime->format('w');
    $dayName = DAYS_OF_WEEK[$dayOfWeek];
    $hours = OPERATING_HOURS[$dayOfWeek];
    
    // Create DateTime objects for opening and closing times on the reservation date
    $tz = new DateTimeZone(TIMEZONE);
    $dateStr = $reservationTime->format('Y-m-d');
    
    $openingTime = DateTime::createFromFormat(
        'Y-m-d H:i',
        "$dateStr {$hours['opens']}",
        $tz
    );
    
    $closingTime = DateTime::createFromFormat(
        'Y-m-d H:i',
        "$dateStr {$hours['closes']}",
        $tz
    );
    
    if (!$openingTime || !$closingTime) {
        return ['valid' => false, 'message' => 'Error validating opening hours.'];
    }
    
    // Check if reservation is within opening hours
    if ($reservationTime < $openingTime || $reservationTime > $closingTime) {
        $selectedTime = $reservationTime->format('g:i A');
        $selectedDate = $reservationTime->format('l, F j, Y');
        
        $debugMessage = 
            "❌ Selected time is outside opening hours.\n\n" .
            "📅 Day: $dayName ($selectedDate)\n" .
            "🕐 Operating Hours: {$hours['display']}\n" .
            "⏰ You selected: $selectedTime\n\n" .
            "📊 Debug Information:\n" .
            "• Current Time (UK): " . $currentTime->format('Y-m-d H:i:s') . "\n" .
            "• Requested Time (UK): " . $reservationTime->format('Y-m-d H:i:s') . "\n" .
            "• Opening Time (UK): " . $openingTime->format('Y-m-d H:i:s') . "\n" .
            "• Closing Time (UK): " . $closingTime->format('Y-m-d H:i:s') . "\n" .
            "• Day of Week (0-6): $dayOfWeek\n" .
            "• Time in 24h format: " . $reservationTime->format('H:i') . "\n" .
            "• Comparison: reservationTime < opening? " . ($reservationTime < $openingTime ? 'YES' : 'NO') . "\n" .
            "• Comparison: reservationTime > closing? " . ($reservationTime > $closingTime ? 'YES' : 'NO');
        
        return ['valid' => false, 'message' => $debugMessage];
    }
    
    return ['valid' => true, 'dayName' => $dayName, 'hours' => $hours];
}

/**
 * Main validation orchestrator
 */
function validateReservation() {
    // Check required fields
    $fieldValidation = validateRequiredFields();
    if (!$fieldValidation['valid']) {
        return $fieldValidation;
    }
    
    // Sanitize inputs
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $date = trim($_POST['date']);
    $time = trim($_POST['time']);
    $guests = trim($_POST['guests']);
    
    // Validate email
    $emailValidation = validateEmail($email);
    if (!$emailValidation['valid']) {
        return $emailValidation;
    }
    
    // Validate phone
    $phoneValidation = validatePhone($phone);
    if (!$phoneValidation['valid']) {
        return $phoneValidation;
    }
    
    // Validate date/time format
    $dateTimeValidation = validateDateTime($date, $time, TIMEZONE);
    if (!$dateTimeValidation['valid']) {
        return $dateTimeValidation;
    }
    $reservationTime = $dateTimeValidation['datetime'];
    
    // Get current time in UK timezone
    $tz = new DateTimeZone(TIMEZONE);
    $currentTime = new DateTime('now', $tz);
    
    // Validate not in past
    $pastValidation = validateNotInPast($reservationTime, $currentTime);
    if (!$pastValidation['valid']) {
        return $pastValidation;
    }
    
    // Validate advance booking (20+ hours)
    $advanceValidation = validateAdvanceBooking($reservationTime, $currentTime);
    if (!$advanceValidation['valid']) {
        return $advanceValidation;
    }
    
    // Validate opening hours
    $hoursValidation = validateOpeningHours($reservationTime, $currentTime);
    if (!$hoursValidation['valid']) {
        return $hoursValidation;
    }
    
    // Validate number of guests
    $guestCount = intval($guests);
    if ($guestCount < 1 || $guestCount > 20) {
        return ['valid' => false, 'message' => 'Number of guests must be between 1 and 20.'];
    }
    
    return [
        'valid' => true,
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'date' => $date,
        'time' => $time,
        'guests' => $guestCount,
        'reservationTime' => $reservationTime,
        'currentTime' => $currentTime,
        'dayName' => $hoursValidation['dayName']
    ];
}

// ========== EXECUTE VALIDATION ==========
$validation = validateReservation();

if (!$validation['valid']) {
    // Return 200 OK so the frontend can parse the JSON error message
    // instead of the browser treating it as a generic HTTP error
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'message' => $validation['message']
    ]);
    exit;
}

// ========== DATABASE & PROCESSING ==========

try {
    require_once 'db_config.php';
    $db = getDBConnection();
    
    // Extract validated data
    $name = $validation['name'];
    $phone = $validation['phone'];
    $email = $validation['email'];
    $date = $validation['date'];
    $time = $validation['time'];
    $guests = $validation['guests'];
    $currentTime = $validation['currentTime'];
    $reservationTime = $validation['reservationTime'];
    $dayName = $validation['dayName'];
    
    // Sanitize for storage
    $name_sanitized = htmlspecialchars($name);
    $phone_sanitized = htmlspecialchars($phone);
    $email_sanitized = htmlspecialchars($email);
    
    $created_at = $currentTime->format('Y-m-d H:i:s');
    
    // ========== STEP 1: Insert into database ==========
    $stmt = $db->prepare(
        "INSERT INTO reservations 
         (customer_name, phone_number, email, num_guests, reservation_date, reservation_time, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)"
    );
    
    if (!$stmt->execute([
        $name_sanitized,
        $phone_sanitized,
        $email_sanitized,
        $guests,
        $date,
        $time,
        $created_at
    ])) {
        throw new Exception("Database insertion failed: " . implode(", ", $stmt->errorInfo()));
    }
    
    $reservation_id = $db->lastInsertId();
    
    // ========== STEP 2: Create comprehensive log entry ==========
    $log_entry = [
        'reservation_id' => $reservation_id,
        'timestamp' => $created_at,
        'day_of_week' => $dayName,
        'customer_name' => $name,
        'phone' => $phone,
        'email' => $email,
        'reservation_date' => $date,
        'reservation_time' => $time,
        'num_guests' => $guests,
        'status' => 'Pending',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'validation_passed' => true,
        'system_notes' => "Advance booking: 2+ hours required | Validated successfully"
    ];
    
    // Append to JSON log file
    $log_file = __DIR__ . '/reservation_logs.json';
    $existing_logs = file_exists($log_file) 
        ? json_decode(file_get_contents($log_file), true) ?: []
        : [];
    
    $existing_logs[] = $log_entry;
    
    $log_write = file_put_contents($log_file, json_encode($existing_logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($log_write === false) {
        // Log write failure is not critical - don't throw exception
        error_log("Warning: Could not write to reservation_logs.json");
    }
    
    // ========== STEP 3: Trigger email queue (EMAIL SYSTEM) ==========
    // Email sending system is handled separately - we create a queue file in the expected format
    $queue_file = __DIR__ . '/email_queue_request_' . $reservation_id . '_' . time() . '.json';
    
    // Format matches what process_email_queue.php expects
    $email_queue_data = [
        'customer' => [
            'to' => $email_sanitized,
            'to_name' => $name_sanitized,
            'subject' => 'Reservation Request Received - Haveli Restaurant',
            'template' => 'request',
            'data' => [
                'customer_name' => $name,
                'reservation_date' => $date,
                'reservation_time' => $time,
                'num_guests' => $guests,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'day_of_week' => $dayName
            ]
        ]
    ];
    
    write_queue_file($queue_file, $email_queue_data);

    // ========== STEP 3b: Send notification to admin ==========
    $admin_queue_file = __DIR__ . '/email_queue_admin_' . $reservation_id . '_' . time() . '.json';
    $admin_email_data = [
        'customer' => [
            'to' => ADMIN_EMAIL,
            'to_name' => 'Haveli Admin',
            'subject' => 'New Reservation Request - ' . $name . ' (' . $guests . ' guests)',
            'template' => 'admin_notification',
            'data' => [
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'reservation_date' => $date,
                'reservation_time' => $time,
                'num_guests' => $guests,
                'day_of_week' => $dayName,
                'reservation_id' => $reservation_id
            ]
        ]
    ];
    write_queue_file($admin_queue_file, $admin_email_data);

    triggerEmailProcessing();
    
    // ========== STEP 4: Return success response ==========
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "✅ Reservation confirmed! We've received your booking request for $dayName at $time. You will receive a confirmation email shortly.",
        'reservation_id' => $reservation_id,
        'reservation_date' => $date,
        'reservation_time' => $time,
        'confirmation_sent' => true
    ]);
    exit;
    
} catch (Throwable $e) {
    // ========== ERROR HANDLING ==========
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your reservation. Please try again.',
        'error_detail' => $e->getMessage(),
        'error_file' => basename($e->getFile()),
        'error_line' => $e->getLine()
    ]);
    exit;
}
?>
