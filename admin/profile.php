<?php
// ============================================================
// admin/profile.php — Halaman Profil Admin SEKALA
// ============================================================
$current_page = 'profile';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/admin_auth.php';

$id_user = $_SESSION['id_user'] ?? 0;

// Ambil data admin dari database
$adminData = null;
$stmt = $conn->prepare("SELECT * FROM tb_user WHERE id_user = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    $adminData = $result->fetch_assoc();
    $stmt->close();
}

// Fallback ke session jika query gagal
if (!$adminData) {
    $adminData = [
        'id_user'        => $id_user,
        'nama'           => $_SESSION['nama'] ?? 'Admin',
        'email'          => $_SESSION['email'] ?? 'admin@sekala.com',
        'no_telp'        => $_SESSION['no_telp'] ?? '',
        'role'           => $_SESSION['role'] ?? 'Admin',
        'foto_profil'    => null,
        'updated_at'     => null,
    ];
}

// Cek kolom yang ada (no_telp, foto_profil, updated_at mungkin belum ada)
$noTelp     = $adminData['no_telp']     ?? ($adminData['telepon'] ?? '');
$fotoProfil = $adminData['foto_profil'] ?? ($adminData['foto'] ?? null);
$updatedAt  = $adminData['updated_at']  ?? ($adminData['last_update'] ?? null);

// Flash message
$flash = '';
if (isset($_SESSION['profile_msg'])) {
    $flash = $_SESSION['profile_msg'];
    unset($_SESSION['profile_msg']);
}

// Statistik akun
$totalPesananHandled = 0;
$rPesanan = $conn->query("SELECT COUNT(*) AS n FROM tb_pesanan");
if ($rPesanan) $totalPesananHandled = $rPesanan->fetch_assoc()['n'] ?? 0;

$totalVerifikasi = 0;
$rVerif = $conn->query("SELECT COUNT(*) AS n FROM tb_pembayaran WHERE status_pembayaran='terverifikasi'");
if ($rVerif) $totalVerifikasi = $rVerif->fetch_assoc()['n'] ?? 0;

$totalCustomer = 0;
$rCust = $conn->query("SELECT COUNT(*) AS n FROM tb_pelanggan");
if ($rCust) $totalCustomer = $rCust->fetch_assoc()['n'] ?? 0;

$lastLogin = $_SESSION['last_login'] ?? date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Profil Admin — SEKALA</title>
  <link rel="icon" type="image/png" href="../assets/Logo3.png" style="border-radius: 50%; width: 32px; height: 32px;">
  <meta name="description" content="Kelola informasi profil dan pengaturan akun admin SEKALA">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/admin.css?v=3">
  <link rel="stylesheet" href="assets/profile.css">
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
          <div class="topbar-page-title">Profil Admin</div>
          <div class="topbar-breadcrumb">Admin / Profil</div>
        </div>
      </div>
      <div class="topbar-right">
        <a href="../index.php" target="_blank" class="topbar-btn btn-outline-sm">🌐 Lihat Website</a>
      </div>
    </div>

    <div class="admin-content">

      <!-- Page Header -->
      <div class="profile-page-header">
        <div>
          <h1 class="profile-page-title">Profil Admin</h1>
          <p class="profile-page-sub">Kelola informasi profile dan pengaturan akun anda</p>
        </div>
      </div>

      <!-- Flash handled by custom popup script -->

      <!-- Main Grid -->
      <div class="profile-grid">

        <!-- ======== LEFT: Profil Card ======== -->
        <div class="profile-left">

          <!-- Photo & Identity -->
          <div class="profile-card profile-identity-card">
            <div class="profile-photo-section">
              <div class="profile-photo-wrapper" id="photoWrapper">
                <?php if ($fotoProfil && file_exists(__DIR__ . '/../uploads/' . $fotoProfil)): ?>
                  <img src="../uploads/<?= htmlspecialchars($fotoProfil) ?>" alt="Foto Profil" class="profile-photo" id="profilePhotoImg">
                <?php else: ?>
                  <div class="profile-photo-placeholder" id="profilePhotoPlaceholder">
                    <span><?= strtoupper(mb_substr($adminData['nama'] ?? 'A', 0, 1)) ?></span>
                  </div>
                <?php endif; ?>
                <label for="photoInput" class="profile-photo-overlay" title="Unggah foto profil baru">
                  <span class="photo-overlay-icon">📷</span>
                  <span class="photo-overlay-text">Ubah Foto</span>
                </label>
              </div>
              <input type="file" id="photoInput" name="foto_profil" accept="image/*" style="display:none" onchange="previewPhoto(this)">

              <div class="profile-identity-info">
                <div class="profile-identity-name"><?= htmlspecialchars($adminData['nama'] ?? 'Admin') ?></div>
                <div class="profile-identity-role">
                  <span class="role-badge">🛡️ <?= htmlspecialchars($adminData['role'] ?? 'Admin') ?></span>
                </div>
                <div class="profile-identity-email">✉️ <?= htmlspecialchars($adminData['email'] ?? '') ?></div>
              </div>
            </div>
          </div>

          <!-- Edit Form -->
          <div class="profile-card profile-form-card">
            <div class="profile-card-header">
              <span class="profile-card-icon">✏️</span>
              <div>
                <div class="profile-card-title">Edit Informasi Profil</div>
                <div class="profile-card-sub">Perbarui data pribadi akun anda</div>
              </div>
            </div>

            <form action="update_profile.php" method="POST" enctype="multipart/form-data" id="profileForm">
              <input type="hidden" name="foto_input" id="fotoInputHidden">

              <!-- Foto (hidden, akan diupload lewat input terpisah) -->
              <div style="display:none">
                <input type="file" name="foto_profil" id="fotoUploadField">
              </div>

              <!-- Nama Lengkap -->
              <div class="pform-group">
                <label class="pform-label" for="namaInput">
                  <span class="pform-label-icon">👤</span> Nama Lengkap
                </label>
                <div class="pform-input-wrap">
                  <input
                    type="text"
                    id="namaInput"
                    name="nama"
                    class="pform-input"
                    value="<?= htmlspecialchars($adminData['nama'] ?? '') ?>"
                    placeholder="Masukkan nama lengkap"
                    required
                    maxlength="100"
                  >
                  <span class="pform-input-icon">✏️</span>
                </div>
                <div class="pform-hint">Nama yang akan ditampilkan di admin panel</div>
              </div>

              <!-- No. Telepon -->
              <div class="pform-group">
                <label class="pform-label" for="telpInput">
                  <span class="pform-label-icon">📱</span> Nomor Telepon
                </label>
                <div class="pform-input-wrap">
                  <input
                    type="tel"
                    id="telpInput"
                    name="no_telp"
                    class="pform-input"
                    value="<?= htmlspecialchars($noTelp) ?>"
                    placeholder="Contoh: +62 812 3456 7890"
                    maxlength="20"
                  >
                  <span class="pform-input-icon">📱</span>
                </div>
                <div class="pform-hint">Nomor telepon aktif untuk kontak</div>
              </div>

              <!-- Email (Vault) -->
              <div class="pform-group">
                <label class="pform-label">
                  <span class="pform-label-icon">✉️</span> Alamat Email
                  <span class="vault-badge">🔒 Terkunci</span>
                </label>
                <div class="pform-input-wrap vault">
                  <input
                    type="email"
                    class="pform-input vault-input"
                    value="<?= htmlspecialchars($adminData['email'] ?? '') ?>"
                    readonly
                    tabindex="-1"
                  >
                  <span class="pform-input-icon">🔒</span>
                </div>
                <div class="pform-hint vault-hint">Email tidak dapat diubah. Hubungi developer untuk perubahan.</div>
              </div>

              <!-- Role (Vault) -->
              <div class="pform-group">
                <label class="pform-label">
                  <span class="pform-label-icon">🛡️</span> Role / Jabatan
                  <span class="vault-badge">🔒 Terkunci</span>
                </label>
                <div class="pform-input-wrap vault">
                  <input
                    type="text"
                    class="pform-input vault-input"
                    value="<?= htmlspecialchars($adminData['role'] ?? 'Admin') ?>"
                    readonly
                    tabindex="-1"
                  >
                  <span class="pform-input-icon">🛡️</span>
                </div>
                <div class="pform-hint vault-hint">Role ditentukan oleh sistem dan tidak dapat diubah secara manual.</div>
              </div>

              <!-- Unggah Foto (tersembunyi, trigger dari photo overlay) -->
              <input type="file" name="foto_profil" id="fotoProfilUpload" accept="image/*" style="display:none" onchange="syncPhotoToForm(this)">

              <div class="pform-actions">
                <button type="submit" class="pform-btn-save" id="btnSave">
                  <span class="btn-icon">💾</span> Simpan Perubahan
                </button>
                <button type="reset" class="pform-btn-reset" onclick="resetForm()">
                  <span class="btn-icon">↩️</span> Reset
                </button>
              </div>
            </form>
          </div>

        </div><!-- end .profile-left -->

        <!-- ======== RIGHT: Statistik Akun ======== -->
        <div class="profile-right">

          <!-- Stat Akun -->
          <div class="profile-card profile-stats-card">
            <div class="profile-card-header">
              <span class="profile-card-icon">📊</span>
              <div>
                <div class="profile-card-title">Statistik Akun</div>
                <div class="profile-card-sub">Ringkasan aktivitas admin</div>
              </div>
            </div>

            <div class="acc-stats-list">

              <!-- Last Update -->
              <div class="acc-stat-item">
                <div class="acc-stat-icon" style="background:#EFF6FF; color:#2563EB;">🕐</div>
                <div class="acc-stat-body">
                  <div class="acc-stat-label">Terakhir Update Profil</div>
                  <div class="acc-stat-value">
                    <?php if ($updatedAt): ?>
                      <?= date('d M Y, H:i', strtotime($updatedAt)) ?> WITA
                    <?php else: ?>
                      <span style="color:#94A3B8;font-style:italic;font-size:13px;">Belum pernah diperbarui</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Total Pesanan -->
              <div class="acc-stat-item">
                <div class="acc-stat-icon" style="background:#F0FDF4; color:#10B981;">📋</div>
                <div class="acc-stat-body">
                  <div class="acc-stat-label">Total Pesanan Dikelola</div>
                  <div class="acc-stat-value"><?= number_format($totalPesananHandled) ?> Pesanan</div>
                </div>
              </div>

              <!-- Pembayaran Terverifikasi -->
              <div class="acc-stat-item">
                <div class="acc-stat-icon" style="background:#FFFBEB; color:#F59E0B;">💳</div>
                <div class="acc-stat-body">
                  <div class="acc-stat-label">Pembayaran Terverifikasi</div>
                  <div class="acc-stat-value"><?= number_format($totalVerifikasi) ?> Transaksi</div>
                </div>
              </div>

              <!-- Total Customer -->
              <div class="acc-stat-item">
                <div class="acc-stat-icon" style="background:#FDF4FF; color:#9333EA;">👥</div>
                <div class="acc-stat-body">
                  <div class="acc-stat-label">Total Customer Terdaftar</div>
                  <div class="acc-stat-value"><?= number_format($totalCustomer) ?> Customer</div>
                </div>
              </div>

              <!-- Tanggal Bergabung -->
              <div class="acc-stat-item">
                <div class="acc-stat-icon" style="background:#FFF1F2; color:#E11D48;">📅</div>
                <div class="acc-stat-body">
                  <div class="acc-stat-label">Sesi Login Aktif</div>
                  <div class="acc-stat-value"><?= date('d M Y, H:i') ?> WITA</div>
                </div>
              </div>

            </div>
          </div>

          <!-- Info Keamanan -->
          <div class="profile-card profile-security-card">
            <div class="profile-card-header">
              <span class="profile-card-icon">🔐</span>
              <div>
                <div class="profile-card-title">Keamanan Akun</div>
                <div class="profile-card-sub">Status proteksi akun anda</div>
              </div>
            </div>
            <div class="security-items">
              <div class="security-item">
                <div class="security-item-left">
                  <div class="security-item-icon secured">✅</div>
                  <div>
                    <div class="security-item-title">Autentikasi Aktif</div>
                    <div class="security-item-sub">Login dengan email & password</div>
                  </div>
                </div>
                <span class="security-status secured">Aman</span>
              </div>
              <div class="security-item">
                <div class="security-item-left">
                  <div class="security-item-icon secured">🛡️</div>
                  <div>
                    <div class="security-item-title">Role Admin</div>
                    <div class="security-item-sub">Hak akses penuh ke admin panel</div>
                  </div>
                </div>
                <span class="security-status secured">Aktif</span>
              </div>
              <div class="security-item">
                <div class="security-item-left">
                  <div class="security-item-icon <?= $noTelp ? 'secured' : 'warn' ?>">
                    <?= $noTelp ? '📱' : '⚠️' ?>
                  </div>
                  <div>
                    <div class="security-item-title">Nomor Telepon</div>
                    <div class="security-item-sub"><?= $noTelp ? 'Nomor telepon terdaftar' : 'Belum ditambahkan' ?></div>
                  </div>
                </div>
                <span class="security-status <?= $noTelp ? 'secured' : 'warn' ?>">
                  <?= $noTelp ? 'Lengkap' : 'Perlu diisi' ?>
                </span>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="profile-card profile-actions-card">
            <div class="profile-card-header">
              <span class="profile-card-icon">⚡</span>
              <div>
                <div class="profile-card-title">Aksi Cepat</div>
                <div class="profile-card-sub">Navigasi ke halaman lain</div>
              </div>
            </div>
            <div class="quick-actions-list">
              <a href="index.php" class="quick-action-btn">
                <span>🏠</span> Dashboard Admin
              </a>
              <a href="../index.php" target="_blank" class="quick-action-btn">
                <span>🌐</span> Kunjungi Website
              </a>
              <a href="kelola_admin.php" class="quick-action-btn">
                <span>🛡️</span> Kelola Admin
              </a>
              <a href="logout.php" class="quick-action-btn danger">
                <span>🚪</span> Keluar / Logout
              </a>
            </div>
          </div>

        </div><!-- end .profile-right -->

      </div><!-- end .profile-grid -->

    </div><!-- end .admin-content -->
  </div><!-- end .admin-main -->
</div><!-- end .admin-layout -->

<script>
// Preview foto sebelum upload
function previewPhoto(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const wrapper = document.getElementById('photoWrapper');
      let img = document.getElementById('profilePhotoImg');
      const placeholder = document.getElementById('profilePhotoPlaceholder');
      if (!img) {
        img = document.createElement('img');
        img.id = 'profilePhotoImg';
        img.className = 'profile-photo';
        if (placeholder) placeholder.style.display = 'none';
        wrapper.insertBefore(img, wrapper.firstChild);
      }
      img.src = e.target.result;
      // Sync ke form upload
      syncFileToFormInput(input.files[0]);
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function syncFileToFormInput(file) {
  const dt = new DataTransfer();
  dt.items.add(file);
  const uploadField = document.getElementById('fotoProfilUpload');
  if (uploadField) uploadField.files = dt.files;
}

function syncPhotoToForm(input) {
  previewPhoto(input);
}

function resetForm() {
  const img = document.getElementById('profilePhotoImg');
  const placeholder = document.getElementById('profilePhotoPlaceholder');
  if (placeholder) placeholder.style.display = 'flex';
  if (img) img.remove();
}

// Auto-hide flash message
const flash = document.getElementById('flashMsg');
if (flash) {
  setTimeout(() => {
    flash.style.transition = 'opacity .5s';
    flash.style.opacity = '0';
    setTimeout(() => flash.remove(), 500);
  }, 4000);
}

// Save button loading state
document.getElementById('profileForm')?.addEventListener('submit', function() {
  const btn = document.getElementById('btnSave');
});
</script>

<script>
// 1. Intercept Profile Form Submit
const form = document.getElementById('profileForm');
if (form) {
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (typeof showPopup === 'function') {
      showPopup('save', 'Simpan Perubahan?', 'Apakah Anda yakin ingin menyimpan perubahan pada profil Anda?', () => {
        // Tampilkan state loading
        const btnSave = document.getElementById('btnSave');
        form.submit();
      });
    } else {
      form.submit();
    }
  });
}

// 2. Handle Flash Messages via PHP
<?php if ($flash): ?>
  setTimeout(() => {
    if (typeof showPopup === 'function') {
      <?php if (strpos(strtolower($flash), 'berhasil') !== false): ?>
        showPopup('success', 'Berhasil!', '<?= htmlspecialchars($flash) ?>', null);
      <?php else: ?>
        showPopup('error', 'Informasi', '<?= htmlspecialchars($flash) ?>', null);
      <?php endif; ?>
    } else {
      alert('<?= htmlspecialchars($flash) ?>');
    }
  }, 100);
<?php endif; ?>
</script>
</body>
</html>
