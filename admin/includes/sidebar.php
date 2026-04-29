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
    <div class="image sidebar-logo-img"><img src="../assets/Logo3.png" width="30px" height="30px" style="border-radius: 20%"></div>
    <div class="sidebar-brand-text">
      <div class="sidebar-brand-name">SEKALA</div>
      <div class="sidebar-brand-sub">Admin Panel</div>
    </div>
    <button class="sidebar-desk-toggle" onclick="toggleDesktopSidebar()" title="Toggle Sidebar">
      <span class="sdt-icon-burger">☰</span>
      <span class="sdt-icon-arrow">❮</span>
    </button>
  </div>

  <nav class="sidebar-nav">
    <?php foreach ($menu as $m): ?>
    <a href="<?= $m['href'] ?>" class="sidebar-link <?= $cp === $m['key'] ? 'active' : '' ?>">
      <span class="sidebar-icon"><?= $m['icon'] ?></span>
      <span class="sidebar-label"><?= $m['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <!-- ── Footer: User Card dengan dropdown aksi ── -->
  <div class="sidebar-footer">
    <div class="sidebar-user-card" id="sidebarUserCard" onclick="toggleUserMenu()" role="button" aria-expanded="false">
      <div class="sidebar-user-avatar"><?= strtoupper(mb_substr($_SESSION['nama'] ?? 'A', 0, 1)) ?></div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></div>
        <div class="sidebar-user-role">Administrator</div>
      </div>
      <svg class="sidebar-user-chevron" id="userChevron" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
      </svg>
    </div>

    <!-- Dropdown aksi — muncul di dalam user card -->
    <div class="sidebar-user-menu" id="sidebarUserMenu">
      <a href="profile.php" class="sidebar-user-action <?= $cp === 'profile' ? 'sua-active' : '' ?>">
        <span class="sua-icon">👤</span>
        <span>Profile</span>
      </a>
      <a href="../index.php" target="_blank" class="sidebar-user-action">
        <span class="sua-icon">🌐</span>
        <span>Lihat Website</span>
      </a>
      <a href="logout.php" class="sidebar-user-action sua-logout">
        <span class="sua-icon">🚪</span>
        <span>Logout</span>
      </a>
    </div>
  </div>
</aside>

<script>
function toggleUserMenu() {
  const card   = document.getElementById('sidebarUserCard');
  const menu   = document.getElementById('sidebarUserMenu');
  const chev   = document.getElementById('userChevron');
  const isOpen = menu.classList.contains('open');

  menu.classList.toggle('open');
  card.classList.toggle('user-card-open');
  card.setAttribute('aria-expanded', !isOpen);
  chev.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}

// Auto-buka jika sedang di halaman profile
<?php if ($cp === 'profile'): ?>
document.addEventListener('DOMContentLoaded', function() {
  const menu = document.getElementById('sidebarUserMenu');
  const card = document.getElementById('sidebarUserCard');
  const chev = document.getElementById('userChevron');
  menu.classList.add('open');
  card.classList.add('user-card-open');
  card.setAttribute('aria-expanded','true');
  chev.style.transform = 'rotate(180deg)';
});
<?php endif; ?>

function toggleDesktopSidebar() {
  document.body.classList.toggle('sidebar-collapsed');
  if (document.body.classList.contains('sidebar-collapsed')) {
    localStorage.setItem('sekala_sidebar', 'collapsed');
  } else {
    localStorage.setItem('sekala_sidebar', 'expanded');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  if (localStorage.getItem('sekala_sidebar') === 'collapsed') {
    document.body.classList.add('sidebar-collapsed');
  }
});

// ==========================================
// CUSTOM POPUP MODAL LOGIC (GLOBAL ADMIN)
// ==========================================
let confirmAction = null;

function showPopup(type, t, m, confirmCb) {
  const popup = document.getElementById('pwebPopup');
  const title = document.getElementById('pwebPopupTitle');
  const msg = document.getElementById('pwebPopupMsg');
  const icon = document.getElementById('pwebPopupIcon');
  const btnCancel = document.getElementById('pwebPopupCancel');
  const btnConfirm = document.getElementById('pwebPopupConfirm');

  if (!popup) return;

  title.textContent = t;
  msg.textContent = m;
  btnConfirm.style.display = 'block';
  btnCancel.style.display = 'block';
  
  if (type === 'save') {
    icon.innerHTML = '💾';
    icon.className = 'pweb-popup-icon pweb-icon-save';
    btnConfirm.textContent = 'Ya, Simpan';
    btnConfirm.className = 'pweb-popup-btn pweb-btn-save-confirm';
  } else if (type === 'logout') {
    icon.innerHTML = '🚪';
    icon.className = 'pweb-popup-icon pweb-icon-logout';
    btnConfirm.textContent = 'Ya, Keluar';
    btnConfirm.className = 'pweb-popup-btn pweb-btn-logout-confirm';
  } else if (type === 'success' || type === 'error') {
    icon.innerHTML = type === 'success' ? '✅' : 'ℹ️';
    icon.className = 'pweb-popup-icon ' + (type === 'success' ? 'pweb-icon-success' : 'pweb-icon-save');
    btnConfirm.textContent = 'Tutup';
    btnConfirm.className = 'pweb-popup-btn pweb-btn-save-confirm';
    btnCancel.style.display = 'none';
  }
  
  confirmAction = confirmCb;
  popup.classList.add('show');
}

function hidePopup() {
  const popup = document.getElementById('pwebPopup');
  if (popup) popup.classList.remove('show');
  setTimeout(() => { confirmAction = null; }, 300);
}

document.addEventListener('DOMContentLoaded', () => {
  const btnCancel = document.getElementById('pwebPopupCancel');
  const btnConfirm = document.getElementById('pwebPopupConfirm');

  if (btnCancel) btnCancel.addEventListener('click', hidePopup);
  if (btnConfirm) btnConfirm.addEventListener('click', () => {
    if (confirmAction) {
      const cb = confirmAction;
      hidePopup();
      cb();
    } else {
      hidePopup();
    }
  });

  // Intercept Global Admin Logout Links
  const logoutLinks = document.querySelectorAll('a[href="logout.php"], a[href="../logout.php"]');
  logoutLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const targetHref = link.getAttribute('href');
      showPopup('logout', 'Keluar Akun?', 'Apakah Anda yakin ingin keluar dari halaman admin?', () => {
        window.location.href = targetHref;
      });
    });
  });
});
</script>

<!-- Custom Popup Modal -->
<div class="pweb-popup-overlay" id="pwebPopup">
  <div class="pweb-popup-box">
    <div class="pweb-popup-icon" id="pwebPopupIcon">❓</div>
    <div class="pweb-popup-title" id="pwebPopupTitle">Konfirmasi</div>
    <div class="pweb-popup-msg" id="pwebPopupMsg">Apakah Anda yakin?</div>
    <div class="pweb-popup-actions" id="pwebPopupActions">
      <button class="pweb-popup-btn pweb-popup-cancel" id="pwebPopupCancel">Batal</button>
      <button class="pweb-popup-btn pweb-popup-confirm" id="pwebPopupConfirm">Ya, Yakin</button>
    </div>
  </div>
</div>
