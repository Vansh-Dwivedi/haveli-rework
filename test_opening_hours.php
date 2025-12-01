<?php
// Test script to verify opening hours validation
echo "<h2>Opening Hours Validation Test Results</h2>";

// Test cases for different days and times
$testCases = [
    // Monday tests (8AM to 5PM)
    ['day' => 'Monday', 'date' => '2025-11-24', 'time' => '08:00', 'expected' => true],  // Opening time
    ['day' => 'Monday', 'date' => '2025-11-24', 'time' => '12:00', 'expected' => true],  // Midday
    ['day' => 'Monday', 'date' => '2025-11-24', 'time' => '17:00', 'expected' => false], // Closing time
    ['day' => 'Monday', 'date' => '2025-11-24', 'time' => '18:00', 'expected' => false], // After closing
    
    // Tuesday tests (8AM to 10PM)
    ['day' => 'Tuesday', 'date' => '2025-11-25', 'time' => '08:00', 'expected' => true],  // Opening time
    ['day' => 'Tuesday', 'date' => '2025-11-25', 'time' => '14:00', 'expected' => true],  // Afternoon
    ['day' => 'Tuesday', 'date' => '2025-11-25', 'time' => '22:00', 'expected' => false], // Closing time
    ['day' => 'Tuesday', 'date' => '2025-11-25', 'time' => '23:00', 'expected' => false], // After closing
    
    // Wednesday tests (8AM to 10PM)
    ['day' => 'Wednesday', 'date' => '2025-11-26', 'time' => '08:00', 'expected' => true],  // Opening time
    ['day' => 'Wednesday', 'date' => '2025-11-26', 'time' => '22:00', 'expected' => false], // Closing time
    
    // Thursday tests (8AM to 10PM)
    ['day' => 'Thursday', 'date' => '2025-11-27', 'time' => '08:00', 'expected' => true],   // Opening time
    ['day' => 'Thursday', 'date' => '2025-11-27', 'time' => '22:00', 'expected' => false], // Closing time
    
    // Friday tests (8AM to 10PM)
    ['day' => 'Friday', 'date' => '2025-11-28', 'time' => '08:00', 'expected' => true],   // Opening time
    ['day' => 'Friday', 'date' => '2025-11-28', 'time' => '22:00', 'expected' => false], // Closing time
    
    // Saturday tests (9AM to 11PM)
    ['day' => 'Saturday', 'date' => '2025-11-29', 'time' => '09:00', 'expected' => true],  // Opening time
    ['day' => 'Saturday', 'date' => '2025-11-29', 'time' => '15:00', 'expected' => true],  // Afternoon
    ['day' => 'Saturday', 'date' => '2025-11-29', 'time' => '23:00', 'expected' => false], // Closing time
    
    // Sunday tests (9AM to 9PM)
    ['day' => 'Sunday', 'date' => '2025-11-30', 'time' => '09:00', 'expected' => true],  // Opening time
    ['day' => 'Sunday', 'date' => '2025-11-30', 'time' => '15:00', 'expected' => true],  // Afternoon
    ['day' => 'Sunday', 'date' => '2025-11-30', 'time' => '21:00', 'expected' => false], // Closing time
];

echo "<table border='1'>";
echo "<tr><th>Day</th><th>Date</th><th>Time</th><th>Expected</th><th>Actual</th><th>Result</th></tr>";

foreach ($testCases as $test) {
    $reservation_date = $test['date'];
    $reservation_time = $test['time'];
    
    $ukTz = new DateTimeZone('Europe/London');
    $dt = DateTime::createFromFormat('Y-m-d H:i', $reservation_date . ' ' . $reservation_time, $ukTz);
    
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
    
    // Check if the time is within opening hours
    $isValid = ($dt >= $openT && $dt < $closeT);
    
    $passed = ($isValid === $test['expected']) ? 'PASS' : 'FAIL';
    
    echo "<tr>";
    echo "<td>{$test['day']}</td>";
    echo "<td>{$test['date']}</td>";
    echo "<td>{$test['time']}</td>";
    echo "<td>" . ($test['expected'] ? 'Valid' : 'Invalid') . "</td>";
    echo "<td>" . ($isValid ? 'Valid' : 'Invalid') . "</td>";
    echo "<td style='color: " . ($passed === 'PASS' ? 'green' : 'red') . ";'>$passed</td>";
    echo "</tr>";
}

echo "</table>";

// Display opening hours summary
echo "<h2>Haveli Bar Lounge Opening Hours</h2>";
echo "<ul>";
echo "<li>Monday: 8AM to 5PM</li>";
echo "<li>Tuesday-Thursday: 8AM to 10PM</li>";
echo "<li>Friday: 8AM to 10PM</li>";
echo "<li>Saturday: 9AM to 11PM</li>";
echo "<li>Sunday: 9AM to 9PM</li>";
echo "</ul>";
?>