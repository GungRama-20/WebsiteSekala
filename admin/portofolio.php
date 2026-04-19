<?php
// ============================================================
// admin/portofolio.php — CRUD Portofolio
// ============================================================
$current_page = 'portofolio';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin_auth.php';

$flash_msg  = '';
$flash_type = '';

// Hapus
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = $conn->query("SELECT gambar FROM tb_portofolio WHERE id_portofolio=$id LIMIT 1")->fetch_assoc();
    if ($row && $row['gambar'] && file_exists('../' . $row['gambar'])) @unlink('../' . $row['gambar']);
    $conn->query("DELETE FROM tb_portofolio WHERE id_portofolio=$id");
    $flash_msg = 'Portofolio berhasil dihapus.'; $flash_type = 'success';
}

// Tambah / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $id      = (int)($_POST['id_portofolio'] ?? 0);
    $judul   = $conn->real_escape_string(trim($_POST['judul'] ?? ''));
    $kategori= $conn->real_escape_string(trim($_POST['kategori'] ?? ''));
    $deskripsi=$conn->real_escape_string(trim($_POST['deskripsi'] ?? ''));

    $gambar_path = '';
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $fn = 'porto_' . time() . '_' . rand(100,999) . '.' . $ext;
            if (!is_dir('../uploads/porto')) mkdir('../uploads/porto', 0755, true);
            move_uploaded_file($_FILES['gambar']['tmp_name'], '../uploads/porto/' . $fn);
            $gambar_path = 'uploads/porto/' . $fn;
        }
    }

    if ($action === 'tambah' && $judul) {
        $gm = $gambar_path ? "'$gambar_path'" : "NULL";
        $conn->query("INSERT INTO tb_portofolio (judul, kategori, deskripsi, gambar) VALUES ('$judul','$kategori','$deskripsi',$gm)");
        $flash_msg = 'Portofolio berhasil ditambahkan.'; $flash_type = 'success';
    } elseif ($action === 'edit' && $id > 0 && $judul) {
        $gmUpdate = $gambar_path ? ", gambar='$gambar_path'" : '';
        $conn->query("UPDATE tb_portofolio SET judul='$judul', kategori='$kategori', deskripsi='$deskripsi'$gmUpdate WHERE id_portofolio=$id");
        $flash_msg = 'Portofolio berhasil diperbarui.'; $flash_type = 'success';
    }
}

// List
$portos = [];
$res = $conn->query("SELECT * FROM tb_portofolio ORDER BY tanggal_upload DESC");
if ($res) { while ($r = $res->fetch_assoc()) $portos[] = $r; }

// Edit data
$editPorto = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $er  = $conn->query("SELECT * FROM tb_portofolio WHERE id_portofolio=$eid LIMIT 1");
    if ($er) $editPorto = $er->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Data Portofolio — SEKALA Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/admin.css">
  <style>
    .porto-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;padding:20px;}
    .porto-card{border-radius:14px;overflow:hidden;border:1.5px solid var(--border);background:#fff;transition:box-shadow .2s,transform .2s;}
    .porto-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.08);transform:translateY(-3px);}
    .porto-img{width:100%;height:160px;object-fit:cover;background:linear-gradient(135deg,#1C4E8C,#3B82F6);display:flex;align-items:center;justify-content:center;font-size:40px;}
    .porto-info{padding:14px;}
    .porto-cat{font-size:11px;color:var(--primary-mid);font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;}
    .porto-title{font-weight:700;color:var(--dark);font-size:.9rem;margin-bottom:10px;}
    .porto-actions{display:flex;gap:6px;}
  </style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/sidebar.php'; ?>
  <div class="admin-main">
    <div class="admin-topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" onclick="document.getElementById('adminSidebar').classList.toggle('open')">☰</button>
        <div><div class="topbar-page-title">Data Portofolio</div><div class="topbar-breadcrumb">Admin / Portofolio</div></div>
      </div>
      <div class="topbar-right">
        <button onclick="document.getElementById('tambahModal').classList.add('open')" class="topbar-btn btn-primary-sm">➕ Tambah</button>
      </div>
    </div>
    <div class="admin-content">
      <?php if ($flash_msg): ?>
      <div class="admin-flash <?= $flash_type ?>"><?= $flash_type==='success'?'✅':'❌' ?> <?= htmlspecialchars($flash_msg) ?></div>
      <?php endif; ?>

      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title">🎨 Portofolio (<?= count($portos) ?>)</div>
        </div>
        <?php if (empty($portos)): ?>
        <div class="empty-state"><div class="empty-state-icon">🎨</div><h4>Belum ada portofolio</h4><p>Klik tombol "+ Tambah" untuk menambahkan karya.</p></div>
        <?php else: ?>
        <div class="porto-grid">
          <?php foreach ($portos as $po): ?>
          <div class="porto-card">
          <?php
          // Handle path gambar: bisa 'assets/...' (root) atau 'uploads/porto/...'
          $imgSrc = '';
          if ($po['gambar']) {
              if (file_exists('../' . $po['gambar'])) {
                  $imgSrc = '../' . $po['gambar'];
              } elseif (file_exists($po['gambar'])) {
                  $imgSrc = $po['gambar'];
              }
          }
          ?>
          <?php if ($imgSrc): ?>
          <img class="porto-img" src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($po['judul']) ?>">
          <?php else: ?>
          <div class="porto-img" style="background:linear-gradient(135deg,#1C4E8C,#3B82F6);">🎨</div>
          <?php endif; ?>
            <div class="porto-info">
              <div class="porto-cat"><?= htmlspecialchars($po['kategori'] ?: 'Desain') ?></div>
              <div class="porto-title"><?= htmlspecialchars($po['judul']) ?></div>
              <div class="porto-actions">
                <a href="portofolio.php?edit=<?= $po['id_portofolio'] ?>" class="topbar-btn btn-outline-sm" style="font-size:11px;padding:5px 10px;flex:1;text-align:center;">✏️ Edit</a>
                <a href="portofolio.php?delete=<?= $po['id_portofolio'] ?>" class="topbar-btn btn-danger-sm" style="font-size:11px;padding:5px 10px;"
                   onclick="return confirm('Hapus portofolio ini?')">🗑️</a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="tambahModal">
  <div class="modal-box">
    <div class="modal-title">➕ Tambah Portofolio</div>
    <form method="POST" action="portofolio.php" enctype="multipart/form-data">
      <input type="hidden" name="action" value="tambah">
      <div class="form-grp"><label class="form-lbl">Judul *</label><input class="form-ctrl" type="text" name="judul" required placeholder="Nama karya..."></div>
      <div class="form-row">
        <div class="form-grp"><label class="form-lbl">Kategori</label>
          <select class="form-ctrl" name="kategori">
            <option value="Poster & Visual">Poster & Visual</option>
            <option value="Social Media Feed">Social Media Feed</option>
            <option value="Video Pendek">Video Pendek</option>
            <option value="Branding">Branding</option>
          </select>
        </div>
      </div>
      <div class="form-grp"><label class="form-lbl">Deskripsi</label><textarea class="form-ctrl" name="deskripsi" rows="2" style="border-radius:10px;resize:vertical;"></textarea></div>
      <div class="form-grp"><label class="form-lbl">Gambar (JPG/PNG)</label><input class="form-ctrl" type="file" name="gambar" accept="image/*"></div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
        <button type="button" onclick="document.getElementById('tambahModal').classList.remove('open')" class="topbar-btn btn-outline-sm">Batal</button>
        <button type="submit" class="topbar-btn btn-primary-sm">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<?php if ($editPorto): ?>
<div class="modal-overlay open">
  <div class="modal-box">
    <div class="modal-title">✏️ Edit Portofolio</div>
    <form method="POST" action="portofolio.php" enctype="multipart/form-data">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id_portofolio" value="<?= $editPorto['id_portofolio'] ?>">
      <div class="form-grp"><label class="form-lbl">Judul *</label><input class="form-ctrl" type="text" name="judul" value="<?= htmlspecialchars($editPorto['judul']) ?>" required></div>
      <div class="form-row">
        <div class="form-grp"><label class="form-lbl">Kategori</label>
          <select class="form-ctrl" name="kategori">
            <?php foreach (['Poster & Visual','Social Media Feed','Video Pendek','Branding'] as $k): ?>
            <option value="<?= $k ?>" <?= $editPorto['kategori']===$k?'selected':'' ?>><?= $k ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-grp"><label class="form-lbl">Deskripsi</label><textarea class="form-ctrl" name="deskripsi" rows="2" style="border-radius:10px;resize:vertical;"><?= htmlspecialchars($editPorto['deskripsi']) ?></textarea></div>
      <div class="form-grp"><label class="form-lbl">Ganti Gambar (kosongkan jika tidak berubah)</label><input class="form-ctrl" type="file" name="gambar" accept="image/*"></div>
      <?php if ($editPorto['gambar']): ?>
      <div style="font-size:12px;color:#94A3B8;margin-bottom:12px;">Gambar saat ini: <?= htmlspecialchars(basename($editPorto['gambar'])) ?></div>
      <?php endif; ?>
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <a href="portofolio.php" class="topbar-btn btn-outline-sm">Batal</a>
        <button type="submit" class="topbar-btn btn-primary-sm">Simpan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
</body>
</html>
