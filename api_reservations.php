<?php
/**
 * Reservations API
 * Handles reservation-related operations
 */

header('Content-Type: application/json');

// Allow requests from your domain
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db_config.php';

try {
    $pdo = getDBConnection();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            // Create new reservation
            $customer_name = $_POST['customer_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $reservation_date = $_POST['reservation_date'] ?? '';
            $reservation_time = $_POST['reservation_time'] ?? '';
            $num_guests = $_POST['num_guests'] ?? 1;
            $special_requests = $_POST['special_requests'] ?? '';
            
            if (empty($customer_name) || empty($email) || empty($reservation_date) || empty($reservation_time)) {
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO reservations 
                (customer_name, email, phone, reservation_date, reservation_time, num_guests, special_requests, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            if ($stmt->execute([$customer_name, $email, $phone, $reservation_date, $reservation_time, $num_guests, $special_requests])) {
                $reservation_id = $pdo->lastInsertId();
                
                // Create email queue for request received
                require_once __DIR__ . '/email_templates.php';
                $request_queue = [
                    'customer' => [
                        'to' => $email,
                        'to_name' => $customer_name,
                        'subject' => '📥 Reservation Request Received - Haveli Restaurant',
                        'template' => 'request',
                        'data' => [
                            'customer_name' => $customer_name,
                            'reservation_date' => $reservation_date,
                            'reservation_time' => $reservation_time,
                            'num_guests' => $num_guests
                        ],
                        'retry_count' => 0,
                        'max_retries' => 3,
                        'created_at' => date('Y-m-d H:i:s'),
                        'priority' => 'normal'
                    ]
                ];
                
                $queue_file = __DIR__ . '/email_queue_request_' . $reservation_id . '_' . time() . '.json';
                file_put_contents($queue_file, json_encode($request_queue, JSON_PRETTY_PRINT));
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Reservation submitted successfully',
                    'reservation_id' => $reservation_id
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create reservation']);
            }
            break;
            
        case 'list':
            // Get all reservations
            $stmt = $pdo->query("SELECT * FROM reservations ORDER BY created_at DESC");
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'reservations' => $reservations]);
            break;
            
        case 'get':
            // Get single reservation
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
            $stmt->execute([$id]);
            $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($reservation) {
                echo json_encode(['success' => true, 'reservation' => $reservation]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Reservation not found']);
            }
            break;
            
        case 'update':
            // Update reservation
            $id = $_POST['id'] ?? 0;
            $status = $_POST['status'] ?? '';
            
            if (!$id || !$status) {
                echo json_encode(['success' => false, 'message' => 'Missing ID or status']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
            if ($stmt->execute([$status, $id])) {
                echo json_encode(['success' => true, 'message' => 'Reservation updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed']);
            }
            break;
            
        case 'delete':
            // Delete reservation
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Missing reservation ID']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
            if ($stmt->execute([$id])) {
                echo json_encode(['success' => true, 'message' => 'Reservation deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Delete failed']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>