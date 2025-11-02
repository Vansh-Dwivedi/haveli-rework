<?php
/**
 * Admin utility: Clear all reservations
 * - Requires admin session
 * - Use ?confirm=1 to perform deletion
 * - Use ?format=json for JSON output
 */

session_start();

$asJson = isset($_GET['format']) && strtolower($_GET['format']) === 'json';
if ($asJson) {
    header('Content-Type: application/json');
}

// Require admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($asJson) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
    } else {
        header('Location: admin_access.php');
    }
    exit;
}

require_once __DIR__ . '/db_config.php';

function respond($ok, $msg, $extra = []) {
    global $asJson;
    if ($asJson) {
        echo json_encode(['success' => $ok, 'message' => $msg] + $extra);
        return;
    }
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Clear Reservations</title>';
    echo '<style>body{font-family:Arial;margin:40px;max-width:720px} .btn{display:inline-block;padding:10px 16px;border-radius:6px;text-decoration:none;color:#fff} .danger{background:#c0392b} .muted{color:#555} .card{border:1px solid #ddd;border-radius:8px;padding:20px}</style>';
    echo '</head><body>';
    echo '<h1>Clear Reservations</h1>';
    echo '<div class="card">' . htmlspecialchars($msg) . '</div>';
    echo '<p><a class="btn" style="background:#6c757d" href="admin_dashboard.php">Back to Dashboard</a></p>';
    echo '</body></html>';
}

try {
    $pdo = getDBConnection();

    // If not confirmed, show a confirmation page
    if (!isset($_GET['confirm'])) {
        if ($asJson) {
            echo json_encode(['success' => false, 'message' => 'Add ?confirm=1 to clear all reservations']);
            exit;
        }
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Confirm Clear</title>';
        echo '<style>body{font-family:Arial;margin:40px;max-width:720px} .btn{display:inline-block;padding:10px 16px;border-radius:6px;text-decoration:none;color:#fff} .danger{background:#c0392b} .muted{color:#555} .card{border:1px solid #ddd;border-radius:8px;padding:20px}</style>';
        echo '</head><body>';
        echo '<h1>Confirm clearing all reservations</h1>';
        echo '<div class="card"><p class="muted">This will permanently remove all rows from the reservations table and reset its auto-increment counter.</p></div>';
        echo '<p><a class="btn danger" href="admin_clear_reservations.php?confirm=1">Yes, delete all reservations</a></p>';
        echo '<p><a class="btn" style="background:#6c757d" href="admin_dashboard.php">Cancel</a></p>';
        echo '</body></html>';
        exit;
    }

    // Count current rows
    $count = (int)$pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();

    // Try TRUNCATE first (fast, resets AUTO_INCREMENT)
    $removed = 0;
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $pdo->exec('TRUNCATE TABLE reservations');
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        $removed = $count;
    } catch (Exception $e) {
        // Fallback to DELETE + reset auto-increment
        $pdo->exec('DELETE FROM reservations');
        $pdo->exec('ALTER TABLE reservations AUTO_INCREMENT = 1');
        $removed = $count; // best-effort
    }

    respond(true, "Deleted all reservations ($removed removed).", ['removed' => $removed]);
    exit;
} catch (Exception $e) {
    respond(false, 'Error clearing reservations: ' . $e->getMessage());
    exit;
}
?>
