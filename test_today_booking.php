<?php
// Test for booking today at 3PM (Tuesday)
echo "<h2>Testing Today's Booking at 3PM (Tuesday)</h2>";

// Get current time in UK timezone
$ukTz = new DateTimeZone('Europe/London');
$nowUk = new DateTime('now', $ukTz);
echo "<p>Current time (UK): " . $nowUk->format('Y-m-d H:i:s') . "</p>";

// Test booking for today at 3PM
$today = $nowUk->format('Y-m-d');
$bookingTime = '15:00';
$bookingDateTime = DateTime::createFromFormat('Y-m-d H:i', $today . ' ' . $bookingTime, $ukTz);

echo "<p>Attempting to book for: " . $bookingDateTime->format('Y-m-d H:i:s') . "</p>";

// Check if booking is at least 1 hour in advance
$minAllowed = (clone $nowUk)->modify('+1 hours');
echo "<p>Minimum allowed booking time: " . $minAllowed->format('Y-m-d H:i:s') . "</p>";

if ($bookingDateTime < $minAllowed) {
    echo "<p style='color: red;'>❌ Booking FAILED: Not enough advance time (needs 1 hour minimum)</p>";
    $hoursDiff = $minAllowed->diff($bookingDateTime);
    echo "<p>Time difference: " . $hoursDiff->format('%h hours %i minutes') . "</p>";
} else {
    echo "<p style='color: green;'>✅ Booking PASSES minimum advance time requirement</p>";
    
    // Now check opening hours for Tuesday
    $day = (int)$bookingDateTime->format('N'); // 2 for Tuesday
    echo "<p>Day of week: $day (Tuesday)</p>";
    
    // Tuesday opening hours: 8AM to 10PM
    $opens = '08:00';
    $closes = '22:00';
    
    $openT = DateTime::createFromFormat('Y-m-d H:i', $bookingDateTime->format('Y-m-d') . ' ' . $opens, $ukTz);
    $closeT = DateTime::createFromFormat('Y-m-d H:i', $bookingDateTime->format('Y-m-d') . ' ' . $closes, $ukTz);
    
    echo "<p>Opening hours: " . $openT->format('H:i') . " to " . $closeT->format('H:i') . "</p>";
    
    if ($bookingDateTime >= $openT && $bookingDateTime < $closeT) {
        echo "<p style='color: green;'>✅ Booking PASSES opening hours validation</p>";
        echo "<p style='color: green; font-weight: bold;'>🎉 Overall: Booking should be SUCCESSFUL!</p>";
    } else {
        echo "<p style='color: red;'>❌ Booking FAILS opening hours validation</p>";
    }
}

// Test with a future Tuesday to bypass the 3-hour rule
echo "<h2>Testing Future Tuesday at 3PM (to bypass 3-hour rule)</h2>";
$futureDate = new DateTime('next Tuesday', $ukTz);
$futureBooking = DateTime::createFromFormat('Y-m-d H:i', $futureDate->format('Y-m-d') . ' 15:00', $ukTz);
echo "<p>Future booking date: " . $futureBooking->format('Y-m-d H:i:s') . "</p>";

$futureDay = (int)$futureBooking->format('N');
echo "<p>Day of week: $futureDay (Tuesday)</p>";

$futureOpenT = DateTime::createFromFormat('Y-m-d H:i', $futureBooking->format('Y-m-d') . ' 08:00', $ukTz);
$futureCloseT = DateTime::createFromFormat('Y-m-d H:i', $futureBooking->format('Y-m-d') . ' 22:00', $ukTz);

if ($futureBooking >= $futureOpenT && $futureBooking < $futureCloseT) {
    echo "<p style='color: green;'>✅ Future Tuesday 3PM booking PASSES opening hours validation</p>";
} else {
    echo "<p style='color: red;'>❌ Future Tuesday 3PM booking FAILS opening hours validation</p>";
}
?>