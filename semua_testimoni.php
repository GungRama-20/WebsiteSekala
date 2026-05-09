<?php
// ============================================================
// semua_testimoni.php — Halaman Semua Testimoni
// ============================================================
require_once 'config.php';
require_once 'auth.php';

// Pagination
$per_page = 9;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Total testimoni tampil
$total_res = $conn->query("SELECT COUNT(*) as total FROM tb_testimoni WHERE tampil=1");
$total = $total_res ? (int)$total_res->fetch_assoc()['total'] : 0;
$total_pages = max(1, ceil($total / $per_page));

// Ambil testimoni
$testi_result = $conn->query(
    "SELECT t.isi_testimoni, t.rating, t.tanggal, p.nama
     FROM tb_testimoni t
     LEFT JOIN tb_pelanggan p ON t.id_pelanggan = p.id_pelanggan
     WHERE t.tampil = 1
     ORDER BY t.tanggal DESC
     LIMIT $per_page OFFSET $offset"
);
$testimonials = [];
if ($testi_result && $testi_result->num_rows > 0) {
    while ($row = $testi_result->fetch_assoc()) $testimonials[] = $row;
}

// Fallback jika kosong
if (empty($testimonials)) {
    $testimonials = [
        ['nama'=>'Made Ari','rating'=>5,'tanggal'=>date('Y-m-d'),'isi_testimoni'=>'SEKALA sangat membantu bisnis kami membuat konten Instagram. Prosesnya mudah dan desainnya cepat selesai.'],
        ['nama'=>'Komang Dewi','rating'=>5,'tanggal'=>date('Y-m-d'),'isi_testimoni'=>'Dengan SEKALA saya tinggal kirim request dan hasil desainnya sudah siap dipakai untuk promosi. Sangat praktis.'],
        ['nama'=>'Wayan Putra','rating'=>5,'tanggal'=>date('Y-m-d'),'isi_testimoni'=>'Pelayanan di SEKALA sangat profesional. Revisi mudah karena semua tercatat di sistem.'],
        ['nama'=>'Nyoman Sari','rating'=>5,'tanggal'=>date('Y-m-d'),'isi_testimoni'=>'Konten yang dibuat SEKALA sangat berkualitas dan sesuai dengan kebutuhan bisnis saya. Recommended!'],
        ['nama'=>'Ketut Wijaya','rating'=>5,'tanggal'=>date('Y-m-d'),'isi_testimoni'=>'Proses request sangat mudah dan hasilnya memuaskan. Tim SEKALA sangat responsif dan profesional.'],
        ['nama'=>'Putu Indriani','rating'=>5,'tanggal'=>date('Y-m-d'),'isi_testimoni'=>'Sangat puas dengan layanan SEKALA. Desain poster yang dihasilkan melebihi ekspektasi saya.'],
    ];
    $total = count($testimonials);
}

// ---- CEK CUSTOMER BISA BUAT TESTIMONI BARU ----
$canTestimoni      = false;
$id_pelanggan_form = 0;
$alreadyTestimoni  = false;
$flashT            = getFlash();

if (isLoggedIn() && (getCurrentUser()['role'] ?? '') !== 'Admin') {
    $uEmail = getCurrentUser()['email'];
    // Cek punya pesanan
    $stCek = $conn->prepare(
        "SELECT p.id_pelanggan FROM tb_pelanggan p
         JOIN tb_pesanan o ON p.id_pelanggan = o.id_pelanggan
         WHERE p.email = ? LIMIT 1"
    );
    $stCek->bind_param('s', $uEmail);
    $stCek->execute();
    $resCek = $stCek->get_result()->fetch_assoc();
    $stCek->close();
    if ($resCek) {
        $id_pelanggan_form = $resCek['id_pelanggan'];
        // Cek sudah pernah buat testimoni
        $stTcek = $conn->prepare("SELECT id_testimoni FROM tb_testimoni WHERE id_pelanggan = ? LIMIT 1");
        $stTcek->bind_param('i', $id_pelanggan_form);
        $stTcek->execute();
        $alreadyTestimoni = $stTcek->get_result()->num_rows > 0;
        $stTcek->close();
        $canTestimoni = !$alreadyTestimoni;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Semua Testimoni — SEKALA</title>
  <link rel="icon" type="image/png" href="assets/Logo3.png" style="border-radius: 50%; width: 32px; height: 32px;">
  <meta name="description" content="Baca semua testimoni pelanggan yang telah menggunakan layanan desain konten digital SEKALA.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="dashboard.css">
  <style>
    .testi-page-hero {
      background: var(--navy-grad);
      padding: 64px 0 48px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .testi-page-hero::before {
      content: '';
      position: absolute;
      width: 400px; height: 400px;
      background: rgba(255,255,255,0.04);
      border-radius: 50%;
      top: -150px; right: -100px;
    }
    .testi-page-hero .section-label { display: inline-block; background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.85); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; padding: 5px 16px; border-radius: 999px; margin-bottom: 14px; }
    .testi-page-hero h1 { font-family: var(--font-display); font-size: 2.2rem; font-weight: 800; color: #fff; margin-bottom: 12px; }
    .testi-page-hero p { color: rgba(255,255,255,0.7); font-size: 15px; }
    .testi-page-hero .hero-back { display: inline-flex; align-items: center; gap: 6px; color: rgba(255,255,255,0.7); font-size: 13px; text-decoration: none; margin-bottom: 24px; transition: color .2s; }
    .testi-page-hero .hero-back:hover { color: #fff; }
    .testi-all-section { padding: 64px 0 80px; background: var(--bg); }
    .testi-all-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; margin-bottom: 48px; }
    .testi-count-badge { display: inline-flex; align-items: center; gap: 6px; background: var(--primary-pale); color: var(--primary-mid); font-size: 12px; font-weight: 700; padding: 5px 14px; border-radius: 999px; margin-bottom: 28px; }
    /* Pagination */
    .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; }
    .page-btn { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; font-size: 13px; font-weight: 700; text-decoration: none; border: 1.5px solid var(--border); color: var(--dark); background: var(--white); transition: var(--transition); }
    .page-btn:hover, .page-btn.active { background: var(--primary-mid); color: #fff; border-color: var(--primary-mid); }
    .page-btn.disabled { opacity: 0.4; pointer-events: none; }
    @media (max-width: 768px) { .testi-all-grid { grid-template-columns: 1fr; } .testi-page-hero h1 { font-size: 1.6rem; } }
  </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- Hero -->
<div class="testi-page-hero">
  <div class="container">
    <a href="index.php#section-testimoni" class="hero-back">← Kembali ke Beranda</a>
    <div class="section-label">Kata Mereka</div>
    <h1>Semua Testimoni Pelanggan</h1>
    <p>Apa yang dikatakan klien kami tentang layanan SEKALA</p>
  </div>
</div>

<!-- All Testimoni -->
<section class="testi-all-section">
  <div class="container">
    <div class="testi-count-badge">
      ⭐ <?= $total ?> Testimoni Pelanggan
    </div>
    <div class="testi-all-grid">
      <?php foreach ($testimonials as $t): ?>
      <div class="testi-card">
        <div class="testi-stars"><?= str_repeat('★', (int)$t['rating']) ?></div>
        <p class="testi-text"><?= htmlspecialchars($t['isi_testimoni']) ?></p>
        <div class="testi-author">
          <div class="testi-avatar"><?= strtoupper(mb_substr($t['nama'], 0, 1)) ?></div>
          <div>
            <div class="testi-name"><?= htmlspecialchars($t['nama']) ?></div>
            <div class="testi-role">Pelanggan SEKALA<?= isset($t['tanggal']) ? ' · ' . date('M Y', strtotime($t['tanggal'])) : '' ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination (hanya jika ada data nyata dari DB) -->
    <?php if ($total > $per_page && count($testimonials) === $per_page): ?>
    <div class="pagination">
      <a href="?page=<?= max(1, $page-1) ?>" class="page-btn <?= $page<=1?'disabled':'' ?>">‹</a>
      <?php for ($p = 1; $p <= $total_pages; $p++): ?>
        <a href="?page=<?= $p ?>" class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <a href="?page=<?= min($total_pages, $page+1) ?>" class="page-btn <?= $page>=$total_pages?'disabled':'' ?>">›</a>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ======================================================
     FORM TULIS TESTIMONI BARU (customer yg sudah order)
     ====================================================== -->
<?php if (isLoggedIn() && $canTestimoni): ?>
<section style="background:#fff;padding:48px 0 64px;">
  <div class="container">
    <div style="max-width:600px;margin:0 auto;">
      <div style="text-align:center;margin-bottom:28px;">
        <div class="section-label" style="display:inline-block;margin-bottom:10px;">Bagikan Pengalaman</div>
        <h2 class="section-title" style="font-size:1.5rem;">Tulis Testimoni Anda</h2>
        <p style="font-size:14px;color:#64748B;margin-top:8px;">Sudah menggunakan layanan SEKALA? Ceritakan pengalaman Anda!</p>
      </div>

      <?php if ($flashT && $flashT['type'] === 'success'): ?>
      <div style="background:#D1FAE5;color:#065F46;border:1px solid #6EE7B7;border-radius:12px;padding:13px 16px;font-size:14px;margin-bottom:16px;">
        ✅ <?= htmlspecialchars($flashT['message']) ?>
      </div>
      <?php elseif ($flashT && $flashT['type'] === 'error'): ?>
      <div style="background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5;border-radius:12px;padding:13px 16px;font-size:14px;margin-bottom:16px;">
        ❌ <?= htmlspecialchars($flashT['message']) ?>
      </div>
      <?php endif; ?>

      <div class="testi-form-card" style="background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:20px;padding:32px;">
        <form method="POST" action="testimoni_submit.php" id="form-testi-baru">
          <input type="hidden" name="id_pelanggan" value="<?= $id_pelanggan_form ?>">
          <input type="hidden" name="id_pesanan"   value="0">
          <input type="hidden" name="redirect_to"  value="semua_testimoni.php">
          <input type="hidden" name="rating"        value="5" id="rating-hidden-st">

          <!-- Star Rating -->
          <div style="margin-bottom:18px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:8px;">Rating Anda</label>
            <div id="star-picker-st" style="display:flex;align-items:center;gap:4px;">
              <?php for ($s = 1; $s <= 5; $s++): ?>
              <button type="button"
                class="star-btn-st<?= $s <= 5 ? ' active' : '' ?>"
                data-val="<?= $s ?>"
                onclick="setRatingST(<?= $s ?>)"
                style="font-size:32px;background:none;border:none;cursor:pointer;color:<?= $s <= 5 ? '#F59E0B' : '#CBD5E1' ?>;transition:color .15s;line-height:1;">★</button>
              <?php endfor; ?>
              <span id="star-label-st" style="font-size:13px;color:#64748B;margin-left:8px;font-weight:600;">5 / 5</span>
            </div>
          </div>

          <!-- Textarea -->
          <div style="margin-bottom:18px;">
            <label style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:8px;">
              Testimoni Anda <span style="color:#EF4444;">*</span>
            </label>
            <textarea name="isi_testimoni" id="testi-text-st" rows="4" required
              style="width:100%;padding:12px 16px;border:1.5px solid #E2E8F0;border-radius:12px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;resize:vertical;outline:none;background:#fff;"
              placeholder="Ceritakan pengalaman Anda menggunakan layanan SEKALA..."></textarea>
            <div style="font-size:12px;color:#94A3B8;margin-top:4px;text-align:right;">
              <span id="char-count-st">0</span> / 500 karakter
            </div>
          </div>

          <button type="submit"
            style="width:100%;padding:14px;border-radius:999px;background:#0F1B2D;color:#fff;font-weight:700;font-size:14px;border:none;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;transition:all .25s;"
            onmouseover="this.style.background='#1E293B';this.style.transform='translateY(-1px)'"
            onmouseout="this.style.background='#0F1B2D';this.style.transform='translateY(0)'">
            ⭐ Kirim Testimoni
          </button>
        </form>
      </div>
    </div>
  </div>
</section>
<script>
function setRatingST(val) {
  document.getElementById('rating-hidden-st').value = val;
  document.getElementById('star-label-st').textContent = val + ' / 5';
  document.querySelectorAll('.star-btn-st').forEach((b, i) => {
    b.style.color = i < val ? '#F59E0B' : '#CBD5E1';
  });
}
document.getElementById('testi-text-st')?.addEventListener('input', function() {
  document.getElementById('char-count-st').textContent = Math.min(this.value.length, 500);
  if (this.value.length > 500) this.value = this.value.substring(0, 500);
});
</script>

<?php elseif (isLoggedIn() && $alreadyTestimoni): ?>
<section style="background:#fff;padding:40px 0;">
  <div class="container" style="text-align:center;">
    <div style="display:inline-flex;align-items:center;gap:10px;background:#D1FAE5;color:#065F46;border:1px solid #6EE7B7;padding:14px 24px;border-radius:999px;font-size:14px;font-weight:600;">
      ✅ Anda sudah pernah mengirim testimoni. Terima kasih!
    </div>
  </div>
</section>

<?php elseif (isLoggedIn() && !$canTestimoni && (getCurrentUser()['role'] ?? '') !== 'Admin'): ?>
<section style="background:#fff;padding:40px 0;">
  <div class="container" style="text-align:center;">
    <div style="display:inline-flex;align-items:center;gap:10px;background:#DBEAFE;color:#1E40AF;border:1px solid #93C5FD;padding:14px 24px;border-radius:999px;font-size:14px;font-weight:600;">
      ℹ️ Selesaikan pesanan terlebih dahulu untuk dapat menulis testimoni.
    </div>
  </div>
</section>
<?php endif; ?>

<!-- FOOTER / KONTAK -->
<footer class="footer" id="section-kontak">
  <div class="container">
    <div class="footer-inner">
      <div class="footer-brand">
        <div class="footer-logo">
          <div class="image"><img src="assets/Logo3.png" width="30px" height="30px" style="border-radius: 20%"></div>
          <span class="footer-logo-text">SEKALA</span>
        </div>
        <p class="footer-desc">Mewujudkan ide promosi bisnis Anda menjadi konten digital yang nyata, rapi, dan berkualitas. Berakar pada filosofi Bali untuk mendukung transformasi digital UMKM lokal.</p>
        <div class="footer-cta-label">Bingung Mau Mulai Dari Mana?</div>
        <div class="footer-email-wrap">
          <input type="email" placeholder="Masukan email untuk konsultasi gratis...">
          <button class="footer-email-btn" type="button">→</button>
        </div>
      </div>
      <div>
        <div class="footer-col-title">Navigasi</div>
        <ul class="footer-links">
          <li><a href="index.php">Home</a></li>
          <li><a href="index.php#section-how">Cara Kerja</a></li>
          <li><a href="index.php#section-paket">Paket</a></li>
          <li><a href="index.php#section-portofolio">Portofolio</a></li>
          <?php if (isLoggedIn()): ?>
            <li><a href="logout.php">Logout</a></li>
          <?php else: ?>
            <li><a href="signin.php">Masuk</a></li>
          <?php endif; ?>
        </ul>
      </div>
      <div>
        <div class="footer-col-title">Layanan Kami</div>
        <ul class="footer-links">
          <li><a href="#">Poster &amp; Visual</a></li>
          <li><a href="#">Social Media Feed</a></li>
          <li><a href="#">Video Pendek</a></li>
          <li><a href="#">Custom Project</a></li>
        </ul>
      </div>
      <div>
        <div class="footer-col-title">Hubungi Kami</div>
        <ul class="footer-links">
          <li><a href="mailto:desainsekala@gmail.com">desainsekala@gmail.com</a></li>
          <li><a href="https://wa.me/62812345678">WhatsApp</a></li>
          <li><a href="https://www.instagram.com/seka.ladesign/?utm_source=ig_web_button_share_sheet">Instagram</a></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="footer-bottom">© 2025 SEKALA. All rights reserved.</div>
</footer>

</body>
</html>
