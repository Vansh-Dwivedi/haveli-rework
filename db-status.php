<?php
/**
 * Database Status Page (db-status.php)
 * - Shows whether the database is reachable and basic health checks
 * - Admin-only access (uses same session guard as dashboard)
 * - Optional JSON output with ?format=json
 */

session_start();

// Optional debug toggle: /db-status.php?debug=1
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Restrict access to authenticated admins only
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // If JSON requested, return 401 JSON; else redirect
    if (isset($_GET['format']) && strtolower($_GET['format']) === 'json') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit;
    }
    header('Location: admin_access.php');
    exit;
}

// Helper to run a connection attempt safely
function try_pdo_connect($label, $dsn, $user, $pass) {
    $result = [
        'label' => $label,
        'dsn' => $dsn,
        'user' => $user,
        'connected' => false,
        'server_version' => null,
        'latency_ms' => null,
        'error' => null,
        'checks' => [
            'select_1' => null,
            'reservations_count' => null,
            'orders_count' => null,
        ],
    ];

    $start = microtime(true);
    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        // Connected
        $result['connected'] = true;
        $result['server_version'] = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        $result['latency_ms'] = (int) round((microtime(true) - $start) * 1000);

        // Basic query checks
        try {
            $stmt = $pdo->query('SELECT 1 AS ok');
            $row = $stmt->fetch();
            $result['checks']['select_1'] = ($row && (int)$row['ok'] === 1) ? 'ok' : 'fail';
        } catch (Exception $e) {
            $result['checks']['select_1'] = 'error';
        }

        // Optional table counts (best-effort)
        foreach ([
            'reservations' => 'reservations_count',
            'orders' => 'orders_count',
        ] as $table => $key) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) AS c FROM `{$table}`");
                $count = $stmt->fetch();
                $result['checks'][$key] = isset($count['c']) ? (int)$count['c'] : 0;
            } catch (Exception $e) {
                $result['checks'][$key] = null; // table may not exist; keep null
            }
        }

    } catch (Exception $e) {
        $result['connected'] = false;
        $result['latency_ms'] = (int) round((microtime(true) - $start) * 1000);
        // Sanitize error (no credentials)
        $result['error'] = $e->getMessage();
    }

    return $result;
}

// Known configurations to try (order matters)
$attempts = [];

// Attempt 1: Config used in db_config.php (preferred)
// If available, use the same function as the app
try {
    require_once __DIR__ . '/db_config.php';
    $start = microtime(true);
    $pdo = getDBConnection();
    $attempts[] = [
        'label' => 'db_config (getDBConnection)',
        'dsn' => 'mysql:host=localhost;dbname=haveli_db;charset=utf8',
        'user' => 'from db_config',
        'connected' => true,
        'server_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
        'latency_ms' => (int) round((microtime(true) - $start) * 1000),
        'error' => null,
        'checks' => [
            'select_1' => (function() use ($pdo) {
                try { $r=$pdo->query('SELECT 1 AS ok')->fetch(); return ($r && (int)$r['ok']===1) ? 'ok' : 'fail'; } catch (Exception $e) { return 'error'; }
            })(),
            'reservations_count' => (function() use ($pdo) {
                try { $r=$pdo->query('SELECT COUNT(*) AS c FROM `reservations`')->fetch(); return isset($r['c'])?(int)$r['c']:0; } catch (Exception $e) { return null; }
            })(),
            'orders_count' => (function() use ($pdo) {
                try { $r=$pdo->query('SELECT COUNT(*) AS c FROM `orders`')->fetch(); return isset($r['c'])?(int)$r['c']:0; } catch (Exception $e) { return null; }
            })(),
        ],
    ];
} catch (Exception $e) {
    // Fall back to manual attempts below if db_config fails
}

// Attempt 2: API legacy default (root with no password) — often disabled
$attempts[] = try_pdo_connect(
    'API legacy (root, no password)',
    'mysql:host=localhost;dbname=haveli_db;charset=utf8',
    'root',
    ''
);

// Choose first successful attempt as primary status
$primary = null;
foreach ($attempts as $a) {
    if ($a['connected']) { $primary = $a; break; }
}
if ($primary === null) { $primary = $attempts[0]; }

// JSON output if requested
if (isset($_GET['format']) && strtolower($_GET['format']) === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $primary['connected'],
        'active_config' => $primary['label'],
        'latency_ms' => $primary['latency_ms'],
        'server_version' => isset($primary['server_version']) ? $primary['server_version'] : null,
        'checks' => $primary['checks'],
        'all_attempts' => $attempts,
        'timestamp' => date('c'),
    ]);
    exit;
}

// Ensure standards mode and correct content-type for HTML output
header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Database Status • Haveli</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin-dashboard.css" rel="stylesheet">
    <style>
        .db-status-wrap { max-width: 960px; margin: 40px auto; padding: 24px; background: var(--white, #fff); border: 1px solid var(--gray-200, #e5e7eb); border-radius: 12px; }
        .db-status-header { display:flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .db-pill { display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius: 999px; font-weight:600; }
        .ok { background:#10b9811a; color:#065f46; border:1px solid #10b98140; }
        .fail { background:#ef44441a; color:#7f1d1d; border:1px solid #ef444440; }
        .meta { color: var(--gray-600, #4b5563); font-size: 14px; }
        .grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 16px; margin-top:12px; }
        .card { border:1px solid var(--gray-200,#e5e7eb); border-radius: 8px; padding:16px; background:#fff; }
        .muted { color: var(--gray-500,#6b7280); }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
        .attempts { margin-top: 24px; }
        .attempts h3 { margin-bottom: 8px; }
        .btn { display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border-radius:8px; border:1px solid var(--gray-300,#d1d5db); background:#fff; cursor:pointer; text-decoration:none; color:inherit; }
        .btn:hover { background:#f9fafb; }
    </style>
</head>
<body>
    <div class="db-status-wrap">
        <div class="db-status-header">
            <h1><i class="fas fa-database"></i> Database Status</h1>
            <?php if ($primary['connected']): ?>
                <span class="db-pill ok"><i class="fas fa-check-circle"></i> Connected</span>
            <?php else: ?>
                <span class="db-pill fail"><i class="fas fa-times-circle"></i> Not Connected</span>
            <?php endif; ?>
        </div>
        <p class="meta">Checked at <strong><?php echo date('Y-m-d H:i:s'); ?></strong>. <a class="btn" href="?format=json"><i class="fas fa-code"></i> JSON</a></p>

        <div class="grid">
            <div class="card">
                <h3><i class="fas fa-server"></i> Connection</h3>
                <p>Status: <?php echo $primary['connected'] ? '<strong style="color:#065f46">Connected</strong>' : '<strong style="color:#7f1d1d">Failed</strong>'; ?></p>
                <p>Active config: <span class="mono"><?php echo htmlspecialchars($primary['label']); ?></span></p>
                <p>Latency: <span class="mono"><?php echo (int)$primary['latency_ms']; ?> ms</span></p>
                <p>Server version: <span class="mono"><?php echo htmlspecialchars(isset($primary['server_version']) ? $primary['server_version'] : 'n/a'); ?></span></p>
                <?php if (!$primary['connected'] && !empty($primary['error'])): ?>
                    <p class="muted">Error: <span class="mono"><?php echo htmlspecialchars($primary['error']); ?></span></p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3><i class="fas fa-table"></i> Tables</h3>
                <p>reservations: <strong><?php echo $primary['checks']['reservations_count'] === null ? 'n/a' : (int)$primary['checks']['reservations_count']; ?></strong></p>
                <p>orders: <strong><?php echo $primary['checks']['orders_count'] === null ? 'n/a' : (int)$primary['checks']['orders_count']; ?></strong></p>
                <p class="muted">If "n/a", the table may not exist or this user lacks privileges.</p>
            </div>

            <div class="card">
                <h3><i class="fas fa-stethoscope"></i> Health Checks</h3>
                <p>SELECT 1: <strong><?php echo $primary['checks']['select_1'] ?: 'n/a'; ?></strong></p>
                <p>Time: <span class="mono"><?php echo date('c'); ?></span></p>
            </div>
        </div>

        <div class="attempts">
            <h3><i class="fas fa-list"></i> Connection Attempts</h3>
            <div class="grid">
                <?php foreach ($attempts as $a): ?>
                    <div class="card">
                        <p><strong><?php echo htmlspecialchars($a['label']); ?></strong></p>
                        <p>Status: <?php echo $a['connected'] ? '<span style="color:#065f46">Connected</span>' : '<span style="color:#7f1d1d">Failed</span>'; ?></p>
                        <p>Latency: <span class="mono"><?php echo (int)$a['latency_ms']; ?> ms</span></p>
                        <p>Server: <span class="mono"><?php echo htmlspecialchars(isset($a['server_version']) ? $a['server_version'] : 'n/a'); ?></span></p>
                        <?php if (!$a['connected'] && !empty($a['error'])): ?>
                            <details><summary>Error details</summary>
                                <div class="mono muted" style="white-space: pre-wrap; word-break: break-word; margin-top:8px;"><?php echo htmlspecialchars($a['error']); ?></div>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <p class="muted" style="margin-top:16px;">Tip: For consistency, use a single DB configuration across the app. This page tries multiple known configs to help diagnose mismatches.</p>
        <p><a class="btn" href="admin_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></p>
    </div>
</body>
</html>
