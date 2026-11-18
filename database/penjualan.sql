CREATE DATABASE IF NOT EXISTS penjualan CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE penjualan;

CREATE TABLE IF NOT EXISTS user (
    id_user INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'Kasir'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS produk (
    id_produk INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(30) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    harga DECIMAL(12,0) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0,
    dibuat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pelanggan (
    id_pelanggan INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    telepon VARCHAR(20) NOT NULL DEFAULT '',
    alamat VARCHAR(255) NOT NULL DEFAULT '',
    dibuat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pesanan (
    id_pesanan INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    no_faktur VARCHAR(30) NOT NULL UNIQUE,
    id_pelanggan INT UNSIGNED NULL,
    id_user INT NULL,
    tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(14,0) NOT NULL DEFAULT 0,
    bayar DECIMAL(14,0) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS detail_pesanan (
    id_detail INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT UNSIGNED NOT NULL,
    id_produk INT UNSIGNED NULL,
    nama_produk VARCHAR(100) NOT NULL,
    harga DECIMAL(12,0) NOT NULL,
    jumlah INT NOT NULL,
    subtotal DECIMAL(14,0) NOT NULL
) ENGINE=InnoDB;

INSERT INTO user (nama, username, password, role)
SELECT 'Administrator', 'admin', MD5('admin123'), 'Admin' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM user WHERE username = 'admin');

INSERT INTO user (nama, username, password, role)
SELECT 'Kasir Toko', 'kasir', MD5('kasir123'), 'Kasir' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM user WHERE username = 'kasir');

INSERT INTO produk (kode, nama, harga, stok)
SELECT * FROM (
    SELECT 'BRG-001' AS kode, 'Beras Premium 5 Kg' AS nama, 68000 AS harga, 40 AS stok UNION ALL
    SELECT 'BRG-002', 'Minyak Goreng 2 Liter', 36000, 60 UNION ALL
    SELECT 'BRG-003', 'Gula Pasir 1 Kg', 17500, 80 UNION ALL
    SELECT 'BRG-004', 'Telur Ayam 1 Kg', 29000, 50 UNION ALL
    SELECT 'BRG-005', 'Kopi Bubuk 200 g', 15000, 70 UNION ALL
    SELECT 'BRG-006', 'Mi Instan Goreng', 3500, 200 UNION ALL
    SELECT 'BRG-007', 'Sabun Mandi Batang', 4500, 120 UNION ALL
    SELECT 'BRG-008', 'Air Mineral 600 ml', 3000, 300
) AS contoh
WHERE NOT EXISTS (SELECT 1 FROM produk);

INSERT INTO pelanggan (nama, telepon, alamat)
SELECT * FROM (
    SELECT 'Budi Santoso' AS nama, '081234567801' AS telepon, 'Jl. Merdeka No. 10' AS alamat UNION ALL
    SELECT 'Siti Aminah', '081234567802', 'Jl. Melati No. 5' UNION ALL
    SELECT 'Andi Wijaya', '081234567803', 'Jl. Sudirman No. 21'
) AS contoh
WHERE NOT EXISTS (SELECT 1 FROM pelanggan);
