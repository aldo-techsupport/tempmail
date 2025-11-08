-- Create Database
CREATE DATABASE IF NOT EXISTS temp_email_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE temp_email_db;

-- Create emails table
CREATE TABLE IF NOT EXISTS emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) DEFAULT 'No Subject',
    body TEXT,
    headers TEXT,
    received_at DATETIME NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    INDEX idx_to_email (to_email),
    INDEX idx_received_at (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create generated_emails table (untuk tracking email yang di-generate)
CREATE TABLE IF NOT EXISTS generated_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_address VARCHAR(255) NOT NULL UNIQUE,
    access_token VARCHAR(64) UNIQUE,
    created_at DATETIME NOT NULL,
    last_accessed DATETIME,
    access_count INT DEFAULT 0,
    INDEX idx_email (email_address),
    INDEX idx_token (access_token),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create cleanup event (auto delete emails older than 24 hours)
CREATE EVENT IF NOT EXISTS cleanup_old_emails
ON SCHEDULE EVERY 1 HOUR
DO
  DELETE FROM emails WHERE received_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Create cleanup event for old generated emails (older than 7 days)
CREATE EVENT IF NOT EXISTS cleanup_old_generated_emails
ON SCHEDULE EVERY 1 DAY
DO
  DELETE FROM generated_emails WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
