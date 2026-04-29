<?php
// ============================================================
// update_profile_web.php — Handler Update Profil Website
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: profile.php'); exit; }

$id_user = $_SESSION['id_user'] ?? 0;
$isAdmin = ($_SESSION['role'] ?? '') === 'Admin';

$nama    = trim($_POST['nama'] ?? '');
$noTelp  = trim($_POST['no_telp'] ?? '');
$alamat  = trim($_POST['alamat'] ?? '');
$email   = trim($_POST['email'] ?? '');

if (empty($nama)) {
    $_SESSION['profile_msg'] = 'Nama lengkap tidak boleh kosong.';
    header('Location: profile.php'); exit;
}

// Pastikan kolom ada
@$conn->query("ALTER TABLE tb_user ADD COLUMN IF NOT EXISTS no_telp VARCHAR(20) DEFAULT NULL");
@$conn->query("ALTER TABLE tb_user ADD COLUMN IF NOT EXISTS foto_profil VARCHAR(255) DEFAULT NULL");
@$conn->query("ALTER TABLE tb_user ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL");

// Upload foto
$fotoProfil  = null;
$uploadDir   = __DIR__ . '/uploads/profile/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if (!empty($_FILES['foto_profil']['name']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
    $file    = $_FILES['foto_profil'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];

    if (!in_array($ext, $allowed)) {
        $_SESSION['profile_msg'] = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.';
        header('Location: profile.php'); exit;
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        $_SESSION['profile_msg'] = 'Ukuran foto maksimal 3MB.';
        header('Location: profile.php'); exit;
    }

    // Hapus foto lama
    $stmtOld = $conn->prepare("SELECT foto_profil FROM tb_user WHERE id_user=?");
    if ($stmtOld) {
        $stmtOld->bind_param("i",$id_user);
        $stmtOld->execute();
        $old = $stmtOld->get_result()->fetch_assoc();
        $stmtOld->close();
        if ($old && $old['foto_profil']) { $op=$uploadDir.basename($old['foto_profil']); if(file_exists($op)) @unlink($op); }
    }

    $newFile = 'profil_'.$id_user.'_'.time().'.'.$ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir.$newFile)) {
        $fotoProfil = 'profile/'.$newFile;
    }
}

$now = date('Y-m-d H:i:s');

if ($isAdmin) {
    if ($fotoProfil) {
        $stmt = $conn->prepare("UPDATE tb_user SET nama=?, no_telp=?, foto_profil=?, updated_at=? WHERE id_user=?");
        $stmt->bind_param("ssssi", $nama, $noTelp, $fotoProfil, $now, $id_user);
    } else {
        $stmt = $conn->prepare("UPDATE tb_user SET nama=?, no_telp=?, updated_at=? WHERE id_user=?");
        $stmt->bind_param("sssi", $nama, $noTelp, $now, $id_user);
    }
} else {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['profile_msg'] = 'Format email tidak valid.';
        header('Location: profile.php'); exit;
    }
    if ($fotoProfil) {
        $stmt = $conn->prepare("UPDATE tb_user SET nama=?, email=?, no_telp=?, alamat=?, foto_profil=?, updated_at=? WHERE id_user=?");
        $stmt->bind_param("ssssssi", $nama, $email, $noTelp, $alamat, $fotoProfil, $now, $id_user);
    } else {
        $stmt = $conn->prepare("UPDATE tb_user SET nama=?, email=?, no_telp=?, alamat=?, updated_at=? WHERE id_user=?");
        $stmt->bind_param("sssssi", $nama, $email, $noTelp, $alamat, $now, $id_user);
    }
}

if ($stmt && $stmt->execute()) {
    $old_email = $_SESSION['email'] ?? '';
    
    $_SESSION['nama']    = $nama;
    $_SESSION['no_telp'] = $noTelp;
    
    if (!$isAdmin) {
        $_SESSION['email'] = $email;
        
        // SINKRONISASI KE tb_pelanggan
        // Mengupdate data customer di halaman admin agar selalu sinkron
        $stmtSync = $conn->prepare("UPDATE tb_pelanggan SET nama=?, email=?, no_hp=?, alamat=? WHERE email=?");
        if ($stmtSync) {
            $stmtSync->bind_param("sssss", $nama, $email, $noTelp, $alamat, $old_email);
            $stmtSync->execute();
            $stmtSync->close();
        }
    }
    
    $_SESSION['profile_msg'] = 'Anda sudah berhasil menyimpan perubahan.';
} else {
    $_SESSION['profile_msg'] = 'Gagal menyimpan perubahan profil.';
}
if ($stmt) $stmt->close();
header('Location: profile.php'); exit;
