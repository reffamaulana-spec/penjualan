<?php

require_once __DIR__ . '/config/fungsi.php';
siapkan_tabel($koneksi);

$q = $koneksi->query("SELECT
    (SELECT COUNT(*) FROM produk) AS jml_produk,
    (SELECT COUNT(*) FROM pelanggan) AS jml_pelanggan,
    (SELECT COUNT(*) FROM pesanan WHERE DATE(tanggal) = CURDATE()) AS transaksi_hari_ini,
    (SELECT COALESCE(SUM(total), 0) FROM pesanan WHERE DATE(tanggal) = CURDATE()) AS omzet_hari_ini");
$stat = $q->fetch_assoc();

$menipis = $koneksi->query("SELECT nama, stok FROM produk WHERE stok <= 10 ORDER BY stok ASC LIMIT 5");
$terakhir = $koneksi->query("SELECT s.id_pesanan, s.no_faktur, s.tanggal, s.total, COALESCE(p.nama, 'Umum') AS pelanggan FROM pesanan s LEFT JOIN pelanggan p ON p.id_pelanggan = s.id_pelanggan ORDER BY s.id_pesanan DESC LIMIT 5");
?>
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card shadow-sm border-0 border-start border-primary border-4">
            <div class="card-body p-4">
                <h4 class="mb-2">Selamat Datang, <?= htmlspecialchars($_SESSION['nama']); ?>!</h4>
                <p class="text-muted mb-0">
                    Saat ini Anda mengakses sistem dengan hak akses sebagai
                    <strong><?= htmlspecialchars($_SESSION['role']); ?></strong>.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Omzet Hari Ini</div>
            <div class="fs-5 fw-bold text-success"><?= rupiah($stat['omzet_hari_ini']); ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Transaksi Hari Ini</div>
            <div class="fs-5 fw-bold"><?= (int)$stat['transaksi_hari_ini']; ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Total Produk</div>
            <div class="fs-5 fw-bold"><?= (int)$stat['jml_produk']; ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Total Pelanggan</div>
            <div class="fs-5 fw-bold"><?= (int)$stat['jml_pelanggan']; ?></div>
        </div></div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Stok Menipis</h6>
                <?php if ($menipis->num_rows > 0) { ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($m = $menipis->fetch_assoc()) { ?>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span><?= htmlspecialchars($m['nama']); ?></span>
                                <span class="badge bg-danger"><?= (int)$m['stok']; ?></span>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <p class="text-muted small mb-0">Semua stok aman.</p>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-primary me-2"></i>Transaksi Terakhir</h6>
                <?php if ($terakhir->num_rows > 0) { ?>
                    <ul class="list-group list-group-flush">
                        <?php while ($t = $terakhir->fetch_assoc()) { ?>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <a href="index.php?page=faktur&aksi=detail&id=<?= (int)$t['id_pesanan']; ?>" class="text-decoration-none"><?= htmlspecialchars($t['no_faktur']); ?> <span class="text-muted small">&middot; <?= htmlspecialchars($t['pelanggan']); ?></span></a>
                                <span class="fw-semibold"><?= rupiah($t['total']); ?></span>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <p class="text-muted small mb-0">Belum ada transaksi.</p>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
