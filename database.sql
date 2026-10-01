-- Jalankan di database baru: sudo mariadb < database.sql
CREATE DATABASE IF NOT EXISTS rental_ps;
USE rental_ps;

CREATE TABLE unit (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(50) NOT NULL,
  jenis VARCHAR(20) NOT NULL,          -- PS3 / PS4 / PS5
  tarif_per_jam INT NOT NULL,
  status ENUM('kosong','dipakai') DEFAULT 'kosong'
);

CREATE TABLE member (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(60) NOT NULL,
  no_hp VARCHAR(20),
  tgl_daftar DATETIME NOT NULL,
  biaya_daftar INT NOT NULL DEFAULT 0,
  aktif TINYINT NOT NULL DEFAULT 1     -- 0 = sudah dihapus admin (riwayat tetap ada)
);

CREATE TABLE sesi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  unit_id INT NOT NULL,
  waktu_mulai DATETIME NOT NULL,
  waktu_selesai DATETIME NULL,
  durasi_menit INT NULL,
  total_biaya INT NULL,
  paket_menit INT NOT NULL DEFAULT 0,   -- 0 = bebas, selain itu = durasi paket
  member_id INT NULL,                   -- kosong = pelanggan umum
  diskon_persen INT NOT NULL DEFAULT 0,
  FOREIGN KEY (unit_id) REFERENCES unit(id),
  FOREIGN KEY (member_id) REFERENCES member(id)
);

CREATE TABLE admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL,
  password VARCHAR(255) NOT NULL,
  peran ENUM('admin','kasir') NOT NULL DEFAULT 'admin'
);

CREATE TABLE tutup_kas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  waktu DATETIME NOT NULL,             -- kapan admin menekan Reset pendapatan
  total INT NOT NULL DEFAULT 0         -- total pendapatan saat direset
);

CREATE TABLE permintaan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jenis ENUM('reset_kas','hapus_member') NOT NULL,
  member_id INT NULL,
  diminta_oleh VARCHAR(30) NOT NULL,
  waktu_minta DATETIME NOT NULL,
  status ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  waktu_putus DATETIME NULL
);

-- Data contoh
INSERT INTO unit (nama, jenis, tarif_per_jam) VALUES
('Meja 1','PS5',10000),
('Meja 2','PS5',10000),
('Meja 3','PS5',10000),
('Meja 4','PS5',10000),
('Meja 5','PS5',10000),
('Meja 6','PS5',10000),
('Meja 7','PS5',10000),
('Meja 8','PS5',10000),
('Meja 9','PS5',10000),
('Meja 10','PS5',10000);

-- Login admin (password disimpan dalam bentuk MD5)
INSERT INTO admin (username, password) VALUES ('D1vzz', MD5('D1vzzDB17'));