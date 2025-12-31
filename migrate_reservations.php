<?php
/**
 * One-off migration helper: creates the `reservations` table if missing.
 *
 * Usage (CLI): php migrate_reservations.php
 * Usage (web): upload temporarily, visit /migrate_reservations.php, then delete it.
 */

declare(strict_types=1);

require_once __DIR__ . '/db_config.php';

header('Content-Type: text/plain; charset=UTF-8');

try {
    $pdo = getDBConnection();
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $sql = "CREATE TABLE IF NOT EXISTS reservations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255),
            phone VARCHAR(30),
            reservation_date DATE NOT NULL,
            reservation_time TIME NOT NULL,
            guests INT NOT NULL,
            status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);
        echo "OK: reservations table created (or already existed) on MySQL.\n";
        exit(0);
    }

    if ($driver === 'sqlite') {
        $sql = "CREATE TABLE IF NOT EXISTS reservations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT,
            phone TEXT,
            reservation_date TEXT NOT NULL,
            reservation_time TEXT NOT NULL,
            guests INTEGER NOT NULL,
            status TEXT DEFAULT 'pending',
            notes TEXT,
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now'))
        );";

        $pdo->exec($sql);

        // Keep updated_at current on UPDATE (best-effort; ignore if trigger already exists).
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS reservations_set_updated_at
            AFTER UPDATE ON reservations
            FOR EACH ROW
            BEGIN
                UPDATE reservations SET updated_at = datetime('now') WHERE id = OLD.id;
            END;");

        echo "OK: reservations table created (or already existed) on SQLite.\n";
        exit(0);
    }

    echo "ERROR: Unsupported PDO driver: {$driver}\n";
    exit(2);
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
