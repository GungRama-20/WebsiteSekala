<?php
// ============================================================
// signin.php — Halaman Login SEKALA
// ============================================================
require_once 'config.php';
require_once 'auth.php';

// Jika sudah login, redirect ke dashboard (sesuai role)
if (isLoggedIn()) {
    if (($_SESSION['role'] ?? '') === 'Admin') {
        redirect('admin/index.php');
    }
    redirect('index.php');
}

$error   = '';
$success = '';

// ---- Proses Form Login ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = e($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi input
    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';

    } else {
        // Cari user berdasarkan email di tb_user
        $stmt = $conn->prepare("SELECT id_user, nama, email, password, role FROM tb_user WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // ✅ Login berhasil — simpan session
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            setFlash('Selamat datang kembali, ' . $user['nama'] . '! 👋', 'success');

            // Role-based redirect — 'Admin' sesuai ENUM di database
            if ($user['role'] === 'Admin') {
                redirect('admin/index.php');
            }

            // Redirect ke halaman sebelumnya (jika ada) atau ke index
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirect);

        } else {
            $error = 'Email atau password salah. Silakan coba lagi.';
        }
    }
}

// Ambil flash message (jika ada, misal dari auth.php)
$flash = getFlash();
if ($flash) {
    $error = $flash['message'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Masuk — SEKALA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* ---- Reset & Variables ---- */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --primary:       #1C4E8C;
      --primary-mid:   #2563EB;
      --primary-light: #3B82F6;
      --primary-pale:  #DBEAFE;
      --dark:          #0F1B2D;
      --dark-3:        #334155;
      --gray:          #64748B;
      --gray-light:    #94A3B8;
      --border:        #E2E8F0;
      --bg:            #F8FAFC;
      --white:         #FFFFFF;
      --danger:        #EF4444;
      --success:       #10B981;
      --warning-bg:    #FEF3C7;
      --warning-color: #92400E;
    }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--white);
      color: var(--dark);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }

    /* ---- AUTH BOX ---- */
    .auth-box { width: 100%; max-width: 480px; }

    /* Logo */
    .auth-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 36px; text-decoration: none; }
    .auth-logo-icon {
      width: 38px; height: 38px;
      background: linear-gradient(135deg, #0F1B2D, #1C4E8C, #3B82F6);
      border-radius: 10px; display: flex; align-items: center; justify-content: center;
      color: #fff; font-weight: 800; font-size: 15px; font-family: 'Sora', sans-serif;
      box-shadow: 0 4px 12px rgba(28,78,140,0.3);
    }
    .auth-logo-text { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800; color: var(--dark); }

    /* Heading */
    .auth-title { font-family: 'Sora', sans-serif; font-size: 1.9rem; font-weight: 800; color: var(--dark); margin-bottom: 10px; }
    .auth-sub { font-size: 14px; color: var(--primary-mid); line-height: 1.65; margin-bottom: 30px; }

    /* Alert */
    .alert {
      padding: 13px 16px; border-radius: 12px; font-size: 14px; font-weight: 500;
      margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
    }
    .alert-error   { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
    .alert-warning { background: var(--warning-bg); color: var(--warning-color); border: 1px solid #FDE68A; }
    .alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }

    /* Form */
    .form-group { margin-bottom: 18px; }
    .form-label { display: block; font-weight: 600; font-size: 13px; color: var(--dark-3); margin-bottom: 7px; }
    .form-control {
      width: 100%; padding: 13px 18px;
      border: 1.5px solid var(--border); border-radius: 999px;
      font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; color: var(--dark);
      background: var(--white); outline: none; transition: all 0.25s;
    }
    .form-control:focus { border-color: var(--primary-mid); box-shadow: 0 0 0 3px rgba(37,99,235,0.11); }
    .form-control::placeholder { color: var(--gray-light); }

    /* Forgot */
    .auth-forgot { display: inline-block; font-size: 13px; color: var(--primary-mid); font-weight: 600; text-decoration: none; margin-top: 6px; }
    .auth-forgot:hover { text-decoration: underline; }

    /* Button */
    .btn-signin {
      width: 100%; padding: 15px;
      border-radius: 999px; background: var(--dark); color: #fff;
      font-size: 15px; font-weight: 700; border: none; cursor: pointer;
      font-family: 'Plus Jakarta Sans', sans-serif; margin-top: 8px;
      transition: all 0.25s;
    }
    .btn-signin:hover { background: #1E293B; transform: translateY(-1px); }
    .btn-signin:active { transform: translateY(0); }

    /* Or divider */
    .auth-or { display: flex; align-items: center; gap: 14px; color: var(--gray-light); font-size: 13px; margin: 22px 0; }
    .auth-or::before, .auth-or::after { content: ''; flex: 1; height: 1px; background: var(--border); }

    /* Social buttons */
    .auth-social { display: flex; flex-direction: column; gap: 12px; }
    .btn-social {
      width: 100%; padding: 13px; border-radius: 999px;
      background: var(--bg); border: 1.5px solid var(--border);
      display: flex; align-items: center; justify-content: center; gap: 10px;
      font-size: 14px; font-weight: 600; color: var(--dark-3);
      cursor: pointer; font-family: 'Plus Jakarta Sans', sans-serif; transition: all 0.25s;
    }
    .btn-social:hover { border-color: var(--gray-light); background: var(--white); }

    /* Sign up link */
    .auth-signup { text-align: center; margin-top: 24px; font-size: 14px; color: var(--gray); }
    .auth-signup a { color: var(--primary-mid); font-weight: 700; text-decoration: none; }
    .auth-signup a:hover { text-decoration: underline; }
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

  <h1 class="auth-title">Welcome Back to SEKALA</h1>
  <p class="auth-sub">Masuk dan wujudkan ide bisnis Anda menjadi konten digital yang nyata, rapi, dan profesional.</p>

  <!-- Error / Warning Alert -->
  <?php if ($error): ?>
    <div class="alert alert-<?= (strpos($error, 'login') !== false) ? 'warning' : 'error' ?>">
      <span><?= htmlspecialchars($error) ?></span>
    </div>
  <?php endif; ?>

  <!-- Form Login -->
  <form method="POST" action="signin.php" autocomplete="on">

    <div class="form-group">
      <label class="form-label" for="email">Email</label>
      <input
        class="form-control"
        id="email"
        name="email"
        type="email"
        placeholder="Masukan email anda"
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
        placeholder="Masukan password anda"
        required
        autocomplete="current-password"
      />
      <a class="auth-forgot" href="forgot_password.php">Forgot Password?</a>
    </div>

    <button type="submit" class="btn-signin">Sign in</button>
  </form>

  <div class="auth-or">Or</div>

  <!-- Social Login -->
  <div class="auth-social">
    <button class="btn-social" type="button" onclick="window.location.href='google_login.php'">
      <svg width="18" height="18" viewBox="0 0 48 48">
        <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.654 32.657 29.332 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
        <path fill="#FF3D00" d="m6.306 14.691 6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
        <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238C29.211 35.091 26.715 36 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
        <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a11.96 11.96 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
      </svg>
      Sign in with Google
    </button>
  </div>

  <p class="auth-signup">
    Don't you have an account? <a href="signup.php">Sign up</a>
  </p>

</div>

</body>
</html>
