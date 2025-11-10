# 📧 Bulk Email Generator - Dokumentasi

Fitur generate email massal memungkinkan admin untuk membuat hingga 1000 email temporary sekaligus.

## 🎯 Fitur Utama

- Generate 1-1000 email dalam satu kali proses
- Dua mode: Sequential dan Random
- Custom prefix untuk email
- Copy semua email dengan satu klik
- Validasi duplikat otomatis
- Transaction-safe (rollback jika error)
- Real-time statistics

## 📋 Cara Menggunakan

### 1. Akses Halaman Generate

**Dari Dashboard:**
- Login ke admin panel
- Klik card "Generate Email Massal" (warna ungu)

**Dari Menu:**
- Login ke admin panel
- Klik menu "📧 Generate Email Massal" di header

### 2. Atur Parameter

#### Jumlah Email (Required)
- Minimum: 1 email
- Maximum: 1000 email
- Default: 10 email

#### Prefix (Optional)
- Format: huruf, angka, dash (-), underscore (_)
- Contoh: `user`, `test`, `demo`, `temp`
- Jika kosong: email akan dimulai langsung dengan angka/random

#### Mode Generate

**Sequential Mode (Default):**
- Email dibuat dengan nomor urut
- Contoh dengan prefix "user": user1, user2, user3, ...
- Contoh tanpa prefix: 1, 2, 3, ...

**Random Mode:**
- Email dibuat dengan string random 16 karakter
- Contoh dengan prefix "user": user3f2a1b4c5d6e7f8g, usera7d9e2f1b8c4d5e6, ...
- Contoh tanpa prefix: 3f2a1b4c5d6e7f8g, a7d9e2f1b8c4d5e6, ...

**🎭 Faker Mode (NEW!):**
- Email dibuat dengan nama-nama realistis
- 3 tipe faker:
  - **Name Based**: john.smith, sarah_jones123, michael.brown
  - **Combo**: cooluser123, super_gamer456, prodev789
  - **Word**: john1234, sarah567, michael890
- Otomatis disable prefix dan random mode
- Lebih natural dan realistis untuk testing

### 3. Generate & Copy

1. Klik tombol "🚀 Generate Email"
2. Tunggu proses selesai (biasanya < 5 detik untuk 1000 email)
3. Lihat hasil di bagian bawah
4. Klik "📋 Copy Semua Email" untuk copy semua
5. Atau klik "Copy" pada masing-masing email

## 💡 Contoh Penggunaan

### Contoh 1: Testing dengan Sequential
```
Jumlah: 10
Prefix: test
Random: ✗

Hasil:
test1@alrelshop.my.id
test2@alrelshop.my.id
test3@alrelshop.my.id
...
test10@alrelshop.my.id
```

### Contoh 2: Production dengan Random
```
Jumlah: 100
Prefix: user
Random: ✓

Hasil:
user3f2a1b4c5d6e7f8g@alrelshop.my.id
usera7d9e2f1b8c4d5e6@alrelshop.my.id
user8c4b5a6d9e2f1b3@alrelshop.my.id
...
(100 email dengan random string)
```

### Contoh 3: Bulk Testing tanpa Prefix
```
Jumlah: 50
Prefix: (kosong)
Random: ✗

Hasil:
1@alrelshop.my.id
2@alrelshop.my.id
3@alrelshop.my.id
...
50@alrelshop.my.id
```

### Contoh 4: Secure Random tanpa Prefix
```
Jumlah: 20
Prefix: (kosong)
Random: ✓

Hasil:
3f2a1b4c5d6e7f8g@alrelshop.my.id
a7d9e2f1b8c4d5e6@alrelshop.my.id
...
(20 email dengan random string)
```

### Contoh 5: Faker - Name Based 🎭
```
Jumlah: 10
Faker: ✓
Tipe: Name Based

Hasil:
john.smith@alrelshop.my.id
sarah_jones123@alrelshop.my.id
michael.brown@alrelshop.my.id
emma.garcia@alrelshop.my.id
david_miller456@alrelshop.my.id
...
(10 email dengan nama realistis)
```

### Contoh 6: Faker - Combo 🎭
```
Jumlah: 10
Faker: ✓
Tipe: Combo

Hasil:
cooluser123@alrelshop.my.id
super_gamer456@alrelshop.my.id
megadev789@alrelshop.my.id
promaster321@alrelshop.my.id
ultraplayer654@alrelshop.my.id
...
(10 email dengan kombinasi kata)
```

### Contoh 7: Faker - Word 🎭
```
Jumlah: 10
Faker: ✓
Tipe: Word

Hasil:
john1234@alrelshop.my.id
sarah5678@alrelshop.my.id
michael_9012@alrelshop.my.id
emma.3456@alrelshop.my.id
david7890@alrelshop.my.id
...
(10 email dengan nama + angka)
```

## ⚙️ Technical Details

### Database
Email disimpan di table `generated_emails`:
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

### Validasi
- Duplikat email otomatis di-skip
- Format email divalidasi sebelum insert
- Transaction rollback jika terjadi error fatal

### Performance
- 1000 email: ~3-5 detik
- 100 email: ~0.5-1 detik
- 10 email: ~0.1 detik

### Security
- Session-based authentication
- SQL injection protection (PDO prepared statements)
- XSS protection (htmlspecialchars)
- Input validation & sanitization

## 🔍 Troubleshooting

### Error: "Jumlah email harus antara 1 sampai 1000"
**Solusi:** Pastikan input jumlah antara 1-1000

### Error: "Email sudah ada"
**Penyebab:** Email dengan format yang sama sudah pernah di-generate
**Solusi:** 
- Gunakan prefix yang berbeda
- Aktifkan mode Random
- Hapus email lama dari database

### Error: "Format email tidak valid"
**Penyebab:** Prefix mengandung karakter tidak valid
**Solusi:** Gunakan hanya huruf, angka, dash (-), dan underscore (_)

### Tidak ada email yang berhasil dibuat
**Penyebab:** Semua email sudah ada di database
**Solusi:**
- Cek di dashboard berapa total email ter-generate
- Gunakan prefix baru atau mode random
- Hapus email lama jika tidak diperlukan

## 📊 Best Practices

### Untuk Testing
- Gunakan sequential mode dengan prefix jelas (test, demo, dev)
- Jumlah kecil (10-50 email)
- Mudah di-track dan di-manage

### Untuk Production
- Gunakan random mode untuk keamanan
- Prefix yang meaningful (user, customer, temp)
- Generate sesuai kebutuhan

### Untuk Load Testing
- Generate 500-1000 email
- Gunakan random mode
- Monitor database performance

### Untuk Demo/Presentation 🎭
- **Gunakan Faker mode** untuk email yang terlihat lebih profesional
- Tipe "Name Based" paling realistis
- Cocok untuk screenshot, video demo, atau presentasi
- Email terlihat seperti user real

## 🎓 Tips & Tricks

1. **Copy Cepat**: Gunakan tombol "Copy Semua Email" untuk copy semua sekaligus
2. **Prefix Meaningful**: Gunakan prefix yang menjelaskan tujuan (test, prod, demo)
3. **Random untuk Security**: Gunakan random mode jika email akan digunakan untuk testing security
4. **Sequential untuk Debug**: Gunakan sequential mode untuk mudah di-track saat debugging
5. **Batch Processing**: Generate dalam batch kecil (100-200) untuk performa optimal
6. **🎭 Faker untuk Demo**: Gunakan faker mode saat membuat demo atau presentasi untuk tampilan lebih profesional
7. **Name Based = Realistis**: Tipe "Name Based" menghasilkan email paling natural (john.smith, sarah_jones)
8. **Combo = Fun**: Tipe "Combo" cocok untuk gaming atau komunitas (cooluser123, supergamer456)
9. **Word = Simple**: Tipe "Word" paling sederhana dan mudah diingat (john1234, sarah567)

## 📝 Changelog

### Version 1.1 (November 2025)
- ✅ 🎭 **NEW: Faker Mode** - Generate email dengan nama realistis
- ✅ 3 tipe faker: Name Based, Combo, Word
- ✅ Auto-disable conflicting options
- ✅ Improved duplicate handling for faker

### Version 1.0 (November 2025)
- ✅ Initial release
- ✅ Sequential mode
- ✅ Random mode
- ✅ Bulk copy feature
- ✅ Real-time statistics
- ✅ Duplicate validation
- ✅ Transaction safety

## 🔗 Related Documentation

- [README.md](README.md) - Main documentation
- [ADMIN_GUIDE.md](ADMIN_GUIDE.md) - Admin panel guide
- [SECURITY.md](SECURITY.md) - Security documentation

## 💬 Support

Jika ada pertanyaan atau masalah:
1. Cek dokumentasi ini
2. Cek [README.md](README.md) untuk troubleshooting umum
3. Cek database: `SELECT COUNT(*) FROM generated_emails;`
4. Cek log error di browser console

---

**Happy Generating! 🚀**
