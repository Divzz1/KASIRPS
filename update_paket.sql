-- Jalankan sekali jika database sudah terlanjur di-import sebelumnya
USE rental_ps;
ALTER TABLE sesi ADD COLUMN paket_menit INT NOT NULL DEFAULT 0;
