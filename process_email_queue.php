<?php
/**
 * Automatic Email Queue Processor
 * - Scans local JSON queue files and sends emails via PHPMailer
 * - Safe to call via AJAX from the dashboard
 */

ob_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

register_shutdown_function(function() {
	$error = error_get_last();
	if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
		if (ob_get_length()) ob_clean();
		echo json_encode([
			'success' => false,
			'message' => 'Fatal Error: ' . $error['message'],
			'file' => basename($error['file']),
			'line' => $error['line']
		]);
	}
});

ignore_user_abort(true);
@set_time_limit(60);

$SMTP_CANDIDATES = [
    ['host' => 'localhost', 'port' => 25, 'secure' => 'none', 'auth' => false],
    ['host' => 'smtp.gmail.com', 'port' => 587, 'secure' => 'tls'],
    ['host' => 'smtp.gmail.com', 'port' => 465, 'secure' => 'ssl'],
];
$SMTP_USER = 'sloughhaveli@gmail.com';

$SMTP_PASS = getenv('HAVELI_SMTP_PASS');
if (!$SMTP_PASS && file_exists(__DIR__ . '/smtp_password.php')) {
	include __DIR__ . '/smtp_password.php';
	if (isset($SMTP_PASSWORD) && $SMTP_PASSWORD) {
		$SMTP_PASS = $SMTP_PASSWORD;
	}
}

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

function detect_http_provider() {
	$sendgridKey = getenv('SENDGRID_API_KEY');
	if ($sendgridKey) {
		return ['provider' => 'sendgrid', 'api_key' => $sendgridKey];
	}
	$mailgunKey = getenv('MAILGUN_API_KEY');
	$mailgunDomain = getenv('MAILGUN_DOMAIN');
	if ($mailgunKey && $mailgunDomain) {
		$endpoint = getenv('MAILGUN_API_BASE') ?: 'https://api.mailgun.net';
		return ['provider' => 'mailgun', 'api_key' => $mailgunKey, 'domain' => $mailgunDomain, 'base' => $endpoint];
	}
	$configFile = __DIR__ . '/provider_config.php';
	if (file_exists($configFile)) {
		include $configFile;
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
	$queue_files = load_queue_files(__DIR__ . '/email_queue_*.json');

	if (empty($queue_files)) {
		echo json_encode(['success' => true, 'message' => 'No emails in queue', 'processed' => 0, 'remaining' => 0]);
		exit;
	}

	// SMTP Password check removed to allow no-auth localhost


	$chosen = null;
	$attempts = [];
	foreach ($SMTP_CANDIDATES as $cand) {
		$probe = probe_connect($cand['host'], $cand['port'], 5);
		$probe['secure'] = $cand['secure'];
		$attempts[] = $probe;
		if ($probe['ok']) { $chosen = $cand; break; }
	}
	$useHttpProvider = false;
	$useLocalMTA = false;
	$httpProvider = null;
	if (!$chosen) {
		$detected = detect_http_provider();
		if ($detected) {
			$useHttpProvider = true;
			$httpProvider = $detected;
		} else {
			$useLocalMTA = true;
		}
	}

	$processed = 0;
	$failed = 0;
	$errors = [];
	$max_per_run = 5;
	$sent_this_run = 0;
	$last_sent_from = $SMTP_USER;

    foreach ($queue_files as $file) {
        if ($sent_this_run >= $max_per_run) { break; }
        
        // Retry reading file up to 3 times if empty (race condition fix)
        $rawPayload = false;
        for ($i = 0; $i < 3; $i++) {
            $rawPayload = file_get_contents($file);
            if ($rawPayload !== false && strlen(trim($rawPayload)) > 0) {
                break;
            }
            usleep(200000); // Wait 200ms
        }

        $payload = json_decode($rawPayload, true);
		if (!$payload || !isset($payload['customer'])) {
			$jsonError = json_last_error_msg();
			$preview = $rawPayload !== false ? substr(trim($rawPayload), 0, 120) : 'unreadable';
			$errors[] = basename($file) . ': invalid queue format (' . $jsonError . ') preview=' . $preview;
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
		} elseif ($template === 'admin_notification') {
			$htmlBody = getAdminNotificationTemplate(
				$data['customer_name'] ?? 'Guest',
				$data['customer_email'] ?? '',
				$data['customer_phone'] ?? '',
				$data['reservation_date'] ?? '',
				$data['reservation_time'] ?? '',
				$data['num_guests'] ?? '',
				$data['day_of_week'] ?? '',
				$data['reservation_id'] ?? ''
			);
		} else {
			$htmlBody = '<p>Thank you from Haveli Restaurant.</p>';
		}

		if (!$useHttpProvider && !$useLocalMTA) {
			$mail = new PHPMailer(true);
			try {
				$mail->isSMTP();
				$mail->Host = $chosen['host'];
				
				$useAuth = isset($chosen['auth']) ? $chosen['auth'] : true;
				$mail->SMTPAuth = $useAuth;
				if ($useAuth) {
					$mail->Username = $SMTP_USER;
					$mail->Password = $SMTP_PASS;
				}
				$mail->Port = $chosen['port'];

				if (($chosen['secure'] ?? 'tls') === 'none') {
					$mail->SMTPSecure = '';
					$mail->SMTPAutoTLS = false;
				} else {
					$mail->SMTPSecure = $chosen['secure'];
					$mail->SMTPAutoTLS = true;
				}
				$mail->CharSet = 'UTF-8';
				$mail->Timeout = 15;
				$mail->SMTPKeepAlive = false;
				$mail->SMTPOptions = [
					'ssl' => [
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true,
					]
				];

				// GoDaddy localhost relay requires a domain-based sender, not Gmail
				$fromEmail = $SMTP_USER;
				if ($chosen['host'] === 'localhost') {
					$fromEmail = 'info@haveli.co.uk'; 
				}
				$last_sent_from = $fromEmail;

				$mail->setFrom($fromEmail, 'Haveli Restaurant');
				$mail->addReplyTo($SMTP_USER, 'Haveli Restaurant');
				$mail->Sender = $fromEmail; // Ensure Return-Path matches From for SPF

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
				$errors[] = basename($file) . ': ' . $e->getMessage();
			}
		} elseif ($useHttpProvider) {
			$resp = ['ok' => false, 'http' => 0, 'error' => ''];
			if (!function_exists('curl_init')) {
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
			$mail = new PHPMailer(true);
			try {
				if (is_executable('/usr/sbin/sendmail')) {
					$mail->isSendmail();
					$mail->Sendmail = '/usr/sbin/sendmail -t -i';
				} else {
					$mail->isMail();
				}
				$mail->CharSet = 'UTF-8';
				
				// Use domain email for local MTA as well
				$fromEmail = 'info@haveli.co.uk';
				$last_sent_from = $fromEmail;
				
				$mail->setFrom($fromEmail, 'Haveli Restaurant');
				$mail->Sender = $fromEmail;
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
	if (ob_get_length()) ob_clean();

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
		'sent_as' => $last_sent_from,
		'used' => (!$useHttpProvider && !$useLocalMTA && isset($chosen))
			? ['host' => $chosen['host'], 'port' => $chosen['port'], 'secure' => $chosen['secure']]
			: ($useHttpProvider ? ['provider' => $httpProvider['provider']] : ['provider' => 'local-mta']),
		'attempts' => isset($attempts) ? $attempts : []
	]);

} catch (Exception $e) {
	if (ob_get_length()) ob_clean();
	echo json_encode([
		'success' => false,
		'message' => 'Error processing emails: ' . $e->getMessage()
	]);
}
?>
