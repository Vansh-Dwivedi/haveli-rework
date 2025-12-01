<?php
// Test by directly including submit_reservations.php
echo "<h2>Testing by Including submit_reservations.php</h2>";

// Set up POST data exactly as user would submit
$_POST = [
    'name' => 'Test Customer',
    'phone' => '1234567890',
    'email' => 'test@example.com',
    'date' => '2025-11-18', // Today (Tuesday)
    'time' => '15:00', // 3PM
    'guests' => '2'
];

echo "<h3>POST Data Set:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>Including submit_reservations.php...</h3>";

// Capture output
ob_start();
include 'submit_reservations.php';
$output = ob_get_clean();

echo "<h3>Output from submit_reservations.php:</h3>";
echo "<pre>";
echo htmlspecialchars($output);
echo "</pre>";

// Parse the JSON response
$result = json_decode($output, true);

if ($result && isset($result['success'])) {
    if ($result['success']) {
        echo "<p style='color: green; font-size: 18px; font-weight: bold;'>🎉 SUCCESS: " . htmlspecialchars($result['message']) . "</p>";
    } else {
        echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ FAILED: " . htmlspecialchars($result['message']) . "</p>";
    }
} else {
    echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ ERROR: Invalid JSON response</p>";
}

echo "<h3>Test Summary</h3>";
echo "<p>This test directly includes submit_reservations.php with the same POST data</p>";
echo "<p>If this shows SUCCESS, the issue is likely with the web server or form submission</p>";
echo "<p>If this shows FAILED, there's still a logic issue in submit_reservations.php</p>";
?>