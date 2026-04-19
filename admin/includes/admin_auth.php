<?php
// ============================================================
// admin/includes/admin_auth.php — Proteksi Halaman Admin
// Role 'Admin' sesuai ENUM database: ENUM('Admin','Pelanggan')
// ============================================================
require_once __DIR__ . '/../../config.php';

/**
 * Wajib login sebagai Admin, redirect jika bukan
 */
function requireAdmin() {
    // Pastikan session sudah aktif
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Belum login
    if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
        header('Location: ../signin.php');
        exit;
    }

    // Bukan Admin ('Admin' kapital sesuai ENUM database)
    if (($_SESSION['role'] ?? '') !== 'Admin') {
        header('Location: ../signin.php');
        exit;
    }
}

requireAdmin();
