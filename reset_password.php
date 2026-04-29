<?php
require_once 'config.php';
require_once 'auth.php';

$error = '';
$successMsg = '';
$validToken = false;
$userId = null;

// Ambil token dari URL
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    $error = "Token reset kata sandi tidak ditemukan.";
} else {
    // Verifikasi token
    $stmt = $conn->prepare("SELECT id_user, reset_expires FROM tb_user WHERE reset_token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        // Cek apakah token sudah expired
        if (strtotime($user['reset_expires']) > time()) {
            $validToken = true;
            $userId = $user['id_user'];
        } else {
            $error = "Tautan reset kata sandi ini sudah kadaluarsa. Silakan request ulang.";
        }
    } else {
        $error = "Tautan reset kata sandi tidak valid.";
    }
    $stmt->close();
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPass = $_POST['password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';
    
    if (strlen($newPass) < 6) {
        $error = "Password minimal harus 6 karakter.";
    } elseif ($newPass !== $confirmPass) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        // Hash password baru
        $hashedPass = password_hash($newPass, PASSWORD_DEFAULT);
        
        // Update password dan kosongkan token
        $upd = $conn->prepare("UPDATE tb_user SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id_user = ?");
        $upd->bind_param("si", $hashedPass, $userId);
        
        if ($upd->execute()) {
            $successMsg = "Kata sandi Anda berhasil diperbarui! Silakan login dengan kata sandi baru.";
            $validToken = false; // Sembunyikan form
        } else {
            $error = "Terjadi kesalahan saat memperbarui kata sandi.";
        }
        $upd->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password — SEKALA</title>
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
    .btn-login {
      display: block; width: 100%; padding: 15px; border-radius: 999px; background: var(--dark); color: #fff;
      font-size: 15px; font-weight: 700; text-decoration: none; transition: all 0.25s; margin-top: 20px;
    }
    .btn-login:hover { background: #000; transform: translateY(-2px); }
  </style>
</head>
<body>

<div class="auth-box">
  <a href="index.php" class="auth-logo"><img src="assets/logo.png" alt="SEKALA Logo"></a>
  <h1 class="auth-title">Buat Password Baru</h1>
  
  <?php if ($successMsg): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($successMsg) ?></div>
    <a href="signin.php" class="btn-login">Menuju Halaman Login</a>
  <?php else: ?>
    <p class="auth-sub">Silakan masukkan kata sandi baru Anda di bawah ini.</p>

    <?php if ($error): ?>
      <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($validToken): ?>
      <form method="POST" action="">
        <div class="form-group">
          <label class="form-label" for="password">Password Baru</label>
          <input class="form-control" id="password" name="password" type="password" placeholder="Minimal 6 karakter" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm_password">Konfirmasi Password</label>
          <input class="form-control" id="confirm_password" name="confirm_password" type="password" placeholder="Ulangi password baru" required>
        </div>
        <button type="submit" class="btn-submit">Simpan Password Baru</button>
      </form>
    <?php else: ?>
      <a href="forgot_password.php" class="back-login">Kirim ulang tautan reset</a>
    <?php endif; ?>
  <?php endif; ?>
</div>

</body>
</html>
