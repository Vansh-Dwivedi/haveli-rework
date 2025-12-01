<?php
/**
 * Admin Dashboard API
 * Provides data for the unified admin dashboard
 */

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

// SECURE AUTHENTICATION CHECK
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check session timeout (30 minutes)
$session_timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_destroy();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // Use centralized DB configuration if a test harness hasn't provided getDBConnection()
    if (!function_exists('getDBConnection')) {
        require_once __DIR__ . '/db_config.php';
    }
    require_once __DIR__ . '/queue_helpers.php';
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'get_stats':
            // Get dashboard statistics
            $stats = [];
            
            // Total reservations
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM reservations');
            $stats['total_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Pending reservations
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM reservations WHERE status = "pending"');
            $stats['pending_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Total refused reservations
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM reservations WHERE status = "refused"');
            $stats['total_refused'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Total orders
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM orders');
            $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Email queue count
            $queue_files = glob(__DIR__ . '/email_queue_*.json');
            $stats['email_queue'] = count($queue_files);
            
            // Emails sent today (estimate based on successful processing)
            $stats['emails_sent'] = max(0, 10 - $stats['email_queue']); // Simple estimate
            
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        case 'get_reservations':
            // Get pagination parameters
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
            $offset = ($page - 1) * $limit;

            // Optional filters: q (name/email/phone), reservation_date, date range, status, guests
            $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
            $reservation_date = isset($_GET['reservation_date']) ? trim((string)$_GET['reservation_date']) : '';
            $date_from = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : '';
            $date_to = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : '';
            $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
            $guests_min = isset($_GET['guests_min']) ? (int)$_GET['guests_min'] : 0;
            $guests_max = isset($_GET['guests_max']) ? (int)$_GET['guests_max'] : 0;

            // Build WHERE clause dynamically and safely
            $whereParts = [];
            $params = [];
            if ($q !== '') {
                $whereParts[] = '(customer_name LIKE :q OR email LIKE :q OR phone_number LIKE :q)';
                $params[':q'] = "%$q%";
            }
            if ($reservation_date !== '') {
                // Exact date match
                $whereParts[] = 'reservation_date = :rdate';
                $params[':rdate'] = $reservation_date;
            }

            if ($date_from !== '' && $date_to !== '') {
                $whereParts[] = 'reservation_date BETWEEN :date_from AND :date_to';
                $params[':date_from'] = $date_from;
                $params[':date_to'] = $date_to;
            } elseif ($date_from !== '') {
                $whereParts[] = 'reservation_date >= :date_from';
                $params[':date_from'] = $date_from;
            } elseif ($date_to !== '') {
                $whereParts[] = 'reservation_date <= :date_to';
                $params[':date_to'] = $date_to;
            }

            if ($status !== '' && strtolower($status) !== 'all') {
                $whereParts[] = 'LOWER(status) = :status';
                $params[':status'] = strtolower($status);
            }

            if ($guests_min > 0) {
                $whereParts[] = 'num_guests >= :gmin';
                $params[':gmin'] = $guests_min;
            }
            if ($guests_max > 0) {
                $whereParts[] = 'num_guests <= :gmax';
                $params[':gmax'] = $guests_max;
            }

            $whereSql = '';
            if (count($whereParts) > 0) {
                $whereSql = ' WHERE ' . implode(' AND ', $whereParts);
            }

            // Get total count for pagination with filters
            $countSql = 'SELECT COUNT(*) as total FROM reservations' . $whereSql;
            $countStmt = $pdo->prepare($countSql);
            foreach ($params as $k => $v) {
                $countStmt->bindValue($k, $v);
            }
            $countStmt->execute();
            $total_count = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Calculate pagination info
            $total_pages = $total_count > 0 ? (int)ceil($total_count / $limit) : 1;
            $has_next = $page < $total_pages;
            $has_prev = $page > 1;

            // Get paginated results with filters
            $sql = "SELECT * FROM reservations" . $whereSql . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Build pagination response
            $pagination = [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $total_count,
                'total_pages' => $total_pages,
                'has_next' => $has_next,
                'has_prev' => $has_prev,
                'next_page' => $has_next ? $page + 1 : null,
                'prev_page' => $has_prev ? $page - 1 : null,
                'start' => $total_count === 0 ? 0 : $offset + 1,
                'end' => $total_count === 0 ? 0 : min($offset + $limit, $total_count)
            ];

            echo json_encode([
                'success' => true,
                'reservations' => $reservations,
                'pagination' => $pagination
            ]);
            break;
            
        case 'get_orders':
            $stmt = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 20');
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add items summary for each order
            foreach ($orders as &$order) {
                $order['items_summary'] = 'Order #' . $order['id']; // Simplified
            }
            
            echo json_encode(['success' => true, 'orders' => $orders]);
            break;
            
        case 'get_email_status':
            // Get email configuration from db_config or create default
            $emailConfig = [
                'smtp' => [
                    'host' => 'smtppro.zoho.eu',
                    'port' => 465,
                    'username' => 'info@haveli.co.uk',
                    'encryption' => 'ssl'
                ]
            ];
            
            $queue_files = glob(__DIR__ . '/email_queue_*.json');
            
            $email_status = [
                'email_config' => [
                    'host' => $emailConfig['smtp']['host'],
                    'port' => $emailConfig['smtp']['port'],
                    'from' => $emailConfig['smtp']['username']
                ],
                'queue_count' => count($queue_files),
                'stats' => [
                    'emails_today' => 0, // Will be calculated from actual logs when implemented
                    'success_rate' => 0  // Will be calculated from actual email delivery data
                ]
            ];
            
            echo json_encode(['success' => true] + $email_status);
            break;
            
        case 'confirm_reservation':
            $reservation_id = $_POST['reservation_id'] ?? 0;
            
            if (!$reservation_id) {
                echo json_encode(['success' => false, 'message' => 'No reservation ID provided']);
                break;
            }
            
            // Update reservation status
            $update_stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ?");
            $result = $update_stmt->execute([$reservation_id]);
            $affected_rows = $update_stmt->rowCount();
            
            if ($affected_rows > 0) {
                // Get reservation details for email
                $res_stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
                $res_stmt->execute([$reservation_id]);
                $reservation = $res_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($reservation) {
                    // Create email queue with improved reliability
                    require_once __DIR__ . '/email_templates.php';
                    
                    $confirmation_email_queue = [
                        'customer' => [
                            'to' => $reservation['email'],
                            'to_name' => $reservation['customer_name'],
                            'subject' => '🎉 Congratulations! Your Haveli Booking is Confirmed',
                            'template' => 'confirmation',
                            'data' => [
                                'customer_name' => $reservation['customer_name'],
                                'reservation_date' => $reservation['reservation_date'],
                                'reservation_time' => $reservation['reservation_time'],
                                'num_guests' => $reservation['num_guests']
                            ],
                            'retry_count' => 0,
                            'max_retries' => 3,
                            'created_at' => date('Y-m-d H:i:s'),
                            'priority' => 'high'
                        ]
                    ];
                    
                    // Create queue file with improved naming in root directory
                    $queue_file = __DIR__ . '/email_queue_confirm_' . $reservation_id . '_' . time() . '.json';
                    write_queue_file($queue_file, $confirmation_email_queue);
                    
                    // Trigger immediate processing with multiple methods for reliability
                    triggerEmailProcessing();
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Reservation confirmed! Confirmation email queued and will be sent within 30 seconds.',
                        'reservation_id' => $reservation_id
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Reservation not found after update']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No reservation found with that ID']);
            }
            break;

        case 'bulk_update_reservations':
            // Expect POST with reservation_ids (JSON array) and status
            $body_ids = $_POST['reservation_ids'] ?? '';
            $new_status = $_POST['status'] ?? '';

            if (!$body_ids || !$new_status) {
                echo json_encode(['success' => false, 'message' => 'Missing reservation_ids or status']);
                break;
            }

            $allowed = ['confirmed', 'refused'];
            if (!in_array(strtolower($new_status), $allowed, true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                break;
            }

            $ids = json_decode($body_ids, true);
            if (!is_array($ids) || count($ids) === 0) {
                echo json_encode(['success' => false, 'message' => 'No reservation ids provided']);
                break;
            }

            // Build placeholders
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "UPDATE reservations SET status = ? WHERE id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $params = array_merge([strtolower($new_status)], $ids);
            // Cleanup old undo logs (48 hours)
            try {
                $now = time();
                $files = glob(__DIR__ . '/bulk_update_log_*.json');
                foreach ($files as $f) {
                    if (is_file($f) && ($now - filemtime($f)) > 48 * 3600) {
                        @unlink($f);
                    }
                }
            } catch (Exception $e) {
                // ignore cleanup errors
            }

            try {
                // Fetch previous statuses and reservation details for logging and email queueing
                $selectSql = "SELECT id, status, email, customer_name, reservation_date, reservation_time, num_guests FROM reservations WHERE id IN ($placeholders)";
                $selectStmt = $pdo->prepare($selectSql);
                $selectStmt->execute($ids);
                $rows = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

                // Map id => old_status
                $previous = [];
                foreach ($rows as $r) {
                    $previous[] = ['id' => (int)$r['id'], 'old_status' => $r['status']];
                }

                // Perform update inside transaction
                $pdo->beginTransaction();
                $stmt->execute($params);
                $count = $stmt->rowCount();
                $pdo->commit();

                // Optional reason for bulk refusal (single reason applied to all)
                $bulk_reason = $_POST['reason'] ?? '';

                // Queue emails for each reservation according to new_status
                require_once __DIR__ . '/email_templates.php';
                foreach ($rows as $r) {
                    $reservation_id = (int)$r['id'];
                    $queue = [];
                    if (strtolower($new_status) === 'confirmed') {
                        $queue = [
                            'customer' => [
                                'to' => $r['email'],
                                'to_name' => $r['customer_name'],
                                'subject' => '🎉 Your Haveli reservation is confirmed',
                                'template' => 'confirmation',
                                'data' => [
                                    'customer_name' => $r['customer_name'],
                                    'reservation_date' => $r['reservation_date'],
                                    'reservation_time' => $r['reservation_time'],
                                    'num_guests' => $r['num_guests']
                                ],
                                'retry_count' => 0,
                                'max_retries' => 3,
                                'created_at' => date('Y-m-d H:i:s'),
                                'priority' => 'high'
                            ]
                        ];
                        $queue_file = __DIR__ . '/email_queue_confirm_bulk_' . $reservation_id . '_' . time() . '.json';
                        write_queue_file($queue_file, $queue);
                    } elseif (strtolower($new_status) === 'refused') {
                        $queue = [
                            'customer' => [
                                'to' => $r['email'],
                                'to_name' => $r['customer_name'],
                                'subject' => 'Update on your Haveli reservation',
                                'template' => 'rejection',
                                'data' => [
                                    'customer_name' => $r['customer_name'],
                                    'reservation_date' => $r['reservation_date'],
                                    'reservation_time' => $r['reservation_time'],
                                    'num_guests' => $r['num_guests'],
                                    'reason' => $bulk_reason ?: 'Reservation not available'
                                ],
                                'retry_count' => 0,
                                'max_retries' => 3,
                                'created_at' => date('Y-m-d H:i:s'),
                                'priority' => 'high'
                            ]
                        ];
                        $queue_file = __DIR__ . '/email_queue_reject_bulk_' . $reservation_id . '_' . time() . '.json';
                        write_queue_file($queue_file, $queue);
                    }
                }

                // Trigger email processing once after queueing
                triggerEmailProcessing();

                // Create an undo log file to allow reverting this bulk operation
                $log = [
                    'created_at' => date('Y-m-d H:i:s'),
                    'operation' => 'bulk_update',
                    'new_status' => strtolower($new_status),
                    'changes' => $previous
                ];
                $log_file = __DIR__ . '/bulk_update_log_' . time() . '_' . bin2hex(random_bytes(6)) . '.json';
                write_queue_file($log_file, $log);

                $undo_token = basename($log_file);

                echo json_encode(['success' => true, 'message' => "Updated $count reservations.", 'updated' => $count, 'undo_token' => $undo_token]);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Bulk update error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Database error during bulk update']);
            }
            break;

        case 'bulk_undo_update':
            // Undo a previous bulk update using the undo token (log filename)
            $token = $_POST['token'] ?? $_GET['token'] ?? '';
            if (!$token) {
                echo json_encode(['success' => false, 'message' => 'No undo token provided']);
                break;
            }

            // Validate token to avoid path traversal
            if (strpos($token, 'bulk_update_log_') !== 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                break;
            }

            $logPath = __DIR__ . '/' . $token;
            if (!file_exists($logPath)) {
                echo json_encode(['success' => false, 'message' => 'Undo token not found or expired']);
                break;
            }

            $content = json_decode(file_get_contents($logPath), true);
            if (!$content || !isset($content['changes'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid log file']);
                break;
            }

            $changes = $content['changes'];
            try {
                $pdo->beginTransaction();
                $updated = 0;
                foreach ($changes as $c) {
                    $stmt = $pdo->prepare('UPDATE reservations SET status = ? WHERE id = ?');
                    $stmt->execute([$c['old_status'], $c['id']]);
                    $updated += $stmt->rowCount();
                }
                $pdo->commit();
                // Remove the log file after successful undo
                @unlink($logPath);
                echo json_encode(['success' => true, 'message' => "Reverted $updated reservations.", 'reverted' => $updated]);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Bulk undo error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error reverting updates']);
            }
            break;

            case 'refuse_reservation':
                error_log("Refuse reservation request: " . json_encode($_POST));
                $reservation_id = $_POST['reservation_id'] ?? 0;
                $reason = $_POST['reason'] ?? '';
                if (!$reservation_id) {
                    error_log("No reservation ID provided in request");
                    echo json_encode(['success' => false, 'message' => 'No reservation ID provided']);
                    break;
                }
                error_log("Processing refusal for reservation ID: $reservation_id");
                
                // First verify the reservation exists and is in a refusable state
                $check_stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? AND status NOT IN ('refused', 'cancelled')");
                $check_stmt->execute([$reservation_id]);
                $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$existing) {
                    error_log("Reservation not found or already processed: ID = $reservation_id");
                    echo json_encode(['success' => false, 'message' => 'No active reservation found with that ID']);
                    break;
                }
                
                // Update reservation status to refused
                $update_stmt = $pdo->prepare("UPDATE reservations SET status = 'refused' WHERE id = ? AND status NOT IN ('refused', 'cancelled')");
                $update_stmt->execute([$reservation_id]);
                
                if ($update_stmt->rowCount() > 0) {
                    error_log("Successfully updated reservation status to Refused: ID = $reservation_id");
                    // Use the reservation details we already fetched
                    $reservation = $existing;
                    if ($reservation) {
                        // Save internal note if provided
                        if (!empty($_POST['internal_note'])) {
                            $note_stmt = $pdo->prepare("UPDATE reservations SET internal_note = ? WHERE id = ?");
                            $note_stmt->execute([$_POST['internal_note'], $reservation_id]);
                        }
                        
                        require_once __DIR__ . '/email_templates.php';
                        $html = getRejectionEmailTemplate(
                            $reservation['customer_name'],
                            $reservation['reservation_date'],
                            $reservation['reservation_time'],
                            $reservation['num_guests'],
                            $reason
                        );

                        $rejection_queue = [
                            'customer' => [
                                'to' => $reservation['email'],
                                'to_name' => $reservation['customer_name'],
                                'subject' => 'Update on your Haveli reservation',
                                'template' => 'rejection',
                                'data' => [
                                    'customer_name' => $reservation['customer_name'],
                                    'reservation_date' => $reservation['reservation_date'],
                                    'reservation_time' => $reservation['reservation_time'],
                                    'num_guests' => $reservation['num_guests'],
                                    'reason' => $reason
                                ],
                                'retry_count' => 0,
                                'max_retries' => 3,
                                'created_at' => date('Y-m-d H:i:s'),
                                'priority' => 'high'
                            ]
                        ];
                        $queue_file = __DIR__ . '/email_queue_reject_' . $reservation_id . '_' . time() . '.json';
                        file_put_contents($queue_file, json_encode($rejection_queue, JSON_PRETTY_PRINT));
                        triggerEmailProcessing();

                        echo json_encode(['success' => true, 'message' => 'Reservation refused and customer notified.', 'reservation_id' => $reservation_id]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Reservation not found after update']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'No reservation found with that ID']);
                }
                break;
            
        case 'get_analytics':
            $analytics = [];
            
            // Weekly reservations (last 7 days)
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM reservations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $analytics['weekly_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Monthly reservations (last 30 days)
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM reservations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $analytics['monthly_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Conversion rate calculation (confirmed vs total)
            $total_stmt = $pdo->query("SELECT COUNT(*) as count FROM reservations");
            $confirmed_stmt = $pdo->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'confirmed'");
            $total = $total_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $confirmed = $confirmed_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $analytics['conversion_rate'] = $total > 0 ? round(($confirmed / $total) * 100, 1) : 0;
            
            // Average rating from actual reservations (if you have rating column)
            try {
                $rating_stmt = $pdo->query("SELECT AVG(rating) as avg_rating FROM reservations WHERE rating IS NOT NULL AND rating > 0");
                $rating_result = $rating_stmt->fetch(PDO::FETCH_ASSOC);
                $analytics['avg_rating'] = $rating_result['avg_rating'] ? round($rating_result['avg_rating'], 1) : 0;
            } catch (Exception $e) {
                // Rating column might not exist yet
                $analytics['avg_rating'] = 0;
            }
            
            // Age groups analysis from actual customer data
            $age_groups = [];
            try {
                // Assuming you might have age or birth_date column
                $stmt = $pdo->query("SELECT 
                    CASE 
                        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 26 AND 35 THEN '26-35' 
                        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) BETWEEN 36 AND 50 THEN '36-50'
                        WHEN TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) > 50 THEN '51+'
                        ELSE 'Unknown'
                    END as age_group,
                    COUNT(*) as count
                    FROM reservations 
                    WHERE birth_date IS NOT NULL
                    GROUP BY 1");
                $age_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Age data not available, show empty array
                $age_groups = [];
            }
            $analytics['age_groups'] = $age_groups;
            
            // Top locations from actual reservation data
            $locations = [];
            try {
                $stmt = $pdo->query("SELECT location, COUNT(*) as count FROM reservations WHERE location IS NOT NULL GROUP BY location ORDER BY count DESC LIMIT 5");
                $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Location column might not exist
                $locations = [];
            }
            $analytics['locations'] = $locations;
            
            // Reservation trends (last 7 days) - REAL DATA
            $trends = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE DATE(created_at) = ?");
                $stmt->execute([$date]);
                $trends[] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            }
            $analytics['reservation_trends'] = $trends;
            
            // Peak hours analysis from actual reservation times
            $peak_hours = [];
            try {
                $stmt = $pdo->query("SELECT 
                    CONCAT(HOUR(reservation_time), ':00-', (HOUR(reservation_time) + 1), ':00') as time_slot,
                    COUNT(*) as reservations
                    FROM reservations 
                    WHERE reservation_time IS NOT NULL
                    GROUP BY HOUR(reservation_time)
                    ORDER BY HOUR(reservation_time)");
                $peak_hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Rename column for consistency
                foreach ($peak_hours as &$hour) {
                    $hour['time'] = $hour['time_slot'];
                    unset($hour['time_slot']);
                }
            } catch (Exception $e) {
                // Fallback if reservation_time column format is different
                $peak_hours = [];
            }
            $analytics['peak_hours'] = $peak_hours;
            
            echo json_encode(['success' => true, 'analytics' => $analytics]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    error_log("Dashboard API DB Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

/**
 * Trigger email processing with multiple methods for maximum reliability
 */
function triggerEmailProcessing() {
    // Method 1: Direct execution using the main email processor
    if (function_exists('exec')) {
        $php_path = PHP_BINARY;
        $script_path = __DIR__ . '/process_email_queue.php';
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("start /B \"\" \"$php_path\" \"$script_path\" > nul 2>&1");
        } else {
            exec("$php_path \"$script_path\" > /dev/null 2>&1 &");
        }
    }
    
    // Method 2: Create a trigger file
    $trigger_file = __DIR__ . '/email_trigger_' . time() . '.flag';
    file_put_contents($trigger_file, 'process_now');
    
    // Method 3: Log for manual processing if needed
    error_log("[EMAIL QUEUE] New confirmation email queued for immediate processing");
}
?>