# SEKALA — Panduan Setup & Struktur File
## Platform Request Konten Digital UMKM

---

## 📁 STRUKTUR FILE LENGKAP

```
sekala/
│
├── config.php              ← Koneksi database MySQL
│
├── index.php               ← Landing Page + Dashboard (BEBAS akses)
├── detail_paket.php        ← Detail Paket (BEBAS akses)
├── form_request.php        ← Form Pemesanan (WAJIB LOGIN)
├── pembayaran.php          ← Pembayaran (WAJIB LOGIN)
├── selesai.php             ← Halaman sukses pembayaran
│
├── signin.php              ← Halaman Login
├── signup.php              ← Halaman Register
├── logout.php              ← Proses Logout
│
├── includes/
│   ├── auth.php            ← Middleware cek login (isLoggedIn, requireLogin)
│   └── navbar.php          ← Navbar dinamis (login/logout state)
│
├── assets/
│   ├── css/
│   │   ├── style.css       ← Global variables & base styles
│   │   ├── dashboard.css   ← Styles khusus landing/dashboard
│   │   └── pages.css       ← Styles detail, form, payment, done
│   └── js/
│       └── main.js         ← Smooth scroll & interaksi frontend
│
└── uploads/                ← Folder upload file (buat manual, chmod 755)
```

---

## 🚀 CARA SETUP

### 1. Import Database
```sql
-- Buka phpMyAdmin → Import file: sekala.sql
-- Atau jalankan lewat terminal:
mysql -u root -p < sekala.sql
```

### 2. Konfigurasi Database
Edit file `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // User MySQL Anda
define('DB_PASS', '');           // Password MySQL Anda
define('DB_NAME', 'sekala');
```

### 3. Buat Folder Upload
```bash
mkdir uploads
chmod 755 uploads
```

### 4. Buat User Test (Opsional)
Jalankan SQL ini di phpMyAdmin untuk membuat akun test:
```sql
INSERT INTO tb_user (nama, email, password, role, created_at)
VALUES (
    'Admin SEKALA',
    'admin@sekala.id',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password: password
    'Admin',
    NOW()
);
```

---

## 🔒 SISTEM AUTH & PROTEKSI

### Halaman BEBAS akses (tidak butuh login):
- `index.php` — Landing page + dashboard
- `detail_paket.php` — Detail paket (bisa dilihat siapa saja)
- `signin.php`, `signup.php` — Halaman auth

### Halaman WAJIB LOGIN:
- `form_request.php` — Klik "Mulai Request" → cek login dulu
- `pembayaran.php` — Setelah isi form

### Cara pakai di halaman yang wajib login:
```php
require_once 'config.php';
require_once 'includes/auth.php';

// Tambahkan baris ini di PALING ATAS halaman
requireLogin();
```

---

## 🔄 ALUR USER (FLOW)

```
index.php (bebas)
    ↓ pilih paket
detail_paket.php (bebas)
    ↓ klik "Mulai Request Konten"
    │
    ├── [Belum login] → signin.php
    │       ↓ login sukses
    │       ↓ redirect kembali ke form_request.php
    │
    └── [Sudah login] → form_request.php (protected)
            ↓ isi form & submit
        pembayaran.php (protected)
            ↓ pilih metode & bayar
        selesai.php
            ↓ kembali dashboard atau lihat portofolio
```

---

## 🧩 FUNGSI PENTING

### `includes/auth.php`
| Fungsi | Kegunaan |
|--------|----------|
| `isLoggedIn()` | Cek apakah user sudah login (return bool) |
| `requireLogin($url)` | Paksa login, redirect ke signin jika belum |
| `getCurrentUser()` | Ambil data user aktif dari session |
| `setFlash($msg, $type)` | Simpan pesan flash ke session |
| `getFlash()` | Ambil & hapus flash message |

### `config.php`
| Fungsi | Kegunaan |
|--------|----------|
| `e($conn, $str)` | Escape input untuk anti SQL injection |
| `redirect($url)` | Redirect ke URL |
| `formatRupiah($angka)` | Format angka ke Rupiah |

---

## 📊 TABEL DATABASE YANG DIGUNAKAN

| Tabel | Kegunaan |
|-------|----------|
| `tb_user` | Data login user |
| `tb_pelanggan` | Data pelanggan (dibuat otomatis dari form) |
| `tb_desain` | Data paket / jenis desain |
| `tb_pesanan` | Data pesanan yang masuk |
| `tb_pembayaran` | Data pembayaran |
| `tb_portofolio` | Karya yang ditampilkan di dashboard |
| `tb_testimoni` | Ulasan pelanggan |

---

## ⚙️ TAMBAHAN FIELD tb_user

Saat ini `role` di tb_user hanya ada 'Admin'.
Tambahkan role 'pelanggan' untuk enum:
```sql
ALTER TABLE tb_user MODIFY role ENUM('Admin','pelanggan','') NOT NULL DEFAULT 'pelanggan';
```

---

## 💡 CATATAN PENTING

1. **password_hash()** digunakan saat register — sudah aman
2. **password_verify()** digunakan saat login — cocok dengan hash
3. Flash message otomatis hilang setelah ditampilkan
4. Setelah login, user diarahkan ke halaman yang dituju sebelumnya
5. Folder `uploads/` harus bisa ditulis oleh web server

---

© 2025 SEKALA — Platform Konten Digital UMKM
