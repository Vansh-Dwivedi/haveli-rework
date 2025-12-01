<?php
// Test if 3:00 PM reservation is outside opening hours on a weekday
$ukTz = new DateTimeZone('Europe/London');

// Test for today (Monday)
$today = new DateTime('now', $ukTz);
$reservationTime = DateTime::createFromFormat('Y-m-d H:i', $today->format('Y-m-d') . ' 15:00', $ukTz);

echo "<h2>3:00 PM Reservation Test</h2>";
echo "<p><strong>Today's Date:</strong> " . $today->format('Y-m-d') . " (" . $today->format('l') . ")</p>";
echo "<p><strong>Reservation Time:</strong> " . $reservationTime->format('Y-m-d H:i') . " (3:00 PM)</p>";

$day = (int)$reservationTime->format('N');
if ($day >= 1 && $day <= 5) {
    $opens = '17:00';
    $closes = '23:00';
    echo "<p><strong>Day Type:</strong> Weekday (Monday-Friday)</p>";
} else {
    $opens = '12:00';
    $closes = '23:00';
    echo "<p><strong>Day Type:</strong> Weekend (Saturday-Sunday)</p>";
}

echo "<p><strong>Opening Hours:</strong> " . $opens . " - " . $closes . "</p>";

$openTime = DateTime::createFromFormat('Y-m-d H:i', $reservationTime->format('Y-m-d') . ' ' . $opens, $ukTz);
$closeTime = DateTime::createFromFormat('Y-m-d H:i', $reservationTime->format('Y-m-d') . ' ' . $closes, $ukTz);

echo "<p><strong>Opening Time:</strong> " . $openTime->format('H:i') . " (" . $openTime->format('g A') . ")</p>";
echo "<p><strong>Closing Time:</strong> " . $closeTime->format('H:i') . " (" . $closeTime->format('g A') . ")</p>";

if ($reservationTime < $openTime || $reservationTime >= $closeTime) {
    echo "<p style='color: red; font-weight: bold;'>❌ RESERVATION TIME IS OUTSIDE OPENING HOURS</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ RESERVATION TIME IS WITHIN OPENING HOURS</p>";
}

// Test for weekend
echo "<hr>";
$weekend = new DateTime('next Saturday', $ukTz);
$weekendReservation = DateTime::createFromFormat('Y-m-d H:i', $weekend->format('Y-m-d') . ' 15:00', $ukTz);

echo "<h3>Weekend Test (Saturday)</h3>";
echo "<p><strong>Date:</strong> " . $weekend->format('Y-m-d') . " (" . $weekend->format('l') . ")</p>";
echo "<p><strong>Reservation Time:</strong> " . $weekendReservation->format('H:i') . " (3:00 PM)</p>";

$weekendDay = (int)$weekendReservation->format('N');
if ($weekendDay >= 1 && $weekendDay <= 5) {
    $weekendOpens = '17:00';
    $weekendCloses = '23:00';
} else {
    $weekendOpens = '12:00';
    $weekendCloses = '23:00';
}

echo "<p><strong>Opening Hours:</strong> " . $weekendOpens . " - " . $weekendCloses . "</p>";

$weekendOpenTime = DateTime::createFromFormat('Y-m-d H:i', $weekendReservation->format('Y-m-d') . ' ' . $weekendOpens, $ukTz);
$weekendCloseTime = DateTime::createFromFormat('Y-m-d H:i', $weekendReservation->format('Y-m-d') . ' ' . $weekendCloses, $ukTz);

if ($weekendReservation < $weekendOpenTime || $weekendReservation >= $weekendCloseTime) {
    echo "<p style='color: red; font-weight: bold;'>❌ RESERVATION TIME IS OUTSIDE OPENING HOURS</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ RESERVATION TIME IS WITHIN OPENING HOURS</p>";
}
?>