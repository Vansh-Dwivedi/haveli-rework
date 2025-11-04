<?php
/**
 * Database Migration Script
 * Adds missing columns to reservations table
 * Run this once to update database schema
 */

require_once __DIR__ . '/db_config.php';

echo "<h2>Haveli Database Migration</h2>";

try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
    
    // Check if reservations table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'reservations'");
    if ($stmt->rowCount() === 0) {
        echo "<p style='color: red;'>❌ Reservations table not found</p>";
        exit;
    }
    echo "<p style='color: green;'>✅ Reservations table found</p>";
    
    // Get existing columns
    $stmt = $pdo->query("SHOW COLUMNS FROM reservations");
    $existingColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }
    
    echo "<h3>Checking columns...</h3>";
    
    // Columns to add
    $columnsToAdd = [
        'internal_note' => "TEXT NULL COMMENT 'Internal staff notes'",
        'rating' => "DECIMAL(2,1) NULL COMMENT 'Customer rating 1.0-5.0'",
        'birth_date' => "DATE NULL COMMENT 'Customer birth date for demographics'",
        'location' => "VARCHAR(100) NULL COMMENT 'Customer location/city'"
    ];
    
    $addedCount = 0;
    
    foreach ($columnsToAdd as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            try {
                $sql = "ALTER TABLE reservations ADD COLUMN `{$column}` {$definition}";
                $pdo->exec($sql);
                echo "<p style='color: green;'>✅ Added column: {$column}</p>";
                $addedCount++;
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Could not add {$column}: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ️ Column {$column} already exists</p>";
        }
    }
    
    // Update status values to lowercase for consistency
    echo "<h3>Standardizing status values...</h3>";
    try {
        $pdo->exec("UPDATE reservations SET status = 'refused' WHERE status = 'Refused'");
        $pdo->exec("UPDATE reservations SET status = 'cancelled' WHERE status = 'Cancelled'");
        $pdo->exec("UPDATE reservations SET status = 'confirmed' WHERE status = 'Confirmed'");
        $pdo->exec("UPDATE reservations SET status = 'pending' WHERE status = 'Pending'");
        echo "<p style='color: green;'>✅ Status values standardized to lowercase</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ Could not update status values: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Migration Summary</h3>";
    echo "<p><strong>Columns added: {$addedCount}</strong></p>";
    echo "<p><strong>Total columns checked: " . count($columnsToAdd) . "</strong></p>";
    
    if ($addedCount > 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ Migration completed successfully!</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ No new columns needed - database is up to date</p>";
    }
    
    echo "<p><a href='admin_dashboard.php'>← Back to Admin Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
}
?>