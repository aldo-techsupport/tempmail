<?php
require_once 'config.php';

// Generate random email address
function generateRandomEmail() {
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $length = rand(8, 12);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString . EMAIL_DOMAIN;
}

// Get all emails for specific address
function getEmails($email_address) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM emails WHERE to_email = :email ORDER BY received_at DESC");
    $stmt->execute(['email' => $email_address]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get single email by ID
function getEmailById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM emails WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Save incoming email to database
function saveEmail($to_email, $from_email, $subject, $body, $headers = '') {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO emails (to_email, from_email, subject, body, headers, received_at) 
                           VALUES (:to_email, :from_email, :subject, :body, :headers, NOW())");
    
    return $stmt->execute([
        'to_email' => $to_email,
        'from_email' => $from_email,
        'subject' => $subject,
        'body' => $body,
        'headers' => $headers
    ]);
}

// Delete old emails (older than 24 hours)
function cleanOldEmails() {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM emails WHERE received_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    return $stmt->execute();
}

// Get email count for address
function getEmailCount($email_address) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM emails WHERE to_email = :email");
    $stmt->execute(['email' => $email_address]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

// Generate token for email
function generateTokenForEmail($email) {
    $conn = getDBConnection();
    
    // Check if token already exists
    $stmt = $conn->prepare("SELECT access_token FROM generated_emails WHERE email_address = :email");
    $stmt->execute(['email' => $email]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && !empty($result['access_token'])) {
        return $result['access_token'];
    }
    
    // Generate new token
    $token = bin2hex(random_bytes(16)); // 32 character token
    
    // Save or update token
    try {
        $stmt = $conn->prepare("INSERT INTO generated_emails (email_address, access_token, created_at) 
                               VALUES (:email, :token, NOW()) 
                               ON DUPLICATE KEY UPDATE access_token = :token");
        $stmt->execute(['email' => $email, 'token' => $token]);
    } catch (PDOException $e) {
        // If error, return a temporary token
        return md5($email . time());
    }
    
    return $token;
}

// Get email by token
function getEmailByToken($token) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT email_address FROM generated_emails WHERE access_token = :token");
    $stmt->execute(['token' => $token]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Update last accessed
        $update = $conn->prepare("UPDATE generated_emails SET last_accessed = NOW(), access_count = access_count + 1 WHERE access_token = :token");
        $update->execute(['token' => $token]);
        
        return $result['email_address'];
    }
    
    return null;
}
?>
