-- ============================================================
-- seed_testimoni.sql
-- Masukkan data testimoni sample ke database
-- Jalankan di phpMyAdmin → database: sekala
-- ============================================================

USE sekala;

-- ============================================================
-- LANGKAH 1: Pastikan kolom id_pelanggan bisa NULL
-- (agar sample testimoni tidak butuh pelanggan nyata)
-- ============================================================
ALTER TABLE `tb_testimoni`
  MODIFY COLUMN `id_pelanggan` INT NULL;

-- ============================================================
-- LANGKAH 2: Pastikan kolom id_pesanan ada (nullable)
-- ============================================================
ALTER TABLE `tb_testimoni`
  ADD COLUMN IF NOT EXISTS `id_pesanan` INT NULL AFTER `id_pelanggan`;

-- ============================================================
-- LANGKAH 3: Pastikan kolom tampil ada
-- ============================================================
ALTER TABLE `tb_testimoni`
  ADD COLUMN IF NOT EXISTS `tampil` TINYINT(1) NOT NULL DEFAULT 1 AFTER `tanggal`;

-- ============================================================
-- LANGKAH 4: Insert pelanggan sample (jika belum ada)
-- ============================================================
INSERT IGNORE INTO `tb_pelanggan` (id_pelanggan, nama, email, no_hp, alamat) VALUES
(10, 'Made Ari',     'made@example.com',    '08123456789', 'Denpasar, Bali'),
(11, 'Komang Dewi',  'komang@example.com',  '08198765432', 'Ubud, Bali'),
(12, 'Wayan Putra',  'wayan@example.com',   '08111222333', 'Kuta, Bali'),
(13, 'Nyoman Sari',  'nyoman@example.com',  '08144555666', 'Tabanan, Bali'),
(14, 'Ketut Wijaya', 'ketut@example.com',   '08177888999', 'Singaraja, Bali'),
(15, 'Putu Indriani','putu@example.com',    '08166777888', 'Gianyar, Bali');

-- ============================================================
-- LANGKAH 5: Insert testimoni sample (hanya jika tabel kosong)
-- ============================================================
INSERT INTO `tb_testimoni` (id_pelanggan, isi_testimoni, rating, tanggal, tampil)
SELECT * FROM (
  SELECT 10, 'SEKALA sangat membantu bisnis kami membuat konten Instagram. Prosesnya mudah dan desainnya cepat selesai.', 5, DATE_SUB(NOW(), INTERVAL 10 DAY), 1
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tb_testimoni LIMIT 1);

INSERT INTO `tb_testimoni` (id_pelanggan, isi_testimoni, rating, tanggal, tampil)
SELECT * FROM (
  SELECT 11, 'Dengan SEKALA saya tinggal kirim request dan hasil desainnya sudah siap dipakai untuk promosi. Sangat praktis.', 5, DATE_SUB(NOW(), INTERVAL 8 DAY), 1
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tb_testimoni WHERE id_pelanggan = 11 LIMIT 1);

INSERT INTO `tb_testimoni` (id_pelanggan, isi_testimoni, rating, tanggal, tampil)
SELECT * FROM (
  SELECT 12, 'Pelayanan di SEKALA sangat profesional. Revisi mudah karena semua tercatat di sistem.', 5, DATE_SUB(NOW(), INTERVAL 6 DAY), 1
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tb_testimoni WHERE id_pelanggan = 12 LIMIT 1);

INSERT INTO `tb_testimoni` (id_pelanggan, isi_testimoni, rating, tanggal, tampil)
SELECT * FROM (
  SELECT 13, 'Konten yang dibuat SEKALA sangat berkualitas dan sesuai dengan kebutuhan bisnis saya. Recommended!', 5, DATE_SUB(NOW(), INTERVAL 5 DAY), 1
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tb_testimoni WHERE id_pelanggan = 13 LIMIT 1);

INSERT INTO `tb_testimoni` (id_pelanggan, isi_testimoni, rating, tanggal, tampil)
SELECT * FROM (
  SELECT 14, 'Proses request sangat mudah dan hasilnya memuaskan. Tim SEKALA sangat responsif dan profesional.', 5, DATE_SUB(NOW(), INTERVAL 3 DAY), 1
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tb_testimoni WHERE id_pelanggan = 14 LIMIT 1);

INSERT INTO `tb_testimoni` (id_pelanggan, isi_testimoni, rating, tanggal, tampil)
SELECT * FROM (
  SELECT 15, 'Sangat puas dengan layanan SEKALA. Desain poster yang dihasilkan melebihi ekspektasi saya.', 5, DATE_SUB(NOW(), INTERVAL 1 DAY), 1
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tb_testimoni WHERE id_pelanggan = 15 LIMIT 1);

-- ============================================================
-- VERIFIKASI — Cek hasil
-- ============================================================
SELECT
  t.id_testimoni,
  COALESCE(p.nama, '(Tanpa Pelanggan)') AS nama_pelanggan,
  t.rating,
  LEFT(t.isi_testimoni, 50) AS preview,
  t.tampil,
  t.tanggal
FROM tb_testimoni t
LEFT JOIN tb_pelanggan p ON t.id_pelanggan = p.id_pelanggan
ORDER BY t.tanggal DESC;

SELECT CONCAT('Total testimoni: ', COUNT(*)) AS info FROM tb_testimoni;

-- ============================================================
-- SELESAI ✅
-- ============================================================
