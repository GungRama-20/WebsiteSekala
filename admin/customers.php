<?php
// ============================================================
// admin/customers.php — Data Customer
// ============================================================
$current_page = 'customers';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin_auth.php';

$flash_msg  = '';
$flash_type = '';

// Hapus customer
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM tb_pelanggan WHERE id_pelanggan = $id");
    $flash_msg  = 'Data customer berhasil dihapus.';
    $flash_type = 'success';
}

// Edit customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id    = (int)$_POST['id_pelanggan'];
    $nama  = $conn->real_escape_string(trim($_POST['nama'] ?? ''));
    $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $no_hp = $conn->real_escape_string(trim($_POST['no_hp'] ?? ''));
    $alamat= $conn->real_escape_string(trim($_POST['alamat'] ?? ''));
    if ($nama && $email && $id > 0) {
        $conn->query("UPDATE tb_pelanggan SET nama='$nama', email='$email', no_hp='$no_hp', alamat='$alamat' WHERE id_pelanggan=$id");
        $flash_msg  = 'Data customer berhasil diperbarui.';
        $flash_type = 'success';
    }
}

// Ambil data customer
$search = $conn->real_escape_string($_GET['q'] ?? '');
$where  = $search ? "WHERE p.nama LIKE '%$search%' OR p.email LIKE '%$search%'" : '';
$customers = [];
$res = $conn->query(
    "SELECT p.id_pelanggan, p.nama, p.email, p.no_hp, p.alamat, p.tanggal_daftar,
            COUNT(o.id_pesanan) AS total_pesanan
     FROM tb_pelanggan p
     LEFT JOIN tb_pesanan o ON p.id_pelanggan = o.id_pelanggan
     $where
     GROUP BY p.id_pelanggan
     ORDER BY p.tanggal_daftar DESC"
);
if ($res) { while ($r = $res->fetch_assoc()) $customers[] = $r; }

// Customer untuk edit modal
$editCust = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $er  = $conn->query("SELECT * FROM tb_pelanggan WHERE id_pelanggan = $eid LIMIT 1");
    if ($er) $editCust = $er->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Data Customer — SEKALA Admin</title>
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
        <div><div class="topbar-page-title">Data Customer</div><div class="topbar-breadcrumb">Admin / Customer</div></div>
      </div>
    </div>
    <div class="admin-content">
      <?php if ($flash_msg): ?>
      <div class="admin-flash <?= $flash_type ?>"><?= $flash_type==='success'?'✅':'❌' ?> <?= htmlspecialchars($flash_msg) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title">👥 Semua Customer (<?= count($customers) ?>)</div>
          <form method="GET" class="filter-bar">
            <input class="search-input" type="text" name="q" placeholder="Cari nama / email..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="topbar-btn btn-primary-sm">Cari</button>
            <?php if ($search): ?><a href="customers.php" class="topbar-btn btn-outline-sm">Reset</a><?php endif; ?>
          </form>
        </div>
        <div class="admin-card-body admin-table-wrap">
          <table class="admin-table">
            <thead><tr>
              <th>#ID</th><th>Nama</th><th>Email</th><th>No HP</th>
              <th>Total Pesanan</th><th>Daftar</th><th>Aksi</th>
            </tr></thead>
            <tbody>
              <?php if (empty($customers)): ?>
              <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">👥</div><h4>Belum ada customer</h4></div></td></tr>
              <?php else: ?>
              <?php foreach ($customers as $c): ?>
              <tr>
                <td><b>#<?= $c['id_pelanggan'] ?></b></td>
                <td>
                  <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#1C4E8C,#3B82F6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0;">
                      <?= strtoupper(mb_substr($c['nama'],0,1)) ?>
                    </div>
                    <?= htmlspecialchars($c['nama']) ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['no_hp'] ?: '-') ?></td>
                <td><span class="badge badge-proses"><?= $c['total_pesanan'] ?> pesanan</span></td>
                <td><?= $c['tanggal_daftar'] ? date('d M Y', strtotime($c['tanggal_daftar'])) : '-' ?></td>
                <td>
                  <div style="display:flex;gap:6px;">
                    <a href="customers.php?edit=<?= $c['id_pelanggan'] ?>" class="topbar-btn btn-outline-sm" style="font-size:11px;padding:5px 10px;">✏️ Edit</a>
                    <a href="customers.php?delete=<?= $c['id_pelanggan'] ?>" class="topbar-btn btn-danger-sm" style="font-size:11px;padding:5px 10px;"
                       onclick="return confirm('Hapus customer ini?')">🗑️ Hapus</a>
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

<!-- Modal Edit -->
<?php if ($editCust): ?>
<div class="modal-overlay open" id="editModal">
  <div class="modal-box">
    <div class="modal-title">✏️ Edit Data Customer</div>
    <form method="POST" action="customers.php">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id_pelanggan" value="<?= $editCust['id_pelanggan'] ?>">
      <div class="form-grp">
        <label class="form-lbl">Nama Lengkap</label>
        <input class="form-ctrl" type="text" name="nama" value="<?= htmlspecialchars($editCust['nama']) ?>" required>
      </div>
      <div class="form-grp">
        <label class="form-lbl">Email</label>
        <input class="form-ctrl" type="email" name="email" value="<?= htmlspecialchars($editCust['email']) ?>" required>
      </div>
      <div class="form-row">
        <div class="form-grp">
          <label class="form-lbl">No HP</label>
          <input class="form-ctrl" type="text" name="no_hp" value="<?= htmlspecialchars($editCust['no_hp']) ?>">
        </div>
        <div class="form-grp">
          <label class="form-lbl">Alamat</label>
          <input class="form-ctrl" type="text" name="alamat" value="<?= htmlspecialchars($editCust['alamat']) ?>">
        </div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
        <a href="customers.php" class="topbar-btn btn-outline-sm">Batal</a>
        <button type="submit" class="topbar-btn btn-primary-sm">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
</body>
</html>
