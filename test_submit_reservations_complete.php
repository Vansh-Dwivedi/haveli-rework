<?php
// Complete test of submit_reservations.php functionality without web server
echo "<h2>Complete Test of Submit Reservations Functionality</h2>";

// Test cases for different days and times
$testCases = [
    // Valid reservation during opening hours
    ['day' => 'Monday', 'date' => '2025-11-24', 'time' => '12:00', 'expected' => true, 'description' => 'Monday midday (should be valid)'],
    ['day' => 'Tuesday', 'date' => '2025-11-25', 'time' => '14:00', 'expected' => true, 'description' => 'Tuesday afternoon (should be valid)'],
    ['day' => 'Saturday', 'date' => '2025-11-29', 'time' => '15:00', 'expected' => true, 'description' => 'Saturday afternoon (should be valid)'],
    
    // Invalid reservation outside opening hours
    ['day' => 'Monday', 'date' => '2025-11-24', 'time' => '18:00', 'expected' => false, 'description' => 'Monday after closing (should be invalid)'],
    ['day' => 'Sunday', 'date' => '2025-11-30', 'time' => '22:00', 'expected' => false, 'description' => 'Sunday after closing (should be invalid)'],
];

echo "<table border='1'>";
echo "<tr><th>Test</th><th>Day</th><th>Date</th><th>Time</th><th>Expected</th><th>Actual</th><th>Result</th><th>Message</th></tr>";

foreach ($testCases as $index => $test) {
    // Simulate POST data
    $_POST = [
        'name' => 'Test Customer',
        'phone' => '1234567890',
        'email' => 'test@example.com',
        'date' => $test['date'],
        'time' => $test['time'],
        'guests' => '2'
    ];
    
    // Extract validation logic from submit_reservations.php
    $validation_result = validateReservationTime($test['date'], $test['time']);
    
    $actual = $validation_result['valid'];
    $message = $validation_result['message'];
    $passed = ($actual === $test['expected']) ? 'PASS' : 'FAIL';
    
    echo "<tr>";
    echo "<td>" . ($index + 1) . "</td>";
    echo "<td>{$test['day']}</td>";
    echo "<td>{$test['date']}</td>";
    echo "<td>{$test['time']}</td>";
    echo "<td>" . ($test['expected'] ? 'Valid' : 'Invalid') . "</td>";
    echo "<td>" . ($actual ? 'Valid' : 'Invalid') . "</td>";
    echo "<td style='color: " . ($passed === 'PASS' ? 'green' : 'red') . ";'>$passed</td>";
    echo "<td>$message</td>";
    echo "</tr>";
}

echo "</table>";

// Function to validate reservation time (extracted from submit_reservations.php)
function validateReservationTime($reservation_date, $reservation_time) {
    $ukTz = new DateTimeZone('Europe/London');
    $dt = DateTime::createFromFormat('Y-m-d H:i', $reservation_date . ' ' . $reservation_time, $ukTz);
    
    if (!$dt) {
        try {
            $dt = new DateTime($reservation_date . ' ' . $reservation_time, $ukTz);
        } catch (Exception $e) {
            $dt = false;
        }
    }
    
    if (!$dt) {
        return ['valid' => false, 'message' => 'Invalid reservation date or time.'];
    }
    
    // Check if reservation is in the past
    $nowUk = new DateTime('now', $ukTz);
    if ($dt < $nowUk) {
        return ['valid' => false, 'message' => 'Reservations cannot be made in the past.'];
    }
    
    // Check minimum advance booking time (3 hours)
    $minAllowed = (clone $nowUk)->modify('+3 hours');
    if ($dt < $minAllowed) {
        return ['valid' => false, 'message' => 'Reservations must be made at least 3 hours in advance.'];
    }
    
    // Get opening hours for the day
    $day = (int)$dt->format('N');
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
    
    $openT = DateTime::createFromFormat('Y-m-d H:i', $dt->format('Y-m-d') . ' ' . $opens, $ukTz);
    $closeT = DateTime::createFromFormat('Y-m-d H:i', $dt->format('Y-m-d') . ' ' . $closes, $ukTz);
    
    if ($dt < $openT || $dt >= $closeT) {
        return ['valid' => false, 'message' => 'Selected time is outside opening hours.'];
    }
    
    return ['valid' => true, 'message' => 'Reservation time is valid.'];
}

// Display opening hours summary
echo "<h2>Haveli Bar Lounge Opening Hours (Updated)</h2>";
echo "<ul>";
echo "<li><strong>Monday:</strong> 8AM to 5PM</li>";
echo "<li><strong>Tuesday-Thursday:</strong> 8AM to 10PM</li>";
echo "<li><strong>Friday:</strong> 8AM to 10PM</li>";
echo "<li><strong>Saturday:</strong> 9AM to 11PM</li>";
echo "<li><strong>Sunday:</strong> 9AM to 9PM</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> This test simulates the complete validation logic from submit_reservations.php including time checks and opening hours validation.</p>";
?>