<?php

require_once __DIR__ . '/../../config/fungsi.php';
siapkan_tabel($koneksi);
wajib_login();

$admin = isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : 'view';

if (in_array($aksi, ['tambah', 'edit', 'hapus'], true) && !wajib_admin()) {
    return;
}

switch ($aksi) {

    case 'view':
        $keyword = isset($_GET['cari']) ? trim($_GET['cari']) : '';
        $param = '%' . $keyword . '%';
        $stmt = $koneksi->prepare("SELECT id_produk, kode, nama, harga, stok FROM produk WHERE nama LIKE ? OR kode LIKE ? ORDER BY nama ASC");
        $stmt->bind_param("ss", $param, $param);
        $stmt->execute();
        $result = $stmt->get_result();
?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">Data Produk</h5>
            <?php if ($admin) { ?>
                <a href="index.php?page=produk&aksi=tambah" class="btn btn-primary btn-sm rounded-pill">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Produk
                </a>
            <?php } ?>
        </div>
        <form action="index.php" method="GET" class="mb-4">
            <input type="hidden" name="page" value="produk">
            <div class="input-group shadow-sm">
                <input type="text" name="cari" class="form-control" placeholder="Cari kode atau nama produk.." value="<?= htmlspecialchars($keyword); ?>">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
            </div>
        </form>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Produk</th>
                            <th class="text-end">Harga</th>
                            <th class="text-center">Stok</th>
                            <?php if ($admin) { ?><th class="text-center">Aksi</th><?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0) { ?>
                            <?php while ($row = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['kode']); ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['nama']); ?></td>
                                    <td class="text-end"><?= rupiah($row['harga']); ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $row['stok'] <= 10 ? 'bg-danger' : 'bg-success'; ?>"><?= (int)$row['stok']; ?></span>
                                    </td>
                                    <?php if ($admin) { ?>
                                        <td class="text-center text-nowrap">
                                            <a class="btn btn-outline-warning btn-sm rounded-pill" href="index.php?page=produk&aksi=edit&id=<?= (int)$row['id_produk']; ?>"><i class="bi bi-pencil"></i></a>
                                            <a class="btn btn-outline-danger btn-sm rounded-pill" href="index.php?page=produk&aksi=hapus&id=<?= (int)$row['id_produk']; ?>" onclick="return confirm('Yakin ingin menghapus produk ini?');"><i class="bi bi-trash"></i></a>
                                        </td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Data produk tidak ditemukan.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php
        $stmt->close();
        break;

    case 'tambah':
    case 'edit':
        $edit = $aksi === 'edit';
        $id = 0;
        $data = ['kode' => '', 'nama' => '', 'harga' => '', 'stok' => ''];
        $pesan = '';

        if ($edit) {
            $id = (int)filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $stmt = $koneksi->prepare("SELECT kode, nama, harga, stok FROM produk WHERE id_produk = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $ada = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$ada) {
                echo '<div class="alert alert-warning">Data produk tidak ditemukan.</div>';
                break;
            }
            $data = $ada;
        }

        if (isset($_POST['simpan'])) {
            $data['kode'] = trim($_POST['kode']);
            $data['nama'] = trim($_POST['nama']);
            $data['harga'] = trim($_POST['harga']);
            $data['stok'] = trim($_POST['stok']);

            if ($data['kode'] === '' || $data['nama'] === '' || $data['harga'] === '' || $data['stok'] === '') {
                $pesan = 'Semua kolom wajib diisi.';
            } elseif (!ctype_digit($data['harga']) || !ctype_digit($data['stok'])) {
                $pesan = 'Harga dan stok harus berupa angka bulat tidak negatif.';
            } else {
                $harga = (int)$data['harga'];
                $stok = (int)$data['stok'];
                $cek = $koneksi->prepare("SELECT id_produk FROM produk WHERE kode = ? AND id_produk <> ?");
                $cek->bind_param("si", $data['kode'], $id);
                $cek->execute();
                $dipakai = $cek->get_result()->num_rows > 0;
                $cek->close();
                if ($dipakai) {
                    $pesan = 'Kode produk sudah digunakan.';
                } else {
                    if ($edit) {
                        $stmt = $koneksi->prepare("UPDATE produk SET kode = ?, nama = ?, harga = ?, stok = ? WHERE id_produk = ?");
                        $stmt->bind_param("ssiii", $data['kode'], $data['nama'], $harga, $stok, $id);
                    } else {
                        $stmt = $koneksi->prepare("INSERT INTO produk (kode, nama, harga, stok) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("ssii", $data['kode'], $data['nama'], $harga, $stok);
                    }
                    if ($stmt->execute()) {
                        $stmt->close();
                        pindah('index.php?page=produk', $edit ? 'Produk berhasil diperbarui!' : 'Produk berhasil ditambahkan!');
                    }
                    $pesan = 'Gagal menyimpan data produk.';
                    $stmt->close();
                }
            }
        }
        $action = 'index.php?page=produk&aksi=' . $aksi . ($edit ? '&id=' . $id : '');
?>
        <h5 class="fw-bold mb-3"><?= $edit ? 'Edit Produk' : 'Tambah Produk'; ?></h5>
        <?php if ($pesan !== '') { ?>
            <div class="alert alert-warning"><?= htmlspecialchars($pesan); ?></div>
        <?php } ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="<?= $action; ?>" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Kode Produk</label>
                        <input type="text" name="kode" class="form-control" maxlength="30" value="<?= htmlspecialchars($data['kode']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Produk</label>
                        <input type="text" name="nama" class="form-control" maxlength="100" value="<?= htmlspecialchars($data['nama']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Harga (Rp)</label>
                        <input type="number" name="harga" class="form-control" min="0" value="<?= htmlspecialchars($data['harga']); ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Stok</label>
                        <input type="number" name="stok" class="form-control" min="0" value="<?= htmlspecialchars($data['stok']); ?>" required>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100 mb-2">Simpan Produk</button>
                    <a href="index.php?page=produk" class="btn btn-light w-100">Batal</a>
                </form>
            </div>
        </div>
<?php
        break;

    case 'hapus':
        $id = (int)filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $lepas = $koneksi->prepare("UPDATE detail_pesanan SET id_produk = NULL WHERE id_produk = ?");
        $lepas->bind_param("i", $id);
        $lepas->execute();
        $lepas->close();
        $stmt = $koneksi->prepare("DELETE FROM produk WHERE id_produk = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        pindah('index.php?page=produk', $ok ? 'Produk berhasil dihapus.' : 'Produk gagal dihapus atau tidak ditemukan.');
        break;

    default:
        echo '<div class="alert alert-warning">Aksi tidak dikenali.</div>';
        break;
}
