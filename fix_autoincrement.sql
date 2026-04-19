-- ============================================================
-- fix_autoincrement.sql
-- Jalankan di phpMyAdmin untuk memperbaiki semua error DB SEKALA
-- ============================================================

USE sekala;

-- ============================================================
-- 1. SEED tb_desain (WAJIB — ini yang menyebabkan FK error!)
-- ============================================================
INSERT IGNORE INTO `tb_desain` (id_desain, jenis_desain, deskripsi, harga_mulai, estimasi_waktu) VALUES
(1, 'Paket Basic',   'Paket ini dirancang untuk membantu pelaku UMKM mendapatkan desain poster digital dengan proses yang mudah, cepat, dan terstruktur.', 75000,  '1-2 hari'),
(2, 'Paket Starter', 'Cocok untuk bisnis yang butuh konten media sosial rutin. Dapatkan desain berkualitas dengan harga terjangkau.', 150000, '2-3 hari'),
(3, 'Paket Growth',  'Paket terlaris untuk bisnis aktif berkembang. Termasuk konten feed dan flyer promosi.', 300000, '3-4 hari'),
(4, 'Paket Video',   'Solusi konten video pendek untuk Reels, TikTok, dan promosi digital bisnis Anda.', 250000, '4-5 hari');

-- ============================================================
-- 2. HAPUS baris ghost dengan PRIMARY KEY = 0
-- ============================================================
DELETE FROM tb_pelanggan  WHERE id_pelanggan  = 0;
DELETE FROM tb_pesanan    WHERE id_pesanan    = 0;
DELETE FROM tb_pembayaran WHERE id_pembayaran = 0;
DELETE FROM tb_testimoni  WHERE id_testimoni  = 0;

-- ============================================================
-- 3. RESET AUTO_INCREMENT ke nilai yang benar (minimal 1)
-- ============================================================
ALTER TABLE tb_pelanggan  AUTO_INCREMENT = 1;
ALTER TABLE tb_pesanan    AUTO_INCREMENT = 1;
ALTER TABLE tb_pembayaran AUTO_INCREMENT = 1;
ALTER TABLE tb_testimoni  AUTO_INCREMENT = 1;

-- ============================================================
-- 4. VERIFIKASI
-- ============================================================
SELECT 'Semua tabel berhasil diperbaiki!' AS status;

SELECT table_name, auto_increment
FROM information_schema.tables
WHERE table_schema = 'sekala'
  AND table_name IN ('tb_pelanggan','tb_pesanan','tb_pembayaran','tb_testimoni','tb_desain');

SELECT id_desain, jenis_desain, harga_mulai FROM tb_desain ORDER BY id_desain;
