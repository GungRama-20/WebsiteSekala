-- ============================================================
-- update_porto_testi.sql
-- Seed data portofolio dengan gambar poster nyata
-- Jalankan di phpMyAdmin atau terminal MySQL
-- ============================================================

USE sekala;

-- ============================================================
-- 1. SEED DATA PORTOFOLIO (dengan path ke file poster di assets)
-- ============================================================
INSERT IGNORE INTO `tb_portofolio` (id_portofolio, judul, kategori, deskripsi, gambar) VALUES
(1, 'Desain Poster Premium', 'Poster & Visual', 'Contoh poster premium untuk promosi bisnis digital.', 'assets/poster1.jpeg'),
(2, 'Konten Social Media Feed', 'Social Media Feed', 'Desain konten menarik untuk feed Instagram dan Facebook.', 'assets/poster2.jpeg'),
(3, 'Event & Promosi Campaign', 'Poster & Visual', 'Desain promosi event dan campaign bisnis lokal.', 'assets/poster3.jpeg'),
(4, 'Campaign Digital Marketing', 'Social Media Feed', 'Konten digital marketing untuk meningkatkan engagement.', 'assets/poster4.jpeg'),
(5, 'Branding Bisnis UMKM', 'Branding', 'Visual branding profesional untuk UMKM Bali.', 'assets/poster5.jpeg');

-- ============================================================
-- 2. PASTIKAN KOLOM tampil ADA DI tb_testimoni
-- ============================================================
ALTER TABLE `tb_testimoni`
  ADD COLUMN IF NOT EXISTS `tampil` TINYINT(1) DEFAULT 1 AFTER `tanggal`;

-- ============================================================
-- 3. SET semua testimoni lama agar tampil = 1
-- ============================================================
UPDATE `tb_testimoni` SET tampil = 1 WHERE tampil IS NULL OR tampil = 0;

-- ============================================================
-- 4. PASTIKAN DATA PAKET ADA (UPSERT)
-- ============================================================
INSERT INTO `tb_desain` (id_desain, jenis_desain, deskripsi, harga_mulai, estimasi_waktu) VALUES
(1, 'Paket Basic',   'Paket ini dirancang untuk membantu pelaku UMKM mendapatkan desain poster digital dengan proses yang mudah, cepat, dan terstruktur. Seluruh permintaan dilakukan melalui platform sehingga lebih praktis tanpa perlu memahami proses desain secara teknis.', 75000,  '1-2 hari'),
(2, 'Paket Starter', 'Cocok untuk bisnis yang butuh konten media sosial rutin. Dapatkan desain berkualitas dengan harga terjangkau dan proses yang terstruktur.', 150000, '2-3 hari'),
(3, 'Paket Growth',  'Paket terlaris untuk bisnis aktif berkembang. Termasuk konten feed dan flyer promosi untuk mendukung pertumbuhan bisnis Anda.', 300000, '3-4 hari'),
(4, 'Paket Video',   'Solusi konten video pendek untuk Reels, TikTok, dan promosi digital bisnis Anda dengan kualitas profesional dan sentuhan kreatif.', 250000, '4-5 hari')
ON DUPLICATE KEY UPDATE
  jenis_desain   = VALUES(jenis_desain),
  deskripsi      = VALUES(deskripsi),
  harga_mulai    = VALUES(harga_mulai),
  estimasi_waktu = VALUES(estimasi_waktu);

-- ============================================================
-- SELESAI ✅
-- ============================================================
SELECT 'Update portofolio dan testimoni berhasil!' AS info;
SELECT COUNT(*) AS total_porto FROM tb_portofolio;
SELECT COUNT(*) AS total_testi FROM tb_testimoni;
