<?php
// Test bootstrap: provide an in-memory SQLite DB and ensure sessions are ready
if (session_status() === PHP_SESSION_NONE) session_start();

// Provide a test DB connection that will be used instead of db_config.php
function getDBConnection() {
    static $pdo = null;
    if ($pdo) return $pdo;

    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create a simplified reservations table for tests
    $pdo->exec("CREATE TABLE reservations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        customer_name TEXT,
        phone_number TEXT,
        email TEXT,
        num_guests INTEGER,
        reservation_date TEXT,
        reservation_time TEXT,
        status TEXT,
        created_at TEXT,
        updated_at TEXT
    )");

    // Seed sample data
    $now = date('Y-m-d H:i:s');
    $seed = [
        ['Alice','111','alice@example.com',2,'2025-11-01','18:00:00','pending',$now,$now],
        ['Bob','222','bob@example.com',4,'2025-11-02','19:00:00','confirmed',$now,$now],
        ['Carol','333','carol@example.com',6,'2025-11-03','20:00:00','refused',$now,$now],
        ['Dave','444','dave@example.com',3,'2025-11-03','17:30:00','pending',$now,$now],
        ['Eve','555','eve@example.com',5,'2025-11-04','18:30:00','confirmed',$now,$now],
    ];

    $stmt = $pdo->prepare('INSERT INTO reservations (customer_name, phone_number, email, num_guests, reservation_date, reservation_time, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($seed as $row) {
        $stmt->execute($row);
    }

        // Create posts table for blog tests
        $pdo->exec("CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT,
            slug TEXT,
            excerpt TEXT,
            content TEXT,
            author TEXT,
            featured_image TEXT,
            video_url TEXT,
            meta_title TEXT,
            meta_description TEXT,
            status TEXT,
            published_at TEXT,
            created_at TEXT,
            updated_at TEXT
        )");

    return $pdo;
}

// Make sure errors are visible during tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

?>