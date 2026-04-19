-- ============================================================
-- sekala_fixed.sql
-- Update database SEKALA — jalankan di phpMyAdmin
-- ============================================================

USE sekala;

-- ============================================================
-- 1. PASTIKAN SEMUA TABEL ADA
-- ============================================================

CREATE TABLE IF NOT EXISTS `tb_user` (
  `id_user`    INT AUTO_INCREMENT PRIMARY KEY,
  `nama`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('Admin','pelanggan') NOT NULL DEFAULT 'pelanggan',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_pelanggan` (
  `id_pelanggan`   INT AUTO_INCREMENT PRIMARY KEY,
  `id_user`        INT NULL,
  `nama`           VARCHAR(100) NOT NULL,
  `email`          VARCHAR(150),
  `no_hp`          VARCHAR(20),
  `alamat`         TEXT,
  `tanggal_daftar` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_desain` (
  `id_desain`      INT AUTO_INCREMENT PRIMARY KEY,
  `jenis_desain`   VARCHAR(100) NOT NULL,
  `deskripsi`      TEXT,
  `harga_mulai`    INT NOT NULL DEFAULT 0,
  `estimasi_waktu` VARCHAR(50),
  `gambar`         VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_pesanan` (
  `id_pesanan`       INT AUTO_INCREMENT PRIMARY KEY,
  `id_pelanggan`     INT NOT NULL,
  `id_desain`        INT NOT NULL,
  `judul_proyek`     VARCHAR(200),
  `deskripsi_proyek` TEXT,
  `file_referensi`   VARCHAR(255),
  `status_pesanan`   ENUM('pending','proses','selesai','batal') DEFAULT 'pending',
  `tanggal_pesan`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  `deadline`         DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_pembayaran` (
  `id_pembayaran`     INT AUTO_INCREMENT PRIMARY KEY,
  `id_pesanan`        INT NOT NULL,
  `metode_pembayaran` VARCHAR(50),
  `jumlah_bayar`      INT NOT NULL DEFAULT 0,
  `bukti_pembayaran`  VARCHAR(255),
  `status_pembayaran` ENUM('menunggu','terverifikasi','ditolak') DEFAULT 'menunggu',
  `tanggal_bayar`     DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_portofolio` (
  `id_portofolio`  INT AUTO_INCREMENT PRIMARY KEY,
  `judul`          VARCHAR(200) NOT NULL,
  `kategori`       VARCHAR(100),
  `deskripsi`      TEXT,
  `gambar`         VARCHAR(255),
  `tanggal_upload` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tb_testimoni` (
  `id_testimoni`  INT AUTO_INCREMENT PRIMARY KEY,
  `id_pelanggan`  INT NOT NULL,
  `isi_testimoni` TEXT NOT NULL,
  `rating`        TINYINT DEFAULT 5,
  `tanggal`       DATETIME DEFAULT CURRENT_TIMESTAMP,
  `tampil`        TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. ALTER TABLE — Tambah kolom yang belum ada
-- ============================================================

-- Tambah id_user di tb_pelanggan (jika belum ada)
ALTER TABLE `tb_pelanggan`
  ADD COLUMN IF NOT EXISTS `id_user` INT NULL AFTER `id_pelanggan`;

-- Tambah kolom tampil di tb_testimoni (jika belum ada)
ALTER TABLE `tb_testimoni`
  ADD COLUMN IF NOT EXISTS `tampil` TINYINT(1) DEFAULT 1 AFTER `tanggal`;

-- Tambah kolom id_pesanan di tb_testimoni (opsional, untuk link ke pesanan)
ALTER TABLE `tb_testimoni`
  ADD COLUMN IF NOT EXISTS `id_pesanan` INT NULL AFTER `id_pelanggan`;

-- Pastikan role ENUM mencakup kedua nilai
ALTER TABLE `tb_user`
  MODIFY `role` ENUM('Admin','pelanggan') NOT NULL DEFAULT 'pelanggan';

-- ============================================================
-- 3. INSERT ADMIN USER
-- Password: GungRama21 (bcrypt hash)
-- ============================================================

INSERT INTO `tb_user` (nama, email, password, role, created_at)
VALUES (
  'Admin SEKALA',
  'admin@gmail.com',
  '$2y$10$5HflI/It8EpohVcFi5z.Ze3IvgLpxx9EyJ81POsGM6B3WfFRYF9/G',
  'Admin',
  NOW()
)
ON DUPLICATE KEY UPDATE
  password = '$2y$10$5HflI/It8EpohVcFi5z.Ze3IvgLpxx9EyJ81POsGM6B3WfFRYF9/G',
  role     = 'Admin';

-- ============================================================
-- 4. INSERT SAMPLE DATA PAKET (jika tabel kosong)
-- ============================================================

INSERT IGNORE INTO `tb_desain` (id_desain, jenis_desain, deskripsi, harga_mulai, estimasi_waktu) VALUES
(1, 'Paket Basic',   'Paket ini dirancang untuk membantu pelaku UMKM mendapatkan desain poster digital dengan proses yang mudah, cepat, dan terstruktur.', 75000,  '1-2 hari'),
(2, 'Paket Starter', 'Cocok untuk bisnis yang butuh konten media sosial rutin. Dapatkan desain berkualitas dengan harga terjangkau.', 150000, '2-3 hari'),
(3, 'Paket Growth',  'Paket terlaris untuk bisnis aktif berkembang. Termasuk konten feed dan flyer promosi.', 300000, '3-4 hari'),
(4, 'Paket Video',   'Solusi konten video pendek untuk Reels, TikTok, dan promosi digital bisnis Anda.', 250000, '4-5 hari');

-- ============================================================
-- 5. INSERT SAMPLE TESTIMONI (jika kosong)
-- ============================================================

-- (Opsional — uncomment jika ingin sample data)
-- INSERT INTO `tb_pelanggan` (nama, email, no_hp, alamat) VALUES
-- ('Made Ari', 'made@example.com', '08123456789', 'Denpasar, Bali'),
-- ('Komang Dewi', 'komang@example.com', '08198765432', 'Ubud, Bali'),
-- ('Wayan Putra', 'wayan@example.com', '08111222333', 'Kuta, Bali');

-- INSERT INTO `tb_testimoni` (id_pelanggan, isi_testimoni, rating, tampil) VALUES
-- (1, 'SEKALA sangat membantu bisnis kami membuat konten Instagram. Prosesnya mudah dan desainnya cepat selesai.', 5, 1),
-- (2, 'Dengan SEKALA saya tinggal kirim request dan hasil desainnya sudah siap dipakai untuk promosi. Sangat praktis.', 5, 1),
-- (3, 'Pelayanan di SEKALA sangat profesional. Revisi mudah karena semua tercatat di sistem.', 5, 1);

-- ============================================================
-- SELESAI ✅
-- ============================================================
SELECT 'Database SEKALA berhasil diupdate!' AS info;
