<?php
// ============================================================
// detail_paket.php — Halaman Detail Paket
// TIDAK wajib login — siapapun bisa lihat
// Login BARU diwajibkan saat klik "Mulai Request Konten"
// ============================================================
require_once 'config.php';
require_once 'auth.php';

// Ambil ID dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    redirect('index.php');
}

// Ambil data paket dari tb_desain
$stmt = $conn->prepare(
    "SELECT id_desain, jenis_desain, deskripsi, harga_mulai, estimasi_waktu
     FROM tb_desain
     WHERE id_desain = ?
     LIMIT 1"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$paket = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fallback data jika DB kosong
if (!$paket) {
    $fallbacks = [
        1 => ['id_desain'=>1,'jenis_desain'=>'Paket Basic','deskripsi'=>'Paket ini dirancang untuk membantu pelaku UMKM mendapatkan desain poster digital dengan proses yang mudah, cepat, dan terstruktur. Seluruh permintaan dilakukan melalui platform sehingga lebih praktis tanpa perlu memahami proses desain secara teknis.','harga_mulai'=>75000,'estimasi_waktu'=>'1-2 hari'],
        2 => ['id_desain'=>2,'jenis_desain'=>'Paket Starter','deskripsi'=>'Cocok untuk bisnis yang butuh konten media sosial rutin. Dapatkan desain berkualitas dengan harga terjangkau dan proses yang terstruktur.','harga_mulai'=>150000,'estimasi_waktu'=>'2-3 hari'],
        3 => ['id_desain'=>3,'jenis_desain'=>'Paket Growth','deskripsi'=>'Paket terlaris yang mencakup konten media sosial untuk bisnis yang aktif berkembang. Termasuk konten feed dan flyer promosi.','harga_mulai'=>300000,'estimasi_waktu'=>'3-4 hari'],
        4 => ['id_desain'=>4,'jenis_desain'=>'Paket Video','deskripsi'=>'Solusi konten video pendek untuk Reels, TikTok, dan promosi digital bisnis Anda dengan kualitas profesional.','harga_mulai'=>250000,'estimasi_waktu'=>'4-5 hari'],
    ];
    $paket = $fallbacks[$id] ?? null;
    if (!$paket) redirect('index.php');
}

// Fitur per paket (data statis)
$allFeatures = [
    1 => ['1 Desain Poster Digital','Request mudah melalui platform','Revisi tercatat di sistem','File tersimpan rapi & aman','1 kali revisi'],
    2 => ['3 Desain Konten Digital','Request mudah melalui platform','Revisi tercatat di sistem','2 kali revisi','File tersimpan rapi'],
    3 => ['6 Konten Sosial Media','1 Desain Flyer Promosi','3 kali revisi','File terorganisir rapi','Prioritas pengerjaan'],
    4 => ['1 Video Reels / TikTok','Durasi 30-60 detik','Plus voice-over MP3','2 kali revisi','File siap upload'],
];
$features = $allFeatures[$paket['id_desain']] ?? ['Desain berkualitas','Revisi tersedia','File siap download'];

// Testimoni dari DB (hanya yang tampil=1)
$testi_result = $conn->query(
    "SELECT t.isi_testimoni, t.rating, p.nama
     FROM tb_testimoni t
     LEFT JOIN tb_pelanggan p ON t.id_pelanggan = p.id_pelanggan
     WHERE t.tampil = 1
     ORDER BY t.tanggal DESC
     LIMIT 3"
);
$testimonials = [];
if ($testi_result && $testi_result->num_rows > 0) {
    while ($r = $testi_result->fetch_assoc()) $testimonials[] = $r;
}
if (empty($testimonials)) {
    $testimonials = [
        ['nama'=>'Made Ari','rating'=>5,'isi_testimoni'=>'"SEKALA membantu bisnis kami membuat konten Instagram. Prosesnya mudah dan hasilnya profesional."'],
        ['nama'=>'Komang Dewi','rating'=>5,'isi_testimoni'=>'"Tinggal kirim request dan hasil desainnya sudah siap. Sangat praktis untuk UMKM."'],
        ['nama'=>'Wayan Putra','rating'=>5,'isi_testimoni'=>'"Revisi mudah karena semua tercatat di sistem. Sangat memuaskan!"'],
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($paket['jenis_desain']) ?> — SEKALA</title>
  <link rel="icon" type="image/png" href="assets/Logo3.png" style="border-radius: 50%; width: 32px; height: 32px;">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css?v=2">
  <link rel="stylesheet" href="pages.css?v=2">
  <link rel="stylesheet" href="dashboard.css?v=2">
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
  <div class="container">
    <div class="breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep">›</span>
      <span class="current">Detail Paket</span>
    </div>
  </div>
</div>

<!-- DETAIL PAKET -->
<section class="detail-section">
  <div class="container">
    <div class="detail-inner">

      <!-- Gallery dengan foto poster nyata -->
      <div class="detail-gallery">
        <div class="gallery-main" id="gallery-main">
          <img id="gallery-main-img"
               src="assets/poster1.jpeg"
               alt="<?= htmlspecialchars($paket['jenis_desain']) ?> - Preview 1"
               style="width:100%;height:auto;object-fit:contain;border-radius:20px;display:block;"
               onerror="this.parentElement.innerHTML='<div style=&quot;width:100%;aspect-ratio:4/5;background:linear-gradient(135deg,#DBEAFE,#2563EB);display:flex;align-items:center;justify-content:center;border-radius:20px;&quot;><span style=&quot;font-size:80px;&quot;>🎨</span></div>'">
        </div>
        <div class="gallery-thumbs">
          <?php
          $posterThumbs = [
            ['src'=>'assets/poster1.jpeg','label'=>'Preview 1'],
            ['src'=>'assets/poster2.jpeg','label'=>'Preview 2'],
            ['src'=>'assets/poster3.jpeg','label'=>'Preview 3'],
          ];
          foreach ($posterThumbs as $gi => $pt): ?>
          <div class="gallery-thumb <?= $gi===0 ? 'active' : '' ?>" onclick="switchThumb(<?= $gi ?>)" data-src="<?= $pt['src'] ?>">
            <img src="<?= $pt['src'] ?>" alt="<?= htmlspecialchars($pt['label']) ?>"
                 style="width:100%;height:100%;object-fit:cover;border-radius:10px;display:block;"
                 onerror="this.parentElement.style.background='linear-gradient(135deg,#DBEAFE,#2563EB)'">
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Info -->
      <div class="detail-info">
        <div class="detail-category">
          <?php
          $categories = [1=>'Poster & Visual',2=>'Social Media Feed',3=>'Social Media Feed',4=>'Video Pendek'];
          echo $categories[$paket['id_desain']] ?? 'Desain Digital';
          ?>
        </div>

        <h1 class="detail-title"><?= htmlspecialchars($paket['jenis_desain']) ?></h1>

        <div class="detail-stars">★★★★★ <span style="font-size:13px;color:var(--gray);margin-left:6px;">(4.9/5)</span></div>

        <div class="detail-price"><?= formatRupiah($paket['harga_mulai']) ?></div>

        <p class="detail-desc"><?= nl2br(htmlspecialchars($paket['deskripsi'])) ?></p>

        <ul class="detail-features">
          <?php foreach ($features as $f): ?>
          <li class="detail-feature-item">
            <span class="check-icon">✓</span> <?= htmlspecialchars($f) ?>
          </li>
          <?php endforeach; ?>
        </ul>

        <!-- Info box singkat -->
        <div class="detail-info-box">
          <div class="detail-info-box-title">Info singkat</div>
          <ul class="info-box-list">
            <li class="info-box-item"><span class="icon">⏱</span> Pengerjaan <?= htmlspecialchars($paket['estimasi_waktu']) ?></li>
            <li class="info-box-item"><span class="icon">🔄</span> Revisi tersedia</li>
            <li class="info-box-item"><span class="icon">📁</span> File siap download</li>
          </ul>
        </div>

        <!--
          ============================================================
          TOMBOL "Mulai Request Konten"
          → Jika Admin: tombol di-disable (Hanya untuk pelanggan)
          → Jika BELUM login: redirect ke signin.php
          → Jika SUDAH login: ke form_request.php
          ============================================================
        -->
        <?php 
          $isUserAdmin = isLoggedIn() && (getCurrentUser()['role'] ?? '') === 'Admin';
        ?>
        <?php if ($isUserAdmin): ?>
          <!-- ❌ Admin → tombol mati -->
          <button class="btn-request" disabled style="background: #94A3B8; cursor: not-allowed; box-shadow: none; width: 100%;">
            Mulai Request Konten (Khusus Pelanggan)
          </button>
        <?php elseif (isLoggedIn()): ?>
          <!-- ✅ Sudah login → langsung ke form -->
          <a class="btn-request" href="form_request.php?id_desain=<?= $paket['id_desain'] ?>">
            Mulai Request Konten
          </a>
        <?php else: ?>
          <!-- ❌ Belum login → ke signin, simpan tujuan -->
          <a class="btn-request"
             href="signin.php"
             onclick="
               sessionStorage.setItem('redirect_after', 'form_request.php?id_desain=<?= $paket['id_desain'] ?>');
               return true;
             ">
            Mulai Request Konten
          </a>
          <p style="font-size:12px;color:var(--gray);margin-top:10px;text-align:center;">
            🔒 Anda perlu login untuk melanjutkan request
          </p>
        <?php endif; ?>

      </div>
    </div>
  </div>
</section>

<!-- TESTIMONI -->
<section class="testimoni-section">
  <div class="container">
    <div class="section-center">
      <div class="section-label">Kata Mereka</div>
      <h2 class="section-title">Testimoni</h2>
    </div>
    <?php
    $displayTestimonials = !empty($testimonials) ? $testimonials : [
        ['nama'=>'Made Ari','rating'=>5,'isi_testimoni'=>'SEKALA sangat membantu bisnis kami membuat konten Instagram. Prosesnya mudah dan desainnya cepat selesai.'],
        ['nama'=>'Komang Dewi','rating'=>5,'isi_testimoni'=>'Dengan SEKALA saya tinggal kirim request dan hasil desainnya sudah siap dipakai untuk promosi. Sangat praktis.'],
        ['nama'=>'Wayan Putra','rating'=>5,'isi_testimoni'=>'Pelayanan di SEKALA sangat profesional. Revisi mudah karena semua tercatat di sistem.'],
    ];
    $displayTestimonials = array_slice($displayTestimonials, 0, 3);
    ?>
    <div class="testi-grid">
      <?php foreach ($displayTestimonials as $t): ?>
      <div class="testi-card">
        <div class="testi-stars"><?= str_repeat('★', (int)$t['rating']) ?></div>
        <p class="testi-text"><?= htmlspecialchars($t['isi_testimoni']) ?></p>
        <div class="testi-author">
          <div class="testi-avatar"><?= strtoupper(mb_substr($t['nama'], 0, 1)) ?></div>
          <div>
            <div class="testi-name"><?= htmlspecialchars($t['nama']) ?></div>
            <div class="testi-role">Pelanggan SEKALA</div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="testi-lihat-semua">
      <a href="semua_testimoni.php" class="btn-lihat-semua">Lihat Semua <span>→</span></a>
    </div>
  </div>
</section>

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


<script>
// ============================================================
// Gallery Thumbnail Switcher
// ============================================================
function switchThumb(index) {
  var thumbs = document.querySelectorAll('.gallery-thumb');
  var mainImg = document.getElementById('gallery-main-img');

  if (!mainImg || thumbs.length === 0) return;

  // Update gambar besar
  var clickedThumb = thumbs[index];
  if (!clickedThumb) return;

  var newSrc = clickedThumb.getAttribute('data-src');
  if (newSrc) {
    // Animasi fade
    mainImg.style.transition = 'opacity 0.2s ease';
    mainImg.style.opacity = '0';
    setTimeout(function() {
      mainImg.src = newSrc;
      mainImg.style.opacity = '1';
    }, 200);
  }

  // Update class active pada thumbnail
  thumbs.forEach(function(thumb, i) {
    if (i === index) {
      thumb.classList.add('active');
    } else {
      thumb.classList.remove('active');
    }
  });
}
</script>
</body>
</html>
