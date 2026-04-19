# 🌐 SEKALA - Platform Request Konten Digital

## 📌 Deskripsi Project

**SEKALA** adalah platform berbasis web yang memungkinkan pengguna untuk melakukan pemesanan jasa pembuatan konten digital secara online dengan mudah, cepat, dan terstruktur.

Website ini dibangun menggunakan:

* PHP Native
* MySQL Database
* HTML, CSS, JavaScript

---

## 👥 Anggota Kelompok

| No | Nama                        | Jabatan |
| -- | --------------------------- | ------- |
| 1  | I Nyoman Ari Parwata        | CEO     |
| 2  | Dimas Haidzanur Safa        | HISPTER |
| 3  | Anak Agung Rama Dwi Saputra | HACKER  |
| 4  | Komang Swastika             | HUSTLER |
| 5  | I Putu Aditya Martha Wijaya | ANGGOTA |

---

## 🎯 Tujuan Pengembangan

* Mempermudah proses pemesanan jasa konten digital
* Menyediakan sistem manajemen order & pembayaran
* Memberikan pengalaman user yang sederhana dan efisien
* Menyediakan dashboard admin untuk monitoring data

---

## 🚀 Fitur Utama

### 👤 User (Customer)

* Registrasi & Login
* Melihat paket layanan
* Melakukan pemesanan
* Melakukan pembayaran
* Melihat detail pesanan
* Memberikan testimoni

---

### 🛠️ Admin Panel

* Dashboard
* Manajemen Data Customer
* Manajemen Data Pemesanan
* Manajemen Data Pembayaran
* Manajemen Portofolio
* Manajemen Testimoni
* Laporan
* Kelola Admin

---

## 🔄 Alur Sistem

1. User melakukan login
2. User memilih paket
3. User mengisi form pemesanan
4. Data masuk ke database
5. User diarahkan ke halaman pembayaran
6. User melakukan pembayaran
7. Status pesanan diperbarui
8. User melihat halaman “Pembayaran Berhasil”
9. User dapat memberikan testimoni

---

## 🗂️ Struktur Folder Project

```bash
WebsiteSekala/
│── admin/
│   ├── assets/
│   │   └── admin.css
│   ├── includes/
│   │   └── customers.php
│   ├── index.php
│   ├── kelola_admin.php
│   ├── laporan.php
│   ├── logout.php
│   ├── pembayaran.php
│   ├── pesanan.php
│   ├── portofolio.php
│   └── testimoni.php
│
│── assets/
│   ├── foto1.jpeg
│   ├── foto2.jpeg
│   ├── logo.png
│   ├── poster1.jpeg - poster5.jpeg
│
│── uploads/
│   ├── bukti_*.jpg
│   ├── logo_*.png
│
│── auth.php
│── config.php
│── dashboard.css
│── detail_paket.php
│── form_request.php
│── index.php
│── login.php / signin.php / signup.php
│── logout.php
│── navbar.php
│── pembayaran.php
│── selesai.php
│── semua_testimoni.php
│── testimoni_submit.php
│── style.css
│── pages.css
│── main.js
│
│── database/
│   ├── sekala_fixed.sql
│   ├── seed_testimoni.sql
│   ├── fix_testimoni.sql
│   ├── fix_autoincrement.sql
│   ├── update_admin_name.sql
│   └── update_porto_testi.sql
│
│── README.md
```

---

## 🧩 Teknologi yang Digunakan

* PHP Native
* MySQL
* JavaScript
* CSS Custom
* Chart.js (Admin Dashboard)

---

## 🔐 Sistem Login

* User dan Admin menggunakan halaman login yang sama
* Role-based access:

  * User → Dashboard User
  * Admin → Dashboard Admin
* Admin tidak bisa melakukan signup

---

## 💬 Fitur Testimoni

* User dapat mengisi testimoni setelah pesanan selesai
* Hanya 3 testimoni ditampilkan di halaman utama
* Tersedia halaman **“Lihat Semua Testimoni”**
* Admin dapat mengelola testimoni

---

## 📊 Laporan

Admin dapat:

* Melihat data transaksi
* Menghitung total pendapatan
* Export ke PDF
* Print laporan

---

## ⚙️ Cara Menjalankan Project

1. Install XAMPP / Laragon
2. Import database ke phpMyAdmin
3. Letakkan folder ke:

   ```
   htdocs/
   ```
4. Jalankan:

   ```
   http://localhost/WebsiteSekala
   ```

---

## 📌 Catatan

Project ini masih dapat dikembangkan lebih lanjut seperti:

* Integrasi payment gateway (Midtrans)
* Notifikasi WhatsApp
* Peningkatan UI/UX
* Keamanan sistem

---

## ✨ Penutup

SEKALA merupakan solusi digital untuk mempermudah layanan pemesanan konten dengan sistem terintegrasi antara customer dan admin.
