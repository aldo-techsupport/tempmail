<?php
// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'tempmail');
define('DB_PASS', 'alrel1408');
define('DB_NAME', 'tempmail');

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
