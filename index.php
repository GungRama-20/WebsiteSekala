<?php
// ============================================================
// index.php — Landing Page + Dashboard SEKALA
// TIDAK wajib login — siapapun bisa akses
// ============================================================
require_once 'config.php';
require_once 'auth.php';

// Ambil paket dari tb_desain
$paket_result = $conn->query(
    "SELECT id_desain, jenis_desain, deskripsi, harga_mulai, estimasi_waktu
     FROM tb_desain
     ORDER BY harga_mulai ASC
     LIMIT 4"
);
$pakets = [];
if ($paket_result && $paket_result->num_rows > 0) {
    while ($row = $paket_result->fetch_assoc()) {
        $pakets[] = $row;
    }
}

// Fallback paket jika tabel masih kosong
if (empty($pakets)) {
    $pakets = [
        ['id_desain'=>1,'jenis_desain'=>'Paket Basic','deskripsi'=>'Paket poster digital untuk UMKM','harga_mulai'=>75000,'estimasi_waktu'=>'1-2 hari'],
        ['id_desain'=>2,'jenis_desain'=>'Paket Starter','deskripsi'=>'Konten media sosial rutin','harga_mulai'=>150000,'estimasi_waktu'=>'2-3 hari'],
        ['id_desain'=>3,'jenis_desain'=>'Paket Growth','deskripsi'=>'Paket lengkap bisnis aktif','harga_mulai'=>300000,'estimasi_waktu'=>'3-4 hari'],
        ['id_desain'=>4,'jenis_desain'=>'Paket Video','deskripsi'=>'Konten video Reels & TikTok','harga_mulai'=>250000,'estimasi_waktu'=>'4-5 hari'],
    ];
}

// Ambil portofolio dari tb_portofolio
$porto_result = $conn->query(
    "SELECT id_portofolio, judul, kategori, gambar
     FROM tb_portofolio
     ORDER BY tanggal_upload DESC
     LIMIT 10"
);
$portos = [];
if ($porto_result && $porto_result->num_rows > 0) {
    while ($row = $porto_result->fetch_assoc()) {
        $portos[] = $row;
    }
}

// Ambil testimoni dari tb_testimoni + join tb_pelanggan
// Di halaman utama tampilkan max 3
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
    while ($row = $testi_result->fetch_assoc()) {
        $testimonials[] = $row;
    }
}
// Hitung total testimoni yang tampil
$total_testi_res = $conn->query("SELECT COUNT(*) as total FROM tb_testimoni WHERE tampil=1");
$total_testi = $total_testi_res ? (int)$total_testi_res->fetch_assoc()['total'] : 0;

// Fallback testimoni
if (empty($testimonials)) {
    $testimonials = [
        ['nama'=>'Made Ari','rating'=>5,'isi_testimoni'=>'"SEKALA sangat membantu bisnis kami membuat konten Instagram. Prosesnya mudah dan desainnya cepat selesai."'],
        ['nama'=>'Komang Dewi','rating'=>5,'isi_testimoni'=>'"Dengan SEKALA saya tinggal kirim request dan hasil desainnya sudah siap dipakai untuk promosi. Sangat praktis."'],
        ['nama'=>'Wayan Putra','rating'=>5,'isi_testimoni'=>'"Pelayanan di SEKALA sangat profesional. Revisi mudah karena semua tercatat di sistem."'],
    ];
}

// Cek logout param
$logoutMsg = isset($_GET['logout']) ? 'Anda berhasil logout. Sampai jumpa! 👋' : '';

// Fitur paket (data statis tambahan untuk tampilan)
$paketFeatures = [
    1 => ['1 Desain Poster Digital','Request mudah via platform','Revisi tercatat di sistem','1 kali revisi','File siap download'],
    2 => ['3 Desain Konten Digital','Request mudah via platform','2 kali revisi','File terorganisir','Support via WA'],
    3 => ['6 Konten Sosial Media','1 Desain Flyer Promosi','3 kali revisi','Prioritas pengerjaan','File lengkap & rapi'],
    4 => ['1 Video Reels/TikTok','Durasi 30-60 detik','Plus voice-over MP3','2 kali revisi','File siap upload'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SEKALA — Platform Konten Digital UMKM</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="dashboard.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- Logout Flash -->
<?php if ($logoutMsg): ?>
<div class="flash-alert flash-success" id="flash-alert" style="font-family:'Plus Jakarta Sans',sans-serif;padding:14px 20px;background:#D1FAE5;color:#065F46;border-bottom:2px solid #10B981;display:flex;align-items:center;gap:12px;font-size:14px;font-weight:500;">
  <span>✅</span>
  <span><?= htmlspecialchars($logoutMsg) ?></span>
  <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;font-size:18px;cursor:pointer;opacity:0.6;">×</button>
</div>
<?php endif; ?>

<!-- ===================================================
     HERO SECTION
     =================================================== -->
<section class="hero-section" id="section-hero">
  <div class="container">
    <div class="hero-inner">

      <div class="hero-content">
        <div class="hero-eyebrow">🚀 Platform No. 1 UMKM Bali</div>
        <h1 class="hero-title">
          Platform Paling<br>
          Sederhana untuk<br>
          Memesan <span class="highlight">Konten Digital</span>
        </h1>
        <p class="hero-desc">
          Ubah ide Bisnis Anda menjadi konten digital yang nyata, terstruktur,
          dan profesional tanpa riset serta telekomunikasi yang rumit.
        </p>
        <div class="hero-actions">
          <?php if (isLoggedIn()): ?>
            <a class="btn btn-dark btn-lg" href="index.php#section-paket" onclick="smoothScroll('section-paket'); return false;">
              Mulai Request Konten →
            </a>
          <?php else: ?>
            <a class="btn btn-dark btn-lg" href="index.php#section-paket" onclick="smoothScroll('section-paket'); return false;">
              Mulai Request Konten →
            </a>
          <?php endif; ?>
        </div>
        <div class="hero-stats">
          <div class="stat-item">
            <div class="stat-num">500+</div>
            <div class="stat-label">Klien Puas</div>
          </div>
          <div class="stat-item">
            <div class="stat-num">1.200+</div>
            <div class="stat-label">Proyek Selesai</div>
          </div>
          <div class="stat-item">
            <div class="stat-num">4.9★</div>
            <div class="stat-label">Rating Rata-rata</div>
          </div>
        </div>
      </div>

      <div class="hero-visual">
        <div class="hero-img-wrap">
          <div class="image"><img src="assets/foto1-removebg-preview.png" width="500px" height="auto" top="-50px"></div>
            </div>
          </div>
          </div>
          <div class="hero-stars">
            <div class="hero-stars-row">★★★★★</div>
            <div class="hero-stars-text">500+ Review Bintang 5</div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- QUICK VALUE -->
<section class="quick-value">
  <div class="container">
    <div class="quick-value-head">
      <h2>Mengapa Memilih SEKALA?</h2>
      <p>Semua proses konten bisnis Anda kini lebih sederhana dan teratur.</p>
    </div>
    <div class="quick-value-grid">
      <div class="qv-card"><div class="qv-icon">📋</div><div class="qv-title">Request Terstruktur</div><div class="qv-desc">Ajukan kebutuhan konten Anda melalui form yang jelas, terstruktur, dan mudah diisi.</div></div>
      <div class="qv-card"><div class="qv-icon">⚡</div><div class="qv-title">Status Pengerjaan Jelas</div><div class="qv-desc">Pantau proses konten dan status hingga selesai secara real-time di platform.</div></div>
      <div class="qv-card"><div class="qv-icon">🔄</div><div class="qv-title">Revisi Tercatat</div><div class="qv-desc">Setiap revisi tercatat otomatis agar hasil sesuai kebutuhan Anda.</div></div>
      <div class="qv-card"><div class="qv-icon">📁</div><div class="qv-title">Semua File Rapi</div><div class="qv-desc">Hasil desain tersimpan rapi dan dapat diunduh kapan saja dari platform.</div></div>
    </div>
  </div>
</section>

<!-- HOW SEKALA WORKS -->
<section class="how-section" id="section-how">
  <div class="container">
    <div class="how-inner">
      <div class="how-img-side">
        <div class="image"><img src="assets/foto2-removebg-preview.png" width="400px" height="auto"></div>
      </div>
      <div class="how-content">
        <div class="how-head">
          <div class="section-label">Cara Kerja</div>
          <h2 class="section-title">How <span>SEKALA</span> Works</h2>
          <p class="section-sub">Proses sederhana dan terstruktur dari request hingga konten siap digunakan.</p>
        </div>
        <div class="how-steps">
          <?php
          $steps = [
            ['num'=>1,'title'=>'Buat Request Konten','desc'=>'Isi formulir request dengan mudah. Tentukan jenis konten, ukuran, referensi desain, dan informasi bisnis Anda.'],
            ['num'=>2,'title'=>'Proses Kreatif','desc'=>'Tim kami mengerjakan konten berdasarkan brief yang diberikan. Semua progres dapat dipantau dari dashboard.'],
            ['num'=>3,'title'=>'Revisi & Feedback','desc'=>'Berikan feedback dengan mudah. Seluruh catatan revisi tercatat di sistem agar hasilnya sesuai ekspektasi.'],
            ['num'=>4,'title'=>'Konten Selesai','desc'=>'Konten selesai langsung tersedia di dashboard Anda. Unduh dan gunakan untuk kebutuhan promosi bisnis.'],
          ];
          foreach ($steps as $s): ?>
          <div class="how-step">
            <div class="step-num"><?= $s['num'] ?></div>
            <div class="step-body">
              <div class="step-title"><?= htmlspecialchars($s['title']) ?></div>
              <div class="step-desc"><?= htmlspecialchars($s['desc']) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PAKET -->
<section class="paket-section" id="section-paket">
  <div class="container">
    <div class="section-center">
      <div class="section-label">Layanan Kami</div>
      <h2 class="section-title">Paket</h2>
    </div>

    <div class="paket-grid">
      <?php foreach ($pakets as $i => $p):
        $isFeatured = ($i === 2); // Paket ke-3 jadi featured
        $features   = $paketFeatures[$p['id_desain']] ?? ['Desain berkualitas','Revisi tersedia','File siap download'];
      ?>
      <div class="paket-card <?= $isFeatured ? 'featured' : '' ?>">
        <?php if ($isFeatured): ?>
          <div class="paket-badge">⭐ Terlaris</div>
        <?php endif; ?>

        <div class="paket-name"><?= htmlspecialchars($p['jenis_desain']) ?></div>
        <div class="paket-type">Estimasi: <?= htmlspecialchars($p['estimasi_waktu']) ?></div>
        <div class="paket-harga">
          <?= formatRupiah($p['harga_mulai']) ?>
          <small>/project</small>
        </div>

        <ul class="feature-list">
          <?php foreach ($features as $f): ?>
            <li class="feature-item"><span class="check">✓</span><span><?= htmlspecialchars($f) ?></span></li>
          <?php endforeach; ?>
        </ul>

        <!-- Tombol → ke detail_paket.php -->
        <a class="btn-paket" href="detail_paket.php?id=<?= $p['id_desain'] ?>">
          Lihat Detail
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- PORTOFOLIO -->
<section class="portofolio-section" id="section-portofolio">
  <div class="container">
    <div class="section-center">
      <div class="section-label">Karya Kami</div>
      <h2 class="section-title">Portofolio</h2>
    </div>
  </div>
  <div class="porto-slider-wrap">
    <div class="porto-track" id="porto-track">
      <?php
      // Jika ada data DB tampilkan, jika tidak tampilkan poster nyata dari assets
      if (!empty($portos)):
        $allPortos = array_merge($portos, $portos); // double for infinite loop
        foreach ($allPortos as $po): ?>
          <div class="porto-item">
            <img src="<?= htmlspecialchars($po['gambar']) ?>" alt="<?= htmlspecialchars($po['judul']) ?>"
              onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg,#1C4E8C,#3B82F6)'">
            <div class="porto-overlay">
              <div>
                <div class="porto-overlay-text"><?= htmlspecialchars($po['judul']) ?></div>
                <div class="porto-overlay-cat"><?= htmlspecialchars($po['kategori']) ?></div>
              </div>
            </div>
          </div>
        <?php endforeach;
      else:
        // Fallback: gunakan poster nyata dari folder assets
        $posterFallbacks = [
          ['src'=>'assets/poster1.jpeg','judul'=>'Desain Poster Premium','kat'=>'Poster & Visual'],
          ['src'=>'assets/poster2.jpeg','judul'=>'Konten Social Media','kat'=>'Social Media Feed'],
          ['src'=>'assets/poster3.jpeg','judul'=>'Event & Promosi','kat'=>'Poster & Visual'],
          ['src'=>'assets/poster4.jpeg','judul'=>'Campaign Digital','kat'=>'Social Media Feed'],
          ['src'=>'assets/poster5.jpeg','judul'=>'Branding Bisnis','kat'=>'Branding'],
        ];
        // Double untuk infinite scroll
        $allPh = array_merge($posterFallbacks, $posterFallbacks);
        foreach ($allPh as $ph): ?>
          <div class="porto-item">
            <img src="<?= $ph['src'] ?>" alt="<?= htmlspecialchars($ph['judul']) ?>"
              onerror="this.src=''; this.parentElement.style.background='linear-gradient(135deg,#1C4E8C,#3B82F6)'">
            <div class="porto-overlay">
              <div>
                <div class="porto-overlay-text"><?= htmlspecialchars($ph['judul']) ?></div>
                <div class="porto-overlay-cat"><?= htmlspecialchars($ph['kat']) ?></div>
              </div>
            </div>
          </div>
        <?php endforeach;
      endif; ?>
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
    // Jika tidak ada dari DB, gunakan fallback
    $displayTestimonials = !empty($testimonials) ? $testimonials : [
        ['nama'=>'Made Ari','rating'=>5,'isi_testimoni'=>'SEKALA sangat membantu bisnis kami membuat konten Instagram. Prosesnya mudah dan desainnya cepat selesai.'],
        ['nama'=>'Komang Dewi','rating'=>5,'isi_testimoni'=>'Dengan SEKALA saya tinggal kirim request dan hasil desainnya sudah siap dipakai untuk promosi. Sangat praktis.'],
        ['nama'=>'Wayan Putra','rating'=>5,'isi_testimoni'=>'Pelayanan di SEKALA sangat profesional. Revisi mudah karena semua tercatat di sistem.'],
    ];
    // Batasi 3 saja
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
          <div class="footer-logo-icon">S</div>
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
          <li><a href="#section-how" onclick="smoothScroll('section-how'); return false;">Cara Kerja</a></li>
          <li><a href="#section-paket" onclick="smoothScroll('section-paket'); return false;">Paket</a></li>
          <li><a href="#section-portofolio" onclick="smoothScroll('section-portofolio'); return false;">Portofolio</a></li>
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
          <li><a href="mailto:hello@sekala.id">hello@sekala.id</a></li>
          <li><a href="https://wa.me/62812345678">WhatsApp</a></li>
          <li><a href="#">Instagram</a></li>
        </ul>
      </div>
    </div>
  </div>
  <div class="footer-bottom">© 2025 SEKALA. All rights reserved.</div>
</footer>

<script src="assets/js/main.js"></script>
<script>
// Smooth scroll untuk anchor di halaman yang sama
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', function(e) {
    const id = this.getAttribute('href').slice(1);
    const el = document.getElementById(id);
    if (el) { e.preventDefault(); el.scrollIntoView({ behavior: 'smooth' }); }
  });
});
</script>
</body>
</html>
