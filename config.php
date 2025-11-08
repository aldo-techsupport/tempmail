<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'madev');
define('DB_PASS', 'madev');
define('DB_NAME', 'madev');

// Email Domain
define('EMAIL_DOMAIN', '@alrelshop.my.id');

// Database Connection
function getDBConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Auto-refresh interval (in seconds)
define('AUTO_REFRESH_INTERVAL', 10);
?>
