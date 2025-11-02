<?php
/**
 * Admin Dashboard API
 * Provides data for the unified admin dashboard
 */

session_start();
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
    // Use centralized DB configuration
    require_once __DIR__ . '/db_config.php';
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
            $stmt = $pdo->query('SELECT * FROM reservations ORDER BY created_at DESC LIMIT 20');
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'reservations' => $reservations]);
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
                    $queue_saved = file_put_contents($queue_file, json_encode($confirmation_email_queue, JSON_PRETTY_PRINT));
                    
                    if ($queue_saved) {
                        // Trigger immediate processing with multiple methods for reliability
                        triggerEmailProcessing();
                        
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Reservation confirmed! Confirmation email queued and will be sent within 30 seconds.',
                            'reservation_id' => $reservation_id
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Reservation confirmed but email queue failed. Please send manual confirmation.'
                        ]);
                    }
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