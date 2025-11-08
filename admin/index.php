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

// Get recent emails
$recent_emails = $conn->query("SELECT * FROM emails ORDER BY received_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

// Get generated emails
$generated_emails = $conn->query("SELECT * FROM generated_emails ORDER BY created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
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
                <span>Welcome, Admin</span>
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
            <h2>📧 Buat Custom Email</h2>
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

        <div class="admin-section">
            <h2>📋 Email yang Sudah Dibuat</h2>
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
                                <td><?php echo date('d/m/Y H:i', strtotime($email['created_at'])); ?></td>
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
                                <td><?php echo date('d/m/Y H:i', strtotime($email['received_at'])); ?></td>
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
    </script>
</body>
</html>
