<?php

require_once __DIR__ . '/../../config/fungsi.php';
siapkan_tabel($koneksi);
wajib_login();

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : 'view';

switch ($aksi) {

    case 'view':
        $keyword = isset($_GET['cari']) ? trim($_GET['cari']) : '';
        $param = '%' . $keyword . '%';
        $stmt = $koneksi->prepare("SELECT s.id_pesanan, s.no_faktur, s.tanggal, s.total, COALESCE(p.nama, 'Umum') AS pelanggan FROM pesanan s LEFT JOIN pelanggan p ON p.id_pelanggan = s.id_pelanggan WHERE s.no_faktur LIKE ? OR p.nama LIKE ? ORDER BY s.id_pesanan DESC LIMIT 100");
        $stmt->bind_param("ss", $param, $param);
        $stmt->execute();
        $result = $stmt->get_result();
?>
        <h5 class="fw-bold mb-3">Faktur &amp; Kuitansi</h5>
        <form action="index.php" method="GET" class="mb-4">
            <input type="hidden" name="page" value="faktur">
            <div class="input-group shadow-sm">
                <input type="text" name="cari" class="form-control" placeholder="Cari no. faktur atau pelanggan.." value="<?= htmlspecialchars($keyword); ?>">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
            </div>
        </form>
        <div class="row g-3">
            <?php if ($result->num_rows > 0) { ?>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($row['no_faktur']); ?></h6>
                                <p class="text-muted small mb-2"><?= date('d/m/Y H:i', strtotime($row['tanggal'])); ?> &middot; <?= htmlspecialchars($row['pelanggan']); ?></p>
                                <p class="fs-5 fw-bold text-primary mb-3"><?= rupiah($row['total']); ?></p>
                                <a class="btn btn-outline-primary btn-sm rounded-pill w-100" href="index.php?page=faktur&aksi=detail&id=<?= (int)$row['id_pesanan']; ?>"><i class="bi bi-receipt"></i> Lihat Faktur</a>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="col-12"><div class="alert alert-light border text-center">Belum ada faktur.</div></div>
            <?php } ?>
        </div>
<?php
        $stmt->close();
        break;

    case 'detail':
        $id = (int)filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $stmt = $koneksi->prepare("SELECT s.no_faktur, s.tanggal, s.total, s.bayar, COALESCE(p.nama, 'Umum') AS pelanggan, p.telepon, p.alamat, COALESCE(u.nama, '-') AS kasir FROM pesanan s LEFT JOIN pelanggan p ON p.id_pelanggan = s.id_pelanggan LEFT JOIN user u ON u.id_user = s.id_user WHERE s.id_pesanan = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $faktur = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$faktur) {
            echo '<div class="alert alert-warning">Faktur tidak ditemukan.</div>';
            break;
        }
        $stmt = $koneksi->prepare("SELECT nama_produk, harga, jumlah, subtotal FROM detail_pesanan WHERE id_pesanan = ? ORDER BY id_detail ASC");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $items = $stmt->get_result();
?>
        <style>
            @media print {
                .navbar, .offcanvas, .no-print { display: none !important; }
                body { padding-top: 0 !important; background: #fff !important; }
                .card { box-shadow: none !important; border: 0 !important; }
            }
        </style>
        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <a href="index.php?page=faktur" class="btn btn-light btn-sm rounded-pill"><i class="bi bi-arrow-left"></i> Kembali</a>
            <button type="button" class="btn btn-primary btn-sm rounded-pill" onclick="window.print()"><i class="bi bi-printer me-1"></i> Cetak</button>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between flex-wrap mb-4">
                    <div>
                        <h4 class="fw-bold text-primary mb-0">APP PENJUALAN</h4>
                        <span class="text-muted small">Faktur &amp; Kuitansi Penjualan</span>
                    </div>
                    <div class="text-md-end">
                        <div class="fw-bold"><?= htmlspecialchars($faktur['no_faktur']); ?></div>
                        <div class="text-muted small"><?= date('d/m/Y H:i', strtotime($faktur['tanggal'])); ?></div>
                    </div>
                </div>
                <div class="row mb-4 small">
                    <div class="col-6">
                        <div class="text-muted">Pelanggan</div>
                        <div class="fw-semibold"><?= htmlspecialchars($faktur['pelanggan']); ?></div>
                        <?php if (!empty($faktur['telepon'])) { ?><div><?= htmlspecialchars($faktur['telepon']); ?></div><?php } ?>
                        <?php if (!empty($faktur['alamat'])) { ?><div><?= htmlspecialchars($faktur['alamat']); ?></div><?php } ?>
                    </div>
                    <div class="col-6 text-end">
                        <div class="text-muted">Kasir</div>
                        <div class="fw-semibold"><?= htmlspecialchars($faktur['kasir']); ?></div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($it = $items->fetch_assoc()) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($it['nama_produk']); ?></td>
                                    <td class="text-end"><?= rupiah($it['harga']); ?></td>
                                    <td class="text-center"><?= (int)$it['jumlah']; ?></td>
                                    <td class="text-end"><?= rupiah($it['subtotal']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total</th>
                                <th class="text-end"><?= rupiah($faktur['total']); ?></th>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">Bayar</td>
                                <td class="text-end"><?= rupiah($faktur['bayar']); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">Kembalian</td>
                                <td class="text-end"><?= rupiah($faktur['bayar'] - $faktur['total']); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="text-center text-muted small mt-4 mb-0">Terima kasih atas kunjungan Anda.</p>
            </div>
        </div>
<?php
        $stmt->close();
        break;

    default:
        echo '<div class="alert alert-warning">Aksi tidak dikenali.</div>';
        break;
}
