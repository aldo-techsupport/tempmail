<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'generate':
        try {
            $new_email = generateRandomEmail();
            $_SESSION['temp_email'] = $new_email;
            
            // Generate token
            $token = generateTokenForEmail($new_email);
            $_SESSION['email_token'] = $token;
            
            // Construct URL
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/?token=' . $token;
            
            echo json_encode([
                'success' => true, 
                'email' => $new_email,
                'token' => $token,
                'url' => $url
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'restore':
        $token = $_POST['token'] ?? '';
        if ($token) {
            $email = getEmailByToken($token);
            if ($email) {
                $_SESSION['temp_email'] = $email;
                $_SESSION['email_token'] = $token;
                echo json_encode(['success' => true, 'email' => $email, 'token' => $token]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Token tidak valid atau sudah kadaluarsa']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Token tidak diberikan']);
        }
        break;
        
    case 'get_emails':
        $email = $_SESSION['temp_email'] ?? '';
        if ($email) {
            $emails = getEmails($email);
            echo json_encode(['success' => true, 'emails' => $emails, 'count' => count($emails)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No email address']);
        }
        break;
        
    case 'get_email':
        $id = $_GET['id'] ?? 0;
        $email = getEmailById($id);
        if ($email) {
            echo json_encode(['success' => true, 'email' => $email]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Email not found']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
