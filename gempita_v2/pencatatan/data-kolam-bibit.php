<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$pageTitle = 'Data Bibit & Kolam Ikan';
$activeMenu = 'kolam';
$pdo = getDB();
$userId = currentUser()['id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kolam    = trim($_POST['nama_kolam'] ?? '');
    $jenis_ikan    = trim($_POST['jenis_ikan'] ?? '');
    $tanggal_tebar = trim($_POST['tanggal_tebar'] ?? '');
    $jumlah_bibit  = (int)($_POST['jumlah_bibit'] ?? 0);
    $harga_bibit   = (float)($_POST['harga_bibit'] ?? 0);
    $ukuran_kolam  = $_POST['ukuran_kolam'] !== '' ? (float)$_POST['ukuran_kolam'] : null;
    $jenis_kolam   = trim($_POST['jenis_kolam'] ?? '');
    $lokasi_kolam  = trim($_POST['lokasi_kolam'] ?? '');
    $kondisi_air   = trim($_POST['kondisi_air'] ?? 'baik');
    $kejernihan_air= trim($_POST['kejernihan_air'] ?? 'jernih');
    $catatan       = trim($_POST['catatan'] ?? '');

    if ($nama_kolam === '') $errors[] = 'Nama kolam wajib diisi.';
    if ($jenis_ikan === '') $errors[] = 'Jenis ikan wajib diisi.';
    if ($tanggal_tebar === '') $errors[] = 'Tanggal tebar wajib diisi.';
    if ($jumlah_bibit <= 0) $errors[] = 'Jumlah bibit harus lebih dari 0.';
    if ($harga_bibit < 0) $errors[] = 'Harga bibit tidak boleh negatif.';

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO data_kolam_bibit
            (user_id, nama_kolam, jenis_ikan, tanggal_tebar, jumlah_bibit, harga_bibit, ukuran_kolam, jenis_kolam, lokasi_kolam, kondisi_air, kejernihan_air, catatan)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $nama_kolam, $jenis_ikan, $tanggal_tebar, $jumlah_bibit, $harga_bibit, $ukuran_kolam, $jenis_kolam, $lokasi_kolam, $kondisi_air, $kejernihan_air, $catatan]);
        setFlash('success', 'Data bibit dan kolam berhasil disimpan.');
        redirect(APP_URL . '/pencatatan/data-kolam-bibit.php');
    }
}

$stmt = $pdo->prepare("SELECT * FROM data_kolam_bibit WHERE user_id = ? ORDER BY created_at DESC, id DESC");
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="pc-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="pc-main">
        <section class="pc-hero"><h1>Data Bibit & Kolam Ikan</h1><p>Pencatatan budidaya ikan</p></section>
        <section class="pc-card pc-form-card">
            <h2>Input Data Bibit & Kolam Ikan</h2>
            <p class="text-muted">Silakan masukkan detail data hasil panen terbaru Anda.</p>
            <?php if ($errors): ?><div class="pc-alert pc-alert-red"><?= implode('<br>', array_map('clean', $errors)) ?></div><?php endif; ?>
            <form method="POST" class="pc-form">
                <div><label>Nama Kolam</label><input name="nama_kolam" class="form-control" placeholder="Contoh: Kolam A" required></div>
                <div><label>Jumlah Bibit</label><input name="jumlah_bibit" type="number" min="1" class="form-control" placeholder="Contoh: 1000" required></div>
                <div><label>Tanggal</label><input name="tanggal_tebar" type="date" class="form-control" required></div>
                <div><label>Jenis Ikan</label><input name="jenis_ikan" class="form-control" placeholder="Contoh: Ikan Nila" required></div>
                <div><label>Harga Bibit / Ekor</label><input name="harga_bibit" type="number" min="0" step="100" class="form-control" placeholder="Contoh: 500"></div>
                <div><label>Ukuran Kolam (m²)</label><input name="ukuran_kolam" type="number" min="0" step="0.1" class="form-control" placeholder="Contoh: 12"></div>
                <div><label>Jenis Kolam</label><select name="jenis_kolam" class="form-select"><option value="">Pilih jenis kolam</option><option>Tanah</option><option>Terpal</option><option>Beton</option><option>Keramba</option></select></div>
                <div><label>Kondisi Air</label><select name="kondisi_air" class="form-select"><option value="baik">Baik</option><option value="cukup">Cukup</option><option value="buruk">Buruk</option></select></div>
                <div><label>Kejernihan Air</label><select name="kejernihan_air" class="form-select"><option value="jernih">Jernih</option><option value="keruh">Keruh</option><option value="sangat keruh">Sangat Keruh</option></select></div>
                <div><label>Lokasi Kolam</label><input name="lokasi_kolam" class="form-control" placeholder="Contoh: Belakang rumah"></div>
                <div class="pc-full"><label>Catatan</label><textarea name="catatan" class="form-control" rows="3" placeholder="Catatan tambahan..."></textarea></div>
                <button class="pc-btn" type="submit"><i class="bi bi-save"></i> Simpan</button>
            </form>
        </section>
        <div class="pc-alert pc-alert-blue"><i class="bi bi-info-circle"></i> Pastikan data yang diinput sudah sesuai.</div>
        <section class="pc-card mt-4">
            <h2>Riwayat Data Bibit & Kolam</h2>
            <div class="table-responsive">
                <table class="table pc-table align-middle">
                    <thead><tr><th>Kolam</th><th>Jenis Ikan</th><th>Tanggal</th><th>Bibit</th><th>Harga/Ekor</th><th>Modal Bibit</th><th>Kondisi</th></tr></thead>
                    <tbody>
                    <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada data.</td></tr><?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><b><?= clean($r['nama_kolam']) ?></b><br><small><?= clean($r['jenis_kolam'] ?: '-') ?></small></td>
                            <td><?= clean($r['jenis_ikan']) ?></td>
                            <td><?= date('d/m/Y', strtotime($r['tanggal_tebar'])) ?></td>
                            <td><?= number_format((int)$r['jumlah_bibit'],0,',','.') ?></td>
                            <td><?= rupiah((float)$r['harga_bibit']) ?></td>
                            <td><?= rupiah((float)$r['total_modal_bibit']) ?></td>
                            <td><span class="pc-badge"><?= clean($r['kondisi_air']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
