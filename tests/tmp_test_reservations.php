<?php
// Temporary test to invoke admin_dashboard_api.php with an authenticated session
// Run from project root: php tests/tmp_test_reservations.php

// Ensure working directory is project root
chdir(__DIR__ . '/..');

session_start();
// Simulate an admin session
$_SESSION['admin_logged_in'] = true;

// Request parameters
$_GET['action'] = 'get_reservations';
$_GET['page'] = 1;
$_GET['limit'] = 5;

// Capture output produced by the API include
ob_start();
require __DIR__ . '/../admin_dashboard_api.php';
$output = ob_get_clean();

// Print the JSON result (pretty print if possible)
$result = json_decode($output, true);
if ($result === null) {
    // Not valid JSON, print raw output for debugging
    echo "RAW OUTPUT:\n";
    echo $output;
    exit(1);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
