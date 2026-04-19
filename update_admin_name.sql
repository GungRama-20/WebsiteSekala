-- ============================================================
-- update_admin_name.sql
-- Ganti nama admin pertama (role = 'Admin') menjadi "Rama - Admin"
-- Jalankan di phpMyAdmin → database: sekala
-- ============================================================

-- Lihat daftar admin yang ada dulu
SELECT id_user, nama, email, role FROM tb_user WHERE role = 'Admin';

-- Update nama admin pertama (id terkecil / yang sudah ada)
UPDATE tb_user
SET nama = 'Rama - Admin'
WHERE role = 'Admin'
ORDER BY id_user ASC
LIMIT 1;

-- Verifikasi hasil
SELECT id_user, nama, email, role FROM tb_user WHERE role = 'Admin';
