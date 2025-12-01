<?php
// Test script to verify opening hours validation by directly calling submit_reservations.php
echo "<h2>Testing Submit Reservations with New Opening Hours</h2>";

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
echo "<tr><th>Test</th><th>Day</th><th>Date</th><th>Time</th><th>Expected</th><th>Actual</th><th>Result</th><th>Message</th></tr>";

foreach ($testCases as $index => $test) {
    // Save original POST data
    $originalPost = $_POST;
    
    // Set up POST data for the test
    $_POST = [
        'name' => 'Test Customer',
        'phone' => '1234567890',
        'email' => 'test@example.com',
        'date' => $test['date'],
        'time' => $test['time'],
        'guests' => '2'
    ];
    
    // Capture output
    ob_start();
    include 'submit_reservations.php';
    $output = ob_get_clean();
    
    // Restore original POST data
    $_POST = $originalPost;
    
    $result = json_decode($output, true);
    $actual = $result['success'] ?? false;
    $message = $result['message'] ?? ($result['error'] ?? 'No message');
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