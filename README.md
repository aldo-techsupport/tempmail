# Temporary Email System - AlrelShop

Sistem temporary email yang dapat menerima email dari luar dengan domain @alrelshop.my.id

## ✨ Fitur Utama

### User Features:
- 📧 Generate email random otomatis
- 📬 Menerima email dari luar (real-time)
- 🔄 Auto-refresh inbox setiap 10 detik
- ♾️ Unlimited email generation (1000+)
- 🗄️ Database MySQL (bukan SQLite)
- 🧹 Auto-cleanup email lama (24 jam)
- 📱 Responsive design
- 🕐 Timestamp menggunakan waktu lokal komputer (bukan waktu server)
- 🔐 **Auto OTP Detection** - Otomatis mendeteksi dan extract kode OTP dari email
- 🌐 **Webhook Support** - Terima email via HTTP POST webhook

### Admin Features:
- 🔐 Admin panel dengan login (database-based, password hashed)
- ✏️ Buat custom email (contoh: support@alrelshop.my.id)
- 🚀 Generate email massal (hingga 1000 email sekaligus)
- 🎭 **Faker mode** - Generate email dengan nama realistis (john.smith, sarah_jones)
- 🗑️ **Delete email massal** - Hapus email secara bulk, by pattern, atau individual
- 📊 Dashboard statistik
- 👀 Monitor semua email masuk
- 📋 Copy email dengan satu klik
- 👥 Kelola multiple admin users
- 🔐 Ubah password dengan aman

## 🚀 Quick Start

### Akses Website:
- **User**: https://tempmail.alrelshop.my.id
- **Admin**: https://tempmail.alrelshop.my.id/admin/

### Login Admin:
- **Username**: `admin`
- **Password**: `admin123` (default - segera ubah setelah setup!)

### Setup Admin (Pertama Kali):
1. Jalankan: `https://tempmail.alrelshop.my.id/admin/setup_admin.php`
2. Login dengan kredensial default
3. Segera ubah password di menu "Ubah Password"

## 📁 File Structure

```
/www/wwwroot/tempmail.alrelshop.my.id/
├── index.php              # Halaman utama user
├── api.php                # API endpoint (with OTP endpoints)
├── config.php             # Konfigurasi database
├── functions.php          # Helper functions (with OTP extraction)
├── receive_email.php      # Script penerima email (Postfix)
├── webhook.php            # Webhook endpoint untuk terima email via HTTP POST
├── style.css              # User styling
├── script.js              # User JavaScript
├── admin/
│   ├── index.php          # Admin dashboard
│   ├── login.php          # Admin login
│   ├── logout.php         # Admin logout
│   └── admin-style.css    # Admin styling
├── database.sql           # SQL schema
└── create_otp_table.sql   # OTP table schema
```

## 🔧 Konfigurasi

### Database (config.php):
```php
<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'tempmail');
define('DB_PASS', 'alrel1408');
define('DB_NAME', 'tempmail');
define('EMAIL_DOMAIN', '@alrelshop.my.id');
?>
```

### Email Receiving Options:

#### Option 1: Webhook (RECOMMENDED)
Terima email via HTTP POST ke webhook endpoint:

**Endpoint**: `https://tempmail.alrelshop.my.id/webhook.php`

**Example**:
```bash
curl -X POST "https://tempmail.alrelshop.my.id/webhook.php" \
  -d "to=user@alrelshop.my.id" \
  -d "from=sender@example.com" \
  -d "subject=Your OTP is 123456" \
  -d "body=Your verification code is 123456"
```

**Response**:
```json
{
  "success": true,
  "message": "Email received and OTP extracted",
  "email_id": "15",
  "otp_code": "123456",
  "otp_detected": true
}
```

#### Option 2: Postfix Configuration
```bash
# Virtual alias domains
virtual_alias_domains = alrelshop.my.id

# Virtual alias maps
virtual_alias_maps = hash:/etc/postfix/virtual_alrelshop

# Alias
@alrelshop.my.id tempmail_receiver
tempmail_receiver: "|/usr/bin/php /www/wwwroot/tempmail.alrelshop.my.id/receive_email.php"
```

## 📖 Documentation

- [CARA_PAKAI_API_OTP.txt](CARA_PAKAI_API_OTP.txt) - Panduan lengkap API OTP (Indonesian)

## 🎯 Cara Menggunakan

### Untuk User:
1. Buka https://tempmail.alrelshop.my.id
2. Email otomatis di-generate
3. Copy email dan gunakan untuk registrasi
4. Email masuk akan muncul otomatis
5. Klik email untuk melihat isi lengkap
6. **OTP otomatis terdeteksi** dan ditampilkan jika ada

### API OTP:
```bash
# Get latest OTP from any email
curl "https://tempmail.alrelshop.my.id/api.php?action=get_latest_otp_global"

# Get OTP for specific email
curl "https://tempmail.alrelshop.my.id/api.php?action=get_latest_otp&email=user@alrelshop.my.id"

# Search OTP
curl "https://tempmail.alrelshop.my.id/api.php?action=search_otp&search=123456"
```

**Response Example**:
```json
{
  "success": true,
  "otp_code": "123456",
  "email_address": "user@alrelshop.my.id",
  "sender": "noreply@example.com",
  "subject": "Your verification code",
  "extracted_at": "2026-04-22 08:38:12",
  "is_used": 0
}
```

### Untuk Admin:
1. Login ke https://tempmail.alrelshop.my.id/admin/
2. Buat custom email (contoh: `support`)
3. Email akan dibuat: `support@alrelshop.my.id`
4. **Generate Email Massal**: Klik "Generate Email Massal" untuk membuat hingga 1000 email sekaligus
   - Pilih jumlah email (1-1000)
   - Tambahkan prefix (opsional)
   - Gunakan random string atau sequential number
   - Copy semua email yang di-generate dengan satu klik
5. Monitor semua email masuk di dashboard
6. Kelola dan hapus email jika diperlukan

## 🚀 Generate Email Massal

Fitur baru untuk admin yang memungkinkan generate banyak email sekaligus!

### Cara Menggunakan:
1. Login ke admin panel
2. Klik menu "📧 Generate Email Massal" atau card di dashboard
3. Atur parameter:
   - **Jumlah**: 1-1000 email
   - **Prefix**: Opsional (contoh: "user" → user1, user2, ...)
   - **Random String**: Centang untuk generate string random (contoh: user3f2a1b4c5d6e7f8g)
4. Klik "Generate Email"
5. Copy semua email yang dibuat dengan tombol "Copy Semua Email"

### Contoh Penggunaan:

**Sequential (dengan prefix):**
- Input: Jumlah=5, Prefix="test"
- Output: test1@..., test2@..., test3@..., test4@..., test5@...

**Random (dengan prefix):**
- Input: Jumlah=3, Prefix="user", Random=✓
- Output: user3f2a1b4c@..., usera7d9e2f1@..., user8c4b5a6d@...

**Random (tanpa prefix):**
- Input: Jumlah=2, Random=✓
- Output: 3f2a1b4c5d6e7f8g@..., a7d9e2f1b8c4d5e6@...

### Fitur:
- ✅ Generate hingga 1000 email sekaligus
- ✅ Validasi duplikat otomatis
- ✅ Copy semua email dengan satu klik
- ✅ Copy individual email
- ✅ Statistik real-time
- ✅ Transaction-safe (rollback jika error)

## 🛠️ Troubleshooting

### Email tidak masuk?

**Option 1: Test via Webhook (Recommended)**
```bash
curl -X POST "https://tempmail.alrelshop.my.id/webhook.php" \
  -d "to=test@alrelshop.my.id" \
  -d "from=test@example.com" \
  -d "subject=Test OTP 123456" \
  -d "body=Your code is 123456"
```

**Option 2: Check Postfix**
```bash
# Cek Postfix status
sudo systemctl status postfix

# Cek mail log
sudo tail -f /var/log/mail.log

# Test kirim email lokal
echo "Test" | mail -s "Test" test@alrelshop.my.id
```

### Check Database:
```bash
# Check recent emails
mysql -u tempmail -palrel1408 tempmail -e "SELECT * FROM emails ORDER BY received_at DESC LIMIT 5;"

# Check recent OTPs
mysql -u tempmail -palrel1408 tempmail -e "SELECT * FROM otp_codes ORDER BY extracted_at DESC LIMIT 5;"
```

### Check Logs:
```bash
tail -f webhook.log
```

### Current Statistics:
- Total Emails: 15
- Total OTPs: 14
- OTP Extraction Rate: 93.33%
- Status: ✅ WORKING

## 📊 Database Schema

### Table: emails
```sql
CREATE TABLE emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) DEFAULT 'No Subject',
    body TEXT,
    headers TEXT,
    received_at DATETIME NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    INDEX idx_to_email (to_email),
    INDEX idx_received_at (received_at)
);
```

### Table: generated_emails
```sql
CREATE TABLE generated_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_address VARCHAR(255) NOT NULL UNIQUE,
    access_token VARCHAR(64) UNIQUE,
    created_at DATETIME NOT NULL,
    last_accessed DATETIME,
    access_count INT DEFAULT 0,
    INDEX idx_email (email_address),
    INDEX idx_token (access_token)
);
```

### Table: otp_codes (NEW)
```sql
CREATE TABLE otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_address VARCHAR(255) NOT NULL,
    otp_code VARCHAR(20) NOT NULL,
    sender VARCHAR(255),
    subject VARCHAR(500),
    email_id INT,
    extracted_at DATETIME NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    used_at DATETIME,
    INDEX idx_email (email_address),
    INDEX idx_otp (otp_code),
    INDEX idx_extracted_at (extracted_at),
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE
);
```

## 🔒 Security

- Admin panel dilindungi dengan login
- Session-based authentication
- SQL injection protection (PDO prepared statements)
- XSS protection (htmlspecialchars)
- Auto-cleanup email lama

## 📝 License

Free to use for personal and commercial projects.

## 👨‍💻 Support

Jika ada masalah:
1. Cek [STATUS.md](STATUS.md) untuk troubleshooting
2. Cek [ADMIN_GUIDE.md](ADMIN_GUIDE.md) untuk panduan admin
3. Cek log: `/var/log/mail.log`
