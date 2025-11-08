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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_admin') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $email = trim($_POST['email'] ?? '');
        
        if (empty($username) || empty($password)) {
            $error = 'Username dan password harus diisi!';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal 6 karakter!';
        } else {
            try {
                $conn = getDBConnection();
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash, email, created_at) 
                                       VALUES (:username, :password_hash, :email, NOW())");
                $stmt->execute([
                    'username' => $username,
                    'password_hash' => $password_hash,
                    'email' => $email
                ]);
                $success = "Admin '$username' berhasil ditambahkan!";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'Username sudah ada!';
                } else {
                    $error = 'Terjadi kesalahan sistem.';
                    error_log("Add admin error: " . $e->getMessage());
                }
            }
        }
    } elseif ($action === 'toggle_status') {
        $admin_id = $_POST['admin_id'] ?? 0;
        if ($admin_id != $_SESSION['admin_id']) { // Prevent self-deactivation
            try {
                $conn = getDBConnection();
                $stmt = $conn->prepare("UPDATE admin_users SET is_active = NOT is_active WHERE id = :id");
                $stmt->execute(['id' => $admin_id]);
                $success = 'Status admin berhasil diubah!';
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem.';
            }
        } else {
            $error = 'Tidak bisa menonaktifkan akun sendiri!';
        }
    } elseif ($action === 'delete_admin') {
        $admin_id = $_POST['admin_id'] ?? 0;
        if ($admin_id != $_SESSION['admin_id']) { // Prevent self-deletion
            try {
                $conn = getDBConnection();
                $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = :id");
                $stmt->execute(['id' => $admin_id]);
                $success = 'Admin berhasil dihapus!';
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem.';
            }
        } else {
            $error = 'Tidak bisa menghapus akun sendiri!';
        }
    }
}

// Get all admins
$conn = getDBConnection();
$admins = $conn->query("SELECT * FROM admin_users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Admin - Admin Panel</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>👥 Kelola Admin</h1>
            <div class="admin-nav">
                <a href="index.php">← Kembali ke Dashboard</a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="admin-section">
            <h2>➕ Tambah Admin Baru</h2>
            <form method="POST" class="create-email-form">
                <input type="hidden" name="action" value="add_admin">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" required pattern="[a-zA-Z0-9_-]+" style="margin-right: 10px;">
                    <input type="password" name="password" placeholder="Password (min 6 karakter)" required minlength="6" style="margin-right: 10px;">
                    <input type="email" name="email" placeholder="Email (opsional)" style="margin-right: 10px;">
                    <button type="submit" class="btn-primary">Tambah Admin</button>
                </div>
            </form>
        </div>

        <div class="admin-section">
            <h2>📋 Daftar Admin</h2>
            <div class="table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Dibuat</th>
                            <th>Login Terakhir</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                    <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                        <span style="color: #4CAF50;">(Anda)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($admin['email'] ?? '-'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($admin['created_at'])); ?></td>
                                <td><?php echo $admin['last_login'] ? date('d/m/Y H:i', strtotime($admin['last_login'])) : '-'; ?></td>
                                <td>
                                    <?php if ($admin['is_active']): ?>
                                        <span style="color: #4CAF50;">✓ Aktif</span>
                                    <?php else: ?>
                                        <span style="color: #f44336;">✗ Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Ubah status admin ini?')">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                            <button type="submit" class="btn-view">
                                                <?php echo $admin['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline; margin-left: 5px;" onsubmit="return confirm('Hapus admin ini?')">
                                            <input type="hidden" name="action" value="delete_admin">
                                            <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                            <button type="submit" class="btn-delete">Hapus</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
