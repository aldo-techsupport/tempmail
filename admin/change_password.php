<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Password baru dan konfirmasi tidak cocok!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        try {
            $conn = getDBConnection();
            
            // Verify current password
            $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['admin_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($current_password, $user['password_hash'])) {
                // Update password
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE admin_users SET password_hash = :password_hash WHERE id = :id");
                $update->execute([
                    'password_hash' => $new_hash,
                    'id' => $_SESSION['admin_id']
                ]);
                
                $success = 'Password berhasil diubah!';
            } else {
                $error = 'Password lama salah!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem.';
            error_log("Change password error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password - Admin</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>🔐 Ubah Password</h1>
            <div class="admin-nav">
                <a href="index.php">← Kembali ke Dashboard</a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </header>

        <div class="admin-section" style="max-width: 500px; margin: 40px auto;">
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" class="change-password-form">
                <div class="form-group">
                    <label>Password Lama</label>
                    <input type="password" name="current_password" required autofocus>
                </div>

                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" name="new_password" required minlength="6">
                    <small>Minimal 6 karakter</small>
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>

                <button type="submit" class="btn-primary">Ubah Password</button>
            </form>
        </div>
    </div>
</body>
</html>
