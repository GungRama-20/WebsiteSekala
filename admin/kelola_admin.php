<?php
// ============================================================
// admin/kelola_admin.php — Kelola Akun Admin SEKALA
// ============================================================
$current_page = 'kelola_admin';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin_auth.php';

$msg  = '';
$type = '';

// ---- HAPUS ADMIN ----
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    if ($id_hapus === (int)$_SESSION['id_user']) {
        $msg  = 'Tidak dapat menghapus akun yang sedang digunakan.';
        $type = 'error';
    } else {
        $stmt = $conn->prepare("DELETE FROM tb_user WHERE id_user = ? AND role = 'Admin'");
        $stmt->bind_param('i', $id_hapus);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $msg  = 'Akun admin berhasil dihapus.';
            $type = 'success';
        } else {
            $msg  = 'Admin tidak ditemukan.';
            $type = 'error';
        }
        $stmt->close();
    }
}

// ---- TAMBAH / EDIT ADMIN ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode     = $_POST['mode']     ?? 'tambah';
    $id_edit  = (int)($_POST['id_edit'] ?? 0);
    $nama     = trim($_POST['nama']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    // Validasi dasar
    if (empty($nama) || empty($email)) {
        $msg  = 'Nama dan email wajib diisi.';
        $type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg  = 'Format email tidak valid.';
        $type = 'error';
    } elseif ($mode === 'tambah' && empty($password)) {
        $msg  = 'Password wajib diisi untuk admin baru.';
        $type = 'error';
    } else {

        if ($mode === 'tambah') {
            // Cek email sudah terdaftar
            $cek = $conn->prepare("SELECT id_user FROM tb_user WHERE email = ?");
            $cek->bind_param('s', $email);
            $cek->execute();
            $cek->store_result();
            if ($cek->num_rows > 0) {
                $msg  = 'Email sudah digunakan oleh akun lain.';
                $type = 'error';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins  = $conn->prepare("INSERT INTO tb_user (nama, email, password, role) VALUES (?, ?, ?, 'Admin')");
                $ins->bind_param('sss', $nama, $email, $hash);
                $ins->execute();
                if ($ins->affected_rows > 0) {
                    $msg  = "Admin \"$nama\" berhasil ditambahkan.";
                    $type = 'success';
                } else {
                    $msg  = 'Gagal menambahkan admin.';
                    $type = 'error';
                }
                $ins->close();
            }
            $cek->close();

        } elseif ($mode === 'edit') {
            // Cek email bentrok dengan user lain
            $cek = $conn->prepare("SELECT id_user FROM tb_user WHERE email = ? AND id_user != ?");
            $cek->bind_param('si', $email, $id_edit);
            $cek->execute();
            $cek->store_result();
            if ($cek->num_rows > 0) {
                $msg  = 'Email sudah digunakan oleh akun lain.';
                $type = 'error';
            } else {
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $upd  = $conn->prepare("UPDATE tb_user SET nama=?, email=?, password=? WHERE id_user=? AND role='Admin'");
                    $upd->bind_param('sssi', $nama, $email, $hash, $id_edit);
                } else {
                    $upd = $conn->prepare("UPDATE tb_user SET nama=?, email=? WHERE id_user=? AND role='Admin'");
                    $upd->bind_param('ssi', $nama, $email, $id_edit);
                }
                $upd->execute();
                // Update session jika edit diri sendiri
                if ($id_edit === (int)$_SESSION['id_user']) {
                    $_SESSION['nama']  = $nama;
                    $_SESSION['email'] = $email;
                }
                $msg  = "Akun admin \"$nama\" berhasil diperbarui.";
                $type = 'success';
                $upd->close();
            }
            $cek->close();
        }
    }
}

// ---- DATA EDIT (jika GET edit=id) ----
$editData = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $eid  = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT id_user, nama, email FROM tb_user WHERE id_user = ? AND role = 'Admin'");
    $stmt->bind_param('i', $eid);
    $stmt->execute();
    $res  = $stmt->get_result();
    $editData = $res->fetch_assoc();
    $stmt->close();
}

// ---- DAFTAR SEMUA ADMIN ----
$admins = [];
$res = $conn->query("SELECT id_user, nama, email FROM tb_user WHERE role = 'Admin' ORDER BY id_user ASC");
if ($res) { while ($r = $res->fetch_assoc()) $admins[] = $r; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Kelola Admin — SEKALA</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    /* ---- FORM CARD ---- */
    .form-section {
      background: #fff;
      border: 1.5px solid #E2E8F0;
      border-radius: 20px;
      padding: 28px 32px;
      margin-bottom: 28px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    }
    .form-section-title {
      font-family: 'Sora', sans-serif;
      font-size: 1rem; font-weight: 700; color: #0F1B2D;
      margin-bottom: 20px;
      display: flex; align-items: center; gap: 8px;
    }
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    .form-grid-3 {
      grid-template-columns: 1fr 1fr 1fr;
    }
    @media(max-width:700px) { .form-grid, .form-grid-3 { grid-template-columns: 1fr; } }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-label { font-size: 13px; font-weight: 600; color: #334155; }
    .form-label span { font-size: 11px; font-weight: 400; color: #94A3B8; margin-left: 4px; }
    .form-control {
      padding: 11px 16px;
      border: 1.5px solid #E2E8F0; border-radius: 10px;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 14px; color: #0F1B2D; background: #F8FAFC;
      outline: none; transition: all 0.2s;
    }
    .form-control:focus { border-color: #7C3AED; background: #fff; box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
    .form-actions { display: flex; gap: 10px; margin-top: 20px; }
    .btn-submit {
      padding: 11px 26px; border-radius: 999px;
      background: linear-gradient(135deg, #7C3AED, #A855F7);
      color: #fff; font-size: 14px; font-weight: 700;
      border: none; cursor: pointer;
      font-family: 'Plus Jakarta Sans', sans-serif;
      box-shadow: 0 4px 14px rgba(124,58,237,0.3);
      transition: all 0.25s;
    }
    .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(124,58,237,0.4); }
    .btn-cancel {
      padding: 11px 22px; border-radius: 999px;
      background: #F1F5F9; border: 1.5px solid #E2E8F0;
      color: #64748B; font-size: 14px; font-weight: 600;
      text-decoration: none; display: inline-flex; align-items: center;
      font-family: 'Plus Jakarta Sans', sans-serif; transition: all 0.2s;
    }
    .btn-cancel:hover { background: #E2E8F0; color: #334155; }

    /* ---- ALERT ---- */
    .page-alert {
      padding: 14px 20px; border-radius: 14px;
      font-size: 14px; font-weight: 500;
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 20px;
    }
    .page-alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #6EE7B7; }
    .page-alert-error   { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }

    /* ---- TABLE ---- */
    .admin-badge-you {
      display: inline-block; padding: 2px 8px; border-radius: 999px;
      background: #EDE9FE; color: #7C3AED;
      font-size: 10px; font-weight: 700; margin-left: 6px;
    }
    .admin-avatar {
      width: 36px; height: 36px; border-radius: 50%;
      background: linear-gradient(135deg, #7C3AED, #A855F7);
      color: #fff; font-size: 15px; font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .admin-name-cell { display: flex; align-items: center; gap: 10px; }
    .btn-edit-sm {
      padding: 5px 12px; border-radius: 8px;
      background: #DBEAFE; color: #1D4ED8;
      font-size: 12px; font-weight: 600; text-decoration: none;
      transition: all 0.2s;
    }
    .btn-edit-sm:hover { background: #BFDBFE; }
    .btn-del-sm {
      padding: 5px 12px; border-radius: 8px;
      background: #FEE2E2; color: #DC2626;
      font-size: 12px; font-weight: 600; text-decoration: none;
      transition: all 0.2s; border: none; cursor: pointer;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .btn-del-sm:hover { background: #FECACA; }
    .btn-del-sm:disabled { opacity: 0.4; cursor: not-allowed; }
    .hint-text { font-size: 12px; color: #94A3B8; margin-top: 4px; }
  </style>
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
          <div class="topbar-page-title">Kelola Admin</div>
          <div class="topbar-breadcrumb">Admin / Kelola Admin</div>
        </div>
      </div>
      <div class="topbar-right">
        <a href="../index.php" target="_blank" class="topbar-btn btn-outline-sm">🌐 Lihat Website</a>
      </div>
    </div>

    <div class="admin-content">

      <!-- Alert -->
      <?php if ($msg): ?>
      <div class="page-alert page-alert-<?= $type ?>">
        <?= $type === 'success' ? '✅' : '❌' ?>
        <?= htmlspecialchars($msg) ?>
      </div>
      <?php endif; ?>

      <!-- Form Tambah / Edit -->
      <div class="form-section">
        <div class="form-section-title">
          <?= $editData ? '✏️ Edit Akun Admin' : '➕ Tambah Admin Baru' ?>
        </div>
        <form method="POST" action="kelola_admin.php">
          <input type="hidden" name="mode"    value="<?= $editData ? 'edit' : 'tambah' ?>">
          <input type="hidden" name="id_edit" value="<?= $editData['id_user'] ?? '' ?>">

          <div class="form-grid form-grid-3">
            <div class="form-group">
              <label class="form-label">Nama Admin</label>
              <input class="form-control" type="text" name="nama"
                     placeholder="Contoh: Rama - Admin"
                     value="<?= htmlspecialchars($editData['nama'] ?? '') ?>"
                     required>
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email"
                     placeholder="admin@sekala.com"
                     value="<?= htmlspecialchars($editData['email'] ?? '') ?>"
                     required>
            </div>
            <div class="form-group">
              <label class="form-label">
                Password
                <?php if ($editData): ?>
                  <span>(kosongkan jika tidak diubah)</span>
                <?php endif; ?>
              </label>
              <input class="form-control" type="password" name="password"
                     placeholder="<?= $editData ? 'Isi jika ingin ganti password' : 'Password baru admin' ?>"
                     <?= $editData ? '' : 'required' ?>>
            </div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-submit">
              <?= $editData ? '💾 Simpan Perubahan' : '➕ Tambah Admin' ?>
            </button>
            <?php if ($editData): ?>
            <a href="kelola_admin.php" class="btn-cancel">✖ Batal</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Daftar Admin -->
      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title">🛡️ Daftar Akun Admin</div>
          <div style="font-size:13px;color:#94A3B8;"><?= count($admins) ?> admin terdaftar</div>
        </div>
        <div class="admin-card-body admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Nama Admin</th>
                <th>Email</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($admins)): ?>
              <tr><td colspan="4">
                <div class="empty-state">
                  <div class="empty-state-icon">🛡️</div>
                  <h4>Belum ada admin</h4>
                </div>
              </td></tr>
              <?php else: ?>
              <?php foreach ($admins as $i => $a): ?>
              <tr>
                <td><b><?= $i + 1 ?></b></td>
                <td>
                  <div class="admin-name-cell">
                    <div class="admin-avatar"><?= strtoupper(mb_substr($a['nama'], 0, 1)) ?></div>
                    <div>
                      <?= htmlspecialchars($a['nama']) ?>
                      <?php if ($a['id_user'] == $_SESSION['id_user']): ?>
                        <span class="admin-badge-you">Anda</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($a['email']) ?></td>
                <td>
                  <div style="display:flex;gap:8px;align-items:center;">
                    <a href="kelola_admin.php?edit=<?= $a['id_user'] ?>" class="btn-edit-sm">✏️ Edit</a>
                    <?php if ($a['id_user'] != $_SESSION['id_user']): ?>
                    <button class="btn-del-sm"
                      onclick="if(confirm('Yakin hapus admin <?= addslashes(htmlspecialchars($a['nama'])) ?>?')) window.location='kelola_admin.php?hapus=<?= $a['id_user'] ?>'">
                      🗑️ Hapus
                    </button>
                    <?php else: ?>
                    <button class="btn-del-sm" disabled title="Tidak bisa hapus akun sendiri">🗑️ Hapus</button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div style="padding:12px 20px;">
          <p class="hint-text">ℹ️ Admin yang sedang login tidak dapat dihapus. Password hanya diperbarui jika diisi saat edit.</p>
        </div>
      </div>

    </div><!-- end .admin-content -->
  </div><!-- end .admin-main -->
</div>
</body>
</html>
