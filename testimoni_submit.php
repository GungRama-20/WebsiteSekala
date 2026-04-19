<?php
// ============================================================
// testimoni_submit.php — Handler Submit Testimoni
// Bisa dipanggil dari: selesai.php, detail_paket.php, semua_testimoni.php
// ============================================================
require_once 'config.php';
require_once 'auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$id_pelanggan = (int)($_POST['id_pelanggan'] ?? 0);
$id_pesanan   = (int)($_POST['id_pesanan']   ?? 0);
$rating       = max(1, min(5, (int)($_POST['rating'] ?? 5)));
$isi          = trim($_POST['isi_testimoni'] ?? '');
$redirect_to  = trim($_POST['redirect_to']  ?? '');

// Validasi redirect tujuan
$allowed_redirects = ['selesai.php', 'index.php', 'semua_testimoni.php'];
if (preg_match('/^detail_paket\.php\?id=\d+$/', $redirect_to)) {
    $allowed_redirects[] = $redirect_to;
}
if (!in_array($redirect_to, $allowed_redirects)) {
    $redirect_to = 'index.php';
}

// Validasi data wajib
if ($id_pelanggan <= 0 || empty($isi)) {
    setFlash('Data testimoni tidak lengkap.', 'error');
    redirect($redirect_to ?: 'index.php');
}

$tanggal = date('Y-m-d H:i:s');

// Cek apakah kolom id_pesanan ada di tb_testimoni
$colCheck      = $conn->query("SHOW COLUMNS FROM tb_testimoni LIKE 'id_pesanan'");
$hasPesananCol = ($colCheck && $colCheck->num_rows > 0);

// ---- INSERT TESTIMONI ----
if ($hasPesananCol && $id_pesanan > 0) {
    // Dengan id_pesanan: i=id_pelanggan, i=id_pesanan, s=isi, i=rating, s=tanggal
    $stmt = $conn->prepare(
        "INSERT INTO tb_testimoni (id_pelanggan, id_pesanan, isi_testimoni, rating, tanggal, tampil)
         VALUES (?, ?, ?, ?, ?, 1)"
    );
    $stmt->bind_param('iisis', $id_pelanggan, $id_pesanan, $isi, $rating, $tanggal);
} else {
    // Tanpa id_pesanan: i=id_pelanggan, s=isi, i=rating, s=tanggal
    $stmt = $conn->prepare(
        "INSERT INTO tb_testimoni (id_pelanggan, isi_testimoni, rating, tanggal, tampil)
         VALUES (?, ?, ?, ?, 1)"
    );
    $stmt->bind_param('isis', $id_pelanggan, $isi, $rating, $tanggal);
}

if ($stmt->execute()) {
    $stmt->close();
    setFlash('Terima kasih! Testimoni Anda telah berhasil dikirim. 🎉', 'success');
} else {
    $err = $conn->error;
    $stmt->close();
    setFlash('Gagal menyimpan testimoni. Silakan coba lagi. (' . $err . ')', 'error');
}

redirect($redirect_to ?: 'index.php');

