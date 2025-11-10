<?php
session_start();

// Check if logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../config.php';
require_once '../functions.php';
require_once '../faker_names.php';

$success_msg = '';
$error_msg = '';
$generated_emails = [];

// Handle bulk email generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_bulk') {
    $count = intval($_POST['count']);
    $prefix = trim($_POST['prefix'] ?? '');
    $use_random = isset($_POST['use_random']);
    $use_faker = isset($_POST['use_faker']);
    $faker_type = $_POST['faker_type'] ?? 'name';
    
    // Validate count
    if ($count < 1 || $count > 1000) {
        $error_msg = "Jumlah email harus antara 1 sampai 1000!";
    } else {
        $conn = getDBConnection();
        $domain = EMAIL_DOMAIN;
        $success_count = 0;
        $duplicate_count = 0;
        
        // Initialize faker if needed
        $faker = null;
        if ($use_faker) {
            $faker = new SimpleFaker();
        }
        
        try {
            $conn->beginTransaction();
            
            for ($i = 0; $i < $count; $i++) {
                if ($use_faker && $faker) {
                    // Generate faker-based email
                    $email_local = $faker->generateUsername($faker_type);
                } elseif ($use_random) {
                    // Generate random email
                    $random_string = bin2hex(random_bytes(8)); // 16 character random string
                    $email_local = $prefix . $random_string;
                } else {
                    // Generate sequential email
                    $email_local = $prefix . ($i + 1);
                }
                
                $full_email = $email_local . $domain;
                
                try {
                    $stmt = $conn->prepare("INSERT INTO generated_emails (email_address, created_at) VALUES (:email, NOW())");
                    $stmt->execute(['email' => $full_email]);
                    $generated_emails[] = $full_email;
                    $success_count++;
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        // Duplicate entry - try again with different name
                        $duplicate_count++;
                        if ($use_faker && $i < $count - 1) {
                            // Retry with different faker name
                            $i--;
                        }
                    } else {
                        throw $e;
                    }
                }
            }
            
            $conn->commit();
            
            if ($success_count > 0) {
                $success_msg = "Berhasil generate $success_count email!";
                if ($duplicate_count > 0) {
                    $success_msg .= " ($duplicate_count email sudah ada sebelumnya)";
                }
            } else {
                $error_msg = "Tidak ada email yang berhasil dibuat. Semua email sudah ada.";
            }
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

// Get current statistics
$conn = getDBConnection();
$total_generated = $conn->query("SELECT COUNT(*) FROM generated_emails")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Email Massal - Admin Panel</title>
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .generate-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input[type="number"],
        .form-group input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .form-group input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .form-help {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .generated-list {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 30px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .generated-list h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
        }
        
        .email-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .email-item span {
            font-family: monospace;
            font-size: 13px;
        }
        
        .btn-copy-small {
            background: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-copy-small:hover {
            background: #0056b3;
        }
        
        .btn-copy-all {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .btn-copy-all:hover {
            background: #218838;
        }
        
        .stats-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .stats-info strong {
            font-size: 24px;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>📧 Generate Email Massal</h1>
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

        <div class="stats-info">
            <p>Total Email Ter-generate: <strong><?php echo $total_generated; ?></strong></p>
        </div>

        <div class="generate-form">
            <h2>Generate Email Baru</h2>
            <form method="POST">
                <input type="hidden" name="action" value="generate_bulk">
                
                <div class="form-group">
                    <label for="count">Jumlah Email (Max: 1000)</label>
                    <input type="number" id="count" name="count" min="1" max="1000" value="0" required>
                    <div class="form-help">Masukkan jumlah email yang ingin di-generate (1-1000)</div>
                </div>
                
                <div class="form-group">
                    <label for="prefix">Prefix (Opsional)</label>
                    <input type="text" id="prefix" name="prefix" placeholder="user" pattern="[a-zA-Z0-9_-]*" title="Hanya huruf, angka, dash, dan underscore">
                    <div class="form-help">Prefix untuk email, contoh: "user" akan menghasilkan user1, user2, dst.</div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="use_random" id="use_random">
                        Gunakan String Random
                    </label>
                    <div class="form-help">Generate email dengan string random (contoh: user3f2a1b4c5d6e7f8g)</div>
                </div>
                
                <div class="form-group" style="border: 2px solid #28a745; padding: 15px; border-radius: 8px; background: #f0fff4;">
                    <label style="color: #28a745; font-size: 16px;">
                        <input type="checkbox" name="use_faker" id="use_faker">
                        🎭 Use Faker Tools 
                    </label>
                    <div class="form-help" style="color: #155724; margin-bottom: 10px;">Generate email dengan nama-nama realistis seperti john.smith, sarah_jones, dll.</div>
                    
                    <div id="faker_options" style="display: none; margin-top: 10px; padding-left: 25px;">
                        <label style="font-weight: normal; color: #333;">Tipe Faker:</label>
                        <div style="margin-top: 8px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: normal;">
                                <input type="radio" name="faker_type" value="name" checked>
                                <strong>Name Based</strong> - john.smith, sarah_jones123
                            </label>
                            <label style="display: block; margin-bottom: 5px; font-weight: normal;">
                                <input type="radio" name="faker_type" value="combo">
                                <strong>Combo</strong> - cooluser123, super_gamer456
                            </label>
                            <label style="display: block; margin-bottom: 5px; font-weight: normal;">
                                <input type="radio" name="faker_type" value="word">
                                <strong>Word</strong> - john1234, michael567
                            </label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary" style="width: 100%; padding: 15px; font-size: 16px;">
                    🚀 Generate Email
                </button>
            </form>
        </div>

        <?php if (!empty($generated_emails)): ?>
        <div class="generated-list">
            <h3>✅ Email yang Baru Dibuat (<?php echo count($generated_emails); ?>)</h3>
            <button onclick="copyAllEmails()" class="btn-copy-all">📋 Copy Semua Email</button>
            
            <div id="email-list">
                <?php foreach ($generated_emails as $email): ?>
                <div class="email-item">
                    <span><?php echo htmlspecialchars($email); ?></span>
                    <button onclick="copyToClipboard('<?php echo htmlspecialchars($email); ?>')" class="btn-copy-small">Copy</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Email copied: ' + text);
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        }
        
        function copyAllEmails() {
            const emails = <?php echo json_encode($generated_emails); ?>;
            const emailText = emails.join('\n');
            
            navigator.clipboard.writeText(emailText).then(() => {
                alert('Semua email berhasil di-copy! (' + emails.length + ' email)');
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Gagal copy email');
            });
        }
        
        // Toggle faker options
        document.getElementById('use_faker').addEventListener('change', function() {
            const fakerOptions = document.getElementById('faker_options');
            const useRandom = document.getElementById('use_random');
            const prefixInput = document.getElementById('prefix');
            
            if (this.checked) {
                fakerOptions.style.display = 'block';
                // Disable other options when faker is enabled
                useRandom.disabled = true;
                useRandom.checked = false;
                prefixInput.disabled = true;
                prefixInput.value = '';
            } else {
                fakerOptions.style.display = 'none';
                // Enable other options
               = false;
              ut.disabled = false;
            }
        });
      
     Toix fieldrandom checkbox
        document.getElementById('use_raEventListener('change', function(
     stput = docum.getElementById('prefix');
            if (this.checked) {
                prefixInput.placeholder = 'user (opsional)';
            } else {
                prefixInput.placeholder = 'user';
            }
        });
    </script>
</body>
</html>
