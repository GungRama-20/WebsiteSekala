<?php
// ============================================================
// admin/pengunjung.php — Data Pengunjung Website
// ============================================================
$current_page = 'pengunjung';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin_auth.php';

// --- Filter Handling ---
$filter_tipe = $_GET['filter_tipe'] ?? 'tahun';
$filter_tahun = $_GET['tahun'] ?? date('Y');
$filter_bulan = $_GET['bulan'] ?? date('m');

$where_clause = "1=1";
$title_suffix = "Keseluruhan";

if ($filter_tipe === 'tahun') {
    $filter_tahun = (int)$filter_tahun;
    $where_clause = "YEAR(waktu_masuk) = $filter_tahun";
    $title_suffix = "Tahun $filter_tahun";
} elseif ($filter_tipe === 'bulan') {
    $filter_tahun = (int)$filter_tahun;
    $filter_bulan = (int)$filter_bulan;
    $where_clause = "YEAR(waktu_masuk) = $filter_tahun AND MONTH(waktu_masuk) = $filter_bulan";
    $title_suffix = "Bulan " . date('F', mktime(0,0,0,$filter_bulan,1)) . " $filter_tahun";
}

// --- Cards Data ---
$kunjungan_home = $conn->query("SELECT COUNT(*) AS c FROM tb_pengunjung WHERE halaman='Home' AND $where_clause")->fetch_assoc()['c'] ?? 0;
$kunjungan_detail = $conn->query("SELECT COUNT(*) AS c FROM tb_pengunjung WHERE halaman='Detail' AND $where_clause")->fetch_assoc()['c'] ?? 0;
$kunjungan_testi = $conn->query("SELECT COUNT(*) AS c FROM tb_pengunjung WHERE halaman='Testimoni' AND $where_clause")->fetch_assoc()['c'] ?? 0;
$kunjungan_total = $conn->query("SELECT COUNT(*) AS c FROM tb_pengunjung WHERE $where_clause")->fetch_assoc()['c'] ?? 0;

// --- Chart 1: Pengunjung per periode (Bar) ---
$chartLabels = [];
$chartVals = [];

if ($filter_tipe === 'bulan') {
    // Tampilkan per hari
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $filter_bulan, $filter_tahun);
    for ($i=1; $i<=$daysInMonth; $i++) {
        $chartLabels[] = $i;
        $chartVals[] = 0;
    }
    $res = $conn->query("SELECT DAY(waktu_masuk) AS d, COUNT(*) AS c FROM tb_pengunjung WHERE $where_clause GROUP BY d");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $chartVals[$r['d'] - 1] = (int)$r['c'];
        }
    }
} else {
    // Tampilkan per bulan (Jan-Des)
    $bulanNama = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $chartLabels = $bulanNama;
    $chartVals = array_fill(0, 12, 0);
    $res = $conn->query("SELECT MONTH(waktu_masuk) AS m, COUNT(*) AS c FROM tb_pengunjung WHERE $where_clause GROUP BY m");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $chartVals[$r['m'] - 1] = (int)$r['c'];
        }
    }
}

// --- Chart 2: Rata-rata Durasi (Doughnut) ---
$durasiHome = $conn->query("SELECT COALESCE(AVG(durasi_detik),0) AS a FROM tb_pengunjung WHERE halaman='Home' AND $where_clause")->fetch_assoc()['a'] ?? 0;
$durasiDetail = $conn->query("SELECT COALESCE(AVG(durasi_detik),0) AS a FROM tb_pengunjung WHERE halaman='Detail' AND $where_clause")->fetch_assoc()['a'] ?? 0;
$durasiTesti = $conn->query("SELECT COALESCE(AVG(durasi_detik),0) AS a FROM tb_pengunjung WHERE halaman='Testimoni' AND $where_clause")->fetch_assoc()['a'] ?? 0;

$pieLabels = ['Home', 'Detail', 'Testimoni'];
$pieVals = [round($durasiHome), round($durasiDetail), round($durasiTesti)];

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Data Pengunjung — SEKALA Admin</title>
  <link rel="icon" type="image/png" href="../assets/Logo3.png" style="border-radius: 50%; width: 32px; height: 32px;">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/admin.css?v=3">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
  <style>
    @media print {
      body * { visibility: hidden; }
      .print-area, .print-area * { visibility: visible; }
      .print-area { position: absolute; left: 0; top: 0; width: 100%; }
      .admin-topbar, .sidebar-brand, .admin-sidebar, .print-hide { display: none !important; }
      canvas { max-height: 300px; }
      .chart-box { page-break-inside: avoid; }
    }
    .filter-card {
      background: #fff; padding: 16px 20px; border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.04); margin-bottom: 24px;
      display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;
    }
    .filter-group { display: flex; flex-direction: column; gap: 6px; }
    .filter-group label { font-size: 13px; font-weight: 600; color: #64748B; }
    .filter-group select, .filter-group input {
      padding: 8px 12px; border-radius: 8px; border: 1.5px solid #E2E8F0;
      font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px;
      outline: none; transition: 0.2s;
    }
    .filter-group select:focus, .filter-group input:focus { border-color: #2563EB; }
    .btn-filter {
      padding: 9px 16px; border-radius: 8px; background: #2563EB; color: #fff;
      font-weight: 600; border: none; cursor: pointer; transition: 0.2s;
    }
    .btn-filter:hover { background: #1D4ED8; }
    
    .charts-grid-visitor {
      display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;
    }
    @media (max-width: 900px) {
      .charts-grid-visitor { grid-template-columns: 1fr; }
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
        <div>
          <div class="topbar-page-title">Data Pengunjung</div>
          <div class="topbar-breadcrumb">Pengunjung Website</div>
        </div>
      </div>
      <div class="topbar-right">
        <button onclick="window.print()" class="topbar-btn btn-outline-sm print-hide">🖨️ Print PDF</button>
      </div>
    </div>

    <div class="admin-content print-area">
      
      <!-- Filter -->
      <form method="GET" class="filter-card print-hide">
        <div class="filter-group">
          <label>Tipe Filter</label>
          <select name="filter_tipe" id="filter_tipe" onchange="toggleFilterInputs()">
            <option value="keseluruhan" <?= $filter_tipe=='keseluruhan'?'selected':'' ?>>Keseluruhan</option>
            <option value="tahun" <?= $filter_tipe=='tahun'?'selected':'' ?>>Per Tahun</option>
            <option value="bulan" <?= $filter_tipe=='bulan'?'selected':'' ?>>Per Bulan</option>
          </select>
        </div>
        <div class="filter-group" id="group_tahun" style="<?= $filter_tipe=='keseluruhan' ? 'display:none;' : '' ?>">
          <label>Tahun</label>
          <input type="number" name="tahun" value="<?= htmlspecialchars($filter_tahun) ?>" min="2020" max="2099">
        </div>
        <div class="filter-group" id="group_bulan" style="<?= $filter_tipe!=='bulan' ? 'display:none;' : '' ?>">
          <label>Bulan</label>
          <select name="bulan">
            <?php for($i=1; $i<=12; $i++): ?>
            <option value="<?= $i ?>" <?= $filter_bulan==$i ? 'selected':'' ?>><?= date('F', mktime(0,0,0,$i,1)) ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <button type="submit" class="btn-filter">Terapkan Filter</button>
      </form>

      <h3 style="margin-bottom: 20px; color: #0F1B2D;">Statistik: <?= $title_suffix ?></h3>

      <!-- Stat Cards -->
      <div class="stat-cards">
        <div class="stat-card">
          <div class="stat-icon blue">🌐</div>
          <div class="stat-body">
            <div class="stat-val"><?= number_format($kunjungan_total) ?></div>
            <div class="stat-lbl">Total Kunjungan</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green">🏠</div>
          <div class="stat-body">
            <div class="stat-val"><?= number_format($kunjungan_home) ?></div>
            <div class="stat-lbl">Kunjungan Home</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon yellow">📦</div>
          <div class="stat-body">
            <div class="stat-val"><?= number_format($kunjungan_detail) ?></div>
            <div class="stat-lbl">Kunjungan Detail</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red">⭐</div>
          <div class="stat-body">
            <div class="stat-val"><?= number_format($kunjungan_testi) ?></div>
            <div class="stat-lbl">Kunjungan Testimoni</div>
          </div>
        </div>
      </div>

      <!-- Charts -->
      <div class="charts-grid-visitor">
        <div class="chart-box">
          <div class="chart-box-title">📈 Trafik Pengunjung (<?= $filter_tipe=='bulan'?'Harian':'Bulanan' ?>)</div>
          <canvas id="visitorBarChart" height="100"></canvas>
        </div>
        <div class="chart-box">
          <div class="chart-box-title">⏱️ Rata-rata Durasi (Detik)</div>
          <canvas id="visitorPieChart" height="120"></canvas>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
function toggleFilterInputs() {
  const type = document.getElementById('filter_tipe').value;
  document.getElementById('group_tahun').style.display = type === 'keseluruhan' ? 'none' : 'flex';
  document.getElementById('group_bulan').style.display = type === 'bulan' ? 'flex' : 'none';
}

// Chart Data
const barLabels = <?= json_encode($chartLabels) ?>;
const barVals   = <?= json_encode($chartVals) ?>;

new Chart(document.getElementById('visitorBarChart'), {
  type: 'bar',
  data: {
    labels: barLabels,
    datasets: [{
      label: 'Jumlah Pengunjung',
      data: barVals,
      backgroundColor: 'rgba(37,99,235,0.2)',
      borderColor: '#2563EB',
      borderWidth: 2,
      borderRadius: 4,
    }]
  },
  options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}} }
});

const pieLabels = <?= json_encode($pieLabels) ?>;
const pieVals   = <?= json_encode($pieVals) ?>;
const pieColors = ['#10B981', '#F59E0B', '#EF4444'];

if (pieVals.some(v => v > 0)) {
  new Chart(document.getElementById('visitorPieChart'), {
    type: 'doughnut',
    data: {
      labels: pieLabels,
      datasets: [{ data: pieVals, backgroundColor: pieColors, borderWidth: 0 }]
    },
    options: { responsive:true, plugins:{legend:{position:'bottom'}} }
  });
} else {
  document.getElementById('visitorPieChart').parentElement.innerHTML += '<p style="text-align:center;color:#94A3B8;font-size:13px;padding:20px 0;">Belum ada data durasi</p>';
}
</script>
</body>
</html>
