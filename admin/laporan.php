<?php
// ============================================================
// admin/laporan.php — Laporan Gabungan + Export PDF (print)
// ============================================================
$current_page = 'laporan';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin_auth.php';

// Filter bulan
$filterBulan = $_GET['bulan'] ?? date('Y-m');
$bulanEsc    = $conn->real_escape_string($filterBulan);

// Total keseluruhan
$totalCustomer   = $conn->query("SELECT COUNT(*) AS n FROM tb_pelanggan")->fetch_assoc()['n'] ?? 0;
$totalPesanan    = $conn->query("SELECT COUNT(*) AS n FROM tb_pesanan")->fetch_assoc()['n'] ?? 0;
$totalPendapatan = $conn->query("SELECT COALESCE(SUM(jumlah_bayar),0) AS n FROM tb_pembayaran WHERE status_pembayaran='terverifikasi'")->fetch_assoc()['n'] ?? 0;
$totalMenunggu   = $conn->query("SELECT COALESCE(SUM(jumlah_bayar),0) AS n FROM tb_pembayaran WHERE status_pembayaran='menunggu'")->fetch_assoc()['n'] ?? 0;

// Data per bulan yang dipilih
$pesananBulan = [];
$res = $conn->query(
    "SELECT o.id_pesanan, o.judul_proyek, o.status_pesanan, o.tanggal_pesan,
            p.nama AS nama_pelanggan, d.jenis_desain, d.harga_mulai,
            b.metode_pembayaran, b.jumlah_bayar, b.status_pembayaran
     FROM tb_pesanan o
     JOIN tb_pelanggan p  ON o.id_pelanggan = p.id_pelanggan
     JOIN tb_desain d     ON o.id_desain    = d.id_desain
     LEFT JOIN tb_pembayaran b ON o.id_pesanan = b.id_pesanan
     WHERE DATE_FORMAT(o.tanggal_pesan,'%Y-%m') = '$bulanEsc'
     ORDER BY o.tanggal_pesan DESC"
);
if ($res) { while ($r = $res->fetch_assoc()) $pesananBulan[] = $r; }

$pendapatanBulan = array_sum(array_map(
    fn($r) => ($r['status_pembayaran']==='terverifikasi') ? $r['jumlah_bayar'] : 0,
    $pesananBulan
));

$bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$bulanLabel = $bulanIndo[date('n', strtotime($filterBulan . '-01')) - 1] . ' ' . date('Y', strtotime($filterBulan . '-01'));

function statusBadge2($s) {
    $map = ['pending'=>['badge-pending','⏳'],'proses'=>['badge-proses','🔄'],'selesai'=>['badge-selesai','✅'],'batal'=>['badge-batal','❌']];
    [$cls,$icon] = $map[$s] ?? ['badge-pending','?'];
    return "<span class=\"badge $cls\">$icon " . ucfirst($s) . "</span>";
}
function statusBayarBadge2($s) {
    $map = ['menunggu'=>['badge-menunggu','⏳'],'terverifikasi'=>['badge-terverifikasi','✅'],'ditolak'=>['badge-batal','❌']];
    [$cls,$icon] = $map[$s] ?? ['badge-pending','?'];
    return "<span class=\"badge $cls\">$icon " . ucfirst($s) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Laporan — SEKALA Admin</title>
  <link rel="icon" type="image/png" href="../assets/Logo3.png" style="border-radius: 50%; width: 32px; height: 32px;">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    .print-header { display:none; text-align:center; margin-bottom:24px; }
    .print-header h2 { font-family:'Sora',sans-serif; font-size:1.4rem; font-weight:800; color:#0F1B2D; }
    .print-header p  { font-size:13px; color:#64748B; margin-top:4px; }
    @media print {
      .print-header { display:block !important; }
      .stat-cards { grid-template-columns: repeat(4,1fr) !important; }
    }
  </style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/sidebar.php'; ?>
  <div class="admin-main">
    <div class="admin-topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">☰</button>
        <div><div class="topbar-page-title">Laporan</div><div class="topbar-breadcrumb">Admin / Laporan</div></div>
      </div>
      <div class="topbar-right no-print">
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
          <input type="month" name="bulan" value="<?= htmlspecialchars($filterBulan) ?>" class="form-ctrl" style="width:auto;padding:8px 12px;border-radius:10px;">
          <button type="submit" class="topbar-btn btn-primary-sm">Filter</button>
        </form>
        <button onclick="window.print()" class="topbar-btn btn-outline-sm">🖨️ Print / PDF</button>
      </div>
    </div>
    <div class="admin-content">

      <!-- Print Header (hanya muncul saat print) -->
      <div class="print-header">
        <h2>Laporan SEKALA — <?= htmlspecialchars($bulanLabel) ?></h2>
        <p>Dicetak pada: <?= date('d F Y H:i') ?> | Admin: <?= htmlspecialchars($_SESSION['nama'] ?? '') ?></p>
      </div>

      <!-- Summary Total -->
      <h3 style="font-family:'Sora',sans-serif;font-size:.95rem;font-weight:700;color:var(--gray);margin-bottom:12px;text-transform:uppercase;letter-spacing:.05em;">📊 Ringkasan Keseluruhan</h3>
      <div class="stat-cards" style="margin-bottom:28px;">
        <div class="stat-card"><div class="stat-icon blue">👥</div><div class="stat-body"><div class="stat-val"><?= number_format($totalCustomer) ?></div><div class="stat-lbl">Total Customer</div></div></div>
        <div class="stat-card"><div class="stat-icon green">📋</div><div class="stat-body"><div class="stat-val"><?= number_format($totalPesanan) ?></div><div class="stat-lbl">Total Pesanan</div></div></div>
        <div class="stat-card"><div class="stat-icon yellow">💰</div><div class="stat-body"><div class="stat-val" style="font-size:1.1rem;"><?= formatRupiah($totalPendapatan) ?></div><div class="stat-lbl">Pendapatan Terverifikasi</div></div></div>
        <div class="stat-card"><div class="stat-icon red">⏳</div><div class="stat-body"><div class="stat-val" style="font-size:1.1rem;"><?= formatRupiah($totalMenunggu) ?></div><div class="stat-lbl">Menunggu Verifikasi</div></div></div>
      </div>

      <!-- Laporan Bulan -->
      <h3 style="font-family:'Sora',sans-serif;font-size:.95rem;font-weight:700;color:var(--gray);margin-bottom:12px;text-transform:uppercase;letter-spacing:.05em;">📅 Laporan Bulan: <?= htmlspecialchars($bulanLabel) ?></h3>

      <div class="stat-cards" style="margin-bottom:20px;">
        <div class="stat-card"><div class="stat-icon blue">📋</div><div class="stat-body"><div class="stat-val"><?= count($pesananBulan) ?></div><div class="stat-lbl">Pesanan Bulan Ini</div></div></div>
        <div class="stat-card"><div class="stat-icon green">💰</div><div class="stat-body"><div class="stat-val" style="font-size:1.1rem;"><?= formatRupiah($pendapatanBulan) ?></div><div class="stat-lbl">Pendapatan Bulan Ini</div></div></div>
      </div>

      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title">📋 Detail Pesanan — <?= htmlspecialchars($bulanLabel) ?></div>
        </div>
        <div class="admin-card-body admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>#</th><th>Pelanggan</th><th>Paket</th><th>Harga</th><th>Status Pesanan</th><th>Metode</th><th>Jml Bayar</th><th>Status Bayar</th><th>Tanggal</th></tr></thead>
            <tbody>
              <?php if (empty($pesananBulan)): ?>
              <tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon">📊</div><h4>Tidak ada data untuk bulan ini</h4></div></td></tr>
              <?php else: ?>
              <?php foreach ($pesananBulan as $r): ?>
              <tr>
                <td><b>#<?= str_pad($r['id_pesanan'],4,'0',STR_PAD_LEFT) ?></b></td>
                <td><?= htmlspecialchars($r['nama_pelanggan']) ?></td>
                <td><?= htmlspecialchars($r['jenis_desain']) ?></td>
                <td><?= formatRupiah($r['harga_mulai']) ?></td>
                <td><?= statusBadge2($r['status_pesanan']) ?></td>
                <td><?= htmlspecialchars($r['metode_pembayaran'] ?? '-') ?></td>
                <td><?= $r['jumlah_bayar'] ? formatRupiah($r['jumlah_bayar']) : '-' ?></td>
                <td><?= $r['status_pembayaran'] ? statusBayarBadge2($r['status_pembayaran']) : '-' ?></td>
                <td><?= date('d M Y', strtotime($r['tanggal_pesan'])) ?></td>
              </tr>
              <?php endforeach; ?>
              <tr style="font-weight:700;background:#F8FAFC;">
                <td colspan="6" style="text-align:right;padding-right:20px;">Total Pendapatan Bulan Ini:</td>
                <td colspan="3" style="color:var(--success);font-size:1rem;"><?= formatRupiah($pendapatanBulan) ?></td>
              </tr>
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
