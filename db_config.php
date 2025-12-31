<?php
// Centralized DB connection for Haveli
// Deployment credentials per your server setup
function getDBConnection() {
    $host = 'localhost';
    $username = 'Vansh';
    $password = '2001-Vansh';
    $database = 'haveli_db';

    try {
        $dsn = "mysql:host={$host};dbname={$database};charset=utf8";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        die("Database connection failed. Please contact the administrator.");
    }
}

?>