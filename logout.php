<?php
// ============================================================
// logout.php — Proses Logout SEKALA
// ============================================================
require_once 'config.php';
require_once 'auth.php';

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy session
session_destroy();

// Flash tidak bisa dipakai setelah session destroy, jadi redirect langsung
// dengan parameter URL sebagai notifikasi
header('Location: index.php?logout=1');
exit;
