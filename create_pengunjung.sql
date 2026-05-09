CREATE TABLE IF NOT EXISTS tb_pengunjung (
    id_kunjungan INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    halaman VARCHAR(50) NOT NULL,
    waktu_masuk DATETIME NOT NULL,
    durasi_detik INT DEFAULT 0,
    ip_address VARCHAR(50)
);
