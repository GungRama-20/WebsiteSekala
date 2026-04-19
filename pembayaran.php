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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_bayar = e($conn, $_POST['nama_bayar'] ?? '');
    $metode     = e($conn, $_POST['metode']      ?? '');

    if (empty($nama_bayar) || empty($metode)) {
        $error = 'Mohon lengkapi nama dan pilih metode pembayaran.';
    } else {
        $bukti = '';
        if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','pdf'])) {
                $filename = 'bukti_' . $id_pesanan . '_' . time() . '.' . $ext;
                if (!is_dir('uploads')) mkdir('uploads', 0755, true);
                move_uploaded_file($_FILES['bukti']['tmp_name'], 'uploads/' . $filename);
                $bukti = 'uploads/' . $filename;
            }
        }

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

$metode_list = ['VISA'=>'VISA','Mastercard'=>'MC','GoPay'=>'GoPay','OVO'=>'OVO','BCA'=>'BCA','Mandiri'=>'Mandiri','PayPal'=>'PayPal','DANA'=>'DANA','LinkAja'=>'LinkAja'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pembayaran — SEKALA</title>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="pages.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="breadcrumb-bar"><div class="container"><div class="breadcrumb">
  <a href="index.php">Home</a><span class="sep">›</span>
  <a href="detail_paket.php?id=<?= $pesanan['id_desain'] ?>">Detail Paket</a><span class="sep">›</span>
  <a href="form_request.php?id_desain=<?= $pesanan['id_desain'] ?>">Request</a><span class="sep">›</span>
  <span class="current">Pembayaran</span>
</div></div></div>

<div class="payment-hero"><div class="container">
  <h1 class="payment-hero-title">Lanjutkan Ke<br>Pembayaran</h1>
</div></div>

<section class="payment-section"><div class="container">
  <?php if ($error): ?>
  <div style="background:#FEE2E2;color:#991B1B;border:1px solid #FCA5A5;border-radius:12px;padding:13px 16px;margin-bottom:24px;font-size:14px;">❌ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="pembayaran.php?id_pesanan=<?= $id_pesanan ?>" enctype="multipart/form-data">
    <div class="payment-inner">
      <div class="payment-left">
        <div class="payment-title">Metode Pembayaran</div>
        <div class="form-group payment-input">
          <input class="form-control" name="nama_bayar" type="text" placeholder="Nama pemegang pembayaran"
            value="<?= htmlspecialchars($_POST['nama_bayar'] ?? $user['nama']) ?>" required />
        </div>
        <div class="form-group payment-input">
          <select class="form-control" name="metode" id="metode-select" required onchange="syncMethod(this.value)">
            <option value="">— Pilih Metode Pembayaran —</option>
            <optgroup label="Transfer Bank">
              <option value="BCA">BCA Transfer</option>
              <option value="Mandiri">Mandiri Transfer</option>
              <option value="BRI">BRI Transfer</option>
              <option value="BNI">BNI Transfer</option>
            </optgroup>
            <optgroup label="E-Wallet">
              <option value="GoPay">GoPay</option>
              <option value="OVO">OVO</option>
              <option value="DANA">DANA</option>
              <option value="LinkAja">LinkAja</option>
            </optgroup>
            <optgroup label="Kartu / Internasional">
              <option value="VISA">VISA</option>
              <option value="Mastercard">Mastercard</option>
              <option value="PayPal">PayPal</option>
            </optgroup>
          </select>
        </div>
        <div class="payment-methods-box"><div class="payment-methods-grid">
          <?php foreach ($metode_list as $val => $label): ?>
          <div class="method-item" data-method="<?= $val ?>" onclick="selectMethod(this)">
            <span class="method-text"><?= $label ?></span>
          </div>
          <?php endforeach; ?>
        </div></div>
        <div class="form-group" style="margin-top:20px;">
          <label class="form-label">Upload Bukti Pembayaran <span class="req">*</span></label>
          <div class="upload-zone" onclick="document.getElementById('bukti-upload').click()">
            <input type="file" id="bukti-upload" name="bukti" accept="image/jpg,image/jpeg,image/png,.pdf" style="display:none" onchange="showFileName(this,'bukti-name')" />
            <div class="upload-icon">📄</div>
            <div class="upload-label"><span>Click to upload</span> atau drag and drop</div>
            <div class="upload-hint" id="bukti-name">JPG, PNG, PDF (maks. 10MB)</div>
          </div>
        </div>
      </div>
      <div class="payment-right"><div class="payment-summary-box">
        <div class="summary-paket-name"><?= htmlspecialchars($pesanan['jenis_desain']) ?></div>
        <div class="summary-paket-type">ID Pesanan: #<?= str_pad($pesanan['id_pesanan'], 6, '0', STR_PAD_LEFT) ?></div>
        <hr class="summary-divider">
        <div class="summary-row"><span>Harga Paket</span><span class="dots"></span><span class="amount"><?= formatRupiah($pesanan['harga_mulai']) ?></span></div>
        <div class="summary-row" style="margin-top:8px;"><span>Estimasi</span><span class="dots"></span><span class="amount"><?= htmlspecialchars($pesanan['estimasi_waktu']) ?></span></div>
        <hr class="summary-divider">
        <div class="summary-total-row"><span>Total Bayar</span><span class="total-amount"><?= formatRupiah($pesanan['harga_mulai']) ?></span></div>
        <div id="payment-info" style="margin-top:16px;padding:14px;background:var(--bg);border-radius:10px;font-size:13px;display:none;">
          <div style="font-weight:700;color:var(--dark);margin-bottom:8px;">📋 Info Transfer:</div>
          <div id="rekening-detail" style="color:var(--gray);line-height:1.8;"></div>
        </div>
        <button type="submit" class="btn-bayar">Bayar Sekarang — <?= formatRupiah($pesanan['harga_mulai']) ?></button>
        <p style="font-size:11px;color:var(--gray-light);text-align:center;margin-top:10px;">🔒 Transaksi aman dan terenkripsi</p>
      </div></div>
    </div>
  </form>
</div></section>

<script>
const rekeningData = {
  BCA:'Bank BCA<br>No Rek: 123-456-7890<br>a.n. SEKALA Creative',
  Mandiri:'Bank Mandiri<br>No Rek: 987-654-3210<br>a.n. SEKALA Creative',
  BRI:'Bank BRI<br>No Rek: 456-789-0123<br>a.n. SEKALA Creative',
  BNI:'Bank BNI<br>No Rek: 321-654-9870<br>a.n. SEKALA Creative',
  GoPay:'GoPay: 0812-3456-7890<br>a.n. SEKALA Creative',
  OVO:'OVO: 0812-3456-7890<br>a.n. SEKALA Creative',
  DANA:'DANA: 0812-3456-7890<br>a.n. SEKALA Creative',
  LinkAja:'LinkAja: 0812-3456-7890<br>a.n. SEKALA Creative',
  VISA:'Pembayaran via VISA Card<br>Hubungi admin untuk proses',
  Mastercard:'Pembayaran via Mastercard<br>Hubungi admin untuk proses',
  PayPal:'PayPal: billing@sekala.id'
};
function selectMethod(el){document.querySelectorAll('.method-item').forEach(m=>m.classList.remove('active'));el.classList.add('active');document.getElementById('metode-select').value=el.dataset.method;showRekeningInfo(el.dataset.method);}
function syncMethod(val){document.querySelectorAll('.method-item').forEach(m=>m.classList.toggle('active',m.dataset.method===val));showRekeningInfo(val);}
function showRekeningInfo(val){const box=document.getElementById('payment-info');const d=document.getElementById('rekening-detail');if(val&&rekeningData[val]){box.style.display='block';d.innerHTML=rekeningData[val];}else{box.style.display='none';}}
function showFileName(input,targetId){const el=document.getElementById(targetId);if(el&&input.files.length>0){el.textContent='✅ '+input.files[0].name;el.style.color='#10B981';}}
</script>
<script src="main.js"></script>
</body>
</html>
