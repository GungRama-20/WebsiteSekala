-- ============================================================
-- fix_testimoni.sql
-- Jalankan di phpMyAdmin → database: sekala
-- Memperbaiki struktur tb_testimoni agar testimoni bisa masuk
-- ============================================================

USE sekala;

-- 1. Pastikan kolom id_pesanan ada (nullable agar tidak wajib)
ALTER TABLE `tb_testimoni`
  ADD COLUMN IF NOT EXISTS `id_pesanan` INT NULL AFTER `id_pelanggan`;

-- 2. Pastikan kolom tampil ada dengan default 1
ALTER TABLE `tb_testimoni`
  ADD COLUMN IF NOT EXISTS `tampil` TINYINT(1) NOT NULL DEFAULT 1 AFTER `tanggal`;

-- 3. Hapus FK constraint jika ada (agar id_pelanggan=0 tidak error)
-- Cek nama constraint dulu:
SELECT CONSTRAINT_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'sekala'
  AND TABLE_NAME = 'tb_testimoni'
  AND COLUMN_NAME = 'id_pelanggan'
  AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Jika ada FK, jalankan:
-- ALTER TABLE `tb_testimoni` DROP FOREIGN KEY nama_constraint_di_atas;

-- 4. Update semua testimoni lama agar tampil = 1
UPDATE `tb_testimoni` SET tampil = 1 WHERE tampil IS NULL OR tampil = 0;

-- 5. Verifikasi data
SELECT t.id_testimoni, p.nama, t.rating, t.tampil, t.tanggal
FROM tb_testimoni t
LEFT JOIN tb_pelanggan p ON t.id_pelanggan = p.id_pelanggan
ORDER BY t.tanggal DESC;

-- ============================================================
-- SELESAI ✅
-- ============================================================
