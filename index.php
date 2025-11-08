<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check if token provided in URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $email = getEmailByToken($token);
    if ($email) {
        $_SESSION['temp_email'] = $email;
        $_SESSION['email_token'] = $token;
    }
}

// Check if custom email from admin
if (isset($_GET['email'])) {
    $custom_email = $_GET['email'];
    // Validate email domain
    if (strpos($custom_email, EMAIL_DOMAIN) !== false) {
        $_SESSION['temp_email'] = $custom_email;
        // Generate token for this email
        $_SESSION['email_token'] = generateTokenForEmail($custom_email);
    }
}

// Generate random email if not exists
if (!isset($_SESSION['temp_email'])) {
    $_SESSION['temp_email'] = generateRandomEmail();
    // Generate token for new email
    $_SESSION['email_token'] = generateTokenForEmail($_SESSION['temp_email']);
}

$current_email = $_SESSION['temp_email'];
$current_token = $_SESSION['email_token'] ?? '';
$emails = getEmails($current_email);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temporary Email - AlrelShop</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <header>
            <h1>📧 Temporary Email</h1>
            <p>Email sementara untuk melindungi privasi Anda</p>
            <div style="text-align: center; margin-top: 10px;">
                <a href="admin/" style="color: white; text-decoration: none; font-size: 12px; opacity: 0.8;">Admin Panel</a>
            </div>
        </header>

        <div class="email-box">
            <div class="email-display">
                <input type="text" id="tempEmail" value="<?php echo htmlspecialchars($current_email); ?>" readonly>
                <button onclick="copyEmail(event)" class="btn-copy">📋 Copy</button>
                <button onclick="generateNew()" class="btn-new">🔄 Email Baru</button>
            </div>
            
            <div class="token-section">
                <div class="token-info">
                    <span class="token-label">🔑 Token Akses:</span>
                    <input type="text" id="tokenInput" value="<?php echo htmlspecialchars($current_token); ?>" readonly>
                    <button onclick="copyToken(event)" class="btn-copy-token">📋 Copy Token</button>
                </div>
                <div class="token-hint">
                    Simpan token ini untuk mengakses email Anda kembali nanti
                </div>
                <div class="token-url">
                    <small>URL Akses: <span id="tokenUrl"><?php echo 'https://' . $_SERVER['HTTP_HOST'] . '/?token=' . htmlspecialchars($current_token); ?></span></small>
                    <button onclick="copyTokenUrl(event)" class="btn-copy-url">📋</button>
                </div>
            </div>
        </div>
        
        <div class="restore-section">
            <button onclick="toggleRestoreForm()" class="btn-restore">🔓 Pulihkan Email Lama</button>
            <div id="restoreForm" style="display: none;">
                <input type="text" id="restoreToken" placeholder="Masukkan token Anda">
                <button onclick="restoreEmail()" class="btn-restore-submit">Pulihkan</button>
            </div>
        </div>

        <div class="inbox">
            <div class="inbox-header">
                <h2>Inbox (<?php echo count($emails); ?>)</h2>
                <button onclick="refreshInbox()" class="btn-refresh">🔄 Refresh</button>
            </div>
            
            <div id="emailList">
                <?php if (empty($emails)): ?>
                    <div class="no-emails">
                        <p>📭 Tidak ada email masuk</p>
                        <p class="hint">Email akan muncul di sini secara otomatis</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($emails as $email): ?>
                        <div class="email-item" onclick="viewEmail(<?php echo $email['id']; ?>)">
                            <div class="email-from">
                                <strong>Dari:</strong> <?php echo htmlspecialchars($email['from_email']); ?>
                            </div>
                            <div class="email-subject">
                                <?php echo htmlspecialchars($email['subject']); ?>
                            </div>
                            <div class="email-date">
                                <?php echo date('d/m/Y H:i', strtotime($email['received_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal untuk menampilkan email -->
    <div id="emailModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div id="emailContent"></div>
        </div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
