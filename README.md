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

### Admin Features:
- 🔐 Admin panel dengan login
- ✏️ Buat custom email (contoh: support@alrelshop.my.id)
- 📊 Dashboard statistik
- 👀 Monitor semua email masuk
- 🗑️ Hapus email manual
- 📋 Copy email dengan satu klik

## 🚀 Quick Start

### Akses Website:
- **User**: https://tempmail.alrelshop.my.id
- **Admin**: https://tempmail.alrelshop.my.id/admin/

### Login Admin:
- **Username**: `admin`
- **Password**: `admin123`

## 📁 File Structure

```
/www/wwwroot/tempmail.alrelshop.my.id/
├── index.php              # Halaman utama user
├── api.php                # API endpoint
├── config.php             # Konfigurasi database
├── functions.php          # Helper functions
├── receive_email.php      # Script penerima email (Postfix)
├── test_email.php         # Form test email
├── clean_emails.php       # Tool pembersih email
├── style.css              # User styling
├── script.js              # User JavaScript
├── admin/
│   ├── index.php          # Admin dashboard
│   ├── login.php          # Admin login
│   ├── logout.php         # Admin logout
│   └── admin-style.css    # Admin styling
└── database.sql           # SQL schema
```

## 🔧 Konfigurasi

### Database (config.php):
```php
<?php
$host = 'localhost';
$dbname = 'XXXXX';
$username = 'XXXXX';
$password = 'XXXXX';
?>
```

### Postfix Configuration:
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

- [ADMIN_GUIDE.md](ADMIN_GUIDE.md) - Panduan lengkap admin panel
- [STATUS.md](STATUS.md) - Status instalasi dan troubleshooting

## 🎯 Cara Menggunakan

### Untuk User:
1. Buka https://tempmail.alrelshop.my.id
2. Email otomatis di-generate
3. Copy email dan gunakan untuk registrasi
4. Email masuk akan muncul otomatis
5. Klik email untuk melihat isi lengkap

### Untuk Admin:
1. Login ke https://tempmail.alrelshop.my.id/admin/
2. Buat custom email (contoh: `support`)
3. Email akan dibuat: `support@alrelshop.my.id`
4. Monitor semua email masuk di dashboard
5. Kelola dan hapus email jika diperlukan

## 🛠️ Troubleshooting

**Email tidak masuk?**
```bash
# Cek Postfix status
sudo systemctl status postfix

# Cek mail log
sudo tail -f /var/log/mail.log

# Test kirim email lokal
echo "Test" | mail -s "Test" test@alrelshop.my.id
```

**Cek email di database:**
```bash
mysql -u madev -pmadev madev -e "SELECT * FROM emails ORDER BY id DESC LIMIT 5;"
```

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
    created_at DATETIME NOT NULL,
    last_accessed DATETIME,
    access_count INT DEFAULT 0,
    INDEX idx_email (email_address)
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
