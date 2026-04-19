<?php
// ============================================================
// admin/testimoni.php — Data Testimoni
// ============================================================
$current_page = 'testimoni';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin_auth.php';

$flash_msg  = '';
$flash_type = '';

// ---- PASTIKAN KOLOM LENGKAP ----
$conn->query("ALTER TABLE tb_testimoni ADD COLUMN IF NOT EXISTS id_pesanan INT NULL AFTER id_pelanggan");
$conn->query("ALTER TABLE tb_testimoni ADD COLUMN IF NOT EXISTS tampil TINYINT(1) NOT NULL DEFAULT 1 AFTER tanggal");
$conn->query("ALTER TABLE tb_testimoni MODIFY COLUMN id_pelanggan INT NULL");

// ---- AUTO SEED: jika tabel kosong, masukkan data sample ----
$cekIsi = $conn->query("SELECT COUNT(*) AS n FROM tb_testimoni");
if ($cekIsi && $cekIsi->fetch_assoc()['n'] == 0) {
    // Masukkan pelanggan sample jika belum ada
    $samples = [
        [10, 'Made Ari',     'made@example.com',    'SEKALA sangat membantu bisnis kami membuat konten Instagram. Prosesnya mudah dan desainnya cepat selesai.', 5, 10],
        [11, 'Komang Dewi',  'komang@example.com',  'Dengan SEKALA saya tinggal kirim request dan hasil desainnya sudah siap dipakai untuk promosi. Sangat praktis.', 5, 8],
        [12, 'Wayan Putra',  'wayan@example.com',   'Pelayanan di SEKALA sangat profesional. Revisi mudah karena semua tercatat di sistem.', 5, 6],
        [13, 'Nyoman Sari',  'nyoman@example.com',  'Konten yang dibuat SEKALA sangat berkualitas dan sesuai dengan kebutuhan bisnis saya. Recommended!', 5, 5],
        [14, 'Ketut Wijaya', 'ketut@example.com',   'Proses request sangat mudah dan hasilnya memuaskan. Tim SEKALA sangat responsif dan profesional.', 5, 3],
        [15, 'Putu Indriani','putu@example.com',    'Sangat puas dengan layanan SEKALA. Desain poster yang dihasilkan melebihi ekspektasi saya.', 5, 1],
    ];
    foreach ($samples as [$pid, $nama, $email, $isi, $rating, $hariLalu]) {
        // Insert pelanggan sample (ignore jika sudah ada)
        $stP = $conn->prepare("INSERT IGNORE INTO tb_pelanggan (id_pelanggan, nama, email) VALUES (?, ?, ?)");
        $stP->bind_param('iss', $pid, $nama, $email);
        $stP->execute(); $stP->close();

        // Insert testimoni
        $tgl = date('Y-m-d H:i:s', strtotime("-{$hariLalu} days"));
        $stT = $conn->prepare(
            "INSERT INTO tb_testimoni (id_pelanggan, isi_testimoni, rating, tanggal, tampil)
             VALUES (?, ?, ?, ?, 1)"
        );
        $stT->bind_param('isis', $pid, $isi, $rating, $tgl);
        $stT->execute(); $stT->close();
    }
    $flash_msg  = '✅ Data testimoni sample berhasil dimuat otomatis.';
    $flash_type = 'success';
}

// ---- HAPUS ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM tb_testimoni WHERE id_testimoni=$id");
    $flash_msg = 'Testimoni berhasil dihapus.'; $flash_type = 'success';
}

// ---- TOGGLE TAMPIL ----
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $cur = $conn->query("SELECT tampil FROM tb_testimoni WHERE id_testimoni=$id LIMIT 1");
    if ($cur && $row = $cur->fetch_assoc()) {
        $new = $row['tampil'] ? 0 : 1;
        $conn->query("UPDATE tb_testimoni SET tampil=$new WHERE id_testimoni=$id");
        $flash_msg  = $new ? 'Testimoni ditampilkan.' : 'Testimoni disembunyikan.';
        $flash_type = 'success';
    }
}

// ---- AMBIL SEMUA DATA ----
$testis   = [];
$db_error = '';
$res = $conn->query(
    "SELECT t.id_testimoni, t.isi_testimoni, t.rating, t.tanggal, t.tampil,
            COALESCE(p.nama,  '(Tidak diketahui)') AS nama_pelanggan,
            COALESCE(p.email, '')                  AS email_pelanggan
     FROM tb_testimoni t
     LEFT JOIN tb_pelanggan p ON t.id_pelanggan = p.id_pelanggan
     ORDER BY t.tanggal DESC"
);
if ($res) {
    while ($r = $res->fetch_assoc()) $testis[] = $r;
} else {
    $db_error = $conn->error;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Data Testimoni — SEKALA Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/sidebar.php'; ?>
  <div class="admin-main">
    <div class="admin-topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">☰</button>
        <div><div class="topbar-page-title">Data Testimoni</div><div class="topbar-breadcrumb">Admin / Testimoni</div></div>
      </div>
    </div>
    <div class="admin-content">
      <?php if ($flash_msg): ?>
      <div class="admin-flash <?= $flash_type ?>"><?= $flash_type==='success'?'✅':'❌' ?> <?= htmlspecialchars($flash_msg) ?></div>
      <?php endif; ?>

      <?php if ($db_error): ?>
      <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:12px;padding:14px 18px;font-size:13px;color:#991B1B;margin-bottom:20px;">
        ❌ <b>Terjadi kesalahan saat memuat data.</b> <?= htmlspecialchars($db_error) ?>
      </div>
      <?php endif; ?>

      <div style="background:var(--primary-pale);border:1px solid #93C5FD;border-radius:12px;padding:13px 16px;font-size:13px;color:var(--primary);margin-bottom:20px;">
        ℹ️ Maksimal <b>3 testimoni</b> yang ditampilkan di website diambil dari yang berstatus <b>Tampil</b> dan terbaru.
        Semua testimoni dari customer tersimpan di tabel ini.
      </div>

      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title">⭐ Semua Testimoni (<?= count($testis) ?>)</div>
          <a href="../semua_testimoni.php" target="_blank" class="topbar-btn btn-outline-sm" style="font-size:12px;">🌐 Lihat di Website</a>
        </div>
        <div class="admin-card-body admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>#</th><th>Pelanggan</th><th>Rating</th><th>Isi Testimoni</th><th>Tanggal</th><th>Status Tampil</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php if (empty($testis)): ?>
              <tr><td colspan="7">
                <div class="empty-state">
                  <div class="empty-state-icon">⭐</div>
                  <h4>Belum ada testimoni</h4>
                  <p style="font-size:13px;color:#94A3B8;margin-top:8px;">Testimoni dari customer akan muncul di sini.</p>
                </div>
              </td></tr>
              <?php else: ?>

              <?php foreach ($testis as $t): ?>
              <tr>
                <td><b>#<?= $t['id_testimoni'] ?></b></td>
                <td>
                  <div style="font-weight:600;"><?= htmlspecialchars($t['nama_pelanggan'] ?? '—') ?></div>
                  <div style="font-size:11px;color:#94A3B8;"><?= htmlspecialchars($t['email_pelanggan'] ?? '') ?></div>
                </td>
                <td>
                  <span style="color:#F59E0B;font-size:15px;"><?= str_repeat('★', (int)$t['rating']) ?></span>
                  <span style="font-size:11px;color:#94A3B8;"><?= $t['rating'] ?>/5</span>
                </td>
                <td style="max-width:280px;">
                  <div style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                    <?= htmlspecialchars($t['isi_testimoni']) ?>
                  </div>
                </td>
                <td><?= date('d M Y', strtotime($t['tanggal'])) ?></td>
                <td>
                  <?php if ($t['tampil']): ?>
                  <span class="badge badge-selesai">✅ Tampil</span>
                  <?php else: ?>
                  <span class="badge badge-batal">🚫 Tersembunyi</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <a href="testimoni.php?toggle=<?= $t['id_testimoni'] ?>" class="topbar-btn btn-outline-sm" style="font-size:11px;padding:5px 10px;">
                      <?= $t['tampil'] ? '🙈 Sembunyikan' : '👁 Tampilkan' ?>
                    </a>
                    <a href="testimoni.php?delete=<?= $t['id_testimoni'] ?>" class="topbar-btn btn-danger-sm" style="font-size:11px;padding:5px 10px;"
                       onclick="return confirm('Hapus testimoni ini?')">🗑️ Hapus</a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
