<?php
/**
 * Automatic Email Queue Processor
 * - Scans local JSON queue files and sends emails via PHPMailer
 * - Safe to call via AJAX from the dashboard
 */

header('Content-Type: application/json');

// Make sure web requests don't hang forever
ignore_user_abort(true);
@set_time_limit(30);

// SMTP configuration candidates (try in order)
$SMTP_CANDIDATES = [
    ['host' => 'smtppro.zoho.eu', 'port' => 465, 'secure' => 'ssl'],   // Primary (SSL)
    ['host' => 'smtppro.zoho.eu', 'port' => 587, 'secure' => 'tls'],   // Fallback (STARTTLS)
    ['host' => 'smtp.zoho.eu',    'port' => 587, 'secure' => 'tls'],   // Secondary hostname
];
$SMTP_USER = 'info@haveli.co.uk';

// Retrieve SMTP password securely (env first, then local file)
$SMTP_PASS = getenv('HAVELI_SMTP_PASS');
if (!$SMTP_PASS && file_exists(__DIR__ . '/smtp_password.php')) {
    // Optional local file returning $SMTP_PASSWORD
    include __DIR__ . '/smtp_password.php';
    if (isset($SMTP_PASSWORD) && $SMTP_PASSWORD) {
        $SMTP_PASS = $SMTP_PASSWORD;
    }
}

// Include dependencies
require_once __DIR__ . '/email_templates.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function load_queue_files($pattern) {
    $files = glob($pattern);
    return $files ? $files : [];
}

function can_connect($host, $port, $timeout = 5) {
    $errno = 0; $errstr = '';
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($fp) { fclose($fp); return true; }
    return false;
}

// Detailed connectivity probe used for debugging
function probe_connect($host, $port, $timeout = 5) {
    $start = microtime(true);
    $errno = 0; $errstr = '';
    $ok = false;
    $latency = null;
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if ($fp) {
        $ok = true;
        $latency = round((microtime(true) - $start) * 1000);
        fclose($fp);
    } else {
        $latency = round((microtime(true) - $start) * 1000);
    }
    return [
        'host' => $host,
        'port' => $port,
        'ok' => $ok,
        'latency_ms' => $latency,
        'errno' => $errno,
        'error' => $errstr
    ];
}

// Detect if an HTTPS email provider is configured (bypasses SMTP blocks)
function detect_http_provider() {
    $sendgridKey = getenv('SENDGRID_API_KEY');
    if ($sendgridKey) {
        return ['provider' => 'sendgrid', 'api_key' => $sendgridKey];
    }
    $mailgunKey = getenv('MAILGUN_API_KEY');
    $mailgunDomain = getenv('MAILGUN_DOMAIN');
    if ($mailgunKey && $mailgunDomain) {
        // Prefer EU endpoint if domain ends with a European TLD; default to api.mailgun.net
        $endpoint = getenv('MAILGUN_API_BASE') ?: 'https://api.mailgun.net';
        return ['provider' => 'mailgun', 'api_key' => $mailgunKey, 'domain' => $mailgunDomain, 'base' => $endpoint];
    }
    // Local PHP file fallback (preferred on cPanel where env vars are hard to set)
    $configFile = __DIR__ . '/provider_config.php';
    if (file_exists($configFile)) {
        /** @noinspection PhpIncludeInspection */
        include $configFile; // should define $EMAIL_API
        if (isset($EMAIL_API) && is_array($EMAIL_API) && isset($EMAIL_API['provider'])) {
            return $EMAIL_API;
        }
    }
    return null;
}

function send_via_sendgrid($apiKey, $from, $to, $to_name, $subject, $html) {
    $payload = [
        'personalizations' => [[
            'to' => [[ 'email' => $to, 'name' => $to_name ]]
        ]],
        'from' => [ 'email' => $from, 'name' => 'Haveli Restaurant' ],
        'subject' => $subject,
        'content' => [[ 'type' => 'text/html', 'value' => $html ]]
    ];
    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return [ 'ok' => ($http >= 200 && $http < 300), 'http' => $http, 'error' => $err, 'response' => $resp ];
}

function send_via_mailgun($base, $domain, $apiKey, $from, $to, $to_name, $subject, $html) {
    $url = rtrim($base, '/') . '/v3/' . $domain . '/messages';
    $post = [
        'from' => 'Haveli Restaurant <' . $from . '>',
        'to' => $to_name ? ($to_name . ' <' . $to . '>') : $to,
        'subject' => $subject,
        'html' => $html
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_USERPWD => 'api:' . $apiKey,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return [ 'ok' => ($http >= 200 && $http < 300), 'http' => $http, 'error' => $err, 'response' => $resp ];
}

try {
    // Queue files live in project root and start with email_queue_
    $queue_files = load_queue_files(__DIR__ . '/email_queue_*.json');

    if (empty($queue_files)) {
        echo json_encode(['success' => true, 'message' => 'No emails in queue', 'processed' => 0, 'remaining' => 0]);
        exit;
    }

    if (!$SMTP_PASS) {
        echo json_encode([
            'success' => false,
            'message' => 'SMTP password not configured. Set HAVELI_SMTP_PASS env or create smtp_password.php with $SMTP_PASSWORD.',
            'processed' => 0,
            'remaining' => count($queue_files)
        ]);
        exit;
    }

    // Pick the first reachable SMTP endpoint to avoid long timeouts on blocked ports (common on shared hosting)
    $chosen = null;
    $attempts = [];
    foreach ($SMTP_CANDIDATES as $cand) {
        $probe = probe_connect($cand['host'], $cand['port'], 5);
        // Preserve which security we intended for each host/port
        $probe['secure'] = $cand['secure'];
        $attempts[] = $probe;
        if ($probe['ok']) { $chosen = $cand; break; }
    }
    $useHttpProvider = false;
    $useLocalMTA = false; // fallback that uses server's mail()/sendmail
    $httpProvider = null;
    if (!$chosen) {
        // Try HTTPS provider fallback (Mailgun/SendGrid) if configured
        $detected = detect_http_provider();
        if ($detected) {
            $useHttpProvider = true;
            $httpProvider = $detected;
        } else {
            // Last resort: try local MTA (PHP mail or sendmail) - works on many cPanel hosts
            $useLocalMTA = true;
        }
    }

    $processed = 0;
    $failed = 0;
    $errors = [];
    $max_per_run = 5; // safety cap per request to keep it responsive
    $sent_this_run = 0;

    foreach ($queue_files as $file) {
        if ($sent_this_run >= $max_per_run) { break; }
        $payload = json_decode(file_get_contents($file), true);
        if (!$payload || !isset($payload['customer'])) {
            $errors[] = basename($file) . ': invalid queue format';
            $failed++;
            continue;
        }

        $job = $payload['customer'];
        $to = $job['to'] ?? '';
        $to_name = $job['to_name'] ?? '';
        $subject = $job['subject'] ?? 'Haveli Restaurant';
        $template = $job['template'] ?? '';
        $data = $job['data'] ?? [];

        if (!$to) {
            $errors[] = basename($file) . ': missing recipient address';
            $failed++;
            continue;
        }

        // Build HTML body from template
        $htmlBody = '';
        if ($template === 'confirmation') {
            $htmlBody = getConfirmationEmailTemplate(
                $data['customer_name'] ?? 'Guest',
                $data['reservation_date'] ?? '',
                $data['reservation_time'] ?? '',
                $data['num_guests'] ?? ''
            );
        } elseif ($template === 'request') {
            $htmlBody = getRequestReceivedTemplate(
                $data['customer_name'] ?? 'Guest',
                $data['reservation_date'] ?? '',
                $data['reservation_time'] ?? '',
                $data['num_guests'] ?? ''
            );
        } elseif ($template === 'rejection') {
            $htmlBody = getRejectionEmailTemplate(
                $data['customer_name'] ?? 'Guest',
                $data['reservation_date'] ?? '',
                $data['reservation_time'] ?? '',
                $data['num_guests'] ?? '',
                $data['reason'] ?? ''
            );
        } else {
            // Unknown template: send plain message
            $htmlBody = '<p>Thank you from Haveli Restaurant.</p>';
        }

        if (!$useHttpProvider && !$useLocalMTA) {
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = $chosen['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $SMTP_USER;
                $mail->Password = $SMTP_PASS;
                $mail->SMTPSecure = $chosen['secure'];
                $mail->Port = $chosen['port'];
                $mail->CharSet = 'UTF-8';
                $mail->Timeout = 15; // seconds
                $mail->SMTPKeepAlive = false;
                // Be lenient on SSL if host injects proxies/cert issues
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ]
                ];

                // Recipients
                $mail->setFrom($SMTP_USER, 'Haveli Restaurant');
                $mail->addAddress($to, $to_name);

                // Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;

                $mail->send();

                // On success, delete queue file
                @unlink($file);
                $processed++;
                $sent_this_run++;
            } catch (Exception $e) {
                // Update retry count in queue file
                $payload['customer']['retry_count'] = isset($payload['customer']['retry_count']) ? ((int)$payload['customer']['retry_count'] + 1) : 1;
                file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT));
                $failed++;
                $errors[] = basename($file) . ': ' . $e->getMessage();
            }
        } elseif ($useHttpProvider) {
            // HTTP provider path
            $resp = ['ok' => false, 'http' => 0, 'error' => ''];
            if (!function_exists('curl_init')) {
                // cURL is required for HTTP providers
                $payload['customer']['retry_count'] = isset($payload['customer']['retry_count']) ? ((int)$payload['customer']['retry_count'] + 1) : 1;
                file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT));
                $failed++;
                $errors[] = basename($file) . ': HTTP provider send failed (cURL extension missing)';
                continue;
            }
            if ($httpProvider['provider'] === 'sendgrid') {
                $resp = send_via_sendgrid($httpProvider['api_key'], $SMTP_USER, $to, $to_name, $subject, $htmlBody);
            } elseif ($httpProvider['provider'] === 'mailgun') {
                $resp = send_via_mailgun($httpProvider['base'], $httpProvider['domain'], $httpProvider['api_key'], $SMTP_USER, $to, $to_name, $subject, $htmlBody);
            }
            if ($resp['ok']) {
                @unlink($file);
                $processed++;
                $sent_this_run++;
            } else {
                $payload['customer']['retry_count'] = isset($payload['customer']['retry_count']) ? ((int)$payload['customer']['retry_count'] + 1) : 1;
                file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT));
                $failed++;
                $errors[] = basename($file) . ': HTTP provider send failed (provider=' . $httpProvider['provider'] . ', http=' . ($resp['http'] ?? 0) . ', error=' . ($resp['error'] ?? 'n/a') . ')';
            }
        } else {
            // Local MTA path (PHP mail or sendmail)
            $mail = new PHPMailer(true);
            try {
                // Prefer sendmail if available; otherwise fall back to mail()
                if (is_executable('/usr/sbin/sendmail')) {
                    $mail->isSendmail();
                    $mail->Sendmail = '/usr/sbin/sendmail -t -i';
                } else {
                    $mail->isMail();
                }
                $mail->CharSet = 'UTF-8';
                $mail->setFrom($SMTP_USER, 'Haveli Restaurant');
                $mail->Sender = $SMTP_USER; // envelope sender for better DMARC alignment
                $mail->addReplyTo($SMTP_USER, 'Haveli Restaurant');
                $mail->addAddress($to, $to_name);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;

                $mail->send();

                @unlink($file);
                $processed++;
                $sent_this_run++;
            } catch (Exception $e) {
                $payload['customer']['retry_count'] = isset($payload['customer']['retry_count']) ? ((int)$payload['customer']['retry_count'] + 1) : 1;
                file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT));
                $failed++;
                $errors[] = basename($file) . ': Local MTA send failed (' . $e->getMessage() . ')';
            }
        }
    }

    $remaining = count(load_queue_files(__DIR__ . '/email_queue_*.json'));
    $via = 'via unknown';
    if (!$useHttpProvider && !$useLocalMTA) {
        $via = isset($chosen) ? "via {$chosen['host']}:{$chosen['port']} {$chosen['secure']}" : 'via SMTP (unknown)';
    } elseif ($useHttpProvider) {
        $via = 'via ' . strtoupper($httpProvider['provider']) . ' HTTP API';
    } else {
        $via = 'via LOCAL MTA (PHP mail/sendmail)';
    }

    echo json_encode([
        'success' => $failed === 0,
        'message' => "Processed $processed, Failed $failed ($via)",
        'processed' => $processed,
        'failed' => $failed,
        'remaining' => $remaining,
        'errors' => $errors,
        'from' => $SMTP_USER,
        'used' => (!$useHttpProvider && !$useLocalMTA && isset($chosen))
            ? ['host' => $chosen['host'], 'port' => $chosen['port'], 'secure' => $chosen['secure']]
            : ($useHttpProvider ? ['provider' => $httpProvider['provider']] : ['provider' => 'local-mta']),
        'attempts' => isset($attempts) ? $attempts : []
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing emails: ' . $e->getMessage()
    ]);
}
?>