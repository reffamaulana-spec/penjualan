<?php

if (!function_exists('rupiah')) {
    function rupiah($angka)
    {
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
    }
}

if (!function_exists('pindah')) {
    function pindah($url, $pesan = '')
    {
        $js = '<script>';
        if ($pesan !== '') {
            $js .= 'alert(' . json_encode($pesan) . ');';
        }
        $js .= 'window.location=' . json_encode($url) . ';</script>';
        echo $js;
        exit;
    }
}

if (!function_exists('wajib_login')) {
    function wajib_login()
    {
        if (!isset($_SESSION['id_user'])) {
            exit('Silakan login terlebih dahulu.');
        }
    }
}

if (!function_exists('wajib_admin')) {
    function wajib_admin()
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
            echo '<div class="alert alert-danger shadow-sm mt-4">Hanya Admin yang dapat melakukan aksi ini.</div>';
            return false;
        }
        return true;
    }
}

if (!function_exists('pastikan_kolom')) {
    function pastikan_kolom($koneksi, $tabel, $kolom)
    {
        $ada = [];
        $rs = $koneksi->query("SHOW COLUMNS FROM `" . $tabel . "`");
        while ($r = $rs->fetch_assoc()) {
            $ada[strtolower($r['Field'])] = true;
        }
        foreach ($kolom as $nama => $definisi) {
            if (!isset($ada[$nama])) {
                $koneksi->query("ALTER TABLE `" . $tabel . "` ADD COLUMN `" . $nama . "` " . $definisi);
            }
        }
    }
}

if (!function_exists('siapkan_tabel')) {
    function siapkan_tabel($koneksi)
    {
        if (!empty($_SESSION['tabel_siap'])) {
            return;
        }

        $koneksi->query("CREATE TABLE IF NOT EXISTS produk (
            id_produk INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            kode VARCHAR(30) NOT NULL UNIQUE,
            nama VARCHAR(100) NOT NULL,
            harga DECIMAL(12,0) NOT NULL DEFAULT 0,
            stok INT NOT NULL DEFAULT 0,
            dibuat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $koneksi->query("CREATE TABLE IF NOT EXISTS pelanggan (
            id_pelanggan INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(100) NOT NULL,
            telepon VARCHAR(20) NOT NULL DEFAULT '',
            alamat VARCHAR(255) NOT NULL DEFAULT '',
            dibuat TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");

        $koneksi->query("CREATE TABLE IF NOT EXISTS pesanan (
            id_pesanan INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            no_faktur VARCHAR(30) NOT NULL UNIQUE,
            id_pelanggan INT UNSIGNED NULL,
            id_user INT NULL,
            tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            total DECIMAL(14,0) NOT NULL DEFAULT 0,
            bayar DECIMAL(14,0) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB");

        $koneksi->query("CREATE TABLE IF NOT EXISTS detail_pesanan (
            id_detail INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            id_pesanan INT UNSIGNED NOT NULL,
            id_produk INT UNSIGNED NULL,
            nama_produk VARCHAR(100) NOT NULL,
            harga DECIMAL(12,0) NOT NULL,
            jumlah INT NOT NULL,
            subtotal DECIMAL(14,0) NOT NULL
        ) ENGINE=InnoDB");

        pastikan_kolom($koneksi, 'produk', [
            'kode' => "VARCHAR(30) NOT NULL DEFAULT ''",
            'nama' => "VARCHAR(100) NOT NULL DEFAULT ''",
            'harga' => 'DECIMAL(12,0) NOT NULL DEFAULT 0',
            'stok' => 'INT NOT NULL DEFAULT 0'
        ]);
        pastikan_kolom($koneksi, 'pelanggan', [
            'nama' => "VARCHAR(100) NOT NULL DEFAULT ''",
            'telepon' => "VARCHAR(20) NOT NULL DEFAULT ''",
            'alamat' => "VARCHAR(255) NOT NULL DEFAULT ''"
        ]);
        pastikan_kolom($koneksi, 'pesanan', [
            'no_faktur' => "VARCHAR(30) NOT NULL DEFAULT ''",
            'id_pelanggan' => 'INT NULL',
            'id_user' => 'INT NULL',
            'tanggal' => 'DATETIME NULL DEFAULT CURRENT_TIMESTAMP',
            'total' => 'DECIMAL(14,0) NOT NULL DEFAULT 0',
            'bayar' => 'DECIMAL(14,0) NOT NULL DEFAULT 0'
        ]);
        pastikan_kolom($koneksi, 'detail_pesanan', [
            'id_pesanan' => 'INT NOT NULL DEFAULT 0',
            'id_produk' => 'INT NULL',
            'nama_produk' => "VARCHAR(100) NOT NULL DEFAULT ''",
            'harga' => 'DECIMAL(12,0) NOT NULL DEFAULT 0',
            'jumlah' => 'INT NOT NULL DEFAULT 0',
            'subtotal' => 'DECIMAL(14,0) NOT NULL DEFAULT 0'
        ]);

        $_SESSION['tabel_siap'] = true;
    }
}
