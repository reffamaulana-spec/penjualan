<?php

require_once __DIR__ . '/../../config/fungsi.php';
siapkan_tabel($koneksi);
wajib_login();

$admin = isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : 'view';

if ($aksi === 'hapus' && !wajib_admin()) {
    return;
}

switch ($aksi) {

    case 'view':
        $keyword = isset($_GET['cari']) ? trim($_GET['cari']) : '';
        $param = '%' . $keyword . '%';
        $stmt = $koneksi->prepare("SELECT p.id_pelanggan, p.nama, p.telepon, p.alamat, COUNT(s.id_pesanan) AS jml_transaksi FROM pelanggan p LEFT JOIN pesanan s ON s.id_pelanggan = p.id_pelanggan WHERE p.nama LIKE ? OR p.telepon LIKE ? GROUP BY p.id_pelanggan ORDER BY p.nama ASC");
        $stmt->bind_param("ss", $param, $param);
        $stmt->execute();
        $result = $stmt->get_result();
?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">Data Pelanggan</h5>
            <a href="index.php?page=pelanggan&aksi=tambah" class="btn btn-primary btn-sm rounded-pill">
                <i class="bi bi-person-plus-fill me-1"></i> Tambah Pelanggan
            </a>
        </div>
        <form action="index.php" method="GET" class="mb-4">
            <input type="hidden" name="page" value="pelanggan">
            <div class="input-group shadow-sm">
                <input type="text" name="cari" class="form-control" placeholder="Cari nama atau telepon.." value="<?= htmlspecialchars($keyword); ?>">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
            </div>
        </form>
        <div class="row g-3">
            <?php if ($result->num_rows > 0) { ?>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($row['nama']); ?></h6>
                                <span class="badge bg-secondary mb-3"><?= (int)$row['jml_transaksi']; ?> transaksi</span>
                                <p class="text-muted small mb-1"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($row['telepon'] !== '' ? $row['telepon'] : '-'); ?></p>
                                <p class="text-muted small mb-3"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['alamat'] !== '' ? $row['alamat'] : '-'); ?></p>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-outline-warning btn-sm rounded-pill w-50" href="index.php?page=pelanggan&aksi=edit&id=<?= (int)$row['id_pelanggan']; ?>"><i class="bi bi-pencil"></i> Edit</a>
                                    <?php if ($admin) { ?>
                                        <a class="btn btn-outline-danger btn-sm rounded-pill w-50" href="index.php?page=pelanggan&aksi=hapus&id=<?= (int)$row['id_pelanggan']; ?>" onclick="return confirm('Yakin ingin menghapus pelanggan ini?');"><i class="bi bi-trash"></i> Hapus</a>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <div class="col-12"><div class="alert alert-light border text-center">Data pelanggan tidak ditemukan.</div></div>
            <?php } ?>
        </div>
<?php
        $stmt->close();
        break;

    case 'tambah':
    case 'edit':
        $edit = $aksi === 'edit';
        $id = 0;
        $data = ['nama' => '', 'telepon' => '', 'alamat' => ''];
        $pesan = '';

        if ($edit) {
            $id = (int)filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            $stmt = $koneksi->prepare("SELECT nama, telepon, alamat FROM pelanggan WHERE id_pelanggan = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $ada = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$ada) {
                echo '<div class="alert alert-warning">Data pelanggan tidak ditemukan.</div>';
                break;
            }
            $data = $ada;
        }

        if (isset($_POST['simpan'])) {
            $data['nama'] = trim($_POST['nama']);
            $data['telepon'] = trim($_POST['telepon']);
            $data['alamat'] = trim($_POST['alamat']);

            if ($data['nama'] === '') {
                $pesan = 'Nama pelanggan wajib diisi.';
            } elseif ($data['telepon'] !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $data['telepon'])) {
                $pesan = 'Format nomor telepon tidak valid.';
            } else {
                if ($edit) {
                    $stmt = $koneksi->prepare("UPDATE pelanggan SET nama = ?, telepon = ?, alamat = ? WHERE id_pelanggan = ?");
                    $stmt->bind_param("sssi", $data['nama'], $data['telepon'], $data['alamat'], $id);
                } else {
                    $stmt = $koneksi->prepare("INSERT INTO pelanggan (nama, telepon, alamat) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $data['nama'], $data['telepon'], $data['alamat']);
                }
                if ($stmt->execute()) {
                    $stmt->close();
                    pindah('index.php?page=pelanggan', $edit ? 'Pelanggan berhasil diperbarui!' : 'Pelanggan berhasil ditambahkan!');
                }
                $pesan = 'Gagal menyimpan data pelanggan.';
                $stmt->close();
            }
        }
        $action = 'index.php?page=pelanggan&aksi=' . $aksi . ($edit ? '&id=' . $id : '');
?>
        <h5 class="fw-bold mb-3"><?= $edit ? 'Edit Pelanggan' : 'Tambah Pelanggan'; ?></h5>
        <?php if ($pesan !== '') { ?>
            <div class="alert alert-warning"><?= htmlspecialchars($pesan); ?></div>
        <?php } ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="<?= $action; ?>" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Pelanggan</label>
                        <input type="text" name="nama" class="form-control" maxlength="100" value="<?= htmlspecialchars($data['nama']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Telepon</label>
                        <input type="text" name="telepon" class="form-control" maxlength="20" value="<?= htmlspecialchars($data['telepon']); ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3" maxlength="255"><?= htmlspecialchars($data['alamat']); ?></textarea>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary w-100 mb-2">Simpan Pelanggan</button>
                    <a href="index.php?page=pelanggan" class="btn btn-light w-100">Batal</a>
                </form>
            </div>
        </div>
<?php
        break;

    case 'hapus':
        $id = (int)filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $lepas = $koneksi->prepare("UPDATE pesanan SET id_pelanggan = NULL WHERE id_pelanggan = ?");
        $lepas->bind_param("i", $id);
        $lepas->execute();
        $lepas->close();
        $stmt = $koneksi->prepare("DELETE FROM pelanggan WHERE id_pelanggan = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute() && $stmt->affected_rows > 0;
        $stmt->close();
        pindah('index.php?page=pelanggan', $ok ? 'Pelanggan berhasil dihapus.' : 'Pelanggan gagal dihapus atau tidak ditemukan.');
        break;

    default:
        echo '<div class="alert alert-warning">Aksi tidak dikenali.</div>';
        break;
}
