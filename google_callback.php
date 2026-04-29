<?php
require_once 'config.php';
require_once 'auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika ada error dari Google atau tidak ada code
if (isset($_GET['error']) || !isset($_GET['code'])) {
    setFlash('Login Google dibatalkan atau terjadi kesalahan.', 'error');
    redirect('signin.php');
}

// Cek State untuk keamanan CSRF
if (!isset($_GET['state']) || !isset($_SESSION['google_oauth_state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
    setFlash('Sesi login tidak valid. Silakan coba lagi.', 'error');
    redirect('signin.php');
}

// Tukar authorization code dengan access token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/token");
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code'
]));
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);

if (!isset($tokenData['access_token'])) {
    // Jika credentials kosong/salah, Google akan kembalikan error invalid_client
    if (GOOGLE_CLIENT_ID === 'GANTI_DENGAN_CLIENT_ID_GOOGLE_ANDA') {
        setFlash('Sistem belum dikonfigurasi. Harap masukkan GOOGLE_CLIENT_ID dan SECRET di config.php.', 'warning');
    } else {
        setFlash('Gagal mendapatkan token akses dari Google.', 'error');
    }
    redirect('signin.php');
}

$accessToken = $tokenData['access_token'];

// Ambil data profil dari Google
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v2/userinfo");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);
$userInfoResponse = curl_exec($ch);
curl_close($ch);

$googleUser = json_decode($userInfoResponse, true);

if (!isset($googleUser['email'])) {
    setFlash('Gagal mengambil data email dari Google.', 'error');
    redirect('signin.php');
}

$email = e($conn, $googleUser['email']);
$nama  = e($conn, $googleUser['name'] ?? 'User Google');

// Cek apakah email sudah terdaftar di tb_user
$stmt = $conn->prepare("SELECT id_user, nama, email, password, role FROM tb_user WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

$userId = null;
$userRole = 'Pelanggan';

if ($res->num_rows > 0) {
    // User sudah ada, langsung login
    $row = $res->fetch_assoc();
    $userId = $row['id_user'];
    $userRole = $row['role'];
    $nama = $row['nama']; // Gunakan nama yang sudah ada di sistem
} else {
    // User baru, buat akun otomatis
    $randomPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT); // Password acak karena login via Google
    
    // Insert ke tb_user
    $stmtIns = $conn->prepare("INSERT INTO tb_user (nama, email, password, role) VALUES (?, ?, ?, 'Pelanggan')");
    $stmtIns->bind_param("sss", $nama, $email, $randomPassword);
    
    if ($stmtIns->execute()) {
        $userId = $stmtIns->insert_id;
        
        // Buat data pelanggan otomatis
        $stmtCust = $conn->prepare("INSERT INTO tb_pelanggan (id_user, nama, email) VALUES (?, ?, ?)");
        $stmtCust->bind_param("iss", $userId, $nama, $email);
        $stmtCust->execute();
        $stmtCust->close();
    } else {
        setFlash('Gagal membuat akun baru. Silakan coba lagi.', 'error');
        redirect('signin.php');
    }
    $stmtIns->close();
}
$stmt->close();

// Set Session Login
$_SESSION['id_user'] = $userId;
$_SESSION['nama']    = $nama;
$_SESSION['email']   = $email;
$_SESSION['role']    = $userRole;

setFlash("Berhasil login sebagai $nama", 'success');

// Redirect jika ada halaman yang dituju
if (!empty($_SESSION['redirect_after_login'])) {
    $r = $_SESSION['redirect_after_login'];
    unset($_SESSION['redirect_after_login']);
    redirect($r);
} else {
    if ($userRole === 'Admin') {
        redirect('admin/index.php');
    } else {
        redirect('index.php');
    }
}
