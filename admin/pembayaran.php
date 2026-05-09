<?php
// ============================================================
// admin/pembayaran.php — Data Pembayaran + Verifikasi
// ============================================================
$current_page = 'pembayaran';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin_auth.php';

$flash_msg  = '';
$flash_type = '';

// --- Ambil flash dari session (setelah redirect) ---
if (!empty($_SESSION['admin_flash'])) {
    $flash_msg  = $_SESSION['admin_flash']['msg'];
    $flash_type = $_SESSION['admin_flash']['type'];
    unset($_SESSION['admin_flash']);
}

// Verifikasi pembayaran
if (isset($_GET['verif'])) {
    $id = (int)$_GET['verif'];

    // 1. Update status pembayaran
    $stmtV = $conn->prepare("UPDATE tb_pembayaran SET status_pembayaran='terverifikasi' WHERE id_pembayaran=?");
    $stmtV->bind_param('i', $id);
    $stmtV->execute();
    $stmtV->close();

    // 2. Dapatkan id_pesanan dari pembayaran ini
    $stmtG = $conn->prepare("SELECT id_pesanan FROM tb_pembayaran WHERE id_pembayaran=? LIMIT 1");
    $stmtG->bind_param('i', $id);
    $stmtG->execute();
    $rowG = $stmtG->get_result()->fetch_assoc();
    $stmtG->close();

    // 3. Update status pesanan menjadi selesai
    if ($rowG) {
        $id_pesanan_update = (int)$rowG['id_pesanan'];
        $stmtP = $conn->prepare("UPDATE tb_pesanan SET status_pesanan='selesai' WHERE id_pesanan=?");
        $stmtP->bind_param('i', $id_pesanan_update);
        $stmtP->execute();
        $stmtP->close();
    }

    $_SESSION['admin_flash'] = ['msg' => 'Pembayaran berhasil diverifikasi.', 'type' => 'success'];
    header('Location: pembayaran.php');
    exit;
}

// Tolak pembayaran
if (isset($_GET['tolak'])) {
    $id = (int)$_GET['tolak'];

    // 1. Update status pembayaran
    $stmtT = $conn->prepare("UPDATE tb_pembayaran SET status_pembayaran='ditolak' WHERE id_pembayaran=?");
    $stmtT->bind_param('i', $id);
    $stmtT->execute();
    $stmtT->close();

    // 2. Kembalikan status pesanan ke pending agar pelanggan bisa bayar ulang
    $stmtG2 = $conn->prepare("SELECT id_pesanan FROM tb_pembayaran WHERE id_pembayaran=? LIMIT 1");
    $stmtG2->bind_param('i', $id);
    $stmtG2->execute();
    $rowG2 = $stmtG2->get_result()->fetch_assoc();
    $stmtG2->close();

    if ($rowG2) {
        $id_pesanan_tolak = (int)$rowG2['id_pesanan'];
        $stmtP2 = $conn->prepare("UPDATE tb_pesanan SET status_pesanan='pending' WHERE id_pesanan=?");
        $stmtP2->bind_param('i', $id_pesanan_tolak);
        $stmtP2->execute();
        $stmtP2->close();
    }

    $_SESSION['admin_flash'] = ['msg' => 'Pembayaran ditolak. Pelanggan dapat melakukan pembayaran ulang.', 'type' => 'error'];
    header('Location: pembayaran.php');
    exit;
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
  <style>
    /* QRIS badge for payment method */
    .qris-pill {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: linear-gradient(135deg, #E53935, #B71C1C);
      color: #fff;
      font-size: 11px;
      font-weight: 800;
      padding: 4px 10px;
      border-radius: 999px;
      letter-spacing: 0.5px;
    }
    /* QRIS info bar */
    .qris-info-bar {
      background: linear-gradient(135deg, #fff5f5, #fff);
      border: 1.5px solid #FECACA;
      border-radius: 16px;
      padding: 18px 24px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .qris-info-bar img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 10px;
      border: 2px solid #E53935;
    }
    .qris-info-bar-text h4 {
      font-family: 'Sora', sans-serif;
      font-size: 1rem;
      font-weight: 700;
      color: #B91C1C;
      margin-bottom: 4px;
    }
    .qris-info-bar-text p {
      font-size: 0.82rem;
      color: #64748B;
      line-height: 1.5;
    }
    /* Bukti preview link */
    .bukti-link {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      color: #2563EB;
      font-size: 12px;
      font-weight: 600;
      text-decoration: none;
      padding: 4px 8px;
      background: #EFF6FF;
      border-radius: 6px;
      transition: all 0.2s;
    }
    .bukti-link:hover {
      background: #DBEAFE;
      color: #1D4ED8;
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

      <!-- QRIS Info Bar -->
      <div class="qris-info-bar no-print">
        <img src="../assets/QrisSekala.jpg" alt="QRIS SEKALA">
        <div class="qris-info-bar-text">
          <h4>📱 Metode Pembayaran: QRIS</h4>
          <p>
            <b>SEKALA DESAIN</b> · NMID: ID1026517275388 · A01<br>
            Semua pembayaran pelanggan menggunakan QRIS.<br>
            Verifikasi dengan mencocokkan bukti transfer pelanggan dengan nominal pesanan.
          </p>
        </div>
      </div>

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
          <div class="admin-card-title">📱 Semua Pembayaran QRIS</div>
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
                <td><span class="qris-pill">&#9654; QRIS</span></td>
                <td><b><?= formatRupiah($b['jumlah_bayar']) ?></b></td>
                <td>
                  <?php if ($b['bukti_pembayaran']): ?>
                  <a href="../<?= htmlspecialchars($b['bukti_pembayaran']) ?>" target="_blank" class="bukti-link">📎 Lihat Bukti</a>
                  <?php else: ?>
                  <span style="color:#CBD5E1;font-size:12px;">Belum upload</span>
                  <?php endif; ?>
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
