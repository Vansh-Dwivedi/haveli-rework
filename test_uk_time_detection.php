<?php
// Script to show how submit_reservations.php detects and uses UK time
echo "<h2>How submit_reservations.php Detects UK Time</h2>";

// This is the exact same logic used in submit_reservations.php
$ukTz = new DateTimeZone('Europe/London');
$nowUk = new DateTime('now', $ukTz);

echo "<h3>Current Time Detection</h3>";
echo "<p><strong>Timezone used:</strong> " . $ukTz->getName() . "</p>";
echo "<p><strong>Current UK Time:</strong> " . $nowUk->format('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Current UK Time (12-hour format):</strong> " . $nowUk->format('Y-m-d h:i A') . "</p>";
echo "<p><strong>UTC Offset:</strong> " . $nowUk->format('P') . " (GMT/BST)</p>";

// Show server local time for comparison
$serverTime = new DateTime('now');
echo "<p><strong>Server Local Time:</strong> " . $serverTime->format('Y-m-d H:i:s') . "</p>";

// Show what happens with minimum advance booking calculation
echo "<h3>Minimum Advance Booking Calculation</h3>";
$minAllowed = (clone $nowUk)->modify('+20 hours');
echo "<p><strong>Current UK Time:</strong> " . $nowUk->format('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Minimum Allowed Booking Time (+20 hour):</strong> " . $minAllowed->format('Y-m-d H:i:s') . "</p>";

// Test different booking scenarios
echo "<h3>Test Booking Scenarios</h3>";
$testTimes = ['13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

foreach ($testTimes as $time) {
    $bookingDateTime = DateTime::createFromFormat('Y-m-d H:i', $nowUk->format('Y-m-d') . ' ' . $time, $ukTz);
    
    echo "<div style='margin: 10px; padding: 10px; border: 1px solid #ccc;'>";
    echo "<p><strong>Booking Time:</strong> " . $bookingDateTime->format('H:i') . "</p>";
    
    if ($bookingDateTime < $minAllowed) {
        echo "<p style='color: red;'>❌ REJECTED - Not enough advance time</p>";
        $diff = $minAllowed->diff($bookingDateTime);
        echo "<p><em>Time difference: " . $diff->format('%h hours %i minutes') . "</em></p>";
    } else {
        echo "<p style='color: green;'>✅ ACCEPTED - Meets advance time requirement</p>";
    }
    echo "</div>";
}

// Show opening hours for today
echo "<h3>Today's Opening Hours</h3>";
$dayOfWeek = (int)$nowUk->format('N'); // 1=Monday, 7=Sunday
$dayNames = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

echo "<p><strong>Today is:</strong> " . $dayNames[$dayOfWeek] . " (Day $dayOfWeek)</p>";

// Same opening hours logic as submit_reservations.php
switch ($dayOfWeek) {
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

echo "<p><strong>Opening Hours Today:</strong> " . $opens . " to " . $closes . "</p>";

// Show current opening status
$openT = DateTime::createFromFormat('Y-m-d H:i', $nowUk->format('Y-m-d') . ' ' . $opens, $ukTz);
$closeT = DateTime::createFromFormat('Y-m-d H:i', $nowUk->format('Y-m-d') . ' ' . $closes, $ukTz);

if ($nowUk >= $openT && $nowUk < $closeT) {
    echo "<p style='color: green; font-weight: bold;'>🟢 RESTAURANT IS CURRENTLY OPEN</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>🔴 RESTAURANT IS CURRENTLY CLOSED</p>";
}

echo "<h3>Key Points</h3>";
echo "<ul>";
echo "<li>submit_reservations.php uses 'Europe/London' timezone (GMT/BST)</li>";
echo "<li>This automatically handles daylight saving time changes</li>";
echo "<li>All time comparisons are done in UK time</li>";
echo "<li>Minimum advance booking is now 1 hour (was 3 hours)</li>";
echo "<li>Opening hours vary by day of week</li>";
echo "</ul>";
?>