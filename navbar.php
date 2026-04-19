
<?php
// ============================================================
// navbar.php — Navbar Dinamis (Login / User Info)
//
// CARA PAKAI: include 'includes/navbar.php';
// Harus dipanggil setelah config.php dan auth.php sudah di-include
// ============================================================

// Tentukan base path (untuk link href)
// Otomatis deteksi apakah file berada di root atau subfolder
$currentFile = basename($_SERVER['PHP_SELF']);
$basePath    = '';  // Sesuaikan jika project ada di subfolder, misal: '/sekala'

// Deteksi halaman aktif navbar
function isNavActive($page) {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}
?>

<!-- ============================================================
     NAVBAR SEKALA — Sticky + Dinamis
     ============================================================ -->
<nav class="navbar" id="main-navbar">
  <div class="navbar-inner">

    <!-- LOGO -->
    <a class="navbar-logo" href="<?= $basePath ?>index.php">
      <div class="image"><img src="assets/logo.png" width="100px" heigh="auto"></div>
    </a>

    <!-- NAV LINKS (Desktop) -->
    <ul class="navbar-nav" id="navbar-links">
      <li class="nav-item active" data-section="home">
        <a href="<?= $basePath ?>index.php">Home</a>
      </li>
      <li class="nav-item" data-section="section-how">
        <?php if ($currentFile === 'index.php' || $currentFile === 'dashboard.php'): ?>
          <a href="#section-how" onclick="smoothScroll('section-how')">Cara Kerja</a>
        <?php else: ?>
          <a href="<?= $basePath ?>index.php#section-how">Cara Kerja</a>
        <?php endif; ?>
      </li>
      <li class="nav-item" data-section="section-paket">
        <a href="#section-paket" onclick="smoothScroll('section-paket')">Paket</a>
      </li>
      <li class="nav-item" data-section="section-portofolio">
        <a href="#section-portofolio" onclick="smoothScroll('section-portofolio')">Portofolio</a>
      </li>
      <li class="nav-item" data-section="section-kontak">
        <a href="#section-kontak" onclick="smoothScroll('section-kontak')">Kontak</a>
      </li>
    </ul>

    <!-- KANAN: Login atau User Dropdown -->
    <div class="navbar-right">

      <?php if (isLoggedIn()):
        $user = getCurrentUser();
        $initial = strtoupper(mb_substr($user['nama'], 0, 1));
      ?>
        <!-- ✅ SUDAH LOGIN: Tampilkan nama + dropdown -->
        <div class="user-dropdown" id="user-dropdown">
          <button class="user-toggle" onclick="toggleUserDropdown()" aria-expanded="false">
            <div class="user-avatar"><?= htmlspecialchars($initial) ?></div>
            <span class="user-name"><?= htmlspecialchars($user['nama']) ?></span>
            <svg class="dropdown-chevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <div class="dropdown-menu" id="dropdown-menu">
            <div class="dropdown-header">
              <div class="dropdown-user-name"><?= htmlspecialchars($user['nama']) ?></div>
              <div class="dropdown-user-email"><?= htmlspecialchars($user['email']) ?></div>
              <?php if (($user['role'] ?? '') === 'Admin'): ?>
              <div class="dropdown-role-badge">🛡️ Administrator</div>
              <?php endif; ?>
            </div>
            <hr class="dropdown-divider">
            <a class="dropdown-item" href="<?= $basePath ?>profile.php">
              <span class="item-icon">👤</span> Profile
            </a>
            <?php if (($user['role'] ?? '') !== 'Admin'): ?>
            <a class="dropdown-item" href="<?= $basePath ?>pesanan_saya.php">
              <span class="item-icon">📋</span> Pesanan Saya
            </a>
            <?php endif; ?>
            <?php if (($user['role'] ?? '') === 'Admin'): ?>
            <hr class="dropdown-divider">
            <a class="dropdown-item dropdown-item-admin" href="<?= $basePath ?>admin/index.php">
              <span class="item-icon">⚙️</span> Panel Admin
            </a>
            <?php endif; ?>
            <hr class="dropdown-divider">
            <a class="dropdown-item dropdown-item-danger" href="<?= $basePath ?>logout.php">
              <span class="item-icon">🚪</span> Logout
            </a>
          </div>
        </div>

      <?php else: ?>
        <!-- ❌ BELUM LOGIN: Tampilkan tombol Masuk -->
        <a class="btn-masuk" href="<?= $basePath ?>signin.php">Masuk</a>
      <?php endif; ?>

      <!-- Hamburger (Mobile) -->
      <button class="nav-hamburger" onclick="toggleMobileNav()" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>

  </div>

  <!-- Mobile Menu -->
  <div class="navbar-mobile" id="mobile-nav">
    <a href="<?= $basePath ?>index.php">🏠 Home</a>
    <a href="<?= $basePath ?>index.php#section-how">⚙️ Cara Kerja</a>
    <a href="<?= $basePath ?>index.php#section-paket">📦 Paket</a>
    <a href="<?= $basePath ?>index.php#section-portofolio">🎨 Portofolio</a>
    <a href="<?= $basePath ?>index.php#section-kontak">📞 Kontak</a>
    <hr style="border-color:var(--border);margin:8px 0;">
    <?php if (isLoggedIn()): ?>
      <a href="<?= $basePath ?>profile.php">👤 Profile</a>
      <?php if ((getCurrentUser()['role'] ?? '') === 'Admin'): ?>
      <a href="<?= $basePath ?>admin/index.php" style="color:#7C3AED;font-weight:700;">⚙️ Panel Admin</a>
      <?php endif; ?>
      <a href="<?= $basePath ?>logout.php" style="color:var(--danger)">🚪 Logout</a>
    <?php else: ?>
      <a href="<?= $basePath ?>signin.php" style="color:var(--primary-mid);font-weight:700;">Masuk</a>
      <a href="<?= $basePath ?>signup.php">Daftar Akun</a>
    <?php endif; ?>
  </div>
</nav>

<?php if (isLoggedIn() && (getCurrentUser()['role'] ?? '') === 'Admin'): ?>
<!-- ============================================================
     ADMIN FLOATING BAR — Hanya tampil saat Admin melihat website
     ============================================================ -->
<div class="admin-floating-bar" id="admin-floating-bar">
  <div class="admin-bar-inner">
    <div class="admin-bar-left">
      <div class="admin-bar-dot"></div>
      <span class="admin-bar-label">🛡️ Mode Preview Admin</span>
      <span class="admin-bar-user">Anda login sebagai <strong><?= htmlspecialchars(getCurrentUser()['nama']) ?></strong></span>
    </div>
    <div class="admin-bar-right">
      <a href="<?= $basePath ?>admin/index.php" class="admin-bar-btn">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
        Kembali ke Panel Admin
      </a>
      <button class="admin-bar-close" onclick="document.getElementById('admin-floating-bar').style.display='none'" title="Tutup">×</button>
    </div>
  </div>
</div>

<style>
/* ---- ADMIN FLOATING BAR ---- */
.admin-floating-bar {
  position: fixed;
  bottom: 0; left: 0; right: 0;
  z-index: 9999;
  background: linear-gradient(90deg, #1E1B4B 0%, #4C1D95 50%, #5B21B6 100%);
  box-shadow: 0 -4px 24px rgba(109,40,217,0.35);
  animation: slideUpBar 0.4s cubic-bezier(0.34,1.56,0.64,1) both;
}
@keyframes slideUpBar {
  from { transform: translateY(100%); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}
.admin-bar-inner {
  display: flex; align-items: center; justify-content: space-between;
  max-width: 1100px; margin: 0 auto;
  padding: 10px 28px;
}
.admin-bar-left {
  display: flex; align-items: center; gap: 10px;
}
.admin-bar-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: #A78BFA;
  box-shadow: 0 0 0 3px rgba(167,139,250,0.25);
  animation: pulseDot 1.8s ease-in-out infinite;
}
@keyframes pulseDot {
  0%, 100% { box-shadow: 0 0 0 3px rgba(167,139,250,0.25); }
  50%       { box-shadow: 0 0 0 6px rgba(167,139,250,0.08); }
}
.admin-bar-label {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 13px; font-weight: 700; color: #DDD6FE;
  letter-spacing: 0.3px;
}
.admin-bar-user {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 12px; color: #A78BFA;
}
.admin-bar-user strong { color: #EDE9FE; }
.admin-bar-right {
  display: flex; align-items: center; gap: 10px;
}
.admin-bar-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 18px; border-radius: 999px;
  background: linear-gradient(135deg, #7C3AED, #A855F7);
  color: #fff; font-size: 13px; font-weight: 700;
  text-decoration: none; font-family: 'Plus Jakarta Sans', sans-serif;
  box-shadow: 0 4px 14px rgba(124,58,237,0.5);
  transition: all 0.25s ease;
  white-space: nowrap;
}
.admin-bar-btn:hover {
  background: linear-gradient(135deg, #6D28D9, #9333EA);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(124,58,237,0.6);
}
.admin-bar-btn svg { flex-shrink: 0; }
.admin-bar-close {
  background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15);
  color: #C4B5FD; width: 26px; height: 26px; border-radius: 50%;
  font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center;
  line-height: 1; transition: all 0.2s;
  flex-shrink: 0;
}
.admin-bar-close:hover { background: rgba(255,255,255,0.2); color: #fff; }
@media (max-width: 600px) {
  .admin-bar-user { display: none; }
  .admin-bar-inner { padding: 10px 16px; }
  .admin-bar-btn { font-size: 12px; padding: 7px 14px; }
}
</style>
<?php endif; ?>

<!-- Flash Message (dari session) -->
<?php
$flash = getFlash();
if ($flash): ?>
<div class="flash-alert flash-<?= $flash['type'] ?>" id="flash-alert">
  <span class="flash-icon">
    <?php
    echo match($flash['type']) {
        'success' => '✅',
        'error'   => '❌',
        'warning' => '⚠️',
        default   => 'ℹ️',
    };
    ?>
  </span>
  <span><?= htmlspecialchars($flash['message']) ?></span>
  <button onclick="document.getElementById('flash-alert').remove()" class="flash-close">×</button>
</div>
<?php endif; ?>

<style>
/* ---- NAVBAR STYLES ---- */
.navbar {
  position: sticky; top: 0; z-index: 999;
  background: #fff;
  border-bottom: 1px solid #E2E8F0;
  box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}
.navbar-inner {
  display: flex; align-items: center; justify-content: space-between;
  height: 68px; max-width: 1100px; margin: 0 auto; padding: 0 28px;
}
.navbar-logo {
  display: flex; align-items: center; gap: 10px; text-decoration: none;
}
.logo-icon {
  width: 40px; height: 40px;
  background: linear-gradient(135deg, #0F1B2D, #1C4E8C, #3B82F6);
  border-radius: 10px; display: flex; align-items: center; justify-content: center;
  color: #fff; font-weight: 800; font-size: 16px; font-family: 'Sora', sans-serif;
  box-shadow: 0 4px 12px rgba(28,78,140,0.3);
}
.logo-text {
  font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800; color: #0F1B2D;
}
.navbar-nav {
  display: flex; align-items: center; gap: 4px; list-style: none;
}
.navbar-nav li a {
  display: inline-block; padding: 8px 16px; border-radius: 999px;
  font-size: 14px; font-weight: 600; color: #334155; text-decoration: none;
  transition: all 0.25s ease;
}
.navbar-nav li a:hover, .navbar-nav li.active a {
  color: #2563EB; background: #DBEAFE;
}
.navbar-right {
  display: flex; align-items: center; gap: 12px;
}
.btn-masuk {
  padding: 9px 22px; border-radius: 999px;
  border: 2px solid #2563EB; color: #2563EB;
  font-size: 14px; font-weight: 700; text-decoration: none;
  transition: all 0.25s ease; font-family: 'Plus Jakarta Sans', sans-serif;
}
.btn-masuk:hover { background: #2563EB; color: #fff; }

/* ---- USER DROPDOWN ---- */
.user-dropdown { position: relative; }
.user-toggle {
  display: flex; align-items: center; gap: 9px;
  background: #F1F5F9; border: 1.5px solid #E2E8F0;
  border-radius: 999px; padding: 6px 14px 6px 6px;
  cursor: pointer; font-family: 'Plus Jakarta Sans', sans-serif;
  transition: all 0.25s ease;
}
.user-toggle:hover { border-color: #2563EB; background: #DBEAFE; }
.user-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: linear-gradient(135deg, #1C4E8C, #3B82F6);
  color: #fff; font-size: 14px; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.user-name { font-size: 14px; font-weight: 600; color: #0F1B2D; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dropdown-chevron { color: #64748B; transition: transform 0.25s; }
.user-dropdown.open .dropdown-chevron { transform: rotate(180deg); }

.dropdown-menu {
  display: none; position: absolute; top: calc(100% + 10px); right: 0;
  background: #fff; border: 1.5px solid #E2E8F0; border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.12); min-width: 200px;
  padding: 8px; z-index: 1000; animation: dropIn 0.2s ease;
}
.user-dropdown.open .dropdown-menu { display: block; }
@keyframes dropIn {
  from { opacity:0; transform:translateY(-8px); }
  to   { opacity:1; transform:translateY(0); }
}
.dropdown-header { padding: 10px 12px 8px; }
.dropdown-user-name { font-size: 14px; font-weight: 700; color: #0F1B2D; }
.dropdown-user-email { font-size: 12px; color: #64748B; margin-top: 2px; }
.dropdown-divider { border: none; border-top: 1px solid #E2E8F0; margin: 4px 0; }
.dropdown-item {
  display: flex; align-items: center; gap: 9px;
  padding: 10px 12px; border-radius: 10px;
  font-size: 14px; font-weight: 500; color: #334155;
  text-decoration: none; transition: all 0.2s ease;
}
.dropdown-item:hover { background: #F1F5F9; color: #0F1B2D; }
.dropdown-item-danger { color: #EF4444; }
.dropdown-item-danger:hover { background: #FEF2F2; color: #EF4444; }
.dropdown-item-admin { color: #7C3AED; font-weight: 600; }
.dropdown-item-admin:hover { background: #F5F3FF; color: #6D28D9; }
.dropdown-role-badge {
  display: inline-block; margin-top: 5px;
  background: linear-gradient(135deg, #7C3AED, #6D28D9);
  color: #fff; font-size: 10px; font-weight: 700;
  padding: 2px 8px; border-radius: 999px; letter-spacing: 0.5px;
}
.item-icon { font-size: 15px; }

/* ---- HAMBURGER (Mobile) ---- */
.nav-hamburger {
  display: none; flex-direction: column; gap: 5px;
  background: none; border: none; cursor: pointer; padding: 4px;
}
.nav-hamburger span {
  display: block; width: 24px; height: 2.5px;
  background: #0F1B2D; border-radius: 2px; transition: all 0.25s;
}
.navbar-mobile {
  display: none; flex-direction: column;
  background: #fff; border-top: 1px solid #E2E8F0;
  padding: 12px 28px 20px; gap: 2px;
}
.navbar-mobile.open { display: flex; }
.navbar-mobile a {
  padding: 11px 16px; border-radius: 10px;
  font-size: 14px; font-weight: 600; color: #334155;
  text-decoration: none; transition: all 0.2s;
}
.navbar-mobile a:hover { background: #DBEAFE; color: #2563EB; }

/* ---- FLASH ALERT ---- */
.flash-alert {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 20px; font-size: 14px; font-weight: 500;
  font-family: 'Plus Jakarta Sans', sans-serif;
  animation: slideDown 0.3s ease;
}
@keyframes slideDown {
  from { opacity:0; transform:translateY(-10px); }
  to   { opacity:1; transform:translateY(0); }
}
.flash-success { background: #D1FAE5; color: #065F46; border-bottom: 2px solid #10B981; }
.flash-error   { background: #FEE2E2; color: #991B1B; border-bottom: 2px solid #EF4444; }
.flash-warning { background: #FEF3C7; color: #92400E; border-bottom: 2px solid #F59E0B; }
.flash-info    { background: #DBEAFE; color: #1E40AF; border-bottom: 2px solid #3B82F6; }
.flash-icon    { font-size: 16px; }
.flash-close   { margin-left: auto; background: none; border: none; font-size: 18px; cursor: pointer; opacity: 0.6; }
.flash-close:hover { opacity: 1; }

/* ---- RESPONSIVE ---- */
@media (max-width: 768px) {
  .navbar-nav { display: none; }
  .btn-masuk { display: none; }
  .user-name { display: none; }
  .nav-hamburger { display: flex; }
}

/* ---- JS untuk navbar ---- */
</style>

<script>
// Toggle user dropdown
function toggleUserDropdown() {
  const dd = document.getElementById('user-dropdown');
  if (dd) dd.classList.toggle('open');
}

// Tutup dropdown jika klik di luar
document.addEventListener('click', function(e) {
  const dd = document.getElementById('user-dropdown');
  if (dd && !dd.contains(e.target)) dd.classList.remove('open');
});

// Toggle mobile nav
function toggleMobileNav() {
  const nav = document.getElementById('mobile-nav');
  if (nav) nav.classList.toggle('open');
}

// Smooth scroll ke section (untuk halaman yang sama)
function smoothScroll(id) {
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    return false;
  }
}

// Auto-hide flash setelah 5 detik
setTimeout(() => {
  const flash = document.getElementById('flash-alert');
  if (flash) flash.style.transition = 'opacity 0.5s', flash.style.opacity = '0',
    setTimeout(() => flash.remove(), 500);
}, 5000);

window.addEventListener('scroll', function () {

  const sections = document.querySelectorAll("section");
  const navItems = document.querySelectorAll(".nav-item");

  let current = "";

  sections.forEach(section => {
    const sectionTop = section.offsetTop - 100;
    if (pageYOffset >= sectionTop) {
      current = section.getAttribute("id");
    }
  });

  navItems.forEach(li => {
    li.classList.remove("active");

    if (li.getAttribute("data-section") === current) {
      li.classList.add("active");
    }
  });

});
</script>
