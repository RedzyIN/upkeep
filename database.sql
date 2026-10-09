-- LaporFasilitas Kampus
-- Cara pakai: import file ini lewat phpMyAdmin (tab Import)

CREATE DATABASE IF NOT EXISTS db_lapor_fasilitas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_lapor_fasilitas;

DROP TABLE IF EXISTS riwayat_status;
DROP TABLE IF EXISTS laporan;
DROP TABLE IF EXISTS lokasi;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nama       VARCHAR(100) NOT NULL,
  email      VARCHAR(100) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kategori (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE lokasi (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL UNIQUE      -- contoh: "Gedung A - Ruang 201"
);

CREATE TABLE laporan (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  kategori_id   INT NOT NULL,
  lokasi_id     INT NOT NULL,
  judul         VARCHAR(150) NOT NULL,
  deskripsi     TEXT NOT NULL,
  foto          VARCHAR(100) NULL,        -- foto kerusakan (dari pelapor)
  foto_selesai  VARCHAR(100) NULL,        -- foto setelah diperbaiki (dari admin)
  prioritas     ENUM('rendah','sedang','tinggi') NOT NULL DEFAULT 'sedang',
  status        ENUM('dilaporkan','diproses','selesai') NOT NULL DEFAULT 'dilaporkan',
  catatan_admin TEXT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)     REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (kategori_id) REFERENCES kategori(id),
  FOREIGN KEY (lokasi_id)   REFERENCES lokasi(id)
);

-- Jejak perubahan status (dipakai untuk timeline di halaman detail)
CREATE TABLE riwayat_status (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  laporan_id INT NOT NULL,
  status     ENUM('dilaporkan','diproses','selesai') NOT NULL,
  catatan    VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE
);

-- ===== DATA CONTOH =====
-- Login demo -> admin: admin@kampus.test / admin123   |   user: mahasiswa@kampus.test / user123
INSERT INTO users (nama, email, password, role) VALUES
('Admin Sarpras',  'admin@kampus.test',     '$2y$10$LiTI9wo2sIaRqi1Le1XkbOydtksWdUVn2giZUANN1/RGoWQABR39.', 'admin'),
('Mahasiswa Demo', 'mahasiswa@kampus.test', '$2y$10$NhWeqSiKokCLL4oXsSEHYO3z50JkBTmYOb8B/YG2OF4yrN.i28ISG', 'user');

INSERT INTO kategori (nama) VALUES ('Listrik'), ('Air & Sanitasi'), ('AC'), ('Furnitur'), ('Jaringan & WiFi'), ('Kebersihan');

INSERT INTO lokasi (nama) VALUES
('Gedung A - Ruang 101'), ('Gedung A - Ruang 201'), ('Gedung B - Lab Komputer'),
('Perpustakaan'), ('Kantin'), ('Toilet Lantai 1 Gedung A'), ('Masjid Kampus');

INSERT INTO laporan (user_id, kategori_id, lokasi_id, judul, deskripsi, prioritas, status, created_at) VALUES
(2, 3, 2, 'AC tidak dingin',            'AC ruang 201 menyala tapi tidak dingin sejak Senin.', 'sedang', 'selesai',    NOW() - INTERVAL 40 DAY),
(2, 1, 3, 'Stop kontak lab mati',       'Tiga stop kontak di barisan belakang tidak ada arus.', 'tinggi', 'selesai',    NOW() - INTERVAL 33 DAY),
(2, 2, 6, 'Keran wastafel bocor',       'Air menetes terus dari keran kedua.',                  'rendah', 'selesai',    NOW() - INTERVAL 25 DAY),
(2, 5, 4, 'WiFi perpustakaan putus',    'Sinyal sering hilang di area baca lantai 2.',          'tinggi', 'diproses',   NOW() - INTERVAL 14 DAY),
(2, 4, 1, 'Kursi patah',                'Dua kursi di baris depan kakinya patah.',              'sedang', 'diproses',   NOW() - INTERVAL 9 DAY),
(2, 1, 2, 'Lampu ruangan berkedip',     'Lampu tengah berkedip dan mengganggu perkuliahan.',    'sedang', 'dilaporkan', NOW() - INTERVAL 4 DAY),
(2, 3, 3, 'AC bocor menetes ke meja',   'Air menetes dari unit AC ke meja komputer.',           'tinggi', 'dilaporkan', NOW() - INTERVAL 2 DAY),
(2, 6, 5, 'Tempat sampah penuh',        'Tempat sampah kantin belum diangkut dua hari.',        'rendah', 'dilaporkan', NOW() - INTERVAL 1 DAY);

INSERT INTO riwayat_status (laporan_id, status, catatan)
SELECT id, 'dilaporkan', 'Laporan diterima' FROM laporan;
