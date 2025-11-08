<?php
/**
 * Mail Server Setup Guide & Checker
 */

require_once 'config.php';

$domain = str_replace('@', '', EMAIL_DOMAIN);
$checks = [];

// Check 1: Database Connection
try {
    $conn = getDBConnection();
    $checks['database'] = ['status' => true, 'message' => 'Database connection OK'];
} catch (Exception $e) {
    $checks['database'] = ['status' => false, 'message' => 'Database error: ' . $e->getMessage()];
}

// Check 2: MX Record
$mx_records = [];
if (getmxrr($domain, $mx_records)) {
    $checks['mx_record'] = ['status' => true, 'message' => 'MX Record found: ' . implode(', ', $mx_records)];
} else {
    $checks['mx_record'] = ['status' => false, 'message' => 'No MX Record found'];
}

// Check 3: Mail server
$mail_server = 'mail.' . $domain;
$ip = gethostbyname($mail_server);
if ($ip !== $mail_server) {
    $checks['mail_server'] = ['status' => true, 'message' => "Mail server resolved: $mail_server -> $ip"];
} else {
    $checks['mail_server'] = ['status' => false, 'message' => "Mail server not resolved: $mail_server"];
}

// Check 4: Postfix/Mail service
$postfix_running = false;
if (function_exists('exec')) {
    exec('systemctl is-active postfix 2>&1', $output, $return);
    $postfix_running = ($return === 0 && trim($output[0]) === 'active');
}
$checks['postfix'] = [
    'status' => $postfix_running, 
    'message' => $postfix_running ? 'Postfix is running' : 'Postfix not detected or not running'
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Server Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 30px; }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .check-ok {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .check-fail {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .code-block {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 3px solid #007bff;
            margin: 10px 0;
            overflow-x: auto;
        }
        pre {
            margin: 0;
            white-space: pre-wrap;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Mail Server Setup & Checker</h1>
        
        <h2>System Checks</h2>
        <?php foreach ($checks as $name => $check): ?>
            <div class="check-item <?php echo $check['status'] ? 'check-ok' : 'check-fail'; ?>">
                <strong><?php echo ucfirst(str_replace('_', ' ', $name)); ?>:</strong>
                <?php echo $check['message']; ?>
            </div>
        <?php endforeach; ?>
        
        <div class="warning">
            <strong>⚠️ Penting:</strong> Untuk menerima email dari luar, Anda perlu konfigurasi mail server (Postfix/Exim).
            Untuk testing, gunakan <strong>test_email.php</strong>
        </div>
        
        <h2>Quick Test</h2>
        <a href="test_email.php" class="btn">Test Email Sender</a>
        <a href="webhook_test.php" class="btn">Test Webhook</a>
        <a href="index.php" class="btn">Go to Inbox</a>
        
        <h2>Setup Postfix (Ubuntu/Debian)</h2>
        <div class="code-block">
            <pre># Install Postfix
sudo apt update
sudo apt install postfix

# Configure virtual alias
sudo nano /etc/postfix/main.cf

# Add these lines:
virtual_alias_domains = <?php echo $domain; ?>

virtual_alias_maps = pcre:/etc/postfix/virtual_regexp

# Create virtual regexp file
sudo nano /etc/postfix/virtual_regexp

# Add this line:
/^(.+)@<?php echo preg_quote($domain); ?>$/ receive_email

# Create alias
sudo nano /etc/aliases

# Add this line:
receive_email: "|/usr/bin/php <?php echo __DIR__; ?>/receive_email.php"

# Update aliases and restart
sudo newaliases
sudo postmap /etc/postfix/virtual_regexp
sudo systemctl restart postfix</pre>
        </div>
        
        <h2>Alternative: Email Forwarding Services</h2>
        <p>Jika tidak bisa setup mail server, gunakan layanan email forwarding:</p>
        <ul>
            <li><strong>CloudMailin</strong> - Forward email ke webhook</li>
            <li><strong>Mailgun</strong> - Email API dengan webhook</li>
            <li><strong>SendGrid Inbound Parse</strong> - Parse incoming email</li>
        </ul>
        <p>Arahkan webhook ke: <code><?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']); ?>/receive_email.php</code></p>
        
        <h2>Testing Without Mail Server</h2>
        <p>Untuk testing sistem tanpa mail server:</p>
        <ol>
            <li>Buka <a href="test_email.php">test_email.php</a></li>
            <li>Copy email address dari halaman utama</li>
            <li>Paste ke form dan kirim</li>
            <li>Email akan langsung masuk ke inbox</li>
        </ol>
    </div>
</body>
</html>
