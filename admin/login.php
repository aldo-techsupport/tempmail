<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

// Check if already logged in
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id, username, password_hash, email FROM admin_users WHERE username = :username AND is_active = 1");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Login successful
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_email'] = $user['email'];
                
                // Update last login
                $update = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = :id");
                $update->execute(['id' => $user['id']]);
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Username atau password salah!';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            error_log("Login error: " . $e->getMessage());
        }
    } else {
        $error = 'Username dan password harus diisi!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <h1>🔐 Admin Login</h1>
            <p>Temporary Email System</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn-primary btn-block">Login</button>
            </form>
            
            <div class="login-footer">
                <a href="../index.php">← Kembali ke Inbox</a>
            </div>
        </div>
    </div>
</body>
</html>
