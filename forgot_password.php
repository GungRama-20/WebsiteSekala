<?php
require_once 'config.php';
require_once 'auth.php';

// Setup tabel reset password (otomatis tambah kolom jika belum ada)
$conn->query("ALTER TABLE tb_user ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NULL");
$conn->query("ALTER TABLE tb_user ADD COLUMN IF NOT EXISTS reset_expires DATETIME NULL");

$error = '';
$successMsg = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = e($conn, trim($_POST['email']));
    
    if (empty($email)) {
        $error = 'Email tidak boleh kosong.';
    } else {
        // Cek apakah email terdaftar
        $stmt = $conn->prepare("SELECT id_user, nama FROM tb_user WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            
            // Generate token (hexadecimal 64 karakter)
            $token = bin2hex(random_bytes(32));
            // Berlaku untuk 1 jam
            $expires = date('Y-m-d H:i:s', time() + 3600);
            
            // Simpan token ke database
            $upd = $conn->prepare("UPDATE tb_user SET reset_token = ?, reset_expires = ? WHERE id_user = ?");
            $upd->bind_param("ssi", $token, $expires, $user['id_user']);
            
            if ($upd->execute()) {
                // Link reset
                $resetLink = 'http://localhost/WebsiteSekala/reset_password.php?token=' . $token;
                $successMsg = "Link pemulihan kata sandi telah dibuat.";
            } else {
                $error = "Terjadi kesalahan sistem saat membuat token.";
            }
            $upd->close();
        } else {
            // Demi keamanan, tetap tampilkan pesan sukses agar orang tidak bisa menebak email mana yang terdaftar (opsional)
            // Tapi untuk testing lokal, kita beritahu saja emailnya tidak terdaftar.
            $error = 'Email tidak ditemukan di sistem kami.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lupa Password — SEKALA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    body { background: var(--bg-2); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
    .auth-box {
      width: 100%; max-width: 420px; background: #fff; border-radius: 24px;
      padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); text-align: center;
    }
    .auth-logo img { width: 180px; margin-bottom: 24px; }
    .auth-title { font-family: 'Sora', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--dark); margin-bottom: 10px; }
    .auth-sub { font-size: 14px; color: var(--gray); line-height: 1.6; margin-bottom: 24px; }
    .form-group { text-align: left; margin-bottom: 20px; }
    .form-label { display: block; font-weight: 600; font-size: 13px; color: var(--dark-3); margin-bottom: 8px; }
    .form-control {
      width: 100%; padding: 14px 18px; border: 1.5px solid var(--border); border-radius: 999px;
      font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; color: var(--dark);
      background: var(--white); outline: none; transition: all 0.25s;
    }
    .form-control:focus { border-color: var(--primary-mid); box-shadow: 0 0 0 3px rgba(37,99,235,0.11); }
    .btn-submit {
      width: 100%; padding: 15px; border-radius: 999px; background: var(--primary-mid); color: #fff;
      font-size: 15px; font-weight: 700; border: none; cursor: pointer; transition: all 0.25s;
    }
    .btn-submit:hover { background: var(--primary); transform: translateY(-2px); }
    .alert { padding: 14px; border-radius: 12px; font-size: 14px; margin-bottom: 20px; text-align: left; }
    .alert-error { background: #FEE2E2; color: #991B1B; }
    .alert-success { background: #ECFCCB; color: #3F6212; }
    .back-login { display: block; margin-top: 20px; font-size: 14px; color: var(--primary-mid); font-weight: 600; text-decoration: none; }
    .back-login:hover { text-decoration: underline; }
    .simulation-box {
      margin-top: 24px; padding: 20px; border-radius: 16px; background: #F8FAFC; border: 2px dashed #CBD5E1; text-align: left;
    }
    .sim-title { font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 1px; }
    .sim-link { display: inline-block; padding: 10px 16px; background: var(--dark); color: #fff !important; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 600; }
  </style>
</head>
<body>

<div class="auth-box">
  <a href="index.php" class="auth-logo"><img src="assets/logo.png" alt="SEKALA Logo"></a>
  <h1 class="auth-title">Lupa Password</h1>
  <p class="auth-sub">Masukkan email Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.</p>

  <?php if ($error): ?>
    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($successMsg): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($successMsg) ?></div>
    
    <!-- SIMULASI PENGIRIMAN EMAIL (Karena testing di localhost) -->
    <div class="simulation-box">
      <div class="sim-title">⚙️ Simulasi Email Localhost</div>
      <p style="font-size:13px; color:#475569; margin-bottom:12px; line-height:1.5;">Dalam versi production, Anda akan menerima email. Karena ini di localhost, silakan klik tombol di bawah untuk melanjutkan reset password:</p>
      <a href="<?= $resetLink ?>" class="sim-link">🔗 Klik untuk Reset Password</a>
    </div>

  <?php else: ?>
    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label" for="email">Alamat Email</label>
        <input class="form-control" id="email" name="email" type="email" placeholder="contoh@email.com" required>
      </div>
      <button type="submit" class="btn-submit">Kirim Link Reset</button>
    </form>
  <?php endif; ?>

  <a href="signin.php" class="back-login">← Kembali ke halaman Login</a>
</div>

</body>
</html>
