<?php
// Test advance booking requirement for 3:00 PM reservation
$ukTz = new DateTimeZone('Europe/London');
$nowUk = new DateTime('now', $ukTz);

echo "<h2>Advance Booking Test for 3:00 PM</h2>";
echo "<p><strong>Current England Time:</strong> " . $nowUk->format('Y-m-d H:i:s A') . "</p>";

// Test for today
$today = $nowUk->format('Y-m-d');
$reservationTime = DateTime::createFromFormat('Y-m-d H:i', $today . ' 15:00', $ukTz);

echo "<p><strong>Desired Reservation Time:</strong> " . $reservationTime->format('Y-m-d H:i:s A') . " (3:00 PM Today)</p>";

// Calculate minimum allowed booking time (3 hours from now)
$minAllowed = (clone $nowUk)->modify('+3 hours');
echo "<p><strong>Earliest Booking Time (3 hours from now):</strong> " . $minAllowed->format('Y-m-d H:i:s A') . "</p>";

if ($reservationTime < $minAllowed) {
    echo "<p style='color: red; font-weight: bold;'>❌ CANNOT BOOK 3:00 PM TODAY - Less than 3 hours advance notice required</p>";
    
    // Calculate when 3:00 PM would be bookable
    $timeDiff = $minAllowed->diff($reservationTime);
    $hoursToWait = abs($timeDiff->h);
    echo "<p><strong>3:00 PM will be bookable in:</strong> " . $hoursToWait . " hours</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ CAN BOOK 3:00 PM TODAY - Meets 3-hour advance requirement</p>";
}

// Test for tomorrow
echo "<hr>";
$tomorrow = (clone $nowUk)->modify('+1 day')->format('Y-m-d');
$tomorrowReservation = DateTime::createFromFormat('Y-m-d H:i', $tomorrow . ' 15:00', $ukTz);

echo "<h3>Tomorrow's 3:00 PM Test</h3>";
echo "<p><strong>Tomorrow's Date:</strong> " . $tomorrow . "</p>";
echo "<p><strong>Reservation Time:</strong> " . $tomorrowReservation->format('Y-m-d H:i:s A') . "</p>";

$tomorrowMinAllowed = (clone $nowUk)->modify('+3 hours');
if ($tomorrowReservation < $tomorrowMinAllowed) {
    echo "<p style='color: red; font-weight: bold;'>❌ CANNOT BOOK 3:00 PM TOMORROW - Less than 3 hours advance notice required</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ CAN BOOK 3:00 PM TOMORROW - Meets 3-hour advance requirement</p>";
}

// Show exact times when 3:00 PM becomes bookable
echo "<hr>";
echo "<h3>When 3:00 PM Becomes Bookable</h3>";

$today3pm = DateTime::createFromFormat('Y-m-d H:i', $today . ' 15:00', $ukTz);
$bookableTime = (clone $today3pm)->modify('-3 hours');

echo "<p><strong>Today's 3:00 PM becomes bookable after:</strong> " . $bookableTime->format('H:i:s A') . "</p>";

// Check if current time is after 12:00 PM (3:00 PM minus 3 hours)
$noon = DateTime::createFromFormat('Y-m-d H:i', $today . ' 12:00', $ukTz);
if ($nowUk >= $noon) {
    echo "<p style='color: green; font-weight: bold;'>✅ CURRENT TIME IS AFTER 12:00 PM - 3:00 PM booking is now allowed</p>";
} else {
    $timeUntilBookable = $noon->diff($nowUk);
    echo "<p style='color: orange; font-weight: bold;'>⏰ 3:00 PM booking will be allowed in: " . $timeUntilBookable->format('%h hours %i minutes') . "</p>";
}

// Show opening hours check
echo "<hr>";
echo "<h3>Opening Hours Check</h3>";
$day = (int)$today3pm->format('N');
if ($day >= 1 && $day <= 5) {
    $opens = '15:00';
    $closes = '23:00';
    echo "<p><strong>Today is a weekday</strong></p>";
} else {
    $opens = '12:00';
    $closes = '23:00';
    echo "<p><strong>Today is a weekend</strong></p>";
}

echo "<p><strong>Opening Hours:</strong> " . $opens . " - " . $closes . "</p>";

$openTime = DateTime::createFromFormat('Y-m-d H:i', $today . ' ' . $opens, $ukTz);
$closeTime = DateTime::createFromFormat('Y-m-d H:i', $today . ' ' . $closes, $ukTz);

if ($today3pm < $openTime || $today3pm >= $closeTime) {
    echo "<p style='color: red; font-weight: bold;'>❌ 3:00 PM is OUTSIDE opening hours</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ 3:00 PM is WITHIN opening hours</p>";
}
?>