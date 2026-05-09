<?php
// ============================================================
// admin/pesanan.php — Data Pemesanan
// ============================================================
$current_page = 'pesanan';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin_auth.php';

$flash_msg  = '';
$flash_type = '';

// Update status pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id     = (int)$_POST['id_pesanan'];
    $status = $conn->real_escape_string($_POST['status_pesanan'] ?? '');
    $allowed = ['pending','proses','selesai','batal'];
    if ($id > 0 && in_array($status, $allowed)) {
        $conn->query("UPDATE tb_pesanan SET status_pesanan='$status' WHERE id_pesanan=$id");
        $flash_msg  = 'Status pesanan berhasil diperbarui.';
        $flash_type = 'success';
    }
}

// Filter
$filterStatus = $conn->real_escape_string($_GET['status'] ?? '');
$search       = $conn->real_escape_string($_GET['q'] ?? '');

$where  = "WHERE 1";
if ($filterStatus) $where .= " AND o.status_pesanan = '$filterStatus'";
if ($search)       $where .= " AND (p.nama LIKE '%$search%' OR o.judul_proyek LIKE '%$search%')";

$pesanans = [];
$res = $conn->query(
    "SELECT o.id_pesanan, o.judul_proyek, o.deskripsi_proyek, o.status_pesanan,
            o.tanggal_pesan, o.deadline, o.file_referensi,
            p.nama AS nama_pelanggan, p.email AS email_pelanggan,
            d.jenis_desain, d.harga_mulai
     FROM tb_pesanan o
     JOIN tb_pelanggan p ON o.id_pelanggan = p.id_pelanggan
     JOIN tb_desain d    ON o.id_desain    = d.id_desain
     $where
     ORDER BY o.tanggal_pesan DESC"
);
if ($res) { while ($r = $res->fetch_assoc()) $pesanans[] = $r; }

function statusBadge($s) {
    $map = [
        'pending' =>['badge-pending','⏳ Pending'],
        'proses'  =>['badge-proses','🔄 Proses'],
        'selesai' =>['badge-selesai','✅ Selesai'],
        'batal'   =>['badge-batal','❌ Batal'],
    ];
    [$cls,$label] = $map[$s] ?? ['badge-pending',$s];
    return "<span class=\"badge $cls\">$label</span>";
}

// Detail pesanan untuk modal
$detailPesanan = null;
if (isset($_GET['detail'])) {
    $did = (int)$_GET['detail'];
    $dr = $conn->query(
        "SELECT o.*, p.nama AS nama_pelanggan, p.email AS email_pelanggan, p.no_hp,
                d.jenis_desain, d.harga_mulai
         FROM tb_pesanan o
         JOIN tb_pelanggan p ON o.id_pelanggan=p.id_pelanggan
         JOIN tb_desain d    ON o.id_desain=d.id_desain
         WHERE o.id_pesanan=$did LIMIT 1"
    );
    if ($dr) $detailPesanan = $dr->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Data Pemesanan — SEKALA Admin</title>
  <link rel="icon" type="image/png" href="../assets/Logo3.png" style="border-radius: 50%; width: 32px; height: 32px;">
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
        <div><div class="topbar-page-title">Data Pemesanan</div><div class="topbar-breadcrumb">Admin / Pemesanan</div></div>
      </div>
    </div>
    <div class="admin-content">
      <?php if ($flash_msg): ?>
      <div class="admin-flash <?= $flash_type ?>"><?= $flash_type==='success'?'✅':'❌' ?> <?= htmlspecialchars($flash_msg) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title">📋 Semua Pesanan (<?= count($pesanans) ?>)</div>
          <form method="GET" class="filter-bar">
            <input class="search-input" type="text" name="q" placeholder="Cari nama/judul..." value="<?= htmlspecialchars($search) ?>">
            <select class="form-ctrl" name="status" style="width:auto;padding:9px 14px;border-radius:10px;">
              <option value="">Semua Status</option>
              <option value="pending"  <?= $filterStatus==='pending'?'selected':'' ?>>Pending</option>
              <option value="proses"   <?= $filterStatus==='proses'?'selected':'' ?>>Proses</option>
              <option value="selesai"  <?= $filterStatus==='selesai'?'selected':'' ?>>Selesai</option>
              <option value="batal"    <?= $filterStatus==='batal'?'selected':'' ?>>Batal</option>
            </select>
            <button type="submit" class="topbar-btn btn-primary-sm">Filter</button>
            <?php if ($filterStatus||$search): ?><a href="pesanan.php" class="topbar-btn btn-outline-sm">Reset</a><?php endif; ?>
          </form>
        </div>
        <div class="admin-card-body admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>#</th><th>Pelanggan</th><th>Paket</th><th>Judul</th><th>Deadline</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php if (empty($pesanans)): ?>
              <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">📋</div><h4>Belum ada pesanan</h4></div></td></tr>
              <?php else: ?>
              <?php foreach ($pesanans as $p): ?>
              <tr>
                <td><b>#<?= str_pad($p['id_pesanan'],4,'0',STR_PAD_LEFT) ?></b></td>
                <td>
                  <div style="font-weight:600;"><?= htmlspecialchars($p['nama_pelanggan']) ?></div>
                  <div style="font-size:11px;color:#94A3B8;"><?= htmlspecialchars($p['email_pelanggan']) ?></div>
                </td>
                <td><?= htmlspecialchars($p['jenis_desain']) ?></td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($p['judul_proyek']) ?></td>
                <td><?= $p['deadline'] ? date('d M Y', strtotime($p['deadline'])) : '-' ?></td>
                <td><?= statusBadge($p['status_pesanan']) ?></td>
                <td>
                  <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <a href="pesanan.php?detail=<?= $p['id_pesanan'] ?>" class="topbar-btn btn-outline-sm" style="font-size:11px;padding:5px 10px;">👁 Detail</a>
                    <button onclick="openUpdateStatus(<?= $p['id_pesanan'] ?>,'<?= $p['status_pesanan'] ?>')" class="topbar-btn btn-primary-sm" style="font-size:11px;padding:5px 10px;">🔄 Status</button>
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

<!-- Modal Update Status -->
<div class="modal-overlay" id="statusModal">
  <div class="modal-box">
    <div class="modal-title">🔄 Update Status Pesanan</div>
    <form method="POST" action="pesanan.php">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="id_pesanan" id="status-id">
      <div class="form-grp">
        <label class="form-lbl">Status Pesanan</label>
        <select class="form-ctrl" name="status_pesanan" id="status-select">
          <option value="pending">⏳ Pending</option>
          <option value="proses">🔄 Proses</option>
          <option value="selesai">✅ Selesai</option>
          <option value="batal">❌ Batal</option>
        </select>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
        <button type="button" onclick="closeModal('statusModal')" class="topbar-btn btn-outline-sm">Batal</button>
        <button type="submit" class="topbar-btn btn-primary-sm">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Detail -->
<?php if ($detailPesanan): ?>
<div class="modal-overlay open">
  <div class="modal-box" style="max-width:640px;">
    <div class="modal-title">📋 Detail Pesanan #<?= str_pad($detailPesanan['id_pesanan'],4,'0',STR_PAD_LEFT) ?></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13px;margin-bottom:16px;">
      <div><b>Pelanggan:</b><br><?= htmlspecialchars($detailPesanan['nama_pelanggan']) ?></div>
      <div><b>Email:</b><br><?= htmlspecialchars($detailPesanan['email_pelanggan']) ?></div>
      <div><b>Paket:</b><br><?= htmlspecialchars($detailPesanan['jenis_desain']) ?></div>
      <div><b>Harga:</b><br><?= formatRupiah($detailPesanan['harga_mulai']) ?></div>
      <div><b>Deadline:</b><br><?= $detailPesanan['deadline'] ? date('d M Y',strtotime($detailPesanan['deadline'])) : '-' ?></div>
      <div><b>Status:</b><br><?= statusBadge($detailPesanan['status_pesanan']) ?></div>
    </div>
    <div style="background:#F8FAFC;border-radius:10px;padding:14px;font-size:12.5px;color:#334155;margin-bottom:16px;">
      <b>Deskripsi Proyek:</b><br><br>
      <?= nl2br(htmlspecialchars($detailPesanan['deskripsi_proyek'])) ?>
    </div>
    <?php if ($detailPesanan['file_referensi']): ?>
    <div style="margin-bottom:16px;font-size:13px;">
      <b>File Referensi:</b> <a href="../<?= htmlspecialchars($detailPesanan['file_referensi']) ?>" target="_blank" style="color:#2563EB;">📎 Lihat File</a>
    </div>
    <?php endif; ?>
    <div style="display:flex;justify-content:flex-end;">
      <a href="pesanan.php" class="topbar-btn btn-outline-sm">Tutup</a>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function openUpdateStatus(id, status) {
  document.getElementById('status-id').value = id;
  document.getElementById('status-select').value = status;
  document.getElementById('statusModal').classList.add('open');
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
</script>
</body>
</html>
