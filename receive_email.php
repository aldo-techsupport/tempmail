<?php
/**
 * Email Receiver Script
 * This script receives emails via SMTP or HTTP POST
 * Configure your mail server to pipe emails to this script
 */

require_once 'config.php';
require_once 'functions.php';

// Enable error logging
error_log("Receive email script called - Method: " . $_SERVER['REQUEST_METHOD']);

// Method 1: Receive via HTTP POST (for webhook/API)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support multiple POST formats
    $to_email = $_POST['to'] ?? $_POST['to_email'] ?? $_POST['recipient'] ?? '';
    $from_email = $_POST['from'] ?? $_POST['from_email'] ?? $_POST['sender'] ?? '';
    $subject = $_POST['subject'] ?? 'No Subject';
    $body = $_POST['body'] ?? $_POST['text'] ?? $_POST['html'] ?? '';
    $headers = $_POST['headers'] ?? '';
    
    error_log("Received email - To: $to_email, From: $from_email, Subject: $subject");
    
    // Validate email domain
    if (strpos($to_email, EMAIL_DOMAIN) !== false) {
        if (saveEmail($to_email, $from_email, $subject, $body, $headers)) {
            http_response_code(200);
            error_log("Email saved successfully");
            echo json_encode(['success' => true, 'message' => 'Email received']);
        } else {
            http_response_code(500);
            error_log("Failed to save email");
            echo json_encode(['success' => false, 'message' => 'Failed to save email']);
        }
    } else {
        http_response_code(400);
        error_log("Invalid domain: $to_email");
        echo json_encode(['success' => false, 'message' => 'Invalid domain']);
    }
    exit;
}

// Method 2: Receive via STDIN (for piped emails from mail server)
if (php_sapi_name() === 'cli') {
    $raw_email = file_get_contents('php://stdin');
    
    if (!empty($raw_email)) {
        // Parse email headers and body
        $parts = preg_split("/\r?\n\r?\n/", $raw_email, 2);
        $headers = $parts[0] ?? '';
        $body = $parts[1] ?? '';
        
        // Extract To, From, Subject
        preg_match('/^To: (.*)$/m', $headers, $to_matches);
        preg_match('/^From: (.*)$/m', $headers, $from_matches);
        preg_match('/^Subject: (.*)$/m', $headers, $subject_matches);
        
        $to_email = trim($to_matches[1] ?? '');
        $from_email = trim($from_matches[1] ?? '');
        $subject = trim($subject_matches[1] ?? 'No Subject');
        
        // Extract email address from "Name <email@domain.com>" format
        if (preg_match('/<(.+?)>/', $to_email, $matches)) {
            $to_email = $matches[1];
        }
        if (preg_match('/<(.+?)>/', $from_email, $matches)) {
            $from_email = $matches[1];
        }
        
        // Parse multipart email
        $parsed_body = parseEmailBody($body, $headers);
        
        // Validate and save
        if (strpos($to_email, EMAIL_DOMAIN) !== false) {
            saveEmail($to_email, $from_email, $subject, $parsed_body, $headers);
            echo "Email received and saved\n";
        } else {
            echo "Invalid domain\n";
        }
    }
    exit;
}

// Parse email body (handle multipart, HTML, plain text)
function parseEmailBody($body, $headers) {
    // Check if multipart
    if (preg_match('/boundary="?([^"\s]+)"?/i', $headers, $boundary_matches)) {
        $boundary = $boundary_matches[1];
        
        // Split by boundary
        $parts = explode('--' . $boundary, $body);
        
        $plain_text = '';
        $html_text = '';
        
        foreach ($parts as $part) {
            // Skip empty parts
            if (trim($part) === '' || trim($part) === '--') continue;
            
            // Split headers and content
            $part_split = preg_split("/\r?\n\r?\n/", $part, 2);
            if (count($part_split) < 2) continue;
            
            $part_headers = $part_split[0];
            $part_content = $part_split[1];
            
            // Check content type
            if (stripos($part_headers, 'Content-Type: text/plain') !== false) {
                $plain_text = decodeEmailContent($part_content, $part_headers);
            } elseif (stripos($part_headers, 'Content-Type: text/html') !== false) {
                $html_text = decodeEmailContent($part_content, $part_headers);
            }
        }
        
        // Return HTML if available, otherwise plain text
        if (!empty($html_text)) {
            return $html_text;
        } elseif (!empty($plain_text)) {
            // Don't escape plain text - let frontend handle it
            return $plain_text;
        }
    }
    
    // Single part email - check if HTML
    $decoded = decodeEmailContent($body, $headers);
    
    // If content type is HTML, return as is
    if (stripos($headers, 'Content-Type: text/html') !== false) {
        return $decoded;
    }
    
    // Otherwise treat as plain text - don't escape, let frontend handle it
    return $decoded;
}

// Decode email content (handle quoted-printable, base64, etc)
function decodeEmailContent($content, $headers) {
    // Check encoding
    if (stripos($headers, 'Content-Transfer-Encoding: quoted-printable') !== false) {
        $content = quoted_printable_decode($content);
    } elseif (stripos($headers, 'Content-Transfer-Encoding: base64') !== false) {
        $content = base64_decode($content);
    }
    
    // Clean up
    $content = trim($content);
    
    return $content;
}

// Default response
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>
