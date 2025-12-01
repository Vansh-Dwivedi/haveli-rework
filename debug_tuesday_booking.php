<?php
// Debug script to test Tuesday 3PM booking with exact same logic as submit_reservations.php
echo "<h2>Debug: Tuesday 3PM Booking Test</h2>";

// Simulate form submission for Tuesday 3PM
$_POST = [
    'name' => 'Test Customer',
    'phone' => '1234567890',
    'email' => 'test@example.com',
    'date' => '2025-11-18', // Today (Tuesday)
    'time' => '15:00', // 3PM
    'guests' => '2'
];

echo "<h3>Simulated Form Data</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// --- Exact same validation logic as submit_reservations.php ---

// --- Validation ---
if (
    empty($_POST['name']) || 
    empty($_POST['phone']) || 
    empty($_POST['email']) ||
    empty($_POST['date']) || 
    empty($_POST['time']) || 
    empty($_POST['guests'])
) {
    echo "<p style='color: red;'>❌ VALIDATION FAILED: Please fill in all required fields.</p>";
    exit;
}

if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    echo "<p style='color: red;'>❌ VALIDATION FAILED: Please provide a valid email address.</p>";
    exit;
}

// Validate phone
$raw_phone = $_POST['phone'] ?? '';
$digits = preg_replace('/\D+/', '', $raw_phone);
if (strlen($digits) < 10 || strlen($digits) > 15) {
    echo "<p style='color: red;'>❌ VALIDATION FAILED: Please provide a valid phone number.</p>";
    exit;
}

$reservation_date = $_POST['date'];
$reservation_time = $_POST['time'];

echo "<h3>Time Processing</h3>";
echo "<p><strong>Reservation Date:</strong> $reservation_date</p>";
echo "<p><strong>Reservation Time:</strong> $reservation_time</p>";

$ukTz = new DateTimeZone('Europe/London');
echo "<p><strong>Timezone:</strong> " . $ukTz->getName() . "</p>";

$dt = DateTime::createFromFormat('Y-m-d H:i', $reservation_date . ' ' . $reservation_time, $ukTz);
if (!$dt) {
    try {
        $dt = new DateTime($reservation_date . ' ' . $reservation_time, $ukTz);
    } catch (Exception $e) {
        $dt = false;
    }
}
if (!$dt) {
    echo "<p style='color: red;'>❌ VALIDATION FAILED: Invalid reservation date or time.</p>";
    exit;
}

echo "<p><strong>Parsed DateTime:</strong> " . $dt->format('Y-m-d H:i:s') . "</p>";

$nowUk = new DateTime('now', $ukTz);
echo "<p><strong>Current UK Time:</strong> " . $nowUk->format('Y-m-d H:i:s') . "</p>";

if ($dt < $nowUk) {
    echo "<p style='color: red;'>❌ VALIDATION FAILED: Reservations cannot be made in the past.</p>";
    exit;
}

$minAllowed = (clone $nowUk)->modify('+1 hours');
echo "<p><strong>Minimum Allowed Time (+1 hour):</strong> " . $minAllowed->format('Y-m-d H:i:s') . "</p>";

if ($dt < $minAllowed) {
    echo "<p style='color: red;'>❌ VALIDATION FAILED: Reservations must be made at least 1 hour in advance.</p>";
    $diff = $minAllowed->diff($dt);
    echo "<p><strong>Time Difference:</strong> " . $diff->format('%h hours %i minutes') . "</p>";
    exit;
}

echo "<p style='color: green;'>✅ PASSED: Minimum advance time requirement</p>";

// --- Opening Hours Validation ---
$day = (int)$dt->format('N');
echo "<h3>Opening Hours Validation</h3>";
echo "<p><strong>Day of Week:</strong> $day (1=Monday, 2=Tuesday, etc.)</p>";

// New opening hours for Haveli Bar Lounge
// Monday: 8AM to 5PM
// Tuesday-Thursday: 8AM to 10PM
// Friday: 8AM to 10PM
// Saturday: 9AM to 11PM
// Sunday: 9AM to 9PM
switch ($day) {
    case 1: // Monday
        $opens = '08:00';
        $closes = '17:00';
        break;
    case 2: // Tuesday
    case 3: // Wednesday
    case 4: // Thursday
        $opens = '08:00';
        $closes = '22:00';
        break;
    case 5: // Friday
        $opens = '08:00';
        $closes = '22:00';
        break;
    case 6: // Saturday
        $opens = '09:00';
        $closes = '23:00';
        break;
    case 7: // Sunday
        $opens = '09:00';
        $closes = '21:00';
        break;
    default:
        $opens = '08:00';
        $closes = '22:00';
}

echo "<p><strong>Opening Hours:</strong> $opens to $closes</p>";

$openT = DateTime::createFromFormat('Y-m-d H:i', $dt->format('Y-m-d') . ' ' . $opens, $ukTz);
$closeT = DateTime::createFromFormat('Y-m-d H:i', $dt->format('Y-m-d') . ' ' . $closes, $ukTz);

echo "<p><strong>Opening Time:</strong> " . $openT->format('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Closing Time:</strong> " . $closeT->format('Y-m-d H:i:s') . "</p>";

echo "<p><strong>Reservation Time:</strong> " . $dt->format('Y-m-d H:i:s') . "</p>";

if ($dt < $openT) {
    echo "<p style='color: red;'>❌ VALIDATION FAILED: Before opening time</p>";
    exit;
}

if ($dt >= $closeT) {
    echo "<p style='color: red;'>❌ VALIDATION FAILED: At or after closing time</p>";
    exit;
}

echo "<p style='color: green; font-size: 18px; font-weight: bold;'>🎉 SUCCESS: Booking time is within opening hours!</p>";

echo "<h3>Summary</h3>";
echo "<p>This debug script uses the EXACT same logic as submit_reservations.php</p>";
echo "<p>If this shows SUCCESS, then the actual form should also work</p>";
echo "<p>If this shows FAILURE, then there's a logic issue to fix</p>";
?>