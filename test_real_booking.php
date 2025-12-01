<?php
// Test real booking by simulating POST to submit_reservations.php
echo "<h2>Testing Real Booking via POST to submit_reservations.php</h2>";

// Prepare POST data
$postData = [
    'name' => 'Test Customer',
    'phone' => '1234567890',
    'email' => 'test@example.com',
    'date' => '2025-11-18', // Today (Tuesday)
    'time' => '15:00', // 3PM
    'guests' => '2'
];

echo "<h3>POST Data Being Sent:</h3>";
echo "<pre>";
print_r($postData);
echo "</pre>";

// Use cURL to make actual POST request to submit_reservations.php
$ch = curl_init();

// Set URL to the actual file
$url = 'http://localhost/haveli/submit_reservations.php';

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Close cURL
curl_close($ch);

echo "<h3>Response from submit_reservations.php:</h3>";
echo "<p><strong>HTTP Status Code:</strong> $httpCode</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>";

// Parse the JSON response
$result = json_decode($response, true);

if ($result && isset($result['success'])) {
    if ($result['success']) {
        echo "<p style='color: green; font-size: 18px; font-weight: bold;'>🎉 SUCCESS: Booking accepted!</p>";
    } else {
        echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ FAILED: " . htmlspecialchars($result['message']) . "</p>";
    }
} else {
    echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ ERROR: Invalid response from server</p>";
}

echo "<h3>Test Summary</h3>";
echo "<p>This test makes a real HTTP POST request to submit_reservations.php</p>";
echo "<p>It simulates exactly what happens when a user submits the reservation form</p>";
?>