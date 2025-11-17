-- Create otp_codes table
USE madev;

CREATE TABLE IF NOT EXISTS otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_address VARCHAR(255) NOT NULL,
    otp_code VARCHAR(20) NOT NULL,
    sender VARCHAR(255),
    subject VARCHAR(500),
    email_id INT,
    extracted_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    used_at DATETIME,
    INDEX idx_email (email_address),
    INDEX idx_otp (otp_code),
    INDEX idx_extracted_at (extracted_at),
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create cleanup event for old OTP codes (older than 24 hours)
CREATE EVENT IF NOT EXISTS cleanup_old_otp_codes
ON SCHEDULE EVERY 1 HOUR
DO
  DELETE FROM otp_codes WHERE extracted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Show tables to verify
SHOW TABLES LIKE 'otp_codes';
