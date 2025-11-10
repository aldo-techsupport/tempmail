<?php
session_start();

// Check if logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';
require_once '../functions.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_email') {
        $custom_email = trim($_POST['custom_email']);
        $domain = EMAIL_DOMAIN;
        
        // Validate email format
        if (!empty($custom_email) && preg_match('/^[a-z0-9_-]+$/i', $custom_email)) {
            $full_email = $custom_email . $domain;
            
            // Save to generated_emails table
            try {
                $conn = getDBConnection();
                $stmt = $conn->prepare("INSERT INTO generated_emails (email_address, created_at) VALUES (:email, NOW())");
                $stmt->execute(['email' => $full_email]);
                $success_msg = "Email berhasil dibuat: $full_email";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error_msg = "Email sudah ada!";
                } else {
                    $error_msg = "Error: " . $e->getMessage();
                }
            }
        } else {
            $error_msg = "Format email tidak valid! Gunakan huruf, angka, dash, underscore saja.";
        }
    } elseif ($action === 'delete_email') {
        $email_id = $_POST['email_id'];
        $conn = getDBConnection();
        $stmt = $conn->prepare("DELETE FROM emails WHERE id = :id");
        $stmt->execute(['id' => $email_id]);
        $success_msg = "Email berhasil dihapus!";
    }
}

// Get statistics
$conn = getDBConnection();
$stats = [
    'total_emails' => $conn->query("SELECT COUNT(*) FROM emails")->fetchColumn(),
    'total_generated' => $conn->query("SELECT COUNT(*) FROM generated_emails")->fetchColumn(),
    'today_emails' => $conn->query("SELECT COUNT(*) FROM emails WHERE DATE(received_at) = CURDATE()")->fetchColumn(),
];

// Handle search
$search_generated = $_GET['search_generated'] ?? '';
$search_inbox = $_GET['search_inbox'] ?? '';

// Get recent emails with search
$inbox_query = "SELECT *, UNIX_TIMESTAMP(received_at) as timestamp FROM emails";
if (!empty($search_inbox)) {
    $inbox_query .= " WHERE to_email LIKE :search OR from_email LIKE :search OR subject LIKE :search";
}
$inbox_query .= " ORDER BY received_at DESC LIMIT 100";
$stmt = $conn->prepare($inbox_query);
if (!empty($search_inbox)) {
    $search_param = '%' . $search_inbox . '%';
    $stmt->execute(['search' => $search_param]);
} else {
    $stmt->execute();
}
$recent_emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get generated emails with search
$generated_query = "SELECT *, UNIX_TIMESTAMP(created_at) as timestamp FROM generated_emails";
if (!empty($search_generated)) {
    $generated_query .= " WHERE email_address LIKE :search";
}
$generated_query .= " ORDER BY created_at DESC LIMIT 100";
$stmt = $conn->prepare($generated_query);
if (!empty($search_generated)) {
    $search_param = '%' . $search_generated . '%';
    $stmt->execute(['search' => $search_param]);
} else {
    $stmt->execute();
}
$generated_emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Temporary Email</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>🔧 Admin Panel</h1>
            <div class="admin-nav">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                <a href="generate_emails.php" style="margin-right: 10px;">📧 Generate Email</a>
                <a href="delete_emails.php" style="margin-right: 10px;">🗑️ Delete Email</a>
                <a href="manage_admins.php" style="margin-right: 10px;">👥 Kelola Admin</a>
                <a href="change_password.php" style="margin-right: 10px;">🔐 Ubah Password</a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </header>

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_msg)): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Email Masuk</h3>
                <div class="stat-number"><?php echo $stats['total_emails']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Email Hari Ini</h3>
                <div class="stat-number"><?php echo $stats['today_emails']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Email Ter-generate</h3>
                <div class="stat-number"><?php echo $stats['total_generated']; ?></div>
            </div>
        </div>

        <div class="admin-section">
            <h2>📧 Kelola Email</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                    <h3 style="margin-top: 0;">Single Email</h3>
                    <p style="color: #666; font-size: 14px;">Buat satu email custom</p>
                    <form method="POST" class="create-email-form">
                        <input type="hidden" name="action" value="create_email">
                        <div class="form-group">
                            <div class="email-input-group">
                                <input type="text" name="custom_email" placeholder="nama-email" required pattern="[a-zA-Z0-9_-]+" title="Hanya huruf, angka, dash, dan underscore">
                                <span class="domain-suffix"><?php echo EMAIL_DOMAIN; ?></span>
                            </div>
                            <button type="submit" class="btn-primary">Buat Email</button>
                        </div>
                    </form>
                </div>
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; text-align: center; color: white;">
                    <h3 style="margin-top: 0; color: white;">Generate Email Massal</h3>
                    <p style="font-size: 14px; opacity: 0.9;">Generate hingga 1000 email sekaligus!</p>
                    <a href="generate_emails.php" style="display: inline-block; background: white; color: #667eea; padding: 12px 30px; border-radius: 4px; text-decoration: none; font-weight: 600; margin-top: 10px;">
                        🚀 Mulai Generate
                    </a>
                </div>
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; border-radius: 8px; text-align: center; color: white;">
                    <h3 style="margin-top: 0; color: white;">Delete Email</h3>
                    <p style="font-size: 14px; opacity: 0.9;">Hapus email secara massal atau individual</p>
                    <a href="delete_emails.php" style="display: inline-block; background: white; color: #f5576c; padding: 12px 30px; border-radius: 4px; text-decoration: none; font-weight: 600; margin-top: 10px;">
                        🗑️ Kelola Delete
                    </a>
                </div>
            </div>
        </div>

        <div class="admin-section">
            <h2>📋 Email yang Sudah Dibuat</h2>
            <div style="margin-bottom: 15px;">
                <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" name="search_generated" placeholder="🔍 Cari email..." value="<?php echo htmlspecialchars($search_generated); ?>" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    <?php if (!empty($search_inbox)): ?>
                        <input type="hidden" name="search_inbox" value="<?php echo htmlspecialchars($search_inbox); ?>">
                    <?php endif; ?>
                    <button type="submit" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">Cari</button>
                    <?php if (!empty($search_generated)): ?>
                        <a href="?<?php echo !empty($search_inbox) ? 'search_inbox=' . urlencode($search_inbox) : ''; ?>" style="padding: 10px 20px; background: #6c757d; color: white; border-radius: 4px; text-decoration: none; font-weight: 600;">Reset</a>
                    <?php endif; ?>
                </form>
                <?php if (!empty($search_generated)): ?>
                    <p style="margin-top: 10px; color: #666; font-size: 14px;">Ditemukan <?php echo count($generated_emails); ?> hasil untuk "<?php echo htmlspecialchars($search_generated); ?>"</p>
                <?php endif; ?>
            </div>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Email Address</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($generated_emails as $email): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($email['email_address']); ?></strong>
                                    <button onclick="copyToClipboard('<?php echo htmlspecialchars($email['email_address']); ?>')" class="btn-copy-small">📋</button>
                                </td>
                                <td class="local-time" data-timestamp="<?php echo htmlspecialchars($email['created_at']); ?>" data-unix="<?php echo $email['timestamp']; ?>">
                                    <?php echo date('d/m/Y H:i', strtotime($email['created_at'])); ?>
                                </td>
                                <td>
                                    <a href="../index.php?email=<?php echo urlencode($email['email_address']); ?>" target="_blank" class="btn-view">Lihat Inbox</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-section">
            <h2>📬 Email Masuk Terbaru</h2>
            <div style="margin-bottom: 15px;">
                <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" name="search_inbox" placeholder="🔍 Cari email, pengirim, atau subject..." value="<?php echo htmlspecialchars($search_inbox); ?>" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    <?php if (!empty($search_generated)): ?>
                        <input type="hidden" name="search_generated" value="<?php echo htmlspecialchars($search_generated); ?>">
                    <?php endif; ?>
                    <button type="submit" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">Cari</button>
                    <?php if (!empty($search_inbox)): ?>
                        <a href="?<?php echo !empty($search_generated) ? 'search_generated=' . urlencode($search_generated) : ''; ?>" style="padding: 10px 20px; background: #6c757d; color: white; border-radius: 4px; text-decoration: none; font-weight: 600;">Reset</a>
                    <?php endif; ?>
                </form>
                <?php if (!empty($search_inbox)): ?>
                    <p style="margin-top: 10px; color: #666; font-size: 14px;">Ditemukan <?php echo count($recent_emails); ?> hasil untuk "<?php echo htmlspecialchars($search_inbox); ?>"</p>
                <?php endif; ?>
            </div>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Kepada</th>
                            <th>Dari</th>
                            <th>Subject</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_emails as $email): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($email['to_email']); ?></td>
                                <td><?php echo htmlspecialchars($email['from_email']); ?></td>
                                <td><?php echo htmlspecialchars($email['subject']); ?></td>
                                <td class="local-time" data-timestamp="<?php echo htmlspecialchars($email['received_at']); ?>" data-unix="<?php echo $email['timestamp']; ?>">
                                    <?php echo date('d/m/Y H:i', strtotime($email['received_at'])); ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus email ini?')">
                                        <input type="hidden" name="action" value="delete_email">
                                        <input type="hidden" name="email_id" value="<?php echo $email['id']; ?>">
                                        <button type="submit" class="btn-delete">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Email copied: ' + text);
            });
        }
        
        // Format date to local time
        function formatDateLocal(dateString, unixTimestamp) {
            let date;
            if (unixTimestamp) {
                date = new Date(unixTimestamp * 1000); // Convert Unix timestamp to milliseconds
            } else {
                date = new Date(dateString);
            }
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${day}/${month}/${year} ${hours}:${minutes}`;
        }
        
        // Convert all timestamps to local time
        document.addEventListener('DOMContentLoaded', function() {
            const timeElements = document.querySelectorAll('.local-time[data-timestamp]');
            timeElements.forEach(element => {
                const timestamp = element.getAttribute('data-timestamp');
                const unixTimestamp = element.getAttribute('data-unix');
                if (timestamp) {
                    element.textContent = formatDateLocal(timestamp, unixTimestamp);
                }
            });
        });
    </script>
</body>
</html>
