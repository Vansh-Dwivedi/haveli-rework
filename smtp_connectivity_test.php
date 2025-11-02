<?php
header('Content-Type: application/json');

function probe($host, $port, $timeout = 5) {
    $start = microtime(true);
    $errno = 0; $errstr = '';
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    $latency = round((microtime(true) - $start) * 1000);
    if ($fp) { fclose($fp); }
    return [
        'host' => $host,
        'port' => $port,
        'ok' => (bool)$fp,
        'latency_ms' => $latency,
        'errno' => $errno,
        'error' => $errstr
    ];
}

$targets = [
    ['smtppro.zoho.eu', 465],
    ['smtppro.zoho.eu', 587],
    ['smtp.zoho.eu', 587],
    // Local relays
    ['mail.haveli.co.uk', 465],
    ['mail.haveli.co.uk', 587],
    ['localhost', 25]
];

$results = array_map(fn($t) => probe($t[0], $t[1]), $targets);

echo json_encode([
    'success' => true,
    'server' => [
        'php_version' => PHP_VERSION,
        'host' => gethostname(),
    ],
    'probes' => $results
]);
?>
