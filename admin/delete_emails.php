<?php
session_start();

// Check if logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';
require_once '../functions.php';

$success_msg = '';
$error_msg = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $conn = getDBConnection();
    
    if ($action === 'delete_single') {
        $email_id = intval($_POST['email_id']);
        try {
            $stmt = $conn->prepare("DELETE FROM generated_emails WHERE id = :id");
            $stmt->execute(['id' => $email_id]);
            $success_msg = "Email berhasil dihapus!";
        } catch (PDOException $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    } 
    elseif ($action === 'delete_multiple') {
        $email_ids = $_POST['email_ids'] ?? [];
        if (!empty($email_ids)) {
            try {
                $conn->beginTransaction();
                $placeholders = str_repeat('?,', count($email_ids) - 1) . '?';
                $stmt = $conn->prepare("DELETE FROM generated_emails WHERE id IN ($placeholders)");
                $stmt->execute($email_ids);
                $deleted_count = $stmt->rowCount();
                $conn->commit();
                $success_msg = "Berhasil menghapus $deleted_count email!";
            } catch (Exception $e) {
                $conn->rollBack();
                $error_msg = "Error: " . $e->getMessage();
            }
        } else {
            $error_msg = "Pilih minimal 1 email untuk dihapus!";
        }
    }
    elseif ($action === 'delete_all') {
        try {
            $count = $conn->query("SELECT COUNT(*) FROM generated_emails")->fetchColumn();
            $conn->exec("DELETE FROM generated_emails");
            $success_msg = "Berhasil menghapus semua email ($count email)!";
        } catch (PDOException $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }
    elseif ($action === 'delete_old') {
        $days = intval($_POST['days'] ?? 7);
        try {
            $stmt = $conn->prepare("DELETE FROM generated_emails WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)");
            $stmt->execute(['days' => $days]);
            $deleted_count = $stmt->rowCount();
            $success_msg = "Berhasil menghapus $deleted_count email yang lebih dari $days hari!";
        } catch (PDOException $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }
    elseif ($action === 'delete_by_pattern') {
        $pattern = trim($_POST['pattern'] ?? '');
        if (!empty($pattern)) {
            try {
                $stmt = $conn->prepare("DELETE FROM generated_emails WHERE email_address LIKE :pattern");
                $stmt->execute(['pattern' => "%$pattern%"]);
                $deleted_count = $stmt->rowCount();
                $success_msg = "Berhasil menghapus $deleted_count email dengan pattern '$pattern'!";
            } catch (PDOException $e) {
                $error_msg = "Error: " . $e->getMessage();
            }
        } else {
            $error_msg = "Pattern tidak boleh kosong!";
        }
    }
}

// Get statistics
$conn = getDBConnection();
$stats = [
    'total' => $conn->query("SELECT COUNT(*) FROM generated_emails")->fetchColumn(),
    'today' => $conn->query("SELECT COUNT(*) FROM generated_emails WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    'this_week' => $conn->query("SELECT COUNT(*) FROM generated_emails WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
    'old' => $conn->query("SELECT COUNT(*) FROM generated_emails WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
];

// Get all generated emails with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$search_query = '';
$search_params = [];

if (!empty($search)) {
    $search_query = "WHERE email_address LIKE :search";
    $search_params = ['search' => "%$search%"];
}

$total_emails = $conn->prepare("SELECT COUNT(*) FROM generated_emails $search_query");
$total_emails->execute($search_params);
$total_count = $total_emails->fetchColumn();
$total_pages = ceil($total_count / $per_page);

$stmt = $conn->prepare("SELECT *, UNIX_TIMESTAMP(created_at) as timestamp FROM generated_emails $search_query ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($search_params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$generated_emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Email - Admin Panel</title>
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .delete-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .delete-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
        }
        
        .delete-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .stats-grid-small {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card-small {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-card-small h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .stat-card-small .number {
            font-size: 28px;
            font-weight: bold;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .checkbox-cell {
            text-align: center;
            width: 40px;
        }
        
        .select-all-row {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        
        .pagination a:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .pagination .current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .form-inline {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .form-inline input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>🗑️ Delete Email</h1>
            <div class="admin-nav">
                <a href="index.php">← Kembali ke Dashboard</a>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </header>

        <?php if ($success_msg): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert alert-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="stats-grid-small">
            <div class="stat-card-small">
                <h4>Total Email</h4>
                <div class="number"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card-small">
                <h4>Hari Ini</h4>
                <div class="number"><?php echo $stats['today']; ?></div>
            </div>
            <div class="stat-card-small">
                <h4>Minggu Ini</h4>
                <div class="number"><?php echo $stats['this_week']; ?></div>
            </div>
            <div class="stat-card-small">
                <h4>Lebih dari 7 Hari</h4>
                <div class="number"><?php echo $stats['old']; ?></div>
            </div>
        </div>

        <div class="delete-section">
            <h3>⚡ Quick Delete Actions</h3>
            <div class="delete-buttons">
                <form method="POST" onsubmit="return confirm('Hapus email yang lebih dari 7 hari?')" style="margin: 0;">
                    <input type="hidden" name="action" value="delete_old">
                    <input type="hidden" name="days" value="7">
                    <button type="submit" class="btn-warning" style="width: 100%;">
                        🕐 Hapus Email > 7 Hari<br>
                        <small>(<?php echo $stats['old']; ?> email)</small>
                    </button>
                </form>
                
                <form method="POST" onsubmit="return confirm('Hapus SEMUA email? Tindakan ini tidak bisa dibatalkan!')" style="margin: 0;">
                    <input type="hidden" name="action" value="delete_all">
                    <button type="submit" class="btn-danger" style="width: 100%;">
                        💣 Hapus Semua Email<br>
                        <small>(<?php echo $stats['total']; ?> email)</small>
                    </button>
                </form>
            </div>
        </div>

        <div class="delete-section">
            <h3>🔍 Delete by Pattern</h3>
            <form method="POST" class="form-inline" onsubmit="return confirm('Hapus semua email yang mengandung pattern ini?')">
                <input type="hidden" name="action" value="delete_by_pattern">
                <input type="text" name="pattern" placeholder="Contoh: test, user, .smith" required style="flex: 1; min-width: 200px;">
                <button type="submit" class="btn-warning">🔍 Hapus by Pattern</button>
            </form>
            <div class="form-help" style="margin-top: 10px;">
                Contoh: "test" akan menghapus test1@..., test2@..., testing@..., dll.
            </div>
        </div>

        <div class="delete-section">
            <h3>📋 Manage Email (<?php echo $total_count; ?> email)</h3>
            
            <div class="search-box">
                <form method="GET">
                    <input type="text" name="search" placeholder="🔍 Cari email..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>

            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete_multiple">
                
                <div style="margin-bottom: 15px;">
                    <button type="button" onclick="selectAll()" class="btn-primary" style="margin-right: 10px;">✓ Select All</button>
                    <button type="button" onclick="deselectAll()" class="btn-primary" style="margin-right: 10px;">✗ Deselect All</button>
                    <button type="submit" class="btn-danger" onclick="return confirm('Hapus email yang dipilih?')">🗑️ Delete Selected</button>
                    <span id="selectedCount" style="margin-left: 15px; font-weight: 600;"></span>
                </div>

                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th class="checkbox-cell">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll(this)">
                                </th>
                                <th>Email Address</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($generated_emails)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 40px;">
                                        Tidak ada email<?php echo !empty($search) ? " yang cocok dengan pencarian" : ""; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($generated_emails as $email): ?>
                                    <tr>
                                        <td class="checkbox-cell">
                                            <input type="checkbox" name="email_ids[]" value="<?php echo $email['id']; ?>" class="email-checkbox">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($email['email_address']); ?></strong>
                                        </td>
                                        <td class="local-time" data-timestamp="<?php echo htmlspecialchars($email['created_at']); ?>" data-unix="<?php echo $email['timestamp']; ?>">
                                            <?php echo date('d/m/Y H:i', strtotime($email['created_at'])); ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus email ini?')">
                                                <input type="hidden" name="action" value="delete_single">
                                                <input type="hidden" name="email_id" value="<?php echo $email['id']; ?>">
                                                <button type="submit" class="btn-delete">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">« First</a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">‹ Prev</a>
                <?php endif; ?>
                
                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next ›</a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Last »</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleAll(checkbox) {
            const checkboxes = document.querySelectorAll('.email-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateSelectedCount();
        }
        
        function selectAll() {
            const checkboxes = document.querySelectorAll('.email-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
            document.getElementById('selectAllCheckbox').checked = true;
            updateSelectedCount();
        }
        
        function deselectAll() {
            const checkboxes = document.querySelectorAll('.email-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            document.getElementById('selectAllCheckbox').checked = false;
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const checked = document.querySelectorAll('.email-checkbox:checked').length;
            const total = document.querySelectorAll('.email-checkbox').length;
            document.getElementById('selectedCount').textContent = 
                checked > 0 ? `${checked} dari ${total} email dipilih` : '';
        }
        
        // Update count on checkbox change
        document.querySelectorAll('.email-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });
        
        // Format date to local time
        function formatDateLocal(dateString, unixTimestamp) {
            let date;
            if (unixTimestamp) {
                date = new Date(unixTimestamp * 1000);
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
