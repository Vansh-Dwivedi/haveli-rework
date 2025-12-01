<?php
// Test specifically for 3:00 AM booking scenario
$ukTz = new DateTimeZone('Europe/London');
$nowUk = new DateTime('now', $ukTz);

echo "<h1>3:00 AM Booking Test</h1>";
echo "<p><strong>Current England Time:</strong> " . $nowUk->format('Y-m-d H:i:s A') . "</p>";

// Test for 3:00 AM today
$today = $nowUk->format('Y-m-d');
$reservationTime = DateTime::createFromFormat('Y-m-d H:i', $today . ' 03:00', $ukTz);

echo "<p><strong>Testing 3:00 AM Reservation Today:</strong> " . $reservationTime->format('Y-m-d H:i:s A') . "</p>";

// Check 1: 3-hour advance requirement
$minAllowed = (clone $nowUk)->modify('+3 hours');
$advanceCheck = ($reservationTime >= $minAllowed);

// Check 2: Opening hours
$day = (int)$reservationTime->format('N');
if ($day >= 1 && $day <= 5) {
    $opens = '15:00'; // Updated to 15:00 (3:00 PM)
    $closes = '23:00';
    $dayType = 'Weekday';
} else {
    $opens = '12:00';
    $closes = '23:00';
    $dayType = 'Weekend';
}

$openTime = DateTime::createFromFormat('Y-m-d H:i', $reservationTime->format('Y-m-d') . ' ' . $opens, $ukTz);
$closeTime = DateTime::createFromFormat('Y-m-d H:i', $reservationTime->format('Y-m-d') . ' ' . $closes, $ukTz);
$hoursCheck = ($reservationTime >= $openTime && $reservationTime < $closeTime);

echo "<div style='padding: 20px; background: #f0f8ff; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>Requirements Check:</h3>";
echo "<p><strong>1. 3-Hour Advance Requirement:</strong> " . ($advanceCheck ? "✅ PASSED" : "❌ FAILED") . "</p>";
echo "<p><strong>2. Opening Hours Requirement:</strong> " . ($hoursCheck ? "✅ PASSED" : "❌ FAILED") . "</p>";
echo "<p><strong>3. Day Type:</strong> " . $dayType . " (Opens: " . $opens . ", Closes: " . $closes . ")</p>";

// Add debug info
echo "<p><strong>Debug Info:</strong></p>";
echo "<p>Current time: " . $nowUk->format('Y-m-d H:i:s A') . "</p>";
echo "<p>Reservation time: " . $reservationTime->format('Y-m-d H:i:s A') . "</p>";
echo "<p>Min allowed time: " . $minAllowed->format('Y-m-d H:i:s A') . "</p>";
echo "<p>Advance check result: " . ($advanceCheck ? "PASS" : "FAIL") . "</p>";

echo "<h3>Final Result:</h3>";
if ($advanceCheck && $hoursCheck) {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ 3:00 AM RESERVATION IS ALLOWED!</p>";
} else {
    echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ 3:00 AM RESERVATION IS NOT ALLOWED</p>";
    if (!$advanceCheck) {
        echo "<p style='color: #666;'>Reason: Not enough advance notice (need 3 hours).</p>";
    } elseif (!$hoursCheck) {
        echo "<p style='color: #666;'>Reason: Outside opening hours.</p>";
    }
}

echo "</div>";
?>