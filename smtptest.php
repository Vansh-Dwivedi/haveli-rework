<?php
// smtp_port_check.php
// Simple SMTP port accessibility tester.
// Usage: smtp_port_check.php?host=smtp.gmail.com

set_time_limit(30);
error_reporting(E_ALL & ~E_NOTICE);

$host = isset($_GET['host']) ? trim($_GET['host']) : 'smtp.gmail.com';
$timeout = 5; // seconds

// Ports commonly used for SMTP (feel free to add/remove)
$ports = [25, 465, 587, 2525];

// Normalize host
$displayHost = htmlspecialchars($host, ENT_QUOTES, 'UTF-8');

function readLines($fp, $timeoutSecs = 3) {
    stream_set_timeout($fp, $timeoutSecs);
    $out = '';
    // read up to a small limit
    $start = microtime(true);
    while (!feof($fp) && (microtime(true) - $start) < $timeoutSecs) {
        $line = @fgets($fp, 512);
        if ($line === false) break;
        $out .= $line;
        // SMTP multi-line responses: continue until a line starting with code + space appears.
        // But for simplicity we break if a blank line or length small; loop will end on timeout or EOF.
    }
    return $out;
}

function test_smtp_port($host, $port, $timeout) {
    $result = [
        'port' => $port,
        'connect' => false,
        'transport' => 'tcp',
        'banner' => null,
        'ehlo_response' => null,
        'starttls' => null,
        'error' => null,
    ];

    // For implicit SSL (SMTPS) use ssl://
    $use_ssl = ($port == 465);
    $transport = $use_ssl ? 'ssl' : 'tcp';
    $result['transport'] = $transport;

    $target = ($use_ssl ? 'ssl://' : '') . $host . ':' . $port;

    $contextOptions = [];
    if ($use_ssl) {
        // disable peer verification to avoid failures on servers where cert validation fails.
        // In production you may want to enable verification.
        $contextOptions['ssl'] = [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ];
    }
    $context = stream_context_create($contextOptions);

    $errno = null;
    $errstr = null;

    $fp = @stream_socket_client($target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

    if (!$fp) {
        $result['error'] = trim($errstr ?: "Unable to connect (errno $errno)");
        return $result;
    }

    stream_set_timeout($fp, $timeout);
    $result['connect'] = true;

    // Read banner (if any)
    $banner = readLines($fp, 2);
    $result['banner'] = $banner ?: null;

    // For non-SSL implicit ports attempt EHLO and detect STARTTLS capability
    if (!$use_ssl) {
        // send EHLO
        $clientName = 'localhost';
        $ehloCmd = "EHLO $clientName\r\n";
        @fwrite($fp, $ehloCmd);
        usleep(200000); // 200ms wait
        $ehloResp = readLines($fp, 2);
        $result['ehlo_response'] = $ehloResp ?: null;

        // detect STARTTLS mention
        $result['starttls'] = (stripos($ehloResp, 'STARTTLS') !== false) ? true : false;

        // If STARTTLS present, also report that port likely supports opportunistic TLS (client still must perform TLS)
    } else {
        // For SSL implicit we already connected with ssl:// so it's encrypted from the start.
        $result['starttls'] = false;
    }

    // Close politely
    @fwrite($fp, "QUIT\r\n");
    @fclose($fp);

    return $result;
}

// Run tests
$results = [];
foreach ($ports as $p) {
    $results[] = test_smtp_port($host, $p, $timeout);
}

// Output HTML
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>SMTP Port Check - <?= $displayHost ?></title>
<style>
body{font-family:Inter,system-ui,Arial,Helvetica,sans-serif;margin:20px;padding:0;color:#111}
table{border-collapse:collapse;width:100%;max-width:900px}
th,td{padding:8px 10px;border:1px solid #ddd;text-align:left;vertical-align:top}
th{background:#f4f4f4}
.ok{color:green;font-weight:700}
.bad{color:#c00;font-weight:700}
.small{font-size:0.9em;color:#555}
pre{white-space:pre-wrap;word-break:break-word;background:#fafafa;padding:8px;border-radius:4px;border:1px solid #eee}
</style>
</head>
<body>
<h2>SMTP Port Check for <?= $displayHost ?></h2>
<p class="small">Default timeout: <?= $timeout ?>s. Tested ports: <?= implode(', ', $ports) ?>. Note: this checks <em>TCP/connect</em> accessibility and basic SMTP responses — it does not authenticate or fully negotiate TLS.</p>

<table>
<thead>
<tr><th>Port</th><th>Connect</th><th>Transport</th><th>Banner</th><th>EHLO / STARTTLS</th><th>Error</th></tr>
</thead>
<tbody>
<?php foreach ($results as $r): ?>
<tr>
  <td><?= htmlspecialchars($r['port']) ?></td>
  <td><?= $r['connect'] ? '<span class="ok">Open</span>' : '<span class="bad">Closed/Blocked</span>' ?></td>
  <td><?= htmlspecialchars($r['transport']) ?></td>
  <td><pre><?= $r['banner'] ? htmlspecialchars($r['banner']) : '<em>No banner</em>' ?></pre></td>
  <td>
    <?php if ($r['transport'] === 'ssl' && $r['connect']): ?>
      <span class="small">Implicit SSL (connected)</span>
    <?php elseif ($r['ehlo_response']): ?>
      <div class="small"><strong>EHLO response:</strong></div>
      <pre><?= htmlspecialchars($r['ehlo_response']) ?></pre>
      <div class="small"><strong>STARTTLS:</strong> <?= $r['starttls'] ? '<span class="ok">Yes</span>' : 'No' ?></div>
    <?php else: ?>
      <span class="small">No EHLO response</span>
    <?php endif; ?>
  </td>
  <td><?= $r['error'] ? htmlspecialchars($r['error']) : '-' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<hr>
<p class="small">Security notes: This script disables SSL peer validation when connecting to implicit-SSL ports (465) to avoid false negatives caused by cert verification problems. In production consider enabling certificate verification. Also avoid exposing this script on public hosts if you don't want port-scan-like behavior from your server.</p>
</body>
</html>
