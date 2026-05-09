<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();

$id_pesanan  = $_SESSION['last_id_pesanan']    ?? 0;
$metode      = $_SESSION['last_metode']        ?? '-';
$jumlah      = $_SESSION['last_jumlah']        ?? 0;
$paket_nama  = $_SESSION['last_paket']         ?? 'Paket SEKALA';
$estimasi    = $_SESSION['last_estimasi']      ?? '2-3 hari';
$order_id    = 'SKL-' . str_pad($id_pesanan, 6, '0', STR_PAD_LEFT);

// Cek apakah user sudah punya id_pelanggan
$id_pelanggan = 0;
if (isLoggedIn()) {
    $uEmail = getCurrentUser()['email'];
    $stP = $conn->prepare("SELECT id_pelanggan FROM tb_pelanggan WHERE email = ? LIMIT 1");
    $stP->bind_param('s', $uEmail);
    $stP->execute();
    $rP = $stP->get_result()->fetch_assoc();
    $stP->close();
    if ($rP) $id_pelanggan = $rP['id_pelanggan'];
}

// Cek apakah sudah ada testimoni untuk pesanan ini
$already_review = false;
if ($id_pelanggan && $id_pesanan) {
    $stR = $conn->prepare("SELECT id_testimoni FROM tb_testimoni WHERE id_pelanggan = ? AND id_pesanan = ? LIMIT 1");
    if ($stR) { // kolom id_pesanan mungkin belum ada, fallback
        $stR->bind_param('ii', $id_pelanggan, $id_pesanan);
        $stR->execute();
        $already_review = $stR->get_result()->num_rows > 0;
        $stR->close();
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pembayaran Berhasil — SEKALA</title>
  <link rel="icon" type="image/png" href="assets/Logo3.png" style="border-radius: 50%; width: 32px; height: 32px;">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="pages.css">
  <style>
    .testi-form-box{background:#fff;border:1.5px solid #E2E8F0;border-radius:20px;padding:32px;margin-top:24px;max-width:560px;margin-left:auto;margin-right:auto;}
    .testi-form-box h3{font-family:'Sora',sans-serif;font-size:1.1rem;font-weight:700;color:#0F1B2D;margin-bottom:4px;}
    .testi-form-box p{font-size:13px;color:#64748B;margin-bottom:20px;}
    .star-row{display:flex;gap:6px;margin-bottom:16px;}
    .star-btn{font-size:28px;background:none;border:none;cursor:pointer;color:#CBD5E1;transition:color .15s;}
    .star-btn.active,.star-btn:hover{color:#F59E0B;}
    .testi-form-box textarea{width:100%;padding:12px 16px;border:1.5px solid #E2E8F0;border-radius:12px;font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;resize:vertical;min-height:90px;outline:none;}
    .testi-form-box textarea:focus{border-color:#2563EB;box-shadow:0 0 0 3px rgba(37,99,235,.1);}
    .btn-testi{margin-top:14px;width:100%;padding:13px;border-radius:999px;background:#0F1B2D;color:#fff;font-weight:700;font-size:14px;border:none;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;transition:all .25s;}
    .btn-testi:hover{background:#1E293B;transform:translateY(-1px);}
    .skip-link{display:block;text-align:center;margin-top:12px;font-size:13px;color:#94A3B8;text-decoration:none;}
    .skip-link:hover{color:#64748B;}
    .flash-box{padding:12px 16px;border-radius:10px;font-size:14px;margin-bottom:16px;}
    .flash-success{background:#D1FAE5;color:#065F46;border:1px solid #6EE7B7;}
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="done-page">
  <div class="done-card">
    <div class="done-icon-wrap">✅</div>
    <h2 class="done-title">Pembayaran Berhasil!</h2>
    <p class="done-sub">Terima kasih telah memesan layanan SEKALA.<br>Tim kami akan segera memproses pesanan Anda.</p>
    <div class="done-order-id">Order ID: <?= htmlspecialchars($order_id) ?></div>

    <div class="done-info-box">
      <div class="done-info-row"><span class="done-info-label">Paket</span><span class="done-info-val"><?= htmlspecialchars($paket_nama) ?></span></div>
      <div class="done-info-row"><span class="done-info-label">Total Bayar</span><span class="done-info-val"><?= formatRupiah($jumlah) ?></span></div>
      <div class="done-info-row"><span class="done-info-label">Metode</span><span class="done-info-val"><?= htmlspecialchars($metode) ?></span></div>
      <div class="done-info-row"><span class="done-info-label">Estimasi</span><span class="done-info-val"><?= htmlspecialchars($estimasi) ?></span></div>
      <div class="done-info-row"><span class="done-info-label">Status</span><span class="done-info-val" style="color:#F59E0B;">⏳ Menunggu Verifikasi</span></div>
    </div>

    <div class="done-actions">
      <a class="btn btn-primary btn-lg" href="index.php">🏠 Kembali ke Dashboard</a>
      <a class="btn btn-outline btn-lg" href="index.php#section-portofolio">🎨 Lihat Portofolio</a>
    </div>
  </div>

  <!-- Form Testimoni -->
  <?php if (!$already_review && $id_pelanggan): ?>
  <div class="testi-form-box">
    <?php if ($flash && $flash['type'] === 'success'): ?>
    <div class="flash-box flash-success">✅ <?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <h3>⭐ Bagikan Pengalaman Anda</h3>
    <p>Ceritakan pengalaman Anda menggunakan layanan SEKALA. Testimoni Anda sangat berarti!</p>

    <form method="POST" action="testimoni_submit.php">
      <input type="hidden" name="id_pelanggan" value="<?= $id_pelanggan ?>">
      <input type="hidden" name="id_pesanan"   value="<?= $id_pesanan ?>">
      <input type="hidden" name="redirect_to"  value="selesai.php">
      <input type="hidden" name="rating"       value="5" id="rating-hidden">

      <div class="star-row" id="star-row">
        <?php for ($s = 1; $s <= 5; $s++): ?>
        <button type="button" class="star-btn <?= $s <= 5 ? 'active' : '' ?>" data-val="<?= $s ?>" onclick="setRating(<?= $s ?>)">★</button>
        <?php endfor; ?>
      </div>

      <textarea name="isi_testimoni" placeholder="Tulis pengalaman Anda di sini..." required></textarea>

      <button type="submit" class="btn-testi">Kirim Testimoni</button>
    </form>
    <a href="index.php" class="skip-link">Lewati, kembali ke beranda →</a>
  </div>
  <?php else: ?>
  <div style="text-align:center;margin-top:16px;">
    <a href="index.php" style="font-size:13px;color:#94A3B8;text-decoration:none;">← Kembali ke beranda</a>
  </div>
  <?php endif; ?>
</div>

<script>
function setRating(val){
  document.getElementById('rating-hidden').value = val;
  document.querySelectorAll('.star-btn').forEach((b,i)=>{
    b.classList.toggle('active', i < val);
  });
}
</script>
</body>
</html>
