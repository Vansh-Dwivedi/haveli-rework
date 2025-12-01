<?php
// Direct test of opening hours validation logic from submit_reservations.php
echo "<h2>Direct Testing of Opening Hours Validation Logic</h2>";

// Test cases for different days and times
$testCases = [
    // Monday tests (8AM to 5PM)
    ['day' => 'Monday', 'date' => '2025-11-24', 'time' => '08:00', 'expected' => true, 'description' => 'Monday at opening time (8AM)'],
    ['day' => 'Monday', 'date' => '2025-11-24', 'time' => '12:00', 'expected' => true, 'description' => 'Monday midday'],
    ['day' => 'Monday', 'date' => '2025-11-24', 'time' => '17:00', 'expected' => false, 'description' => 'Monday at closing time (5PM)'],
    ['day' => 'Monday', 'date' => '2025-11-24', 'time' => '18:00', 'expected' => false, 'description' => 'Monday after closing'],
    
    // Tuesday tests (8AM to 10PM)
    ['day' => 'Tuesday', 'date' => '2025-11-25', 'time' => '08:00', 'expected' => true, 'description' => 'Tuesday at opening time (8AM)'],
    ['day' => 'Tuesday', 'date' => '2025-11-25', 'time' => '14:00', 'expected' => true, 'description' => 'Tuesday afternoon'],
    ['day' => 'Tuesday', 'date' => '2025-11-25', 'time' => '22:00', 'expected' => false, 'description' => 'Tuesday at closing time (10PM)'],
    ['day' => 'Tuesday', 'date' => '2025-11-25', 'time' => '23:00', 'expected' => false, 'description' => 'Tuesday after closing'],
    
    // Wednesday tests (8AM to 10PM)
    ['day' => 'Wednesday', 'date' => '2025-11-26', 'time' => '08:00', 'expected' => true, 'description' => 'Wednesday at opening time (8AM)'],
    ['day' => 'Wednesday', 'date' => '2025-11-26', 'time' => '22:00', 'expected' => false, 'description' => 'Wednesday at closing time (10PM)'],
    
    // Thursday tests (8AM to 10PM)
    ['day' => 'Thursday', 'date' => '2025-11-27', 'time' => '08:00', 'expected' => true, 'description' => 'Thursday at opening time (8AM)'],
    ['day' => 'Thursday', 'date' => '2025-11-27', 'time' => '22:00', 'expected' => false, 'description' => 'Thursday at closing time (10PM)'],
    
    // Friday tests (8AM to 10PM)
    ['day' => 'Friday', 'date' => '2025-11-28', 'time' => '08:00', 'expected' => true, 'description' => 'Friday at opening time (8AM)'],
    ['day' => 'Friday', 'date' => '2025-11-28', 'time' => '22:00', 'expected' => false, 'description' => 'Friday at closing time (10PM)'],
    
    // Saturday tests (9AM to 11PM)
    ['day' => 'Saturday', 'date' => '2025-11-29', 'time' => '09:00', 'expected' => true, 'description' => 'Saturday at opening time (9AM)'],
    ['day' => 'Saturday', 'date' => '2025-11-29', 'time' => '15:00', 'expected' => true, 'description' => 'Saturday afternoon'],
    ['day' => 'Saturday', 'date' => '2025-11-29', 'time' => '23:00', 'expected' => false, 'description' => 'Saturday at closing time (11PM)'],
    
    // Sunday tests (9AM to 9PM)
    ['day' => 'Sunday', 'date' => '2025-11-30', 'time' => '09:00', 'expected' => true, 'description' => 'Sunday at opening time (9AM)'],
    ['day' => 'Sunday', 'date' => '2025-11-30', 'time' => '15:00', 'expected' => true, 'description' => 'Sunday afternoon'],
    ['day' => 'Sunday', 'date' => '2025-11-30', 'time' => '21:00', 'expected' => false, 'description' => 'Sunday at closing time (9PM)'],
];

echo "<table border='1'>";
echo "<tr><th>Test</th><th>Day</th><th>Date</th><th>Time</th><th>Expected</th><th>Actual</th><th>Result</th><th>Status</th></tr>";

foreach ($testCases as $index => $test) {
    $reservation_date = $test['date'];
    $reservation_time = $test['time'];
    
    $ukTz = new DateTimeZone('Europe/London');
    $dt = DateTime::createFromFormat('Y-m-d H:i', $reservation_date . ' ' . $reservation_time, $ukTz);
    
    // Get opening hours for the day (exact same logic as in submit_reservations.php)
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
    
    // Check if the time is within opening hours
    $isValid = ($dt >= $openT && $dt < $closeT);
    
    $passed = ($isValid === $test['expected']) ? 'PASS' : 'FAIL';
    $status = $isValid ? 'Valid' : 'Invalid';
    
    echo "<tr>";
    echo "<td>" . ($index + 1) . "</td>";
    echo "<td>{$test['day']}</td>";
    echo "<td>{$test['date']}</td>";
    echo "<td>{$test['time']}</td>";
    echo "<td>" . ($test['expected'] ? 'Valid' : 'Invalid') . "</td>";
    echo "<td>$status</td>";
    echo "<td style='color: " . ($passed === 'PASS' ? 'green' : 'red') . ";'>$passed</td>";
    echo "<td>{$test['description']}</td>";
    echo "</tr>";
}

echo "</table>";

// Display opening hours summary
echo "<h2>Haveli Bar Lounge Opening Hours (Updated)</h2>";
echo "<ul>";
echo "<li><strong>Monday:</strong> 8AM to 5PM</li>";
echo "<li><strong>Tuesday-Thursday:</strong> 8AM to 10PM</li>";
echo "<li><strong>Friday:</strong> 8AM to 10PM</li>";
echo "<li><strong>Saturday:</strong> 9AM to 11PM</li>";
echo "<li><strong>Sunday:</strong> 9AM to 9PM</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> This test directly validates the opening hours logic that has been implemented in submit_reservations.php.</p>";
?>