<?php
// ============================================================
// admin/index.php — Dashboard Admin SEKALA
// ============================================================
$current_page = 'dashboard';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin_auth.php';

// Statistik
$totalCustomer  = $conn->query("SELECT COUNT(*) AS n FROM tb_pelanggan")->fetch_assoc()['n'] ?? 0;
$totalPesanan   = $conn->query("SELECT COUNT(*) AS n FROM tb_pesanan")->fetch_assoc()['n'] ?? 0;
$totalPendapatan = $conn->query("SELECT COALESCE(SUM(jumlah_bayar),0) AS n FROM tb_pembayaran WHERE status_pembayaran='terverifikasi'")->fetch_assoc()['n'] ?? 0;
$totalMenunggu  = $conn->query("SELECT COUNT(*) AS n FROM tb_pembayaran WHERE status_pembayaran='menunggu'")->fetch_assoc()['n'] ?? 0;

// Data chart: pesanan per bulan (12 bulan terakhir)
$chartData = [];
for ($i = 11; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $res   = $conn->query("SELECT COUNT(*) AS n FROM tb_pesanan WHERE DATE_FORMAT(tanggal_pesan,'%Y-%m') = '$bulan'");
    $chartData[] = ['label' => $label, 'val' => ($res ? $res->fetch_assoc()['n'] : 0)];
}

// Data chart: distribusi status pesanan
$statusData = [];
$statusRows = $conn->query("SELECT status_pesanan, COUNT(*) AS n FROM tb_pesanan GROUP BY status_pesanan");
if ($statusRows) { while ($r = $statusRows->fetch_assoc()) $statusData[] = $r; }

// Pesanan terbaru
$recentPesanan = [];
$rp = $conn->query(
    "SELECT o.id_pesanan, o.judul_proyek, o.status_pesanan, o.tanggal_pesan,
            p.nama AS nama_pelanggan, d.jenis_desain
     FROM tb_pesanan o
     JOIN tb_pelanggan p ON o.id_pelanggan = p.id_pelanggan
     JOIN tb_desain d    ON o.id_desain    = d.id_desain
     ORDER BY o.tanggal_pesan DESC LIMIT 5"
);
if ($rp) { while ($r = $rp->fetch_assoc()) $recentPesanan[] = $r; }

// Pembayaran menunggu verifikasi
$pendingBayar = [];
$pb = $conn->query(
    "SELECT b.id_pembayaran, b.metode_pembayaran, b.jumlah_bayar, b.tanggal_bayar,
            p.nama AS nama_pelanggan
     FROM tb_pembayaran b
     JOIN tb_pesanan o ON b.id_pesanan = o.id_pesanan
     JOIN tb_pelanggan p ON o.id_pelanggan = p.id_pelanggan
     WHERE b.status_pembayaran = 'menunggu'
     ORDER BY b.tanggal_bayar DESC LIMIT 5"
);
if ($pb) { while ($r = $pb->fetch_assoc()) $pendingBayar[] = $r; }

function statusBadge($s) {
    $map = [
        'pending'        => ['badge-pending','⏳ Pending'],
        'proses'         => ['badge-proses','🔄 Proses'],
        'selesai'        => ['badge-selesai','✅ Selesai'],
        'batal'          => ['badge-batal','❌ Batal'],
        'menunggu'       => ['badge-menunggu','⏳ Menunggu'],
        'terverifikasi'  => ['badge-terverifikasi','✅ Terverifikasi'],
        'ditolak'        => ['badge-batal','❌ Ditolak'],
    ];
    [$cls,$label] = $map[$s] ?? ['badge-pending', $s];
    return "<span class=\"badge $cls\">$label</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Dashboard Admin — SEKALA</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/admin.css?v=2">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="admin-layout">

  <?php include 'includes/sidebar.php'; ?>

  <div class="admin-main">
    <!-- Topbar -->
    <div class="admin-topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">☰</button>
        <div>
          <div class="topbar-page-title">Dashboard</div>
          <div class="topbar-breadcrumb">Admin / Dashboard</div>
        </div>
      </div>
      <div class="topbar-right">
        <a href="../index.php" target="_blank" class="topbar-btn btn-outline-sm">🌐 Lihat Website</a>
      </div>
    </div>

    <div class="admin-content">

      <!-- Stat Cards -->
      <div class="stat-cards">
        <div class="stat-card">
          <div class="stat-icon blue">👥</div>
          <div class="stat-body">
            <div class="stat-val"><?= number_format($totalCustomer) ?></div>
            <div class="stat-lbl">Total Customer</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">📋</div>
          <div class="stat-body">
            <div class="stat-val"><?= number_format($totalPesanan) ?></div>
            <div class="stat-lbl">Total Pesanan</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon yellow">💰</div>
          <div class="stat-body">
            <div class="stat-val" style="font-size:1.2rem;"><?= formatRupiah($totalPendapatan) ?></div>
            <div class="stat-lbl">Total Pendapatan</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red">⏳</div>
          <div class="stat-body">
            <div class="stat-val"><?= number_format($totalMenunggu) ?></div>
            <div class="stat-lbl">Pembayaran Menunggu</div>
          </div>
        </div>
      </div>

      <!-- Charts -->
      <div class="charts-grid">
        <div class="chart-box">
          <div class="chart-box-title">📈 Tren Pesanan (12 Bulan Terakhir)</div>
          <canvas id="chartPesanan" height="120"></canvas>
        </div>
        <div class="chart-box">
          <div class="chart-box-title">📊 Status Pesanan</div>
          <canvas id="chartStatus" height="120"></canvas>
        </div>
      </div>

      <!-- Tabel Pesanan Terbaru -->
      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title">🕐 Pesanan Terbaru</div>
          <a href="pesanan.php" class="topbar-btn btn-outline-sm">Lihat Semua</a>
        </div>
        <div class="admin-card-body admin-table-wrap">
          <table class="admin-table">
            <thead><tr>
              <th>#</th><th>Pelanggan</th><th>Paket</th><th>Judul</th><th>Tanggal</th><th>Status</th>
            </tr></thead>
            <tbody>
              <?php if (empty($recentPesanan)): ?>
              <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">📋</div><h4>Belum ada pesanan</h4></div></td></tr>
              <?php else: ?>
              <?php foreach ($recentPesanan as $r): ?>
              <tr>
                <td><b>#<?= str_pad($r['id_pesanan'],4,'0',STR_PAD_LEFT) ?></b></td>
                <td><?= htmlspecialchars($r['nama_pelanggan']) ?></td>
                <td><?= htmlspecialchars($r['jenis_desain']) ?></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($r['judul_proyek']) ?></td>
                <td><?= date('d M Y', strtotime($r['tanggal_pesan'])) ?></td>
                <td><?= statusBadge($r['status_pesanan']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pembayaran Menunggu -->
      <?php if (!empty($pendingBayar)): ?>
      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title">⚠️ Pembayaran Menunggu Verifikasi</div>
          <a href="pembayaran.php" class="topbar-btn btn-primary-sm">Verifikasi Sekarang</a>
        </div>
        <div class="admin-card-body admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>#</th><th>Pelanggan</th><th>Metode</th><th>Jumlah</th><th>Tanggal</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($pendingBayar as $b): ?>
              <tr>
                <td><b>#<?= str_pad($b['id_pembayaran'],4,'0',STR_PAD_LEFT) ?></b></td>
                <td><?= htmlspecialchars($b['nama_pelanggan']) ?></td>
                <td><?= htmlspecialchars($b['metode_pembayaran']) ?></td>
                <td><?= formatRupiah($b['jumlah_bayar']) ?></td>
                <td><?= date('d M Y H:i', strtotime($b['tanggal_bayar'])) ?></td>
                <td>
                  <a href="pembayaran.php?verif=<?= $b['id_pembayaran'] ?>" class="topbar-btn btn-primary-sm" style="font-size:11px;padding:5px 10px;">✅ Verifikasi</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- end .admin-content -->
  </div><!-- end .admin-main -->
</div>

<script>
// Chart Pesanan
const chartLabels = <?= json_encode(array_column($chartData,'label')) ?>;
const chartVals   = <?= json_encode(array_column($chartData,'val')) ?>;
new Chart(document.getElementById('chartPesanan'), {
  type: 'bar',
  data: {
    labels: chartLabels,
    datasets: [{
      label: 'Jumlah Pesanan',
      data: chartVals,
      backgroundColor: 'rgba(37,99,235,0.15)',
      borderColor: '#2563EB',
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}} }
});

// Chart Status
const statusLabels = <?= json_encode(array_column($statusData,'status_pesanan')) ?>;
const statusVals   = <?= json_encode(array_column($statusData,'n')) ?>;
const statusColors = ['#F59E0B','#3B82F6','#10B981','#EF4444'];
if (statusLabels.length > 0) {
  new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
      labels: statusLabels,
      datasets: [{ data: statusVals, backgroundColor: statusColors, borderWidth: 0 }]
    },
    options: { responsive:true, plugins:{legend:{position:'bottom', labels:{boxWidth:12,font:{size:11}}}} }
  });
} else {
  document.getElementById('chartStatus').parentElement.innerHTML += '<p style="text-align:center;color:#94A3B8;font-size:13px;padding:20px 0;">Belum ada data</p>';
}
</script>
</body>
</html>
