<?php
// Temporary runner to exercise admin_dashboard_api with test bootstrap.
chdir(__DIR__ . '/..');
require_once __DIR__ . '/bootstrap.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['admin_logged_in'] = true;
// Example GET params
$_GET = ['action' => 'get_reservations', 'page' => 1, 'limit' => 3, 'q' => 'a'];
// Capture output
ob_start();
require __DIR__ . '/../admin_dashboard_api.php';
 $out = ob_get_clean();
// Pretty print
$data = json_decode($out, true);
if ($data === null) {
    echo "RAW: \n" . $out . "\n";
} else {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}
