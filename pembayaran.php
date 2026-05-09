<?php
require_once 'config.php';
require_once 'auth.php';

requireLogin('pembayaran.php' . (isset($_GET['id_pesanan']) ? '?id_pesanan='.(int)$_GET['id_pesanan'] : ''));

$user       = getCurrentUser();
$id_pesanan = isset($_GET['id_pesanan']) ? (int)$_GET['id_pesanan'] : (int)($_SESSION['id_pesanan'] ?? 0);

if ($id_pesanan <= 0) redirect('index.php');

$stmt = $conn->prepare(
    "SELECT o.id_pesanan, o.judul_proyek, o.status_pesanan, o.deadline,
            d.jenis_desain, d.harga_mulai, d.estimasi_waktu, d.id_desain
     FROM tb_pesanan o
     JOIN tb_desain d ON o.id_desain = d.id_desain
     WHERE o.id_pesanan = ? LIMIT 1"
);
$stmt->bind_param('i', $id_pesanan);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pesanan) { setFlash('Pesanan tidak ditemukan.', 'error'); redirect('index.php'); }

// Cek apakah pembayaran sudah ada
$stmtCek = $conn->prepare("SELECT id_pembayaran, status_pembayaran FROM tb_pembayaran WHERE id_pesanan = ? ORDER BY id_pembayaran DESC LIMIT 1");
$stmtCek->bind_param('i', $id_pesanan);
$stmtCek->execute();
$cekBayar = $stmtCek->get_result()->fetch_assoc();
$stmtCek->close();

if ($cekBayar && $cekBayar['status_pembayaran'] !== 'ditolak') {
    setFlash('Pesanan ini sudah memiliki data pembayaran ('.$cekBayar['status_pembayaran'].').', 'info');
    redirect('halaman_pesanan_saya.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_bayar = e($conn, $_POST['nama_bayar'] ?? '');
    $metode     = 'QRIS'; // Selalu QRIS

    if (empty($nama_bayar)) {
        $error = 'Mohon masukkan nama pemegang pembayaran.';
    } else {
        $bukti = '';
        if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','pdf'])) {
                $filename = 'bukti_' . $id_pesanan . '_' . time() . '.' . $ext;
                if (!is_dir('uploads')) mkdir('uploads', 0755, true);
                move_uploaded_file($_FILES['bukti']['tmp_name'], 'uploads/' . $filename);
                $bukti = 'uploads/' . $filename;
            } else {
                $error = 'Format file tidak didukung. Gunakan JPG, PNG, atau PDF.';
            }
        } else {
            $error = 'Bukti pembayaran wajib diupload.';
        }

        if (empty($error)) {
            $jumlah_bayar  = $pesanan['harga_mulai'];
            $status_bayar  = 'menunggu';
            $tanggal_bayar = date('Y-m-d H:i:s');

            $stmtBayar = $conn->prepare(
                "INSERT INTO tb_pembayaran (id_pesanan, metode_pembayaran, jumlah_bayar, bukti_pembayaran, status_pembayaran, tanggal_bayar)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmtBayar->bind_param('isisss', $id_pesanan, $metode, $jumlah_bayar, $bukti, $status_bayar, $tanggal_bayar);

            if ($stmtBayar->execute()) {
                $id_pembayaran = $stmtBayar->insert_id;
                $stmtBayar->close();

                $stmtUpd = $conn->prepare("UPDATE tb_pesanan SET status_pesanan = 'proses' WHERE id_pesanan = ?");
                $stmtUpd->bind_param('i', $id_pesanan);
                $stmtUpd->execute();
                $stmtUpd->close();

                $_SESSION['last_id_pesanan']    = $id_pesanan;
                $_SESSION['last_id_pembayaran'] = $id_pembayaran;
                $_SESSION['last_metode']        = $metode;
                $_SESSION['last_jumlah']        = $jumlah_bayar;
                $_SESSION['last_paket']         = $pesanan['jenis_desain'];
                $_SESSION['last_estimasi']      = $pesanan['estimasi_waktu'];

                redirect('selesai.php');
            } else {
                $error = 'Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.';
                $stmtBayar->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pembayaran QRIS — SEKALA</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="pages.css">
  <style>
    /* ====== QRIS Payment Page Styles ====== */
    .payment-hero {
      background: linear-gradient(135deg, #0F1B2D 0%, #1C4E8C 60%, #E53935 100%);
      padding: 60px 0 50px;
      text-align: center;
      color: #fff;
    }
    .payment-hero-title {
      font-family: 'Sora', sans-serif;
      font-size: 2.4rem;
      font-weight: 800;
      line-height: 1.2;
      margin-bottom: 12px;
    }
    .payment-hero-sub {
      color: rgba(255,255,255,0.7);
      font-size: 1rem;
    }

    .payment-section { padding: 50px 0 80px; background: #F1F5F9; }

    /* Layout grid */
    .qris-layout {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      max-width: 1000px;
      margin: 0 auto;
      align-items: start;
    }
    @media (max-width: 768px) {
      .qris-layout { grid-template-columns: 1fr; }
    }

    /* ---- QRIS Card ---- */
    .qris-card {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.10);
      overflow: hidden;
    }
    .qris-card-header {
      background: linear-gradient(135deg, #E53935, #B71C1C);
      padding: 20px 24px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .qris-badge-logo {
      background: #fff;
      border-radius: 10px;
      padding: 6px 12px;
      font-weight: 800;
      font-size: 1rem;
      color: #E53935;
      letter-spacing: 1px;
    }
    .qris-card-header-title {
      color: #fff;
      font-weight: 700;
      font-size: 1rem;
      line-height: 1.3;
    }
    .qris-card-header-sub {
      color: rgba(255,255,255,0.75);
      font-size: 0.8rem;
    }
    .qris-body {
      padding: 28px;
      text-align: center;
    }
    .qris-merchant-name {
      font-family: 'Sora', sans-serif;
      font-size: 1.2rem;
      font-weight: 700;
      color: #0F1B2D;
      margin-bottom: 4px;
    }
    .qris-merchant-id {
      font-size: 0.8rem;
      color: #64748B;
      margin-bottom: 20px;
    }
    .qris-image-wrap {
      position: relative;
      display: inline-block;
      border: 3px solid #E53935;
      border-radius: 16px;
      padding: 8px;
      background: #fff;
      box-shadow: 0 4px 20px rgba(229,57,53,0.15);
    }
    .qris-image-wrap img {
      width: 100%;
      max-width: 280px;
      display: block;
      border-radius: 10px;
    }
    .qris-scan-label {
      margin-top: 16px;
      font-size: 0.85rem;
      font-weight: 700;
      color: #E53935;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .qris-steps {
      display: flex;
      justify-content: center;
      gap: 16px;
      margin-top: 20px;
      flex-wrap: wrap;
    }
    .qris-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      font-size: 0.78rem;
      color: #334155;
      font-weight: 600;
      max-width: 80px;
      text-align: center;
    }
    .qris-step-icon {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: linear-gradient(135deg, #FEF3C7, #FDE68A);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .qris-footer-note {
      margin-top: 20px;
      padding: 12px 16px;
      background: #FFF7ED;
      border-radius: 10px;
      border-left: 3px solid #F59E0B;
      font-size: 0.8rem;
      color: #92400E;
      text-align: left;
    }

    /* ---- Form Card ---- */
    .form-card {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 8px 40px rgba(0,0,0,0.10);
      overflow: hidden;
    }
    .form-card-header {
      background: linear-gradient(135deg, #0F1B2D, #1C4E8C);
      padding: 20px 24px;
    }
    .form-card-header-title {
      color: #fff;
      font-weight: 700;
      font-size: 1rem;
    }
    .form-card-header-sub {
      color: rgba(255,255,255,0.65);
      font-size: 0.8rem;
    }
    .form-card-body { padding: 28px; }

    /* Order Summary */
    .order-summary {
      background: #F8FAFC;
      border-radius: 12px;
      padding: 18px;
      margin-bottom: 24px;
      border: 1px solid #E2E8F0;
    }
    .order-summary-title {
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #64748B;
      margin-bottom: 12px;
    }
    .order-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.9rem;
      padding: 4px 0;
      color: #334155;
    }
    .order-row-label { color: #64748B; }
    .order-row-total {
      border-top: 1px dashed #CBD5E1;
      margin-top: 10px;
      padding-top: 10px;
      font-weight: 800;
      font-size: 1.1rem;
      color: #0F1B2D;
    }
    .order-row-total .total-val { color: #E53935; }

    /* QRIS Already Selected badge */
    .qris-selected-badge {
      display: flex;
      align-items: center;
      gap: 10px;
      background: linear-gradient(135deg, #FFF1F0, #FFE4E1);
      border: 1.5px solid #FECACA;
      border-radius: 12px;
      padding: 14px 18px;
      margin-bottom: 20px;
    }
    .qris-selected-icon { font-size: 1.8rem; }
    .qris-selected-text { font-weight: 700; color: #B91C1C; font-size: 0.95rem; }
    .qris-selected-sub { font-size: 0.8rem; color: #64748B; margin-top: 2px; }

    /* Form controls */
    .fgroup { margin-bottom: 18px; }
    .flabel {
      display: block;
      font-size: 0.85rem;
      font-weight: 700;
      color: #334155;
      margin-bottom: 7px;
    }
    .flabel .req { color: #E53935; margin-left: 3px; }
    .finput {
      width: 100%;
      padding: 12px 16px;
      border: 1.5px solid #E2E8F0;
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.95rem;
      color: #0F1B2D;
      background: #F8FAFC;
      transition: border-color 0.2s, box-shadow 0.2s;
      box-sizing: border-box;
    }
    .finput:focus {
      outline: none;
      border-color: #E53935;
      box-shadow: 0 0 0 3px rgba(229,57,53,0.1);
      background: #fff;
    }

    /* Upload zone */
    .upload-zone {
      border: 2px dashed #CBD5E1;
      border-radius: 12px;
      padding: 28px 20px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
      background: #F8FAFC;
    }
    .upload-zone:hover {
      border-color: #E53935;
      background: #FFF5F5;
    }
    .upload-zone.has-file {
      border-color: #10B981;
      background: #F0FDF4;
    }
    .upload-icon { font-size: 2rem; margin-bottom: 8px; }
    .upload-label { font-size: 0.9rem; color: #334155; font-weight: 600; }
    .upload-label span { color: #E53935; text-decoration: underline; }
    .upload-hint { font-size: 0.78rem; color: #94A3B8; margin-top: 4px; }

    /* Submit button */
    .btn-submit-qris {
      width: 100%;
      padding: 15px;
      background: linear-gradient(135deg, #E53935, #B71C1C);
      color: #fff;
      border: none;
      border-radius: 12px;
      font-family: inherit;
      font-size: 1rem;
      font-weight: 800;
      cursor: pointer;
      transition: all 0.2s;
      letter-spacing: 0.5px;
      margin-top: 8px;
    }
    .btn-submit-qris:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(229,57,53,0.35);
    }
    .btn-submit-qris:active { transform: translateY(0); }
    .secure-note {
      text-align: center;
      font-size: 0.78rem;
      color: #94A3B8;
      margin-top: 12px;
    }

    /* Error banner */
    .error-banner {
      background: #FEE2E2;
      color: #991B1B;
      border: 1px solid #FCA5A5;
      border-radius: 12px;
      padding: 13px 16px;
      margin-bottom: 20px;
      font-size: 14px;
      font-weight: 600;
    }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="breadcrumb-bar"><div class="container"><div class="breadcrumb">
  <a href="index.php">Home</a><span class="sep">›</span>
  <a href="detail_paket.php?id=<?= $pesanan['id_desain'] ?>">Detail Paket</a><span class="sep">›</span>
  <a href="form_request.php?id_desain=<?= $pesanan['id_desain'] ?>">Request</a><span class="sep">›</span>
  <span class="current">Pembayaran</span>
</div></div></div>

<div class="payment-hero">
  <div class="container">
    <h1 class="payment-hero-title">Pembayaran via QRIS</h1>
    <p class="payment-hero-sub">Scan QR Code di bawah menggunakan aplikasi dompet digital favorit Anda</p>
  </div>
</div>

<section class="payment-section">
  <div class="container">
    <?php if ($error): ?>
    <div class="error-banner" style="max-width:1000px;margin:0 auto 24px;">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="pembayaran.php?id_pesanan=<?= $id_pesanan ?>" enctype="multipart/form-data">
      <!-- QRIS metode tersembunyi -->
      <input type="hidden" name="metode" value="QRIS">

      <div class="qris-layout">

        <!-- Kolom Kiri: QR Code QRIS -->
        <div class="qris-card">
          <div class="qris-card-header">
            <div class="qris-badge-logo">QRIS</div>
            <div>
              <div class="qris-card-header-title">QR Code Standar Nasional</div>
              <div class="qris-card-header-sub">Berlaku untuk semua aplikasi berlogo QRIS</div>
            </div>
          </div>
          <div class="qris-body">
            <div class="qris-merchant-name">SEKALA DESAIN</div>
            <div class="qris-merchant-id">NMID: ID1026517275388 · A01</div>

            <div class="qris-image-wrap">
              <img src="assets/QrisSekala.jpg" alt="QRIS SEKALA DESAIN" />
            </div>

            <div class="qris-scan-label">📱 Scan & Bayar</div>

            <div class="qris-steps">
              <div class="qris-step">
                <div class="qris-step-icon">📱</div>
                Buka Aplikasi
              </div>
              <div class="qris-step">
                <div class="qris-step-icon">📷</div>
                Scan QR Code
              </div>
              <div class="qris-step">
                <div class="qris-step-icon">✅</div>
                Konfirmasi Bayar
              </div>
              <div class="qris-step">
                <div class="qris-step-icon">📸</div>
                Upload Bukti
              </div>
            </div>

            <div class="qris-footer-note">
              ⚠️ <b>Penting:</b> QRIS ini berlaku untuk semua aplikasi berlogo QRIS seperti GoPay, OVO, DANA, ShopeePay, LinkAja, m-Banking, dan lainnya.
            </div>
          </div>
        </div>

        <!-- Kolom Kanan: Form Konfirmasi -->
        <div class="form-card">
          <div class="form-card-header">
            <div class="form-card-header-title">📋 Konfirmasi Pembayaran</div>
            <div class="form-card-header-sub">Lengkapi data setelah melakukan pembayaran</div>
          </div>
          <div class="form-card-body">

            <!-- Ringkasan Pesanan -->
            <div class="order-summary">
              <div class="order-summary-title">🧾 Ringkasan Pesanan</div>
              <div class="order-row">
                <span class="order-row-label">Paket</span>
                <span style="font-weight:600;"><?= htmlspecialchars($pesanan['jenis_desain']) ?></span>
              </div>
              <div class="order-row">
                <span class="order-row-label">ID Pesanan</span>
                <span>#<?= str_pad($pesanan['id_pesanan'], 6, '0', STR_PAD_LEFT) ?></span>
              </div>
              <div class="order-row">
                <span class="order-row-label">Estimasi</span>
                <span><?= htmlspecialchars($pesanan['estimasi_waktu']) ?></span>
              </div>
              <div class="order-row order-row-total">
                <span>Total Bayar</span>
                <span class="total-val"><?= formatRupiah($pesanan['harga_mulai']) ?></span>
              </div>
            </div>

            <!-- Metode terpilih (QRIS badge) -->
            <div class="qris-selected-badge">
              <div class="qris-selected-icon">🔴</div>
              <div>
                <div class="qris-selected-text">Metode: QRIS</div>
                <div class="qris-selected-sub">Scan QR di sebelah kiri untuk membayar</div>
              </div>
            </div>

            <!-- Nama pembayar -->
            <div class="fgroup">
              <label class="flabel" for="nama_bayar">Nama Pemegang Pembayaran <span class="req">*</span></label>
              <input
                id="nama_bayar"
                class="finput"
                name="nama_bayar"
                type="text"
                placeholder="Contoh: Budi Santoso"
                value="<?= htmlspecialchars($_POST['nama_bayar'] ?? $user['nama']) ?>"
                required
              />
            </div>

            <!-- Upload Bukti -->
            <div class="fgroup">
              <label class="flabel">Screenshot Bukti Pembayaran <span class="req">*</span></label>
              <div class="upload-zone" id="upload-zone" onclick="document.getElementById('bukti-upload').click()">
                <input
                  type="file"
                  id="bukti-upload"
                  name="bukti"
                  accept="image/jpg,image/jpeg,image/png,.pdf"
                  style="display:none"
                  onchange="handleFileSelect(this)"
                />
                <div class="upload-icon" id="upload-icon">📸</div>
                <div class="upload-label"><span>Klik untuk upload</span> atau drag & drop</div>
                <div class="upload-hint" id="bukti-name">JPG, PNG, PDF · Maks. 10MB</div>
              </div>
            </div>

            <button type="submit" class="btn-submit-qris" id="btn-bayar">
              ✅ Konfirmasi Pembayaran — <?= formatRupiah($pesanan['harga_mulai']) ?>
            </button>
            <div class="secure-note">🔒 Pembayaran Anda aman dan diverifikasi oleh tim SEKALA</div>

          </div>
        </div>

      </div><!-- /.qris-layout -->
    </form>
  </div>
</section>

<script>
function handleFileSelect(input) {
  const zone = document.getElementById('upload-zone');
  const hint = document.getElementById('bukti-name');
  const icon = document.getElementById('upload-icon');
  if (input.files && input.files.length > 0) {
    const f = input.files[0];
    hint.textContent = '✅ ' + f.name;
    hint.style.color = '#10B981';
    hint.style.fontWeight = '700';
    icon.textContent = '📎';
    zone.classList.add('has-file');
  }
}

// Drag & drop support
const zone = document.getElementById('upload-zone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.borderColor='#E53935'; });
zone.addEventListener('dragleave', () => { zone.style.borderColor=''; });
zone.addEventListener('drop', e => {
  e.preventDefault();
  zone.style.borderColor = '';
  const files = e.dataTransfer.files;
  if (files.length > 0) {
    const input = document.getElementById('bukti-upload');
    const dt = new DataTransfer();
    dt.items.add(files[0]);
    input.files = dt.files;
    handleFileSelect(input);
  }
});
</script>
<script src="main.js"></script>
</body>
</html>
