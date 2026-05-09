<?php
// ============================================================
// google_callback.php — Callback dari Google OAuth 2.0
// Fix untuk InfinityFree hosting (SSL + cURL restrictions)
// ============================================================
require_once 'config.php';
require_once 'auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika ada error dari Google atau tidak ada code
if (isset($_GET['error']) || !isset($_GET['code'])) {
    setFlash('Login Google dibatalkan atau terjadi kesalahan: ' . htmlspecialchars($_GET['error'] ?? 'no_code'), 'error');
    redirect('signin.php');
}

// Cek State untuk keamanan CSRF
if (!isset($_GET['state']) || !isset($_SESSION['google_oauth_state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
    setFlash('Sesi login tidak valid. Silakan coba lagi.', 'error');
    redirect('signin.php');
}
unset($_SESSION['google_oauth_state']);

// ============================================================
// Fungsi HTTP request dengan cURL (+ fallback file_get_contents)
// Fix untuk hosting yang punya masalah SSL
// ============================================================
function httpPost($url, $data) {
    // Coba dengan cURL dulu
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_SSL_VERIFYPEER => false,   // Fix SSL di shared hosting
            CURLOPT_SSL_VERIFYHOST => false,   // Fix SSL di shared hosting
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_USERAGENT      => 'SekalaDesain/1.0 PHP/' . PHP_VERSION,
        ]);
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);
        if ($response !== false && empty($err)) {
            return $response;
        }
    }

    // Fallback: file_get_contents (jika cURL diblokir)
    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data),
            'timeout' => 30,
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
    return $response !== false ? $response : null;
}

function httpGet($url, $token) {
    // Coba dengan cURL dulu
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer $token"],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT      => 'SekalaDesain/1.0 PHP/' . PHP_VERSION,
        ]);
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);
        if ($response !== false && empty($err)) {
            return $response;
        }
    }

    // Fallback: file_get_contents
    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "Authorization: Bearer $token\r\n",
            'timeout' => 30,
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
    return $response !== false ? $response : null;
}

// ============================================================
// STEP 1: Tukar authorization code dengan access token
// ============================================================
$tokenResponse = httpPost('https://oauth2.googleapis.com/token', [
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

if (!$tokenResponse) {
    setFlash('Gagal terhubung ke server Google. Coba lagi beberapa saat.', 'error');
    redirect('signin.php');
}

$tokenData = json_decode($tokenResponse, true);

if (!isset($tokenData['access_token'])) {
    $errMsg = $tokenData['error_description'] ?? ($tokenData['error'] ?? 'Token tidak diterima');
    setFlash('Gagal mendapatkan token Google: ' . htmlspecialchars($errMsg), 'error');
    redirect('signin.php');
}

$accessToken = $tokenData['access_token'];

// ============================================================
// STEP 2: Ambil data profil dari Google
// ============================================================
$userInfoResponse = httpGet('https://www.googleapis.com/oauth2/v2/userinfo', $accessToken);

if (!$userInfoResponse) {
    setFlash('Gagal mengambil data profil dari Google.', 'error');
    redirect('signin.php');
}

$googleUser = json_decode($userInfoResponse, true);

if (!isset($googleUser['email'])) {
    setFlash('Gagal mendapatkan email dari Google. Pastikan akun Google Anda valid.', 'error');
    redirect('signin.php');
}

$email = $conn->real_escape_string(trim($googleUser['email']));
$nama  = $conn->real_escape_string(trim($googleUser['name'] ?? 'User Google'));

// ============================================================
// STEP 3: Cek akun di database
// ============================================================
$stmt = $conn->prepare("SELECT id_user, nama, email, role FROM tb_user WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

$userId   = null;
$userRole = 'Pelanggan';

if ($res->num_rows > 0) {
    // User sudah ada — langsung login
    $row      = $res->fetch_assoc();
    $userId   = $row['id_user'];
    $userRole = $row['role'];
    $nama     = $row['nama'];
} else {
    // User baru — buat akun otomatis
    $randomPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

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
        setFlash('Gagal membuat akun baru: ' . $conn->error, 'error');
        redirect('signin.php');
    }
    $stmtIns->close();
}
$stmt->close();

// ============================================================
// STEP 4: Set session dan redirect
// ============================================================
$_SESSION['id_user'] = $userId;
$_SESSION['nama']    = $nama;
$_SESSION['email']   = $email;
$_SESSION['role']    = $userRole;

setFlash("Berhasil login sebagai $nama 👋", 'success');

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
