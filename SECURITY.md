# Security Guide - Temporary Email System

## 🔐 Keamanan Admin

### Setup Awal

1. **Jalankan Setup Admin** (hanya sekali):
   ```
   https://tempmail.alrelshop.my.id/admin/setup_admin.php
   ```
   
2. **Login dengan kredensial default**:
   - Username: `admin`
   - Password: `admin123`

3. **SEGERA ubah password default**:
   - Masuk ke menu "Ubah Password"
   - Gunakan password yang kuat (minimal 6 karakter)

### Fitur Keamanan

✅ **Password Hashing**: Password disimpan dengan `password_hash()` PHP (bcrypt)
✅ **Database-based Auth**: Kredensial tidak hardcoded di file
✅ **Session Management**: Login menggunakan PHP session
✅ **SQL Injection Protection**: Menggunakan PDO prepared statements
✅ **XSS Protection**: Output di-escape dengan `htmlspecialchars()`
✅ **Last Login Tracking**: Mencatat waktu login terakhir
✅ **Active Status**: Admin bisa dinonaktifkan tanpa dihapus

### Kelola Admin

**Menambah Admin Baru**:
1. Login sebagai admin
2. Buka menu "Kelola Admin"
3. Tambahkan username, password, dan email
4. Admin baru bisa langsung login

**Menonaktifkan Admin**:
- Klik tombol "Nonaktifkan" di daftar admin
- Admin yang dinonaktifkan tidak bisa login
- Bisa diaktifkan kembali kapan saja

**Menghapus Admin**:
- Klik tombol "Hapus" di daftar admin
- Admin akan dihapus permanen dari database
- Tidak bisa menghapus akun sendiri

### Best Practices

1. **Password Kuat**:
   - Minimal 8-12 karakter
   - Kombinasi huruf besar, kecil, angka, simbol
   - Jangan gunakan password yang mudah ditebak

2. **Ganti Password Berkala**:
   - Ubah password setiap 3-6 bulan
   - Jangan gunakan password yang sama dengan akun lain

3. **Batasi Akses Admin**:
   - Hanya berikan akses admin ke orang yang dipercaya
   - Nonaktifkan admin yang sudah tidak diperlukan

4. **Monitor Login**:
   - Cek "Login Terakhir" di daftar admin
   - Jika ada aktivitas mencurigakan, segera ubah password

5. **Backup Database**:
   - Backup tabel `admin_users` secara berkala
   - Simpan backup di tempat yang aman

### Struktur Database

```sql
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    created_at DATETIME NOT NULL,
    last_login DATETIME,
    is_active TINYINT(1) DEFAULT 1
);
```

### Reset Password (Jika Lupa)

Jika lupa password, jalankan query SQL ini:

```sql
-- Reset password admin menjadi 'newpassword123'
UPDATE admin_users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE username = 'admin';
```

Atau generate hash baru dengan PHP:
```php
<?php
echo password_hash('password_baru_anda', PASSWORD_DEFAULT);
?>
```

### File Penting

- `admin/login.php` - Halaman login
- `admin/change_password.php` - Ubah password
- `admin/manage_admins.php` - Kelola admin
- `admin/setup_admin.php` - Setup awal (jalankan sekali)
- `admin/logout.php` - Logout

### Troubleshooting

**Tidak bisa login setelah setup?**
- Pastikan tabel `admin_users` sudah dibuat
- Cek apakah ada user admin di database:
  ```sql
  SELECT * FROM admin_users WHERE username = 'admin';
  ```

**Lupa password?**
- Jalankan query reset password di atas
- Atau jalankan ulang `setup_admin.php` (akan skip jika admin sudah ada)

**Error "Terjadi kesalahan sistem"?**
- Cek koneksi database di `config.php`
- Cek error log: `/var/log/php_errors.log`

## 🛡️ Keamanan Email

### Auto-cleanup
- Email otomatis dihapus setelah 24 jam
- Generated email dihapus setelah 7 hari tidak diakses

### Rate Limiting
- Pertimbangkan menambah rate limiting untuk prevent spam
- Bisa menggunakan IP-based atau session-based limiting

### Email Validation
- Hanya menerima email dengan domain `@alrelshop.my.id`
- Validasi format email sebelum disimpan

## 📞 Support

Jika ada masalah keamanan atau pertanyaan:
1. Cek dokumentasi ini
2. Cek `README.md` untuk troubleshooting umum
3. Cek log sistem untuk error details
