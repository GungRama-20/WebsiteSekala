<?php
// ============================================================
// form_request.php — Formulir Request Konten
// ⚠️ WAJIB LOGIN — redirect ke signin jika belum login
// ============================================================
require_once 'config.php';
require_once 'auth.php';

// 🔒 PROTEKSI HALAMAN — wajib login
requireLogin('form_request.php' . (isset($_GET['id_desain']) ? '?id_desain='.(int)$_GET['id_desain'] : ''));

// Ambil ID desain dari URL
$id_desain = isset($_GET['id_desain']) ? (int)$_GET['id_desain'] : 0;

// Redirect kalau tidak ada id_desain
if ($id_desain <= 0) {
    redirect('index.php');
}

// Data paket fallback statis (sama dengan detail_paket.php)
$fallbackDesain = [
    1 => ['id_desain'=>1,'jenis_desain'=>'Paket Basic',   'deskripsi'=>'Paket ini dirancang untuk membantu pelaku UMKM mendapatkan desain poster digital dengan proses yang mudah, cepat, dan terstruktur.','harga_mulai'=>75000,  'estimasi_waktu'=>'1-2 hari'],
    2 => ['id_desain'=>2,'jenis_desain'=>'Paket Starter', 'deskripsi'=>'Cocok untuk bisnis yang butuh konten media sosial rutin. Dapatkan desain berkualitas dengan harga terjangkau.','harga_mulai'=>150000, 'estimasi_waktu'=>'2-3 hari'],
    3 => ['id_desain'=>3,'jenis_desain'=>'Paket Growth',  'deskripsi'=>'Paket terlaris untuk bisnis aktif berkembang. Termasuk konten feed dan flyer promosi.','harga_mulai'=>300000, 'estimasi_waktu'=>'3-4 hari'],
    4 => ['id_desain'=>4,'jenis_desain'=>'Paket Video',   'deskripsi'=>'Solusi konten video pendek untuk Reels, TikTok, dan promosi digital bisnis Anda.','harga_mulai'=>250000, 'estimasi_waktu'=>'4-5 hari'],
];

// Ambil data paket dari DB
$paket = null;
$stmtD = $conn->prepare("SELECT id_desain, jenis_desain, harga_mulai, estimasi_waktu FROM tb_desain WHERE id_desain = ? LIMIT 1");
$stmtD->bind_param('i', $id_desain);
$stmtD->execute();
$paket = $stmtD->get_result()->fetch_assoc();
$stmtD->close();

// Jika tidak ada di DB, seed dari data fallback lalu baca ulang
if (!$paket) {
    $fb = $fallbackDesain[$id_desain] ?? null;
    if (!$fb) {
        // id_desain tidak dikenali — redirect ke index
        redirect('index.php');
    }
    // Seed ke tb_desain agar FK constraint tidak gagal
    $conn->query(
        "INSERT IGNORE INTO tb_desain (id_desain, jenis_desain, deskripsi, harga_mulai, estimasi_waktu)
         VALUES ({$fb['id_desain']}, '{$conn->real_escape_string($fb['jenis_desain'])}',
                '{$conn->real_escape_string($fb['deskripsi'])}',
                {$fb['harga_mulai']}, '{$conn->real_escape_string($fb['estimasi_waktu'])}')"
    );
    $paket = $fb; // gunakan data fallback langsung
}

$error   = '';
$success = '';

// ---- Proses Form Submit ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user = getCurrentUser();

    // Ambil / validasi data form
    $brand       = e($conn, $_POST['brand']       ?? '');
    $pic         = e($conn, $_POST['pic']         ?? '');
    $email_wa    = e($conn, $_POST['email_wa']    ?? '');
    $produk      = e($conn, $_POST['produk']      ?? '');
    $berdiri     = e($conn, $_POST['berdiri']     ?? '');
    $alasan      = e($conn, $_POST['alasan']      ?? '');
    $pesan_utama = e($conn, $_POST['pesan_utama'] ?? '');
    $audience    = e($conn, $_POST['audience']    ?? '');
    $vibe        = e($conn, $_POST['vibe']        ?? '');
    $warna       = e($conn, $_POST['warna']       ?? '');
    $ukuran      = e($conn, $_POST['ukuran']      ?? '');
    $deadline    = e($conn, $_POST['deadline']    ?? '');

    if (empty($brand) || empty($pic) || empty($email_wa) || empty($produk) ||
        empty($berdiri) || empty($pesan_utama) || empty($audience) ||
        empty($warna) || empty($ukuran) || empty($deadline)) {
        $error = 'Mohon lengkapi semua field yang wajib diisi (*).';
    } else {

        // Pastikan AUTO_INCREMENT tb_pelanggan minimal 1
        $conn->query("ALTER TABLE tb_pelanggan AUTO_INCREMENT = 1");

        // Cek / buat pelanggan di tb_pelanggan
        $stmtP = $conn->prepare("SELECT id_pelanggan FROM tb_pelanggan WHERE email = ? LIMIT 1");
        $stmtP->bind_param('s', $user['email']);
        $stmtP->execute();
        $res = $stmtP->get_result();
        $stmtP->close();

        if ($res->num_rows > 0) {
            $id_pelanggan = $res->fetch_assoc()['id_pelanggan'];
        } else {
            // Insert ke tb_pelanggan (IGNORE untuk menghindari duplicate PK)
            $tgl_daftar = date('Y-m-d H:i:s');
            $stmtIns = $conn->prepare("INSERT IGNORE INTO tb_pelanggan (nama, email, no_hp, alamat, tanggal_daftar) VALUES (?, ?, ?, ?, ?)");
            $no_hp = $email_wa;
            $alamat = '-';
            $stmtIns->bind_param('sssss', $user['nama'], $user['email'], $no_hp, $alamat, $tgl_daftar);
            $stmtIns->execute();
            $id_pelanggan = $stmtIns->insert_id;
            $stmtIns->close();

            // Jika insert_id = 0, coba ambil lagi dari DB (IGNORE mungkin skip insert)
            if ($id_pelanggan <= 0) {
                $stmtP2 = $conn->prepare("SELECT id_pelanggan FROM tb_pelanggan WHERE email = ? LIMIT 1");
                $stmtP2->bind_param('s', $user['email']);
                $stmtP2->execute();
                $res2 = $stmtP2->get_result()->fetch_assoc();
                $stmtP2->close();
                $id_pelanggan = $res2 ? $res2['id_pelanggan'] : 0;
            }
        }

        // Jika id_pelanggan masih 0, tolak — jangan buat pesanan dengan FK invalid
        if ($id_pelanggan <= 0) {
            $error = 'Gagal memproses data pelanggan. Silakan hubungi admin.';
        } else {

        // Gabung deskripsi proyek dari semua field form
        $deskripsi_proyek = "Brand: $brand | PIC: $pic | Kontak: $email_wa | Produk: $produk | "
                          . "Berdiri: $berdiri | Alasan: $alasan | Pesan: $pesan_utama | "
                          . "Target Audience: $audience | Vibe: $vibe | Warna: $warna | "
                          . "Ukuran: $ukuran";

        // Handle upload logo
        $file_referensi = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png'])) {
                $filename = 'logo_' . time() . '_' . rand(100,999) . '.' . $ext;
                move_uploaded_file($_FILES['logo']['tmp_name'], 'uploads/' . $filename);
                $file_referensi = 'uploads/' . $filename;
            }
        }

        // Insert ke tb_pesanan
        $status_pesanan  = 'pending';
        $tanggal_pesan   = date('Y-m-d H:i:s');
        $judul_proyek    = "Request $paket[jenis_desain] - $brand";

        $stmtO = $conn->prepare(
            "INSERT INTO tb_pesanan (id_pelanggan, id_desain, judul_proyek, deskripsi_proyek, file_referensi, status_pesanan, tanggal_pesan, deadline)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmtO->bind_param('iissssss', $id_pelanggan, $id_desain, $judul_proyek, $deskripsi_proyek, $file_referensi, $status_pesanan, $tanggal_pesan, $deadline);

        if ($stmtO->execute()) {
                $id_pesanan = $stmtO->insert_id;
                $stmtO->close();

                // Simpan id_pesanan di session untuk halaman pembayaran
                $_SESSION['id_pesanan']  = $id_pesanan;
                $_SESSION['id_desain']   = $id_desain;

                // Redirect ke pembayaran
                redirect('pembayaran.php?id_pesanan=' . $id_pesanan);
            } else {
                $error = 'Terjadi kesalahan saat menyimpan pesanan. Silakan coba lagi.';
                $stmtO->close();
            }
        } // end if id_pelanggan > 0
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Formulir Request — SEKALA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="pages.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
  <div class="container">
    <div class="breadcrumb">
      <a href="index.php">Home</a>
      <span class="sep">›</span>
      <a href="detail_paket.php?id=<?= $paket['id_desain'] ?>">Detail Paket</a>
      <span class="sep">›</span>
      <span class="current">Formulir Request</span>
    </div>
  </div>
</div>

<!-- HERO BANNER -->
<div class="form-hero-banner">
  <div class="container">
    <h1 class="form-hero-title">Formulir Request : [<?= htmlspecialchars($paket['jenis_desain']) ?>]</h1>
    <p class="form-hero-desc">
      Wujudkan ide Anda menjadi karya nyata. Di SEKALA, kami percaya pada komunikasi terstruktur
      untuk hasil kreatif tanpa batas. Mohon lengkapi detail di bawah ini untuk meminimalkan
      miskomunikasi dan mempercepat proses kreatif kami.
    </p>
  </div>
</div>

<!-- FORM SECTION -->
<section class="form-section">
  <div class="container">

    <!-- Error Alert -->
    <?php if ($error): ?>
    <div style="background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5;border-radius:12px;padding:13px 16px;margin-bottom:24px;font-size:14px;">
      ❌ <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="form_request.php?id_desain=<?= $paket['id_desain'] ?>"
          enctype="multipart/form-data">

      <div class="form-section-title">Informasi Bisnis Anda</div>

      <div class="form-two-col">

        <!-- ---- KOLOM KIRI ---- -->
        <div>
          <div class="form-group">
            <label class="form-label">Nama Bussines/Brand <span class="req">*</span></label>
            <input class="form-control" name="brand" type="text"
              placeholder="Contoh: Warung Sari Rasa"
              value="<?= htmlspecialchars($_POST['brand'] ?? '') ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label">Nama Penanggung Jawab <span class="req">*</span></label>
            <input class="form-control" name="pic" type="text"
              placeholder="Nama lengkap Anda"
              value="<?= htmlspecialchars($_POST['pic'] ?? $user['nama'] ?? '') ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label">Email/Whatsapp <span class="req">*</span></label>
            <input class="form-control" name="email_wa" type="text"
              placeholder="email@bisnis.com / 08xxxxxxx"
              value="<?= htmlspecialchars($_POST['email_wa'] ?? getCurrentUser()['email'] ?? '') ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label">Produk/Jasa yang ditawarkan <span class="req">*</span></label>
            <input class="form-control" name="produk" type="text"
              placeholder="Contoh: Makanan & Minuman"
              value="<?= htmlspecialchars($_POST['produk'] ?? '') ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label">Sejak Kapan Bisnis ini Berdiri <span class="req">*</span></label>
            <input class="form-control" name="berdiri" type="text"
              placeholder="Contoh: 2020"
              value="<?= htmlspecialchars($_POST['berdiri'] ?? '') ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label">Unggah Logo (Jika Ada)</label>
            <div class="upload-zone" onclick="document.getElementById('upload-logo').click()">
              <input type="file" id="upload-logo" name="logo" accept="image/jpg,image/jpeg,image/png" style="display:none"
                onchange="showFileName(this,'logo-name')" />
              <div class="upload-icon">🖼</div>
              <div class="upload-label"><span>Click to upload</span> or drag and drop</div>
              <div class="upload-hint" id="logo-name">JPG, JPEG, PNG less than 10MB</div>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Foto Produk/Usaha <span class="req">*</span></label>
            <div class="upload-zone" onclick="document.getElementById('upload-foto').click()">
              <input type="file" id="upload-foto" name="foto" accept="image/jpg,image/jpeg,image/png" style="display:none"
                onchange="showFileName(this,'foto-name')" />
              <div class="upload-icon">🖼</div>
              <div class="upload-label"><span>Click to upload</span> or drag and drop</div>
              <div class="upload-hint" id="foto-name">JPG, JPEG, PNG less than 10MB</div>
            </div>
          </div>
        </div>

        <!-- ---- KOLOM KANAN ---- -->
        <div>
          <div class="form-group">
            <label class="form-label">Alasan Mengerjakan Proyek Ini? <span class="req">*</span></label>
            <select class="form-control" name="alasan" required>
              <option value="">Pilih alasan...</option>
              <option value="Brand Baru"   <?= ($_POST['alasan']??'')==='Brand Baru' ? 'selected':'' ?>>Brand Baru</option>
              <option value="Rebranding"   <?= ($_POST['alasan']??'')==='Rebranding' ? 'selected':'' ?>>Rebranding</option>
              <option value="Promosi Produk" <?= ($_POST['alasan']??'')==='Promosi Produk' ? 'selected':'' ?>>Promosi Produk</option>
              <option value="Event/Acara"  <?= ($_POST['alasan']??'')==='Event/Acara' ? 'selected':'' ?>>Event / Acara</option>
              <option value="Konten Rutin" <?= ($_POST['alasan']??'')==='Konten Rutin' ? 'selected':'' ?>>Konten Rutin</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Pesan Utama (Value) yang Ingin Disampaikan <span class="req">*</span></label>
            <textarea class="form-control" name="pesan_utama" rows="4"
              placeholder="Ceritakan pesan utama yang ingin Anda sampaikan kepada audiens..."
              required><?= htmlspecialchars($_POST['pesan_utama'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Target Audience <span class="req">*</span></label>
            <input class="form-control" name="audience" type="text"
              placeholder="Contoh: Ibu rumah tangga usia 25-40 tahun"
              value="<?= htmlspecialchars($_POST['audience'] ?? '') ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label">Kesan/Vibe yang Diinginkan <span class="req">*</span></label>
            <select class="form-control" name="vibe">
              <option value="">Pilih vibe...</option>
              <option value="Minimalis"   <?= ($_POST['vibe']??'')==='Minimalis' ? 'selected':'' ?>>Minimalis</option>
              <option value="Bold"        <?= ($_POST['vibe']??'')==='Bold' ? 'selected':'' ?>>Bold & Colorful</option>
              <option value="Elegan"      <?= ($_POST['vibe']??'')==='Elegan' ? 'selected':'' ?>>Elegan & Mewah</option>
              <option value="Playful"     <?= ($_POST['vibe']??'')==='Playful' ? 'selected':'' ?>>Playful & Fun</option>
              <option value="Profesional" <?= ($_POST['vibe']??'')==='Profesional' ? 'selected':'' ?>>Profesional</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Warna yang Diinginkan <span class="req">*</span></label>
            <input class="form-control" name="warna" type="text"
              placeholder="Contoh: Biru, Putih, Gold"
              value="<?= htmlspecialchars($_POST['warna'] ?? '') ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label">Pilihan Ukuran &amp; Format <span class="req">*</span></label>
            <input class="form-control" name="ukuran" type="text"
              placeholder="Contoh: 1080x1080px, Square, Story"
              value="<?= htmlspecialchars($_POST['ukuran'] ?? '') ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label">Deadline yang Diharapkan <span class="req">*</span></label>
            <input class="form-control" name="deadline" type="date"
              value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>"
              min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required />
          </div>
        </div>
      </div><!-- end .form-two-col -->

      <div class="form-submit-area">
        <button type="submit" class="btn btn-dark btn-lg" style="min-width:260px;">
          Mulai Request Konten
        </button>
        <p class="form-submit-note">Tim kami akan segera proses ide anda menjadi karya nyata</p>
      </div>

    </form>
  </div>
</section>

<script>
function showFileName(input, targetId) {
  const el = document.getElementById(targetId);
  if (el && input.files.length > 0) {
    el.textContent = '✅ ' + input.files[0].name;
    el.style.color = '#10B981';
  }
}
</script>
<script src="assets/js/main.js"></script>
</body>
</html>
