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
  <title>Regrister — SEKALA</title>
  <link rel="icon" type="image/png" href="assets/Logo3.png" style="border-radius: 50%; width: 32px; height: 32px;">
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

  <div class="auth-or">Or</div>

  <!-- Social Signup -->
  <div class="auth-social" style="display:flex; flex-direction:column; gap:12px;">
    <button class="btn-social" type="button" onclick="window.location.href='google_login.php'" style="width:100%; padding:13px; border-radius:999px; background:var(--bg); border:1.5px solid var(--border); display:flex; align-items:center; justify-content:center; gap:10px; font-size:14px; font-weight:600; color:var(--dark-3); cursor:pointer; font-family:'Plus Jakarta Sans', sans-serif; transition:all 0.25s;">
      <svg width="18" height="18" viewBox="0 0 48 48">
        <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.654 32.657 29.332 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
        <path fill="#FF3D00" d="m6.306 14.691 6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
        <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
        <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a11.96 11.96 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
      </svg>
      Sign up with Google
    </button>
  </div>

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
