<?php
// ============================================================
// signup.php — Halaman Register SEKALA
// Menyimpan ke tabel: tb_user
// ============================================================
require_once 'config.php';
require_once 'auth.php';

// Jika sudah login, redirect
if (isLoggedIn()) {
    redirect('index.php');
}

$error   = '';
$success = '';

// ---- Proses Form Register ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama     = e($conn, $_POST['nama']     ?? '');
    $email    = e($conn, $_POST['email']    ?? '');
    $password = $_POST['password']          ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';

    // ---- Validasi ----
    if (empty($nama) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'Semua field wajib diisi.';

    } elseif (strlen($nama) < 2) {
        $error = 'Nama minimal 2 karakter.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';

    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';

    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';

    } else {
        // Cek apakah email sudah terdaftar
        $stmtCek = $conn->prepare("SELECT id_user FROM tb_user WHERE email = ? LIMIT 1");
        $stmtCek->bind_param('s', $email);
        $stmtCek->execute();
        $stmtCek->store_result();

        if ($stmtCek->num_rows > 0) {
            $error = 'Email ini sudah terdaftar. Silakan gunakan email lain atau masuk.';
            $stmtCek->close();

        } else {
            $stmtCek->close();

            // ✅ Hash password dengan password_hash()
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $role           = 'pelanggan';  // default role
            $created_at     = date('Y-m-d H:i:s');

            // Simpan ke tb_user
            $stmtInsert = $conn->prepare(
                "INSERT INTO tb_user (nama, email, password, role, created_at)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmtInsert->bind_param('sssss', $nama, $email, $hashedPassword, $role, $created_at);

            if ($stmtInsert->execute()) {
                $newId = $stmtInsert->insert_id;
                $stmtInsert->close();

                // ✅ Auto login setelah register
                $_SESSION['id_user'] = $newId;
                $_SESSION['nama']    = $nama;
                $_SESSION['email']   = $email;
                $_SESSION['role']    = $role;

                setFlash('Akun berhasil dibuat! Selamat datang, ' . $nama . '! 🎉', 'success');
                redirect('index.php');

            } else {
                $error = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
                $stmtInsert->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SEKALA | Platform Konten Digital</title>
<link rel="icon" size="128x128 px" href="assets/logo.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --primary-mid:  #2563EB;
      --primary-pale: #DBEAFE;
      --dark:         #0F1B2D;
      --dark-3:       #334155;
      --gray:         #64748B;
      --gray-light:   #94A3B8;
      --border:       #E2E8F0;
      --bg:           #F8FAFC;
      --white:        #FFFFFF;
      --danger:       #EF4444;
      --success:      #10B981;
    }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--white); color: var(--dark);
      min-height: 100vh; display: flex; align-items: center;
      justify-content: center; padding: 40px 20px;
    }
    .auth-box { width: 100%; max-width: 480px; }

    .auth-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 32px; text-decoration: none; }
    .auth-logo-icon {
      width: 38px; height: 38px;
      background: linear-gradient(135deg, #0F1B2D, #1C4E8C, #3B82F6);
      border-radius: 10px; display: flex; align-items: center; justify-content: center;
      color: #fff; font-weight: 800; font-size: 15px; font-family: 'Sora', sans-serif;
      box-shadow: 0 4px 12px rgba(28,78,140,0.3);
    }
    .auth-logo-text { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800; color: var(--dark); }

    .auth-title { font-family: 'Sora', sans-serif; font-size: 1.75rem; font-weight: 800; color: var(--dark); margin-bottom: 8px; }
    .auth-sub { font-size: 14px; color: var(--gray); margin-bottom: 28px; line-height: 1.6; }

    .alert {
      padding: 13px 16px; border-radius: 12px; font-size: 14px; font-weight: 500;
      margin-bottom: 20px; display: flex; align-items: flex-start; gap: 10px;
    }
    .alert-error { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }

    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-weight: 600; font-size: 13px; color: var(--dark-3); margin-bottom: 7px; }
    .form-control {
      width: 100%; padding: 13px 18px;
      border: 1.5px solid var(--border); border-radius: 999px;
      font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; color: var(--dark);
      background: var(--white); outline: none; transition: all 0.25s;
    }
    .form-control:focus { border-color: var(--primary-mid); box-shadow: 0 0 0 3px rgba(37,99,235,0.11); }
    .form-control::placeholder { color: var(--gray-light); }

    /* Password strength hint */
    .password-hint { font-size: 11px; color: var(--gray-light); margin-top: 5px; padding-left: 4px; }

    .btn-signup {
      width: 100%; padding: 15px; border-radius: 999px;
      background: linear-gradient(135deg, #1C4E8C, #2563EB);
      color: #fff; font-size: 15px; font-weight: 700; border: none;
      cursor: pointer; font-family: 'Plus Jakarta Sans', sans-serif;
      margin-top: 8px; transition: all 0.25s;
      box-shadow: 0 4px 14px rgba(37,99,235,0.3);
    }
    .btn-signup:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,0.42); }

    .auth-or { display: flex; align-items: center; gap: 14px; color: var(--gray-light); font-size: 13px; margin: 20px 0; }
    .auth-or::before, .auth-or::after { content: ''; flex: 1; height: 1px; background: var(--border); }

    .auth-signin { text-align: center; font-size: 14px; color: var(--gray); margin-top: 20px; }
    .auth-signin a { color: var(--primary-mid); font-weight: 700; text-decoration: none; }
    .auth-signin a:hover { text-decoration: underline; }

    /* Terms note */
    .terms-note { font-size: 12px; color: var(--gray-light); text-align: center; margin-top: 16px; line-height: 1.6; }
    .terms-note a { color: var(--primary-mid); text-decoration: none; }
  </style>
</head>
<body>

<div class="auth-box">

  <!-- Logo -->
 <a class="auth-logo" href="index.php">
    <div align="center">
    <a href="index.php">
      <img src="assets/logo.png" width="250px">
    </a>
  </div>
  </a>

  <h1 class="auth-title">Buat Akun SEKALA</h1>
  <p class="auth-sub">Daftar gratis dan mulai wujudkan konten digital bisnis Anda bersama SEKALA.</p>

  <!-- Error Alert -->
  <?php if ($error): ?>
    <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Form Register -->
  <form method="POST" action="signup.php" autocomplete="on">

    <div class="form-group">
      <label class="form-label" for="nama">Nama Lengkap</label>
      <input
        class="form-control"
        id="nama"
        name="nama"
        type="text"
        placeholder="Masukan nama lengkap Anda"
        value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
        required
        autocomplete="name"
      />
    </div>

    <div class="form-group">
      <label class="form-label" for="email">Email</label>
      <input
        class="form-control"
        id="email"
        name="email"
        type="email"
        placeholder="Masukan email aktif Anda"
        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
        required
        autocomplete="email"
      />
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Password</label>
      <input
        class="form-control"
        id="password"
        name="password"
        type="password"
        placeholder="Minimal 6 karakter"
        required
        autocomplete="new-password"
        oninput="checkStrength(this.value)"
      />
      <div class="password-hint" id="strength-hint">Minimal 6 karakter</div>
    </div>

    <div class="form-group">
      <label class="form-label" for="confirm_password">Konfirmasi Password</label>
      <input
        class="form-control"
        id="confirm_password"
        name="confirm_password"
        type="password"
        placeholder="Ulangi password Anda"
        required
        autocomplete="new-password"
      />
    </div>

    <button type="submit" class="btn-signup">Buat Akun Sekarang</button>

    <p class="terms-note">
      Dengan mendaftar, Anda menyetujui <a href="#">Syarat &amp; Ketentuan</a>
      dan <a href="#">Kebijakan Privasi</a> SEKALA.
    </p>

  </form>

  <p class="auth-signin">
    Sudah punya akun? <a href="signin.php">Masuk di sini</a>
  </p>

</div>

<script>
function checkStrength(value) {
  const hint = document.getElementById('strength-hint');
  if (!hint) return;
  if (value.length === 0) {
    hint.style.color = '#94A3B8';
    hint.textContent = 'Minimal 6 karakter';
  } else if (value.length < 6) {
    hint.style.color = '#EF4444';
    hint.textContent = '❌ Password terlalu pendek (' + value.length + '/6 karakter)';
  } else if (value.length < 10) {
    hint.style.color = '#F59E0B';
    hint.textContent = '⚠️ Password cukup, tapi bisa lebih kuat';
  } else {
    hint.style.color = '#10B981';
    hint.textContent = '✅ Password kuat!';
  }
}
</script>

</body>
</html>
