<?php
/**
 * Email Webhook Endpoint
 * Endpoint khusus untuk menerima email dari mail server atau email service
 * 
 * Supported formats:
 * - Standard POST with to, from, subject, body
 * - Mailgun format
 * - SendGrid format
 * - Generic webhook format
 */

require_once 'config.php';
require_once 'functions.php';

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/webhook.log');

// Log incoming request
error_log("=== Webhook Called ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log("POST data: " . print_r($_POST, true));
error_log("Raw input: " . file_get_contents('php://input'));

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get raw input for JSON parsing
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);

// Try to extract email data from various formats
$to_email = '';
$from_email = '';
$subject = '';
$body = '';
$headers = '';

// Format 1: Standard POST
if (!empty($_POST['to']) || !empty($_POST['to_email'])) {
    $to_email = $_POST['to'] ?? $_POST['to_email'] ?? $_POST['recipient'] ?? '';
    $from_email = $_POST['from'] ?? $_POST['from_email'] ?? $_POST['sender'] ?? '';
    $subject = $_POST['subject'] ?? 'No Subject';
    $body = $_POST['body'] ?? $_POST['text'] ?? $_POST['html'] ?? $_POST['message'] ?? '';
    $headers = $_POST['headers'] ?? '';
}
// Format 2: JSON POST
elseif ($json_data) {
    $to_email = $json_data['to'] ?? $json_data['to_email'] ?? $json_data['recipient'] ?? '';
    $from_email = $json_data['from'] ?? $json_data['from_email'] ?? $json_data['sender'] ?? '';
    $subject = $json_data['subject'] ?? 'No Subject';
    $body = $json_data['body'] ?? $json_data['text'] ?? $json_data['html'] ?? $json_data['message'] ?? '';
    $headers = $json_data['headers'] ?? '';
}
// Format 3: Mailgun format
elseif (!empty($_POST['recipient'])) {
    $to_email = $_POST['recipient'];
    $from_email = $_POST['sender'] ?? '';
    $subject = $_POST['subject'] ?? 'No Subject';
    $body = $_POST['body-plain'] ?? $_POST['body-html'] ?? '';
    $headers = $_POST['message-headers'] ?? '';
}

error_log("Extracted - To: $to_email, From: $from_email, Subject: $subject");

// Validate email
if (empty($to_email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing recipient email']);
    exit;
}

// Check if email belongs to our domain
if (strpos($to_email, EMAIL_DOMAIN) === false) {
    http_response_code(400);
    error_log("Invalid domain: $to_email (expected: " . EMAIL_DOMAIN . ")");
    echo json_encode(['success' => false, 'message' => 'Invalid email domain']);
    exit;
}

try {
    // Save email to database
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO emails (to_email, from_email, subject, body, headers, received_at) 
                           VALUES (:to_email, :from_email, :subject, :body, :headers, NOW())");
    
    $success = $stmt->execute([
        'to_email' => $to_email,
        'from_email' => $from_email,
        'subject' => $subject,
        'body' => $body,
        'headers' => $headers
    ]);
    
    if ($success) {
        $email_id = $conn->lastInsertId();
        error_log("Email saved with ID: $email_id");
        
        // Try to extract OTP from email content
        $otp_code = extractOTPFromContent($subject, $body);
        
        if ($otp_code) {
            // Save OTP to database
            $otp_saved = saveOTPCode($to_email, $otp_code, $from_email, $subject, $email_id);
            error_log("OTP extracted and saved: $otp_code for email: $to_email");
            
            http_response_code(200);
            echo json_encode([
                'success' => true, 
                'message' => 'Email received and OTP extracted',
                'email_id' => $email_id,
                'otp_code' => $otp_code,
                'otp_detected' => true
            ]);
        } else {
            error_log("No OTP detected in email");
            http_response_code(200);
            echo json_encode([
                'success' => true, 
                'message' => 'Email received',
                'email_id' => $email_id,
                'otp_detected' => false
            ]);
        }
    } else {
        http_response_code(500);
        error_log("Failed to save email to database");
        echo json_encode(['success' => false, 'message' => 'Failed to save email']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
