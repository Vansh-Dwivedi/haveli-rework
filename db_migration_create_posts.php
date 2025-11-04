<?php
require_once __DIR__ . '/db_config.php';

try {
    $pdo = getDBConnection();
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
    echo "Migration complete: posts table created or already exists." . PHP_EOL;
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . PHP_EOL;
}

?>
