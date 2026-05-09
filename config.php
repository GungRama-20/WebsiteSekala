<?php
// ============================================================
// config.php — Koneksi Database MySQL
// Database: sekala (sesuai sekala.sql)
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Ganti dengan user MySQL Anda
define('DB_PASS', '');           // Ganti dengan password MySQL Anda
define('DB_NAME', 'sekala');
define('DB_CHARSET', 'utf8mb4');

// ---- Set Timezone WITA (UTC+7 sesuai jam lokal) ----
date_default_timezone_set('Asia/Jakarta');

// ---- Konfigurasi Login Google (OAuth 2.0) ----
// Dapatkan Client ID dan Secret di: https://console.cloud.google.com/
// ⚠️ GANTI dengan Client ID & Secret dari Google Cloud Console Anda!
define('GOOGLE_CLIENT_ID',     'GANTI_DENGAN_CLIENT_ID_GOOGLE_ANDA');
define('GOOGLE_CLIENT_SECRET', 'GANTI_DENGAN_CLIENT_SECRET_GOOGLE_ANDA');

// ---- Deteksi Environment (localhost vs production) ----
$isLocalhost = (
    isset($_SERVER['HTTP_HOST']) &&
    (
        $_SERVER['HTTP_HOST'] === 'localhost' ||
        strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
        strpos($_SERVER['HTTP_HOST'], 'localhost') !== false
    )
);

if ($isLocalhost) {
    // Redirect URI untuk development local
    define('GOOGLE_REDIRECT_URI', 'http://localhost/WebsiteSekala/google_callback.php');
    define('BASE_URL', 'http://localhost/WebsiteSekala/');
} else {
    // Redirect URI untuk production (domain hosting)
    define('GOOGLE_REDIRECT_URI', 'http://sekaladesain.free.nf/google_callback.php');
    define('BASE_URL', 'http://sekaladesain.free.nf/');
}

// ---- Buat koneksi MySQLi ----
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// ---- Cek koneksi ----
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
        <h2 style="color:#EF4444;">❌ Koneksi Database Gagal</h2>
        <p style="color:#64748B;">' . $conn->connect_error . '</p>
        <small>Pastikan MySQL berjalan dan konfigurasi di config.php sudah benar.</small>
    </div>');
}

// ---- Set charset ----
$conn->set_charset(DB_CHARSET);

// ---- Helper: Escape input (anti SQL injection) ----
function e($conn, $str) {
    return $conn->real_escape_string(trim($str));
}

// ---- Helper: Redirect ----
function redirect($url) {
    header("Location: $url");
    exit;
}

// ---- Helper: Format Rupiah ----
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// ---- Mulai session (jika belum aktif) ----
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
