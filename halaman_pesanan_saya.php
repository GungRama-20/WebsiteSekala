<?php
// ============================================================
// halaman_pesanan_saya.php — Daftar Pesanan Pelanggan
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$id_user = $_SESSION['id_user'] ?? 0;
$email   = $_SESSION['email'] ?? '';
$role    = $_SESSION['role'] ?? '';

// Ambil data pelanggan berdasarkan email
$id_pelanggan = 0;
$stmtP = $conn->prepare("SELECT id_pelanggan FROM tb_pelanggan WHERE email = ? LIMIT 1");
if ($stmtP) {
    $stmtP->bind_param("s", $email);
    $stmtP->execute();
    $resP = $stmtP->get_result()->fetch_assoc();
    if ($resP) {
        $id_pelanggan = $resP['id_pelanggan'];
    }
    $stmtP->close();
}

// Ambil daftar pesanan (gunakan pembayaran terbaru per pesanan)
$pesanan = [];
if ($id_pelanggan > 0) {
    $q = "SELECT p.id_pesanan, p.judul_proyek, p.status_pesanan, p.tanggal_pesan, p.deadline,
                 d.jenis_desain,
                 b.status_pembayaran, b.jumlah_bayar, b.id_pembayaran
          FROM tb_pesanan p
          LEFT JOIN tb_desain d ON p.id_desain = d.id_desain
          LEFT JOIN tb_pembayaran b
            ON b.id_pembayaran = (
               SELECT MAX(bb.id_pembayaran)
               FROM tb_pembayaran bb
               WHERE bb.id_pesanan = p.id_pesanan
            )
          WHERE p.id_pelanggan = ?
          ORDER BY p.tanggal_pesan DESC";
    $stmt = $conn->prepare($q);
    if ($stmt) {
        $stmt->bind_param("i", $id_pelanggan);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $pesanan[] = $row;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Pesanan Saya — SEKALA</title>
  <link rel="icon" type="image/png" href="assets/Logo3.png" style="border-radius: 50%; width: 32px; height: 32px;">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="pages.css">
  <link rel="stylesheet" href="profile-web.css">
  <style>
    .pesanan-hero {
      background: linear-gradient(135deg, #0F1B2D 0%, #1C4E8C 100%);
      padding: 60px 0;
      color: #fff;
      text-align: center;
      margin-top: 0;
    }
    .pesanan-hero h1 {
      font-family: 'Sora', sans-serif;
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 15px;
    }
    .pesanan-hero p {
      color: #94A3B8;
      font-size: 1.1rem;
      max-width: 600px;
      margin: 0 auto;
    }
    .pesanan-section {
      padding: 60px 0;
      background: #F8FAFC;
      min-height: 50vh;
    }
    .pesanan-list {
      display: flex;
      flex-direction: column;
      gap: 20px;
      max-width: 900px;
      margin: 0 auto;
    }
    .pesanan-card {
      background: #fff;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.05);
      border: 1px solid #E2E8F0;
      display: flex;
      flex-direction: column;
      gap: 16px;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .pesanan-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 30px rgba(0,0,0,0.08);
    }
    .pesanan-card-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 1px solid #E2E8F0;
      padding-bottom: 16px;
    }
    .pesanan-title {
      font-family: 'Sora', sans-serif;
      font-size: 1.25rem;
      font-weight: 700;
      color: #0F1B2D;
      margin-bottom: 5px;
    }
    .pesanan-subtitle {
      font-size: 0.9rem;
      color: #64748B;
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .pesanan-badge {
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 0.85rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    .badge-pending { background: #FEF3C7; color: #D97706; }
    .badge-proses { background: #DBEAFE; color: #2563EB; }
    .badge-selesai { background: #D1FAE5; color: #059669; }
    .badge-batal { background: #FEE2E2; color: #DC2626; }
    
    .pesanan-details {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }
    .detail-item {
      flex: 1;
      min-width: 150px;
    }
    .detail-label {
      font-size: 0.85rem;
      color: #64748B;
      margin-bottom: 4px;
    }
    .detail-value {
      font-weight: 600;
      color: #334155;
      font-size: 1rem;
    }
    .pesanan-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 10px;
    }
    .btn-detail {
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      text-decoration: none;
      background: #F1F5F9;
      color: #334155;
      transition: all 0.2s;
    }
    .btn-detail:hover {
      background: #E2E8F0;
      color: #0F1B2D;
    }
    .btn-pay {
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      text-decoration: none;
      background: #2563EB;
      color: #fff;
      transition: all 0.2s;
    }
    .btn-pay:hover {
      background: #1D4ED8;
    }
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: #fff;
      border-radius: 16px;
      border: 1px dashed #CBD5E1;
      max-width: 900px;
      margin: 0 auto;
    }
    .empty-icon {
      font-size: 4rem;
      margin-bottom: 20px;
    }
    .empty-title {
      font-family: 'Sora', sans-serif;
      font-size: 1.5rem;
      font-weight: 700;
      color: #0F1B2D;
      margin-bottom: 10px;
    }
    .empty-desc {
      color: #64748B;
      margin-bottom: 25px;
    }
    .btn-primary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 24px;
      background: linear-gradient(135deg, #2563EB, #1D4ED8);
      color: #fff;
      border-radius: 999px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s;
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(37,99,235,0.3);
    }

    /* Back Bar — style diambil dari profile-web.css (sudah di-link) */
  </style>
</head>
<body>

<!-- Back Bar — konsisten dengan profile.php -->
<div class="profile-back-bar">
  <div class="container">
    <a href="index.php" class="profile-back-btn">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Kembali ke Beranda
    </a>
  </div>
</div>

<div class="pesanan-hero">
  <div class="container">
    <h1>Pesanan Saya</h1>
    <p>Pantau status pesanan dan riwayat transaksi Anda di SEKALA dengan mudah.</p>
  </div>
</div>

<section class="pesanan-section">
  <div class="container">

    <?php
    // Tampilkan flash message jika ada
    $flash = getFlash();
    if ($flash): ?>
    <div style="max-width:900px;margin:0 auto 20px auto;padding:14px 18px;border-radius:12px;
                background:<?= $flash['type']==='info'?'#DBEAFE':($flash['type']==='success'?'#D1FAE5':'#FEE2E2') ?>;
                color:<?= $flash['type']==='info'?'#1D4ED8':($flash['type']==='success'?'#059669':'#DC2626') ?>;
                border:1px solid <?= $flash['type']==='info'?'#93C5FD':($flash['type']==='success'?'#6EE7B7':'#FCA5A5') ?>;
                font-size:14px;font-weight:600;">
      <?= $flash['type']==='success'?'✅':($flash['type']==='info'?'ℹ️':'❌') ?> <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <?php if (empty($pesanan)): ?>
      <div class="empty-state">
        <div class="empty-icon">📦</div>
        <div class="empty-title">Belum Ada Pesanan</div>
        <div class="empty-desc">Anda belum pernah membuat pesanan layanan desain di SEKALA. Yuk, mulai proyek kreatif pertama Anda!</div>
        <a href="index.php#section-paket" class="btn-primary">Lihat Paket Desain</a>
      </div>
    <?php else: ?>
      <div class="pesanan-list">
        <?php foreach ($pesanan as $p): 
          $status = strtolower($p['status_pesanan'] ?? 'pending');
          $badgeClass = 'badge-pending';
          $icon = '⏳';
          if ($status === 'proses') { $badgeClass = 'badge-proses'; $icon = '🔄'; }
          elseif ($status === 'selesai') { $badgeClass = 'badge-selesai'; $icon = '✅'; }
          elseif ($status === 'batal') { $badgeClass = 'badge-batal'; $icon = '❌'; }
          
          $statusBayarDB = $p['status_pembayaran'] ?? null;
          $statusBayar = $statusBayarDB ? strtolower($statusBayarDB) : 'belum_bayar';
          $tglPesan = date('d M Y', strtotime($p['tanggal_pesan']));
        ?>
          <div class="pesanan-card">
            <div class="pesanan-card-head">
              <div>
                <div class="pesanan-title"><?= htmlspecialchars($p['judul_proyek'] ?? 'Pesanan') ?></div>
                <div class="pesanan-subtitle">
                  <span>📅 <?= $tglPesan ?></span>
                  <span>📦 <?= htmlspecialchars($p['jenis_desain'] ?? 'Layanan Desain') ?></span>
                </div>
              </div>
              <div class="pesanan-badge <?= $badgeClass ?>">
                <?= $icon ?> <?= ucfirst($status) ?>
              </div>
            </div>
            
            <div class="pesanan-details">
              <div class="detail-item">
                <div class="detail-label">Status Pembayaran</div>
                <div class="detail-value">
                  <?php if ($statusBayar === 'terverifikasi'): ?>
                    <span style="color:#059669;">Sudah Bayar (Lunas)</span>
                  <?php elseif ($statusBayar === 'menunggu'): ?>
                    <span style="color:#059669;">Sudah Bayar (Menunggu Verifikasi)</span>
                  <?php elseif ($statusBayar === 'ditolak'): ?>
                    <span style="color:#DC2626;">Pembayaran Ditolak</span>
                  <?php else: ?>
                    <span style="color:#DC2626;">Belum Dibayar</span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Total Harga</div>
                <div class="detail-value">Rp <?= number_format($p['jumlah_bayar'] ?? $p['harga_mulai'] ?? 0, 0, ',', '.') ?></div>
              </div>
              <div class="detail-item">
                <div class="detail-label">Deadline</div>
                <div class="detail-value"><?= $p['deadline'] ? date('d M Y', strtotime($p['deadline'])) : '-' ?></div>
              </div>
            </div>
            
            <div class="pesanan-actions">
              <!-- Jika belum lunas / menunggu, tampilkan tombol bayar -->
              <?php if (($statusBayar === 'belum_bayar' || $statusBayar === 'ditolak') && $status !== 'batal'): ?>
                <a href="pembayaran.php?id_pesanan=<?= $p['id_pesanan'] ?>" class="btn-pay">💳 Selesaikan Pembayaran</a>
              <?php elseif ($statusBayar === 'menunggu' || $statusBayar === 'terverifikasi'): ?>
                <span style="padding: 8px 16px; background: #D1FAE5; color: #059669; border-radius: 8px; font-weight: 600; font-size: 0.9rem;">✅ Sudah Bayar</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<script src="assets/js/main.js"></script>

<!-- Scroll to Top Button -->
<button id="scrollToTopBtn" class="scroll-to-top" title="Kembali ke atas">
  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="18 15 12 9 6 15"></polyline>
  </svg>
</button>

<style>
/* Scroll to Top Button Styles */
.scroll-to-top {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 50px;
  height: 50px;
  background: var(--primary-mid, #2563EB);
  color: white;
  border: none;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
  cursor: pointer;
  z-index: 999;
  opacity: 0;
  visibility: hidden;
  transform: translateY(20px);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.scroll-to-top.show {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.scroll-to-top:hover {
  background: var(--primary, #1C4E8C);
  transform: translateY(-5px);
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.6);
}

@media (max-width: 768px) {
  .scroll-to-top {
    bottom: 20px;
    right: 20px;
    width: 45px;
    height: 45px;
  }
}
</style>

<script>
// Scroll to Top Logic
document.addEventListener('DOMContentLoaded', () => {
  const scrollBtn = document.getElementById('scrollToTopBtn');
  
  window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
      scrollBtn.classList.add('show');
    } else {
      scrollBtn.classList.remove('show');
    }
  });

  scrollBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
});
</script>
</body>
</html>
