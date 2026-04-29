<?php
// ============================================================
// profile.php — Halaman Profil User/Admin di Website Utama
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$id_user = $_SESSION['id_user'] ?? 0;

// Pastikan kolom tambahan ada
$alterQueries = [
    "ALTER TABLE tb_user ADD COLUMN IF NOT EXISTS no_telp VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE tb_user ADD COLUMN IF NOT EXISTS foto_profil VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE tb_user ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL",
];
foreach ($alterQueries as $q) { @$conn->query($q); }

// Ambil data user
$userData = null;
$stmt = $conn->prepare("SELECT * FROM tb_user WHERE id_user = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $userData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
if (!$userData) {
    $userData = [
        'id_user'     => $id_user,
        'nama'        => $_SESSION['nama'] ?? 'User',
        'email'       => $_SESSION['email'] ?? '',
        'no_telp'     => '',
        'role'        => $_SESSION['role'] ?? 'Pelanggan',
        'foto_profil' => null,
        'updated_at'  => null,
    ];
}

$noTelp     = $userData['no_telp']     ?? '';
$fotoProfil = $userData['foto_profil'] ?? null;
$updatedAt  = $userData['updated_at']  ?? null;
$isAdmin    = ($userData['role'] ?? '') === 'Admin';

// Flash
$flash = '';
if (isset($_SESSION['profile_msg'])) {
    $flash = $_SESSION['profile_msg'];
    unset($_SESSION['profile_msg']);
}

// Statistik
$totalPesanan = 0;
if ($isAdmin) {
    $r = $conn->query("SELECT COUNT(*) AS n FROM tb_pesanan");
    if ($r) $totalPesanan = $r->fetch_assoc()['n'] ?? 0;
} else {
    $r = $conn->prepare("SELECT COUNT(*) AS n FROM tb_pesanan o JOIN tb_pelanggan p ON o.id_pelanggan=p.id_pelanggan WHERE p.email=?");
    if ($r) { $r->bind_param("s",$userData['email']); $r->execute(); $totalPesanan = $r->get_result()->fetch_assoc()['n'] ?? 0; $r->close(); }
}

$totalVerif = 0;
$rv = $conn->query("SELECT COUNT(*) AS n FROM tb_pembayaran WHERE status_pembayaran='terverifikasi'");
if ($rv) $totalVerif = $rv->fetch_assoc()['n'] ?? 0;

$totalCustomer = 0;
$rc = $conn->query("SELECT COUNT(*) AS n FROM tb_pelanggan");
if ($rc) $totalCustomer = $rc->fetch_assoc()['n'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Profil Saya — SEKALA</title>
  <meta name="description" content="Kelola informasi profil dan pengaturan akun SEKALA Anda">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="pages.css">
  <link rel="stylesheet" href="profile-web.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- Hero Banner -->
<div class="profile-web-hero">
  <div class="profile-web-hero-bg"></div>
  <div class="container">
    <div class="profile-web-hero-inner">
      <div class="profile-web-hero-avatar-wrap">
        <?php if ($fotoProfil && file_exists(__DIR__ . '/uploads/' . $fotoProfil)): ?>
          <img src="uploads/<?= htmlspecialchars($fotoProfil) ?>" alt="Foto Profil" class="profile-web-hero-avatar-img">
        <?php else: ?>
          <div class="profile-web-hero-avatar-letter">
            <?= strtoupper(mb_substr($userData['nama'] ?? 'U', 0, 1)) ?>
          </div>
        <?php endif; ?>
        <label for="photoInputHero" class="profile-web-photo-overlay" title="Ubah foto profil">
          <span>📷</span>
        </label>
        <input type="file" id="photoInputHero" accept="image/*" style="display:none" onchange="previewPhotoHero(this)">
      </div>
      <div class="profile-web-hero-info">
        <div class="profile-web-hero-name"><?= htmlspecialchars($userData['nama'] ?? 'User') ?></div>
        <div class="profile-web-hero-meta">
          <?php if ($isAdmin): ?>
            <span class="profile-web-role-badge admin-badge">🛡️ Administrator</span>
          <?php else: ?>
            <span class="profile-web-role-badge user-badge">👤 Pelanggan</span>
          <?php endif; ?>
          <span class="profile-web-hero-email">✉️ <?= htmlspecialchars($userData['email'] ?? '') ?></span>
        </div>
        <p class="profile-web-hero-sub">Kelola informasi profile dan pengaturan akun anda</p>
      </div>
    </div>
  </div>
</div>

<!-- Content -->
<section class="profile-web-section">
  <div class="container">

    <?php if ($flash): ?>
    <div class="profile-web-flash <?= strpos($flash,'berhasil') !== false ? 'flash-ok' : 'flash-err' ?>" id="webFlash">
      <?= strpos($flash,'berhasil') !== false ? '✅' : '❌' ?> <?= htmlspecialchars($flash) ?>
    </div>
    <?php endif; ?>

    <div class="profile-web-grid">

      <!-- ====== KIRI: Form ====== -->
      <div class="profile-web-left">

        <!-- Kartu identitas foto -->
        <div class="pweb-card pweb-id-card">
          <div class="pweb-card-head">
            <span class="pweb-card-head-icon">🖼️</span>
            <div>
              <div class="pweb-card-head-title">Foto Profil</div>
              <div class="pweb-card-head-sub">Klik foto untuk mengganti</div>
            </div>
          </div>
          <div class="pweb-photo-section">
            <div class="pweb-photo-wrap" id="photoWrapWeb">
              <?php if ($fotoProfil && file_exists(__DIR__ . '/uploads/' . $fotoProfil)): ?>
                <img src="uploads/<?= htmlspecialchars($fotoProfil) ?>" alt="Foto" class="pweb-photo-img" id="webPhotoImg">
              <?php else: ?>
                <div class="pweb-photo-placeholder" id="webPhotoPlaceholder">
                  <?= strtoupper(mb_substr($userData['nama'] ?? 'U', 0, 1)) ?>
                </div>
              <?php endif; ?>
              <label for="photoInputCard" class="pweb-photo-overlay">
                <span class="pweb-photo-overlay-icon">📷</span>
                <span class="pweb-photo-overlay-txt">Ubah Foto</span>
              </label>
            </div>
            <div class="pweb-photo-info">
              <div class="pweb-photo-name"><?= htmlspecialchars($userData['nama'] ?? '') ?></div>
              <div class="pweb-photo-email"><?= htmlspecialchars($userData['email'] ?? '') ?></div>
              <?php if ($isAdmin): ?>
                <span class="profile-web-role-badge admin-badge" style="margin-top:8px;font-size:11px;">🛡️ Administrator</span>
              <?php else: ?>
                <span class="profile-web-role-badge user-badge" style="margin-top:8px;font-size:11px;">👤 Pelanggan</span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Form edit -->
        <div class="pweb-card">
          <div class="pweb-card-head">
            <span class="pweb-card-head-icon">✏️</span>
            <div>
              <div class="pweb-card-head-title">Edit Informasi Profil</div>
              <div class="pweb-card-head-sub">Perbarui data pribadi akun anda</div>
            </div>
          </div>
          <form action="update_profile_web.php" method="POST" enctype="multipart/form-data" id="webProfileForm" class="pweb-form-body">
            <input type="file" name="foto_profil" id="photoInputCard" accept="image/*" style="display:none" onchange="previewPhotoCard(this)">

            <!-- Nama -->
            <div class="pweb-form-group">
              <label class="pweb-form-label" for="webNama">
                <span>👤</span> Nama Lengkap
              </label>
              <div class="pweb-input-wrap">
                <input type="text" id="webNama" name="nama" class="pweb-input"
                  value="<?= htmlspecialchars($userData['nama'] ?? '') ?>"
                  placeholder="Masukkan nama lengkap" required maxlength="100">
                <span class="pweb-input-icon">✏️</span>
              </div>
              <div class="pweb-hint">Nama yang ditampilkan di profil anda</div>
            </div>

            <!-- Telepon -->
            <div class="pweb-form-group">
              <label class="pweb-form-label" for="webTelp">
                <span>📱</span> Nomor Telepon
              </label>
              <div class="pweb-input-wrap">
                <input type="tel" id="webTelp" name="no_telp" class="pweb-input"
                  value="<?= htmlspecialchars($noTelp) ?>"
                  placeholder="Contoh: +62 812 3456 7890" maxlength="20">
                <span class="pweb-input-icon">📱</span>
              </div>
              <div class="pweb-hint">Nomor telepon aktif untuk kontak</div>
            </div>

            <!-- Email (vault) -->
            <div class="pweb-form-group">
              <label class="pweb-form-label">
                <span>✉️</span> Alamat Email
                <span class="pweb-vault-badge">🔒 Terkunci</span>
              </label>
              <div class="pweb-input-wrap pweb-vault">
                <input type="email" class="pweb-input pweb-vault-input"
                  value="<?= htmlspecialchars($userData['email'] ?? '') ?>" readonly tabindex="-1">
                <span class="pweb-input-icon">🔒</span>
              </div>
              <div class="pweb-hint pweb-vault-hint">Email tidak dapat diubah. Hubungi admin untuk perubahan.</div>
            </div>

            <!-- Role (vault) -->
            <div class="pweb-form-group">
              <label class="pweb-form-label">
                <span>🛡️</span> Role / Jabatan
                <span class="pweb-vault-badge">🔒 Terkunci</span>
              </label>
              <div class="pweb-input-wrap pweb-vault">
                <input type="text" class="pweb-input pweb-vault-input"
                  value="<?= htmlspecialchars($userData['role'] ?? 'Pelanggan') ?>" readonly tabindex="-1">
                <span class="pweb-input-icon">🛡️</span>
              </div>
              <div class="pweb-hint pweb-vault-hint">Role ditentukan oleh sistem dan tidak dapat diubah.</div>
            </div>

            <div class="pweb-form-actions">
              <button type="submit" class="pweb-btn-save" id="webBtnSave">
                <span>💾</span> Simpan Perubahan
              </button>
              <button type="reset" class="pweb-btn-reset">
                <span>↩️</span> Reset
              </button>
            </div>
          </form>
        </div>

      </div><!-- end .profile-web-left -->

      <!-- ====== KANAN: Statistik ====== -->
      <div class="profile-web-right">

        <!-- Statistik Akun -->
        <div class="pweb-card">
          <div class="pweb-card-head">
            <span class="pweb-card-head-icon">📊</span>
            <div>
              <div class="pweb-card-head-title">Statistik Akun</div>
              <div class="pweb-card-head-sub">Ringkasan aktivitas akun anda</div>
            </div>
          </div>
          <div class="pweb-stats-list">

            <div class="pweb-stat-item">
              <div class="pweb-stat-icon" style="background:#EFF6FF;color:#2563EB;">🕐</div>
              <div class="pweb-stat-body">
                <div class="pweb-stat-label">Terakhir Update Profil</div>
                <div class="pweb-stat-val">
                  <?php if ($updatedAt): ?>
                    <?= date('d M Y, H:i', strtotime($updatedAt)) ?> WIB
                  <?php else: ?>
                    <span class="pweb-stat-empty">Belum pernah diperbarui</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="pweb-stat-item">
              <div class="pweb-stat-icon" style="background:#F0FDF4;color:#10B981;">📋</div>
              <div class="pweb-stat-body">
                <div class="pweb-stat-label"><?= $isAdmin ? 'Total Pesanan Dikelola' : 'Pesanan Saya' ?></div>
                <div class="pweb-stat-val"><?= number_format($totalPesanan) ?> Pesanan</div>
              </div>
            </div>

            <?php if ($isAdmin): ?>
            <div class="pweb-stat-item">
              <div class="pweb-stat-icon" style="background:#FFFBEB;color:#F59E0B;">💳</div>
              <div class="pweb-stat-body">
                <div class="pweb-stat-label">Pembayaran Terverifikasi</div>
                <div class="pweb-stat-val"><?= number_format($totalVerif) ?> Transaksi</div>
              </div>
            </div>
            <div class="pweb-stat-item">
              <div class="pweb-stat-icon" style="background:#FDF4FF;color:#9333EA;">👥</div>
              <div class="pweb-stat-body">
                <div class="pweb-stat-label">Total Customer</div>
                <div class="pweb-stat-val"><?= number_format($totalCustomer) ?> Customer</div>
              </div>
            </div>
            <?php endif; ?>

            <div class="pweb-stat-item">
              <div class="pweb-stat-icon" style="background:#FFF1F2;color:#E11D48;">📅</div>
              <div class="pweb-stat-body">
                <div class="pweb-stat-label">Sesi Login Aktif</div>
                <div class="pweb-stat-val"><?= date('d M Y, H:i') ?> WIB</div>
              </div>
            </div>

          </div>
        </div>

        <!-- Keamanan -->
        <div class="pweb-card">
          <div class="pweb-card-head">
            <span class="pweb-card-head-icon">🔐</span>
            <div>
              <div class="pweb-card-head-title">Keamanan Akun</div>
              <div class="pweb-card-head-sub">Status proteksi akun anda</div>
            </div>
          </div>
          <div class="pweb-security-list">
            <div class="pweb-sec-item">
              <div class="pweb-sec-left">
                <div class="pweb-sec-icon pweb-sec-ok">✅</div>
                <div>
                  <div class="pweb-sec-title">Autentikasi Aktif</div>
                  <div class="pweb-sec-sub">Login dengan email & password</div>
                </div>
              </div>
              <span class="pweb-sec-badge pweb-sec-badge-ok">Aman</span>
            </div>
            <div class="pweb-sec-item">
              <div class="pweb-sec-left">
                <div class="pweb-sec-icon pweb-sec-ok">🛡️</div>
                <div>
                  <div class="pweb-sec-title">Role Akun</div>
                  <div class="pweb-sec-sub"><?= $isAdmin ? 'Hak akses penuh admin panel' : 'Akun pelanggan aktif' ?></div>
                </div>
              </div>
              <span class="pweb-sec-badge pweb-sec-badge-ok">Aktif</span>
            </div>
            <div class="pweb-sec-item">
              <div class="pweb-sec-left">
                <div class="pweb-sec-icon <?= $noTelp ? 'pweb-sec-ok' : 'pweb-sec-warn' ?>">
                  <?= $noTelp ? '📱' : '⚠️' ?>
                </div>
                <div>
                  <div class="pweb-sec-title">Nomor Telepon</div>
                  <div class="pweb-sec-sub"><?= $noTelp ? 'Nomor telepon terdaftar' : 'Belum ditambahkan' ?></div>
                </div>
              </div>
              <span class="pweb-sec-badge <?= $noTelp ? 'pweb-sec-badge-ok' : 'pweb-sec-badge-warn' ?>">
                <?= $noTelp ? 'Lengkap' : 'Perlu diisi' ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Aksi Cepat -->
        <div class="pweb-card">
          <div class="pweb-card-head">
            <span class="pweb-card-head-icon">⚡</span>
            <div>
              <div class="pweb-card-head-title">Aksi Cepat</div>
              <div class="pweb-card-head-sub">Navigasi cepat ke halaman lain</div>
            </div>
          </div>
          <div class="pweb-quick-list">
            <a href="index.php" class="pweb-quick-btn">🏠 Beranda</a>
            <?php if ($isAdmin): ?>
            <a href="admin/index.php" class="pweb-quick-btn pweb-quick-admin">⚙️ Panel Admin</a>
            <?php else: ?>
            <a href="form_request.php" class="pweb-quick-btn">📋 Buat Pesanan</a>
            <?php endif; ?>
            <a href="semua_testimoni.php" class="pweb-quick-btn">⭐ Testimoni</a>
            <a href="logout.php" class="pweb-quick-btn pweb-quick-danger">🚪 Keluar / Logout</a>
          </div>
        </div>

      </div><!-- end .profile-web-right -->
    </div><!-- end .profile-web-grid -->
  </div>
</section>

<script>
function previewPhotoHero(input) { if(input.files&&input.files[0]) syncPreview(input.files[0]); }
function previewPhotoCard(input) { if(input.files&&input.files[0]) syncPreview(input.files[0]); }

function syncPreview(file) {
  const reader = new FileReader();
  reader.onload = e => {
    // Hero avatar
    const heroWrap = document.querySelector('.profile-web-hero-avatar-wrap');
    let heroImg = heroWrap.querySelector('img');
    let heroLetter = heroWrap.querySelector('.profile-web-hero-avatar-letter');
    if (!heroImg) { heroImg = document.createElement('img'); heroImg.className='profile-web-hero-avatar-img'; heroWrap.insertBefore(heroImg,heroWrap.firstChild); }
    heroImg.src = e.target.result;
    if (heroLetter) heroLetter.style.display='none';

    // Card avatar
    let cardImg = document.getElementById('webPhotoImg');
    const cardPh = document.getElementById('webPhotoPlaceholder');
    if (!cardImg) { cardImg=document.createElement('img'); cardImg.id='webPhotoImg'; cardImg.className='pweb-photo-img'; document.getElementById('photoWrapWeb').insertBefore(cardImg,document.getElementById('photoWrapWeb').firstChild); }
    cardImg.src = e.target.result;
    if (cardPh) cardPh.style.display='none';

    // Sync ke form input
    const dt=new DataTransfer(); dt.items.add(file);
    document.getElementById('photoInputCard').files = dt.files;
  };
  reader.readAsDataURL(file);
}

// Auto-hide flash
const wf = document.getElementById('webFlash');
if (wf) setTimeout(()=>{ wf.style.transition='opacity .5s'; wf.style.opacity='0'; setTimeout(()=>wf.remove(),500); },4000);

// Save loading state
document.getElementById('webProfileForm')?.addEventListener('submit', function(){
  const btn=document.getElementById('webBtnSave');
  btn.innerHTML='<span>⏳</span> Menyimpan...';
  btn.disabled=true;
});
</script>
</body>
</html>
