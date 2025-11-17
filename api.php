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
        
    case 'get_otp_codes':
        // Support both session and direct email parameter
        $email = $_GET['email'] ?? $_SESSION['temp_email'] ?? '';
        $limit = $_GET['limit'] ?? 10;
        
        if ($email) {
            $otp_codes = getOTPCodes($email, $limit);
            echo json_encode([
                'success' => true, 
                'otp_codes' => $otp_codes, 
                'count' => count($otp_codes),
                'email' => $email
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No email address. Use ?email=your@email.com']);
        }
        break;
        
    case 'get_latest_otp':
        // Support both session and direct email parameter
        $email = $_GET['email'] ?? $_SESSION['temp_email'] ?? '';
        
        if ($email) {
            $otp = getLatestOTP($email);
            if ($otp) {
                echo json_encode([
                    'success' => true, 
                    'otp' => $otp['otp_code'],
                    'sender' => $otp['sender'],
                    'subject' => $otp['subject'],
                    'extracted_at' => $otp['extracted_at'],
                    'is_used' => $otp['is_used'],
                    'email' => $email
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No OTP found for this email']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No email address. Use ?email=your@email.com']);
        }
        break;
        
    case 'mark_otp_used':
        $otp_id = $_POST['otp_id'] ?? 0;
        
        if ($otp_id) {
            $success = markOTPAsUsed($otp_id);
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'message' => 'OTP ID required']);
        }
        break;
        
    case 'get_all_latest_otps':
        // Get latest OTP from ALL emails (no email parameter needed)
        $limit = $_GET['limit'] ?? 10;
        
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT *, UNIX_TIMESTAMP(extracted_at) as timestamp 
                               FROM otp_codes 
                               ORDER BY extracted_at DESC 
                               LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $otps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'otps' => $otps,
            'count' => count($otps)
        ]);
        break;
        
    case 'get_latest_otp_global':
        // Get THE latest OTP from any email (global)
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT *, UNIX_TIMESTAMP(extracted_at) as timestamp 
                               FROM otp_codes 
                               ORDER BY extracted_at DESC 
                               LIMIT 1");
        $stmt->execute();
        $otp = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($otp) {
            echo json_encode([
                'success' => true,
                'otp_code' => $otp['otp_code'],
                'email_address' => $otp['email_address'],
                'sender' => $otp['sender'],
                'subject' => $otp['subject'],
                'extracted_at' => $otp['extracted_at'],
                'is_used' => $otp['is_used'],
                'id' => $otp['id']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No OTP found']);
        }
        break;
        
    case 'search_otp':
        // Search OTP by code, sender, or email
        $search = $_GET['search'] ?? '';
        
        if (empty($search)) {
            echo json_encode(['success' => false, 'message' => 'Search parameter required']);
            break;
        }
        
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT *, UNIX_TIMESTAMP(extracted_at) as timestamp 
                               FROM otp_codes 
                               WHERE otp_code LIKE :search 
                                  OR sender LIKE :search 
                                  OR email_address LIKE :search 
                                  OR subject LIKE :search
                               ORDER BY extracted_at DESC 
                               LIMIT 20");
        $searchParam = "%{$search}%";
        $stmt->execute(['search' => $searchParam]);
        $otps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'otps' => $otps,
            'count' => count($otps),
            'search' => $search
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
