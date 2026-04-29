<?php
// ============================================================
// admin/update_profile.php — Handler Update Profil Admin
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$id_user = $_SESSION['id_user'] ?? 0;
if (!$id_user) {
    $_SESSION['profile_msg'] = 'Sesi tidak valid. Silakan login ulang.';
    header('Location: profile.php');
    exit;
}

// Pastikan kolom no_telp, foto_profil, updated_at ada di tb_user
// (tambah kolom jika belum ada – safe migration)
$alterQueries = [
    "ALTER TABLE tb_user ADD COLUMN IF NOT EXISTS no_telp VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE tb_user ADD COLUMN IF NOT EXISTS foto_profil VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE tb_user ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL",
];
foreach ($alterQueries as $q) {
    @$conn->query($q);
}

// Sanitasi input
$nama   = trim($_POST['nama'] ?? '');
$noTelp = trim($_POST['no_telp'] ?? '');

if (empty($nama)) {
    $_SESSION['profile_msg'] = 'Nama lengkap tidak boleh kosong.';
    header('Location: profile.php');
    exit;
}

// Upload foto profil (opsional)
$fotoProfil = null;
$uploadDir  = __DIR__ . '/../uploads/profile/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!empty($_FILES['foto_profil']['name']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
    $file     = $_FILES['foto_profil'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed  = ['jpg','jpeg','png','webp','gif'];

    if (!in_array($ext, $allowed)) {
        $_SESSION['profile_msg'] = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.';
        header('Location: profile.php');
        exit;
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        $_SESSION['profile_msg'] = 'Ukuran foto maksimal 3MB.';
        header('Location: profile.php');
        exit;
    }

    // Hapus foto lama
    $stmtOld = $conn->prepare("SELECT foto_profil FROM tb_user WHERE id_user = ?");
    if ($stmtOld) {
        $stmtOld->bind_param("i", $id_user);
        $stmtOld->execute();
        $oldResult = $stmtOld->get_result()->fetch_assoc();
        $stmtOld->close();
        if ($oldResult && $oldResult['foto_profil']) {
            $oldPath = $uploadDir . basename($oldResult['foto_profil']);
            if (file_exists($oldPath)) @unlink($oldPath);
        }
    }

    $newFileName = 'profil_' . $id_user . '_' . time() . '.' . $ext;
    $destPath    = $uploadDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        $fotoProfil = 'profile/' . $newFileName;
    } else {
        $_SESSION['profile_msg'] = 'Gagal mengunggah foto. Periksa izin folder uploads/.';
        header('Location: profile.php');
        exit;
    }
}

// Bangun query UPDATE
$now = date('Y-m-d H:i:s');
if ($fotoProfil) {
    $stmt = $conn->prepare(
        "UPDATE tb_user SET nama = ?, no_telp = ?, foto_profil = ?, updated_at = ? WHERE id_user = ?"
    );
    $stmt->bind_param("ssssi", $nama, $noTelp, $fotoProfil, $now, $id_user);
} else {
    $stmt = $conn->prepare(
        "UPDATE tb_user SET nama = ?, no_telp = ?, updated_at = ? WHERE id_user = ?"
    );
    $stmt->bind_param("sssi", $nama, $noTelp, $now, $id_user);
}

if ($stmt && $stmt->execute()) {
    // Update session
    $_SESSION['nama']    = $nama;
    $_SESSION['no_telp'] = $noTelp;
    $_SESSION['profile_msg'] = 'Profil berhasil diperbarui!';
} else {
    $_SESSION['profile_msg'] = 'Gagal memperbarui profil: ' . $conn->error;
}

if ($stmt) $stmt->close();
header('Location: profile.php');
exit;
