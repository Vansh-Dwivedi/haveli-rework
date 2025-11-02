<?php
/**
 * Sample Data Population Script
 * Creates realistic dynamic data for analytics demonstration
 */

require_once 'db_config.php';

try {
    $pdo = getDBConnection();
    
    echo "🚀 Starting dynamic data population...\n";
    
    // First, let's ensure the reservations table has the necessary columns
    echo "📊 Checking/creating table structure...\n";
    
    // Create or update reservations table with analytics-friendly columns
    $createReservationsTable = "
    CREATE TABLE IF NOT EXISTS reservations (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        reservation_date DATE NOT NULL,
        reservation_time TIME NOT NULL,
        num_guests INT NOT NULL DEFAULT 1,
        special_requests TEXT,
        status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        rating DECIMAL(2,1) DEFAULT NULL,
        age INT DEFAULT NULL,
        location VARCHAR(100) DEFAULT NULL,
        source VARCHAR(50) DEFAULT 'website'
    )";
    
    $pdo->exec($createReservationsTable);
    
    // Create orders table if it doesn't exist
    $createOrdersTable = "
    CREATE TABLE IF NOT EXISTS orders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        items TEXT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($createOrdersTable);
    
    echo "✅ Tables created/verified\n";
    
    // Clear existing sample data to start fresh
    $pdo->exec("DELETE FROM reservations WHERE customer_name LIKE 'Sample%' OR email LIKE '%@example.com'");
    $pdo->exec("DELETE FROM orders WHERE customer_name LIKE 'Sample%' OR email LIKE '%@example.com'");
    
    echo "🧹 Cleaned existing sample data\n";
    
    // Sample customer data for realistic variety
    $customers = [
        ['name' => 'Sarah Johnson', 'email' => 'sarah.j@example.com', 'phone' => '07123456789', 'age' => 28, 'location' => 'Downtown'],
        ['name' => 'Michael Chen', 'email' => 'mchen@example.com', 'phone' => '07234567890', 'age' => 35, 'location' => 'Suburbs'],
        ['name' => 'Emily Davis', 'email' => 'emily.davis@example.com', 'phone' => '07345678901', 'age' => 42, 'location' => 'Business District'],
        ['name' => 'James Wilson', 'email' => 'j.wilson@example.com', 'phone' => '07456789012', 'age' => 29, 'location' => 'Tourist Area'],
        ['name' => 'Lisa Rodriguez', 'email' => 'lisa.r@example.com', 'phone' => '07567890123', 'age' => 38, 'location' => 'Downtown'],
        ['name' => 'David Thompson', 'email' => 'd.thompson@example.com', 'phone' => '07678901234', 'age' => 55, 'location' => 'Suburbs'],
        ['name' => 'Anna Patel', 'email' => 'anna.patel@example.com', 'phone' => '07789012345', 'age' => 24, 'location' => 'University Area'],
        ['name' => 'Robert Brown', 'email' => 'rob.brown@example.com', 'phone' => '07890123456', 'age' => 48, 'location' => 'Business District'],
        ['name' => 'Jennifer Lee', 'email' => 'jen.lee@example.com', 'phone' => '07901234567', 'age' => 31, 'location' => 'Tourist Area'],
        ['name' => 'Mark Garcia', 'email' => 'mark.g@example.com', 'phone' => '07012345678', 'age' => 45, 'location' => 'Downtown']
    ];
    
    // Time slots for realistic reservation distribution
    $timeSlots = [
        '11:30:00', '12:00:00', '12:30:00', '13:00:00', '13:30:00',
        '18:00:00', '18:30:00', '19:00:00', '19:30:00', '20:00:00', '20:30:00', '21:00:00'
    ];
    
    $statuses = ['pending', 'confirmed', 'completed'];
    $ratings = [4.0, 4.5, 5.0, 3.5, 4.2, 4.8, 4.1, 4.6, 4.9, 5.0];
    
    echo "📅 Creating dynamic reservations for the last 30 days...\n";
    
    $reservationCount = 0;
    
    // Create reservations over the last 30 days with realistic patterns
    for ($day = 29; $day >= 0; $day--) {
        $date = date('Y-m-d', strtotime("-$day days"));
        
        // More reservations on weekends and recent days
        $isWeekend = in_array(date('w', strtotime($date)), [0, 6]);
        $isRecent = $day < 7;
        
        $dailyReservations = $isWeekend ? rand(8, 15) : rand(3, 10);
        if ($isRecent) $dailyReservations += rand(2, 5);
        
        for ($i = 0; $i < $dailyReservations; $i++) {
            $customer = $customers[array_rand($customers)];
            $timeSlot = $timeSlots[array_rand($timeSlots)];
            $status = $statuses[array_rand($statuses)];
            $rating = ($status === 'completed') ? $ratings[array_rand($ratings)] : null;
            $guests = rand(1, 8);
            
            $stmt = $pdo->prepare("
                INSERT INTO reservations 
                (customer_name, email, phone, reservation_date, reservation_time, num_guests, 
                 status, rating, age, location, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $createdAt = date('Y-m-d H:i:s', strtotime($date . ' ' . $timeSlot) - rand(3600, 86400));
            
            $stmt->execute([
                $customer['name'],
                $customer['email'],
                $customer['phone'],
                $date,
                $timeSlot,
                $guests,
                $status,
                $rating,
                $customer['age'],
                $customer['location'],
                $createdAt
            ]);
            
            $reservationCount++;
        }
    }
    
    echo "✅ Created $reservationCount dynamic reservations\n";
    
    // Create some sample orders
    echo "🛒 Creating dynamic orders...\n";
    
    $menuItems = [
        ['name' => 'Chicken Tikka Masala', 'price' => 14.95],
        ['name' => 'Lamb Biryani', 'price' => 18.50],
        ['name' => 'Vegetable Curry', 'price' => 12.95],
        ['name' => 'Naan Bread Set', 'price' => 8.95],
        ['name' => 'Tandoori Mixed Grill', 'price' => 22.95],
        ['name' => 'Fish Curry', 'price' => 16.95],
        ['name' => 'Samosa (4pcs)', 'price' => 6.95],
        ['name' => 'Mango Lassi', 'price' => 4.95]
    ];
    
    $orderCount = 0;
    
    for ($day = 20; $day >= 0; $day--) {
        $date = date('Y-m-d', strtotime("-$day days"));
        $dailyOrders = rand(2, 8);
        
        for ($i = 0; $i < $dailyOrders; $i++) {
            $customer = $customers[array_rand($customers)];
            
            // Random order items
            $orderItems = [];
            $totalAmount = 0;
            $itemCount = rand(2, 5);
            
            for ($j = 0; $j < $itemCount; $j++) {
                $item = $menuItems[array_rand($menuItems)];
                $quantity = rand(1, 3);
                $orderItems[] = $item['name'] . ' x' . $quantity;
                $totalAmount += $item['price'] * $quantity;
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO orders 
                (customer_name, email, phone, items, total_amount, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $createdAt = date('Y-m-d H:i:s', strtotime($date) + rand(0, 86400));
            $orderStatus = ['pending', 'processing', 'completed'][array_rand(['pending', 'processing', 'completed'])];
            
            $stmt->execute([
                $customer['name'],
                $customer['email'],
                $customer['phone'],
                implode(', ', $orderItems),
                round($totalAmount, 2),
                $orderStatus,
                $createdAt
            ]);
            
            $orderCount++;
        }
    }
    
    echo "✅ Created $orderCount dynamic orders\n";
    
    // Display summary statistics
    echo "\n📊 DYNAMIC DATA SUMMARY:\n";
    echo "========================\n";
    
    $stats = [];
    
    // Total reservations
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reservations");
    $stats['total_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Weekly reservations
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reservations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['weekly_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Confirmed reservations
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reservations WHERE status = 'confirmed'");
    $stats['confirmed_reservations'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Average rating
    $stmt = $pdo->query("SELECT AVG(rating) as avg_rating FROM reservations WHERE rating IS NOT NULL");
    $avgRating = $stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'];
    
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Order revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status = 'completed'");
    $revenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'];
    
    echo "📈 Total Reservations: " . $stats['total_reservations'] . "\n";
    echo "📅 This Week: " . $stats['weekly_reservations'] . "\n";
    echo "✅ Confirmed: " . $stats['confirmed_reservations'] . "\n";
    echo "⭐ Average Rating: " . round($avgRating, 1) . "/5.0\n";
    echo "🛒 Total Orders: " . $stats['total_orders'] . "\n";
    echo "💰 Revenue: £" . number_format($revenue, 2) . "\n";
    
    echo "\n🎉 DYNAMIC DATA POPULATION COMPLETE!\n";
    echo "🔄 Your analytics dashboard now has real dynamic data to display.\n";
    echo "📊 Refresh your admin dashboard to see live analytics!\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure your database 'haveli_db' exists and credentials are correct.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>