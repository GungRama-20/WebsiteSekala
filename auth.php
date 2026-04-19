<?php
// ============================================================
// auth.php — Middleware Cek Status Login
//
// CARA PAKAI:
//   Untuk halaman WAJIB login → include di bagian paling atas:
// require_once 'includes/auth.php';
//   requireLogin();
//
//   Untuk cek login tanpa redirect:
//   isLoggedIn() → return true/false
// ============================================================

require_once __DIR__ . '/config.php';

/**
 * Cek apakah user sudah login
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['id_user']) && !empty($_SESSION['id_user']);
}

/**
 * Wajib login — redirect ke signin.php jika belum login
 * Menyimpan URL tujuan agar bisa redirect balik setelah login
 * @param string $redirectAfter URL tujuan setelah login (default: current URL)
 */
function requireLogin($redirectAfter = null) {
    if (!isLoggedIn()) {
        // Simpan URL yang ingin diakses
        if ($redirectAfter === null) {
            $redirectAfter = $_SERVER['REQUEST_URI'];
        }
        $_SESSION['redirect_after_login'] = $redirectAfter;
        $_SESSION['flash_message']        = 'Silakan login terlebih dahulu untuk melanjutkan.';
        $_SESSION['flash_type']           = 'warning';

        redirect('signin.php');
    }
}

/**
 * Simpan flash message ke session
 */
function setFlash($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type']    = $type;
}

/**
 * Ambil & hapus flash message dari session
 * @return array|null ['message'=>..., 'type'=>...]
 */
function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = [
            'message' => $_SESSION['flash_message'],
            'type'    => $_SESSION['flash_type'] ?? 'info',
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $flash;
    }
    return null;
}

/**
 * Ambil data user yang sedang login dari session
 * @return array
 */
function getCurrentUser() {
    if (!isLoggedIn()) return [];
    return [
        'id'    => $_SESSION['id_user'],
        'nama'  => $_SESSION['nama'],
        'email' => $_SESSION['email'],
        'role'  => $_SESSION['role'] ?? '',
    ];
}
