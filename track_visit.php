<?php
// ============================================================
// track_visit.php — Menyimpan data kunjungan secara asynchronous
// ============================================================
require_once 'config.php';
require_once 'auth.php';

if (isLoggedIn() && (getCurrentUser()['role'] ?? '') === 'Admin') {
    echo json_encode(['status' => 'ignored', 'reason' => 'admin']);
    exit;
}
// Endpoint ini akan dipanggil via navigator.sendBeacon atau fetch
// karena bisa berupa POST raw JSON atau form data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

if (!$data) {
    $data = $_POST;
}

$action = $data['action'] ?? '';
$halaman = $data['halaman'] ?? 'Lainnya';
$session_id = session_id();
if (empty($session_id)) {
    session_start();
    $session_id = session_id();
}

// Untuk durasi
$durasi = isset($data['durasi']) ? (int)$data['durasi'] : 0;
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$waktu_masuk = date('Y-m-d H:i:s');

if ($action === 'start') {
    // Insert kunjungan baru untuk session dan halaman ini, atau biarkan create record
    // Kita buat logic: 1 record per session per halaman per hari?
    // Lebih baik insert record setiap kali buka halaman, lalu kita akan aggregate di backend.
    
    // Atau jika page di-refresh, kita bisa cek apakah 5 menit terakhir ada kunjungan di halaman sama
    $stmtCek = $conn->prepare("SELECT id_kunjungan FROM tb_pengunjung WHERE session_id = ? AND halaman = ? AND waktu_masuk >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) ORDER BY id_kunjungan DESC LIMIT 1");
    $stmtCek->bind_param('ss', $session_id, $halaman);
    $stmtCek->execute();
    $res = $stmtCek->get_result()->fetch_assoc();
    $stmtCek->close();
    
    if ($res) {
        echo json_encode(['status' => 'exists', 'id' => $res['id_kunjungan']]);
    } else {
        $stmt = $conn->prepare("INSERT INTO tb_pengunjung (session_id, halaman, waktu_masuk, durasi_detik, ip_address) VALUES (?, ?, ?, 0, ?)");
        $stmt->bind_param('ssss', $session_id, $halaman, $waktu_masuk, $ip_address);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        echo json_encode(['status' => 'started', 'id' => $id]);
    }
} elseif ($action === 'update') {
    $id = $data['id'] ?? 0;
    if ($id && $durasi > 0) {
        // Cek durasi sebelumnya, jangan ditimpa mundur
        $stmt = $conn->prepare("UPDATE tb_pengunjung SET durasi_detik = GREATEST(durasi_detik, ?) WHERE id_kunjungan = ?");
        $stmt->bind_param('ii', $durasi, $id);
        $stmt->execute();
        $stmt->close();
    }
    echo json_encode(['status' => 'updated']);
}
?>
