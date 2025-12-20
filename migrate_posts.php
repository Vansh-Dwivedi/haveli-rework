<?php
/**
 * One-off migration helper: creates the `posts` table if missing.
 *
 * Usage (CLI): php migrate_posts.php
 * Usage (web): upload temporarily, visit /migrate_posts.php, then delete it.
 */

declare(strict_types=1);

require_once __DIR__ . '/db_config.php';

header('Content-Type: text/plain; charset=UTF-8');

try {
    $pdo = getDBConnection();
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $sql = "CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            excerpt TEXT,
            content LONGTEXT,
            author VARCHAR(100),
            featured_image VARCHAR(255),
            video_url VARCHAR(255),
            meta_title VARCHAR(255),
            meta_description VARCHAR(255),
            status ENUM('published','draft') DEFAULT 'draft',
            published_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);
        echo "OK: posts table created (or already existed) on MySQL.\n";
        exit(0);
    }

    if ($driver === 'sqlite') {
        // SQLite-friendly schema; timestamps are stored as text.
        $sql = "CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            excerpt TEXT,
            content TEXT,
            author TEXT,
            featured_image TEXT,
            video_url TEXT,
            meta_title TEXT,
            meta_description TEXT,
            status TEXT DEFAULT 'draft',
            published_at TEXT NULL,
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now'))
        );";

        $pdo->exec($sql);

        // Keep updated_at current on UPDATE (best-effort; ignore if trigger already exists).
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS posts_set_updated_at
            AFTER UPDATE ON posts
            FOR EACH ROW
            BEGIN
                UPDATE posts SET updated_at = datetime('now') WHERE id = OLD.id;
            END;");

        echo "OK: posts table created (or already existed) on SQLite.\n";
        exit(0);
    }

    echo "ERROR: Unsupported PDO driver: {$driver}\n";
    exit(2);
} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
