<?php

require_once __DIR__ . '/../../config/fungsi.php';
siapkan_tabel($koneksi);
wajib_login();

$admin = isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : 'view';

switch ($aksi) {

    case 'view':
        $keyword = isset($_GET['cari']) ? trim($_GET['cari']) : '';
        $param = '%' . $keyword . '%';
        $stmt = $koneksi->prepare("SELECT s.id_pesanan, s.no_faktur, s.tanggal, s.total, s.bayar, COALESCE(p.nama, 'Umum') AS pelanggan, COALESCE(u.nama, '-') AS kasir FROM pesanan s LEFT JOIN pelanggan p ON p.id_pelanggan = s.id_pelanggan LEFT JOIN user u ON u.id_user = s.id_user WHERE s.no_faktur LIKE ? OR p.nama LIKE ? ORDER BY s.id_pesanan DESC LIMIT 100");
        $stmt->bind_param("ss", $param, $param);
        $stmt->execute();
        $result = $stmt->get_result();
?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">Transaksi Pesanan</h5>
            <a href="index.php?page=pesan&aksi=baru" class="btn btn-primary btn-sm rounded-pill">
                <i class="bi bi-cart-plus me-1"></i> Transaksi Baru
            </a>
        </div>
        <form action="index.php" method="GET" class="mb-4">
            <input type="hidden" name="page" value="pesan">
            <div class="input-group shadow-sm">
                <input type="text" name="cari" class="form-control" placeholder="Cari no. faktur atau pelanggan.." value="<?= htmlspecialchars($keyword); ?>">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
            </div>
        </form>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No. Faktur</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Kasir</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0) { ?>
                            <?php while ($row = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['no_faktur']); ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($row['tanggal'])); ?></td>
                                    <td><?= htmlspecialchars($row['pelanggan']); ?></td>
                                    <td><?= htmlspecialchars($row['kasir']); ?></td>
                                    <td class="text-end"><?= rupiah($row['total']); ?></td>
                                    <td class="text-center text-nowrap">
                                        <a class="btn btn-outline-primary btn-sm rounded-pill" href="index.php?page=faktur&aksi=detail&id=<?= (int)$row['id_pesanan']; ?>"><i class="bi bi-receipt"></i></a>
                                        <?php if ($admin) { ?>
                                            <a class="btn btn-outline-danger btn-sm rounded-pill" href="index.php?page=pesan&aksi=hapus&id=<?= (int)$row['id_pesanan']; ?>" onclick="return confirm('Hapus transaksi ini? Stok produk akan dikembalikan.');"><i class="bi bi-trash"></i></a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php
        $stmt->close();
        break;

    case 'baru':
        $pesan = '';
        $produk_list = [];
        $rs = $koneksi->query("SELECT id_produk, kode, nama, harga, stok FROM produk WHERE stok > 0 ORDER BY nama ASC");
        while ($p = $rs->fetch_assoc()) {
            $produk_list[] = $p;
        }
        $pelanggan_list = [];
        $rs = $koneksi->query("SELECT id_pelanggan, nama FROM pelanggan ORDER BY nama ASC");
        while ($p = $rs->fetch_assoc()) {
            $pelanggan_list[] = $p;
        }

        if (isset($_POST['simpan'])) {
            $id_pelanggan = (int)($_POST['id_pelanggan'] ?? 0);
            $bayar = isset($_POST['bayar']) && ctype_digit($_POST['bayar']) ? (int)$_POST['bayar'] : -1;
            $id_produk_in = isset($_POST['id_produk']) && is_array($_POST['id_produk']) ? $_POST['id_produk'] : [];
            $jumlah_in = isset($_POST['jumlah']) && is_array($_POST['jumlah']) ? $_POST['jumlah'] : [];

            $item = [];
            foreach ($id_produk_in as $i => $idp) {
                $idp = (int)$idp;
                $jml = isset($jumlah_in[$i]) ? (int)$jumlah_in[$i] : 0;
                if ($idp > 0 && $jml > 0) {
                    $item[$idp] = ($item[$idp] ?? 0) + $jml;
                }
            }

            if (count($item) === 0) {
                $pesan = 'Pilih minimal satu produk dengan jumlah yang valid.';
            } elseif ($bayar < 0) {
                $pesan = 'Jumlah bayar harus berupa angka.';
            } else {
                $koneksi->begin_transaction();
                try {
                    $baris = [];
                    $total = 0;
                    $ambil = $koneksi->prepare("SELECT nama, harga, stok FROM produk WHERE id_produk = ? FOR UPDATE");
                    foreach ($item as $idp => $jml) {
                        $ambil->bind_param("i", $idp);
                        $ambil->execute();
                        $p = $ambil->get_result()->fetch_assoc();
                        if (!$p) {
                            throw new Exception('Produk tidak ditemukan.');
                        }
                        if ($p['stok'] < $jml) {
                            throw new Exception('Stok ' . $p['nama'] . ' tidak mencukupi (tersisa ' . $p['stok'] . ').');
                        }
                        $sub = (int)$p['harga'] * $jml;
                        $total += $sub;
                        $baris[] = [$idp, $p['nama'], (int)$p['harga'], $jml, $sub];
                    }
                    $ambil->close();

                    if ($bayar < $total) {
                        throw new Exception('Jumlah bayar kurang dari total ' . rupiah($total) . '.');
                    }

                    $sementara = 'TMP' . uniqid();
                    $idu = (int)$_SESSION['id_user'];
                    $idpel = $id_pelanggan > 0 ? $id_pelanggan : null;
                    $ins = $koneksi->prepare("INSERT INTO pesanan (no_faktur, id_pelanggan, id_user, total, bayar) VALUES (?, ?, ?, ?, ?)");
                    $ins->bind_param("siiii", $sementara, $idpel, $idu, $total, $bayar);
                    $ins->execute();
                    $id_pesanan = $koneksi->insert_id;
                    $ins->close();

                    $no_faktur = 'INV-' . date('Ymd') . '-' . str_pad($id_pesanan, 4, '0', STR_PAD_LEFT);
                    $upd = $koneksi->prepare("UPDATE pesanan SET no_faktur = ? WHERE id_pesanan = ?");
                    $upd->bind_param("si", $no_faktur, $id_pesanan);
                    $upd->execute();
                    $upd->close();

                    $det = $koneksi->prepare("INSERT INTO detail_pesanan (id_pesanan, id_produk, nama_produk, harga, jumlah, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                    $kurang = $koneksi->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");
                    foreach ($baris as $b) {
                        $det->bind_param("iisiii", $id_pesanan, $b[0], $b[1], $b[2], $b[3], $b[4]);
                        $det->execute();
                        $kurang->bind_param("ii", $b[3], $b[0]);
                        $kurang->execute();
                    }
                    $det->close();
                    $kurang->close();

                    $koneksi->commit();
                    pindah('index.php?page=faktur&aksi=detail&id=' . $id_pesanan, 'Transaksi berhasil disimpan!');
                } catch (Exception $e) {
                    $koneksi->rollback();
                    $pesan = $e->getMessage();
                }
            }
        }
?>
        <h5 class="fw-bold mb-3">Transaksi Baru</h5>
        <?php if ($pesan !== '') { ?>
            <div class="alert alert-warning"><?= htmlspecialchars($pesan); ?></div>
        <?php } ?>
        <form action="index.php?page=pesan&aksi=baru" method="POST" id="formPesan">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label fw-bold">Pelanggan</label>
                    <select name="id_pelanggan" class="form-select">
                        <option value="0">Umum (tanpa pelanggan)</option>
                        <?php foreach ($pelanggan_list as $pl) { ?>
                            <option value="<?= (int)$pl['id_pelanggan']; ?>"><?= htmlspecialchars($pl['nama']); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="form-label fw-bold m-0">Daftar Barang</label>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" id="tambahBaris"><i class="bi bi-plus-lg"></i> Tambah Baris</button>
                    </div>
                    <div id="daftarBaris"></div>
                    <hr>
                    <div class="d-flex justify-content-between fs-5 fw-bold">
                        <span>Total</span>
                        <span id="totalTampil">Rp 0</span>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <label class="form-label fw-bold">Jumlah Bayar (Rp)</label>
                    <input type="number" name="bayar" id="bayar" class="form-control" min="0" required>
                    <div class="form-text" id="kembalian">Kembalian: Rp 0</div>
                </div>
            </div>
            <button type="submit" name="simpan" class="btn btn-primary w-100 mb-2">Simpan Transaksi</button>
            <a href="index.php?page=pesan" class="btn btn-light w-100">Batal</a>
        </form>

        <template id="templateBaris">
            <div class="row g-2 mb-2 baris align-items-center">
                <div class="col-7 col-md-8">
                    <select name="id_produk[]" class="form-select pilihProduk">
                        <option value="" data-harga="0">-- Pilih Produk --</option>
                        <?php foreach ($produk_list as $p) { ?>
                            <option value="<?= (int)$p['id_produk']; ?>" data-harga="<?= (int)$p['harga']; ?>" data-stok="<?= (int)$p['stok']; ?>"><?= htmlspecialchars($p['nama']); ?> - <?= rupiah($p['harga']); ?> (stok <?= (int)$p['stok']; ?>)</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-3 col-md-3">
                    <input type="number" name="jumlah[]" class="form-control inputJumlah" min="1" value="1">
                </div>
                <div class="col-2 col-md-1 text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm hapusBaris"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        </template>

        <script>
        (function () {
            var daftar = document.getElementById('daftarBaris');
            var templ = document.getElementById('templateBaris');
            var totalEl = document.getElementById('totalTampil');
            var bayarEl = document.getElementById('bayar');
            var kembalianEl = document.getElementById('kembalian');

            function format(n) {
                return 'Rp ' + n.toLocaleString('id-ID');
            }

            function hitung() {
                var total = 0;
                daftar.querySelectorAll('.baris').forEach(function (b) {
                    var opt = b.querySelector('.pilihProduk').selectedOptions[0];
                    var harga = parseInt(opt.getAttribute('data-harga') || '0', 10);
                    var jml = parseInt(b.querySelector('.inputJumlah').value || '0', 10);
                    if (jml > 0) {
                        total += harga * jml;
                    }
                });
                totalEl.textContent = format(total);
                var bayar = parseInt(bayarEl.value || '0', 10);
                kembalianEl.textContent = bayar >= total ? 'Kembalian: ' + format(bayar - total) : 'Kurang: ' + format(total - bayar);
            }

            function tambah() {
                daftar.appendChild(templ.content.cloneNode(true));
                hitung();
            }

            document.getElementById('tambahBaris').addEventListener('click', tambah);
            daftar.addEventListener('input', hitung);
            daftar.addEventListener('change', hitung);
            bayarEl.addEventListener('input', hitung);
            daftar.addEventListener('click', function (e) {
                var tombol = e.target.closest('.hapusBaris');
                if (tombol) {
                    if (daftar.querySelectorAll('.baris').length > 1) {
                        tombol.closest('.baris').remove();
                        hitung();
                    }
                }
            });
            tambah();
        })();
        </script>
<?php
        break;

    case 'hapus':
        if (!wajib_admin()) {
            break;
        }
        $id = (int)filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $koneksi->begin_transaction();
        $ok = false;
        $ambil = $koneksi->prepare("SELECT id_produk, jumlah FROM detail_pesanan WHERE id_pesanan = ?");
        $ambil->bind_param("i", $id);
        $ambil->execute();
        $items = $ambil->get_result()->fetch_all(MYSQLI_ASSOC);
        $ambil->close();

        $hdet = $koneksi->prepare("DELETE FROM detail_pesanan WHERE id_pesanan = ?");
        $hdet->bind_param("i", $id);
        $hdet->execute();
        $hdet->close();
        $hapus = $koneksi->prepare("DELETE FROM pesanan WHERE id_pesanan = ?");
        $hapus->bind_param("i", $id);
        if ($hapus->execute() && $hapus->affected_rows > 0) {
            $balik = $koneksi->prepare("UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
            foreach ($items as $it) {
                if ($it['id_produk'] !== null) {
                    $balik->bind_param("ii", $it['jumlah'], $it['id_produk']);
                    $balik->execute();
                }
            }
            $balik->close();
            $ok = true;
        }
        $hapus->close();
        if ($ok) {
            $koneksi->commit();
        } else {
            $koneksi->rollback();
        }
        pindah('index.php?page=pesan', $ok ? 'Transaksi dihapus dan stok dikembalikan.' : 'Transaksi gagal dihapus atau tidak ditemukan.');
        break;

    default:
        echo '<div class="alert alert-warning">Aksi tidak dikenali.</div>';
        break;
}
