<?php
// Test script to check England GMT/BST time
$ukTz = new DateTimeZone('Europe/London');
$nowUk = new DateTime('now', $ukTz);
$nowUtc = new DateTime('now', new DateTimeZone('UTC'));

echo "<h2>Time Zone Test</h2>";
echo "<p><strong>Current UTC Time:</strong> " . $nowUtc->format('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Current England Time (GMT/BST):</strong> " . $nowUk->format('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Time Zone:</strong> " . $ukTz->getName() . "</p>";
echo "<p><strong>Is DST (British Summer Time):</strong> " . ($nowUk->format('I') ? 'Yes' : 'No') . "</p>";

// Calculate difference from UTC
$offset = $ukTz->getOffset($nowUk) / 3600; // Convert to hours
echo "<p><strong>Offset from UTC:</strong> " . ($offset >= 0 ? '+' : '') . $offset . " hours</p>";

// Test 3 hours from now
$plus3Hours = (clone $nowUk)->modify('+3 hours');
echo "<p><strong>England Time in 3 Hours:</strong> " . $plus3Hours->format('Y-m-d H:i:s') . "</p>";

// Test 3 hours ago
$minus3Hours = (clone $nowUk)->modify('-3 hours');
echo "<p><strong>England Time 3 Hours Ago:</strong> " . $minus3Hours->format('Y-m-d H:i:s') . "</p>";

// Test the specific reservation logic
echo "<h2>Reservation Test</h2>";
$minAllowed = (clone $nowUk)->modify('+2 hours');
echo "<p><strong>Earliest Reservation Time (2 hours from now):</strong> " . $minAllowed->format('Y-m-d H:i:s') . "</p>";

// Test opening hours
$today = $nowUk->format('Y-m-d');
$dayOfWeek = (int)$nowUk->format('N'); // 1 (Monday) to 7 (Sunday)

if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
    $opens = '17:00';
    $closes = '23:00';
    echo "<p><strong>Today is a weekday</strong></p>";
} else {
    $opens = '12:00';
    $closes = '23:00';
    echo "<p><strong>Today is a weekend</strong></p>";
}

$openTime = DateTime::createFromFormat('Y-m-d H:i', $today . ' ' . $opens, $ukTz);
$closeTime = DateTime::createFromFormat('Y-m-d H:i', $today . ' ' . $closes, $ukTz);

echo "<p><strong>Opening Hours Today:</strong> " . $opens . " - " . $closes . "</p>";
echo "<p><strong>Currently Open:</strong> " . ($nowUk >= $openTime && $nowUk < $closeTime ? 'Yes' : 'No') . "</p>";
?>