<?php
// ============================================================
// admin/pembayaran.php — Data Pembayaran + Verifikasi
// ============================================================
$current_page = 'pembayaran';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin_auth.php';

$flash_msg  = '';
$flash_type = '';

// Verifikasi / Tolak pembayaran
if (isset($_GET['verif'])) {
    $id = (int)$_GET['verif'];
    $conn->query("UPDATE tb_pembayaran SET status_pembayaran='terverifikasi' WHERE id_pembayaran=$id");
    $conn->query("UPDATE tb_pesanan SET status_pesanan='selesai' WHERE id_pesanan=(SELECT id_pesanan FROM tb_pembayaran WHERE id_pembayaran=$id LIMIT 1)");
    $flash_msg  = 'Pembayaran berhasil diverifikasi.';
    $flash_type = 'success';
}
if (isset($_GET['tolak'])) {
    $id = (int)$_GET['tolak'];
    $conn->query("UPDATE tb_pembayaran SET status_pembayaran='ditolak' WHERE id_pembayaran=$id");
    $flash_msg  = 'Pembayaran ditolak.';
    $flash_type = 'error';
}

// Filter
$filterStatus = $conn->real_escape_string($_GET['status'] ?? '');
$where = $filterStatus ? "WHERE b.status_pembayaran = '$filterStatus'" : '';

$payments = [];
$res = $conn->query(
    "SELECT b.id_pembayaran, b.id_pesanan, b.metode_pembayaran, b.jumlah_bayar,
            b.bukti_pembayaran, b.status_pembayaran, b.tanggal_bayar,
            p.nama AS nama_pelanggan, o.judul_proyek, d.jenis_desain
     FROM tb_pembayaran b
     JOIN tb_pesanan o    ON b.id_pesanan    = o.id_pesanan
     JOIN tb_pelanggan p  ON o.id_pelanggan  = p.id_pelanggan
     JOIN tb_desain d     ON o.id_desain     = d.id_desain
     $where
     ORDER BY b.tanggal_bayar DESC"
);
if ($res) { while ($r = $res->fetch_assoc()) $payments[] = $r; }

$totalVerif    = array_sum(array_map(fn($r) => $r['status_pembayaran']==='terverifikasi' ? $r['jumlah_bayar'] : 0, $payments));

function statusBayarBadge($s) {
    $map = [
        'menunggu'      => ['badge-menunggu','⏳ Menunggu'],
        'terverifikasi' => ['badge-terverifikasi','✅ Terverifikasi'],
        'ditolak'       => ['badge-batal','❌ Ditolak'],
    ];
    [$cls,$label] = $map[$s] ?? ['badge-pending',$s];
    return "<span class=\"badge $cls\">$label</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Data Pembayaran — SEKALA Admin</title>
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
        <div><div class="topbar-page-title">Data Pembayaran</div><div class="topbar-breadcrumb">Admin / Pembayaran</div></div>
      </div>
      <div class="topbar-right no-print">
        <button onclick="window.print()" class="topbar-btn btn-outline-sm">🖨️ Print</button>
      </div>
    </div>
    <div class="admin-content">
      <?php if ($flash_msg): ?>
      <div class="admin-flash <?= $flash_type ?>"><?= $flash_type==='success'?'✅':'❌' ?> <?= htmlspecialchars($flash_msg) ?></div>
      <?php endif; ?>

      <!-- Summary -->
      <div class="stat-cards" style="margin-bottom:20px;">
        <?php
        $mCnt = count(array_filter($payments, fn($r)=>$r['status_pembayaran']==='menunggu'));
        $vCnt = count(array_filter($payments, fn($r)=>$r['status_pembayaran']==='terverifikasi'));
        $dCnt = count(array_filter($payments, fn($r)=>$r['status_pembayaran']==='ditolak'));
        ?>
        <div class="stat-card"><div class="stat-icon yellow">⏳</div><div class="stat-body"><div class="stat-val"><?= $mCnt ?></div><div class="stat-lbl">Menunggu Verifikasi</div></div></div>
        <div class="stat-card"><div class="stat-icon green">✅</div><div class="stat-body"><div class="stat-val"><?= $vCnt ?></div><div class="stat-lbl">Terverifikasi</div></div></div>
        <div class="stat-card"><div class="stat-icon red">❌</div><div class="stat-body"><div class="stat-val"><?= $dCnt ?></div><div class="stat-lbl">Ditolak</div></div></div>
        <div class="stat-card"><div class="stat-icon blue">💰</div><div class="stat-body"><div class="stat-val" style="font-size:1.1rem;"><?= formatRupiah($totalVerif) ?></div><div class="stat-lbl">Total Terverifikasi</div></div></div>
      </div>

      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title">💳 Semua Pembayaran</div>
          <form method="GET" class="filter-bar no-print">
            <select class="form-ctrl" name="status" style="width:auto;padding:9px 14px;border-radius:10px;" onchange="this.form.submit()">
              <option value="">Semua Status</option>
              <option value="menunggu"      <?= $filterStatus==='menunggu'?'selected':'' ?>>Menunggu</option>
              <option value="terverifikasi" <?= $filterStatus==='terverifikasi'?'selected':'' ?>>Terverifikasi</option>
              <option value="ditolak"       <?= $filterStatus==='ditolak'?'selected':'' ?>>Ditolak</option>
            </select>
          </form>
        </div>
        <div class="admin-card-body admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>#</th><th>Pelanggan</th><th>Paket</th><th>Metode</th><th>Jumlah</th><th>Bukti</th><th>Tanggal</th><th>Status</th><th class="no-print">Aksi</th></tr></thead>
            <tbody>
              <?php if (empty($payments)): ?>
              <tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon">💳</div><h4>Belum ada pembayaran</h4></div></td></tr>
              <?php else: ?>
              <?php foreach ($payments as $b): ?>
              <tr>
                <td><b>#<?= str_pad($b['id_pembayaran'],4,'0',STR_PAD_LEFT) ?></b></td>
                <td><?= htmlspecialchars($b['nama_pelanggan']) ?></td>
                <td><?= htmlspecialchars($b['jenis_desain']) ?></td>
                <td><?= htmlspecialchars($b['metode_pembayaran']) ?></td>
                <td><b><?= formatRupiah($b['jumlah_bayar']) ?></b></td>
                <td>
                  <?php if ($b['bukti_pembayaran']): ?>
                  <a href="../<?= htmlspecialchars($b['bukti_pembayaran']) ?>" target="_blank" style="color:#2563EB;font-size:12px;">📎 Lihat</a>
                  <?php else: echo '-'; endif; ?>
                </td>
                <td><?= date('d M Y', strtotime($b['tanggal_bayar'])) ?></td>
                <td><?= statusBayarBadge($b['status_pembayaran']) ?></td>
                <td class="no-print">
                  <?php if ($b['status_pembayaran'] === 'menunggu'): ?>
                  <div style="display:flex;gap:5px;">
                    <a href="pembayaran.php?verif=<?= $b['id_pembayaran'] ?>&status=<?= $filterStatus ?>" class="topbar-btn btn-primary-sm" style="font-size:11px;padding:5px 9px;"
                       onclick="return confirm('Verifikasi pembayaran ini?')">✅</a>
                    <a href="pembayaran.php?tolak=<?= $b['id_pembayaran'] ?>&status=<?= $filterStatus ?>" class="topbar-btn btn-danger-sm" style="font-size:11px;padding:5px 9px;"
                       onclick="return confirm('Tolak pembayaran ini?')">❌</a>
                  </div>
                  <?php else: echo '<span style="color:#94A3B8;font-size:12px;">—</span>'; endif; ?>
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
