<?php
// ============================================================
// admin/includes/sidebar.php — Sidebar Admin Panel
// Pakai $current_page untuk highlight menu aktif
// ============================================================
$menu = [
    ['icon'=>'🏠','label'=>'Dashboard',       'href'=>'index.php',        'key'=>'dashboard'],
    ['icon'=>'👥','label'=>'Data Customer',    'href'=>'customers.php',    'key'=>'customers'],
    ['icon'=>'📋','label'=>'Data Pemesanan',   'href'=>'pesanan.php',      'key'=>'pesanan'],
    ['icon'=>'💳','label'=>'Data Pembayaran',  'href'=>'pembayaran.php',   'key'=>'pembayaran'],
    ['icon'=>'🎨','label'=>'Data Portofolio',  'href'=>'portofolio.php',   'key'=>'portofolio'],
    ['icon'=>'⭐','label'=>'Data Testimoni',   'href'=>'testimoni.php',    'key'=>'testimoni'],
    ['icon'=>'📊','label'=>'Laporan',          'href'=>'laporan.php',      'key'=>'laporan'],
    ['icon'=>'🛡️','label'=>'Kelola Admin',    'href'=>'kelola_admin.php', 'key'=>'kelola_admin'],
];
$cp = $current_page ?? '';
?>
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand">
    <div class="sidebar-logo">S</div>
    <div class="sidebar-brand-text">
      <div class="sidebar-brand-name">SEKALA</div>
      <div class="sidebar-brand-sub">Admin Panel</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?php foreach ($menu as $m): ?>
    <a href="<?= $m['href'] ?>" class="sidebar-link <?= $cp === $m['key'] ? 'active' : '' ?>">
      <span class="sidebar-icon"><?= $m['icon'] ?></span>
      <span class="sidebar-label"><?= $m['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-user-avatar"><?= strtoupper(mb_substr($_SESSION['nama'] ?? 'A', 0, 1)) ?></div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></div>
        <div class="sidebar-user-role">Administrator</div>
      </div>
    </div>
    <a href="logout.php" class="sidebar-logout">🚪 Logout</a>
  </div>
</aside>
