<?php
/**
 * GEMPITA v2 - Pencatatan: Data Kolam & Bibit
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$pageTitle = 'Data Kolam & Bibit';
$extraCss  = '<link rel="stylesheet" href="' . APP_URL . '/pencatatan/pencatatan.css">';
$user      = currentUser();
$uid       = $user['id'];
$db        = getDB();
$errors    = [];

// ── Handle DELETE ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    $del_id = (int)($_POST['del_id'] ?? 0);
    if ($del_id > 0) {
        $chk = $db->prepare("SELECT id FROM data_kolam_bibit WHERE id = ? AND user_id = ?");
        $chk->execute([$del_id, $uid]);
        if ($chk->fetch()) {
            $db->prepare("DELETE FROM data_kolam_bibit WHERE id = ? AND user_id = ?")
               ->execute([$del_id, $uid]);
            setFlash('success', 'Kolam berhasil dihapus.');
        }
    }
    header('Location: kolam.php'); exit;
}

// ── Handle TOGGLE AKTIF ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'toggle') {
    $tog_id = (int)($_POST['tog_id'] ?? 0);
    if ($tog_id > 0) {
        $db->prepare("UPDATE data_kolam_bibit SET is_aktif = NOT is_aktif WHERE id = ? AND user_id = ?")
           ->execute([$tog_id, $uid]);
        setFlash('success', 'Status kolam diperbarui.');
    }
    header('Location: kolam.php'); exit;
}

// ── Handle SAVE (insert / update) ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'save') {
    $edit_id      = (int)($_POST['edit_id']      ?? 0);
    $nama_kolam   = trim($_POST['nama_kolam']     ?? '');
    $jenis_ikan   = trim($_POST['jenis_ikan']     ?? '');
    $tanggal      = trim($_POST['tanggal_tebar']  ?? '');
    $jumlah_bibit = (int)($_POST['jumlah_bibit']  ?? 0);
    $harga_bibit  = (float)str_replace(['.', ','], ['', '.'], $_POST['harga_bibit'] ?? '0');
    $ukuran       = (float)($_POST['ukuran_kolam'] ?? 0);
    $jenis_kolam  = trim($_POST['jenis_kolam']    ?? '');
    $lokasi       = trim($_POST['lokasi_kolam']   ?? '');
    $kondisi      = trim($_POST['kondisi_air']    ?? 'baik');
    $kejernihan   = trim($_POST['kejernihan_air'] ?? 'jernih');
    $catatan      = trim($_POST['catatan']        ?? '');

    if (!$nama_kolam)      $errors[] = 'Nama kolam wajib diisi.';
    if (!$jenis_ikan)      $errors[] = 'Jenis ikan wajib diisi.';
    if (!$tanggal)         $errors[] = 'Tanggal tebar wajib diisi.';
    if ($jumlah_bibit < 1) $errors[] = 'Jumlah bibit harus lebih dari 0.';
    if ($harga_bibit < 0)  $errors[] = 'Harga bibit tidak boleh negatif.';

    if (empty($errors)) {
        if ($edit_id > 0) {
            $chk = $db->prepare("SELECT id FROM data_kolam_bibit WHERE id=? AND user_id=?");
            $chk->execute([$edit_id, $uid]);
            if ($chk->fetch()) {
                $db->prepare("
                    UPDATE data_kolam_bibit SET
                        nama_kolam=?, jenis_ikan=?, tanggal_tebar=?, jumlah_bibit=?,
                        harga_bibit=?, ukuran_kolam=?, jenis_kolam=?, lokasi_kolam=?,
                        kondisi_air=?, kejernihan_air=?, catatan=?
                    WHERE id=? AND user_id=?
                ")->execute([
                    $nama_kolam, $jenis_ikan, $tanggal, $jumlah_bibit,
                    $harga_bibit,
                    $ukuran    ?: null, $jenis_kolam ?: null, $lokasi  ?: null,
                    $kondisi, $kejernihan, $catatan ?: null,
                    $edit_id, $uid
                ]);
                setFlash('success', 'Data kolam berhasil diperbarui.');
            }
        } else {
            $db->prepare("
                INSERT INTO data_kolam_bibit
                    (user_id, nama_kolam, jenis_ikan, tanggal_tebar, jumlah_bibit,
                     harga_bibit, ukuran_kolam, jenis_kolam, lokasi_kolam,
                     kondisi_air, kejernihan_air, catatan)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $uid, $nama_kolam, $jenis_ikan, $tanggal, $jumlah_bibit,
                $harga_bibit,
                $ukuran ?: null, $jenis_kolam ?: null, $lokasi ?: null,
                $kondisi, $kejernihan, $catatan ?: null
            ]);
            setFlash('success', 'Data kolam & bibit berhasil disimpan!');
        }
        header('Location: kolam.php'); exit;
    }
}

// ── Mode edit ────────────────────────────────────────────────
$editData = null;
if (!empty($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM data_kolam_bibit WHERE id=? AND user_id=?");
    $stmt->execute([(int)$_GET['edit'], $uid]);
    $editData = $stmt->fetch() ?: null;
}

// ── Daftar kolam user ────────────────────────────────────────
$stmt = $db->prepare("
    SELECT k.*,
           COALESCE((SELECT SUM(total_biaya)
                     FROM data_pakan WHERE kolam_id=k.id), 0)       AS modal_pakan,
           COALESCE((SELECT SUM(total_pendapatan)
                     FROM hasil_panen WHERE kolam_id=k.id), 0)      AS pendapatan,
           (SELECT COUNT(*) FROM data_pakan WHERE kolam_id=k.id)    AS jml_pakan,
           (SELECT COUNT(*) FROM hasil_panen WHERE kolam_id=k.id)   AS jml_panen
    FROM data_kolam_bibit k
    WHERE k.user_id = ?
    ORDER BY k.created_at DESC
");
$stmt->execute([$uid]);
$kolams = $stmt->fetchAll();

// Hitung total modal bibit untuk preview form
$preview_modal = 0;
if ($editData) {
    $preview_modal = (float)$editData['jumlah_bibit'] * (float)$editData['harga_bibit'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="pnc-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="pnc-main">

        <!-- Page Header -->
        <div class="pnc-page-header">
            <div>
                <h1 class="pnc-page-title">Data Bibit &amp; Kolam Ikan</h1>
                <p class="pnc-page-sub">Pencatatan Budidaya Ikan</p>
            </div>
            <?php if (!empty($kolams) && !$editData): ?>
            <span class="pnc-badge pnc-badge-blue" style="font-size:.8rem;padding:.3rem .9rem;">
                <?= count($kolams) ?> Kolam Tercatat
            </span>
            <?php endif; ?>
        </div>

        <!-- Form Input / Edit -->
        <div class="pnc-card">
            <div class="pnc-card-header">
                <h2 class="pnc-card-title">
                    <?= $editData ? 'Edit Data Kolam: ' . clean($editData['nama_kolam']) : 'Input Data Bibit & Kolam Ikan' ?>
                </h2>
                <?php if ($editData): ?>
                <a href="kolam.php" class="btn-pnc-outline" style="padding:.35rem .9rem;font-size:.8rem;">
                    <i class="bi bi-x-lg me-1"></i>Batal Edit
                </a>
                <?php endif; ?>
            </div>
            <div class="pnc-card-body">
                <p class="pnc-form-intro">Silakan masukkan detail data bibit panen terbaru Anda.</p>

                <?php if (!empty($errors)): ?>
                <div class="pnc-alert pnc-alert-danger">
                    <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:.1rem;"></i>
                    <div><?= implode('<br>', array_map('clean', $errors)) ?></div>
                </div>
                <?php endif; ?>

                <form method="post" action="kolam.php" id="formKolam">
                    <input type="hidden" name="_action" value="save">
                    <?php if ($editData): ?>
                    <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
                    <?php endif; ?>

                    <div class="pnc-form-grid">

                        <!-- Nama Kolam -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Nama Kolam <span style="color:var(--red)">*</span></label>
                            <input type="text" name="nama_kolam" class="pnc-input"
                                   placeholder="Contoh: Kolam A"
                                   value="<?= clean($editData['nama_kolam'] ?? '') ?>" required>
                        </div>

                        <!-- Jenis Ikan -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Jenis Ikan <span style="color:var(--red)">*</span></label>
                            <input type="text" name="jenis_ikan" class="pnc-input"
                                   placeholder="Contoh: Ikan Nila"
                                   value="<?= clean($editData['jenis_ikan'] ?? '') ?>"
                                   list="listJenisIkan" required>
                            <datalist id="listJenisIkan">
                                <option value="Ikan Nila">
                                <option value="Ikan Lele">
                                <option value="Ikan Gurame">
                                <option value="Ikan Mas">
                                <option value="Ikan Patin">
                                <option value="Ikan Bawal">
                            </datalist>
                        </div>

                        <!-- Jumlah Bibit -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Jumlah Bibit (ekor) <span style="color:var(--red)">*</span></label>
                            <input type="number" name="jumlah_bibit" class="pnc-input"
                                   id="inputJumlahBibit"
                                   placeholder="Contoh: 1000" min="1"
                                   value="<?= $editData['jumlah_bibit'] ?? '' ?>"
                                   oninput="hitungModalBibit()" required>
                        </div>

                        <!-- Harga Bibit per ekor -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Harga Bibit / ekor (Rp)</label>
                            <input type="number" name="harga_bibit" class="pnc-input"
                                   id="inputHargaBibit"
                                   placeholder="Contoh: 500" min="0" step="1"
                                   value="<?= $editData['harga_bibit'] ?? '0' ?>"
                                   oninput="hitungModalBibit()">
                        </div>

                        <!-- Total Modal Bibit (readonly, otomatis) -->
                        <div class="pnc-form-group pnc-form-full">
                            <label class="pnc-label">Total Modal Bibit (otomatis)</label>
                            <input type="text" id="previewModalBibit" class="pnc-input pnc-input-readonly"
                                   readonly value="<?= $preview_modal > 0 ? rupiah($preview_modal) : 'Rp 0' ?>">
                        </div>

                        <!-- Tanggal Tebar -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Tanggal Tebar <span style="color:var(--red)">*</span></label>
                            <input type="date" name="tanggal_tebar" class="pnc-input"
                                   value="<?= $editData['tanggal_tebar'] ?? date('Y-m-d') ?>" required>
                        </div>

                        <!-- Jenis Kolam -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Jenis Kolam</label>
                            <select name="jenis_kolam" class="pnc-select">
                                <option value="">-- Pilih Jenis Kolam --</option>
                                <?php foreach (['tanah','terpal','beton','keramba'] as $jk): ?>
                                <option value="<?= $jk ?>"
                                    <?= ($editData['jenis_kolam'] ?? '') === $jk ? 'selected' : '' ?>>
                                    <?= ucfirst($jk) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Ukuran Kolam -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Ukuran Kolam (m²)</label>
                            <input type="number" name="ukuran_kolam" class="pnc-input"
                                   placeholder="Contoh: 20" min="0" step="0.1"
                                   value="<?= $editData['ukuran_kolam'] ?? '' ?>">
                        </div>

                        <!-- Kondisi Air -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Kondisi Air</label>
                            <select name="kondisi_air" class="pnc-select">
                                <?php foreach (['baik','cukup','buruk'] as $ko): ?>
                                <option value="<?= $ko ?>"
                                    <?= ($editData['kondisi_air'] ?? 'baik') === $ko ? 'selected' : '' ?>>
                                    <?= ucfirst($ko) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Lokasi Kolam -->
                        <div class="pnc-form-group pnc-form-full">
                            <label class="pnc-label">Lokasi Kolam</label>
                            <input type="text" name="lokasi_kolam" class="pnc-input"
                                   placeholder="Contoh: Desa Cimanggu, Kec. Cibinong, Bogor"
                                   value="<?= clean($editData['lokasi_kolam'] ?? '') ?>">
                        </div>

                        <!-- Catatan -->
                        <div class="pnc-form-group pnc-form-full">
                            <label class="pnc-label">Catatan (opsional)</label>
                            <textarea name="catatan" class="pnc-textarea" rows="3"
                                      placeholder="Catatan tambahan tentang kolam ini..."><?= clean($editData['catatan'] ?? '') ?></textarea>
                        </div>

                    </div><!-- /pnc-form-grid -->

                    <div class="pnc-form-actions">
                        <button type="submit" class="btn-pnc-primary btn-pnc-lg">
                            <i class="bi bi-floppy-fill"></i>
                            <?= $editData ? 'Perbarui Data' : 'Simpan' ?>
                        </button>
                        <?php if ($editData): ?>
                        <a href="kolam.php" class="btn-pnc-outline">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="pnc-form-note">
                    <i class="bi bi-info-circle"></i>
                    Pastikan data yang diinput sudah sesuai
                </div>
            </div>
        </div>

        <!-- Daftar Kolam -->
        <?php if (!empty($kolams)): ?>
        <div class="pnc-card mt-4">
            <div class="pnc-card-header">
                <h2 class="pnc-card-title">Daftar Kolam Anda</h2>
                <span class="pnc-badge pnc-badge-blue"><?= count($kolams) ?> Kolam</span>
            </div>
            <div class="pnc-table-wrap">
                <table class="pnc-table">
                    <thead>
                        <tr>
                            <th>Nama Kolam</th>
                            <th>Jenis Ikan</th>
                            <th>Tgl Tebar</th>
                            <th class="text-end">Modal Bibit</th>
                            <th class="text-end">Modal Pakan</th>
                            <th class="text-end">Pendapatan</th>
                            <th>Catatan</th>
                            <th>Status</th>
                            <th style="width:90px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($kolams as $k):
                        $modal_total = (float)$k['total_modal_bibit'] + (float)$k['modal_pakan'];
                        $pend        = (float)$k['pendapatan'];
                        $unt         = $pend - $modal_total;
                    ?>
                    <tr>
                        <td>
                            <div class="pnc-td-title"><?= clean($k['nama_kolam']) ?></div>
                            <div class="pnc-td-sub">
                                <?= $k['jumlah_bibit'] ?> ekor
                                <?= $k['jenis_kolam'] ? '· ' . ucfirst(clean($k['jenis_kolam'])) : '' ?>
                                <?php if ($k['ukuran_kolam']): ?>
                                · <?= $k['ukuran_kolam'] ?> m²
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= clean($k['jenis_ikan']) ?></td>
                        <td class="pnc-td-date">
                            <?= date('d M Y', strtotime($k['tanggal_tebar'])) ?>
                        </td>
                        <td class="text-end text-red fw-600">
                            <?= rupiah((float)$k['total_modal_bibit']) ?>
                            <div class="pnc-td-sub text-end">
                                <?= number_format($k['harga_bibit'], 0, ',', '.') ?>/ekor
                            </div>
                        </td>
                        <td class="text-end text-red">
                            <?= rupiah((float)$k['modal_pakan']) ?>
                            <div class="pnc-td-sub text-end"><?= $k['jml_pakan'] ?> entri</div>
                        </td>
                        <td class="text-end text-green fw-600">
                            <?= rupiah($pend) ?>
                            <div class="pnc-td-sub text-end <?= $unt >= 0 ? 'text-green' : 'text-red' ?>">
                                <?= $unt >= 0 ? '+' : '' ?><?= rupiah($unt) ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($k['kondisi_air']): ?>
                            <span class="pnc-badge pnc-badge-<?= $k['kondisi_air']==='baik'?'green':($k['kondisi_air']==='cukup'?'yellow':'red') ?>" style="font-size:.65rem;">
                                Air <?= $k['kondisi_air'] ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($k['lokasi_kolam']): ?>
                            <div class="pnc-td-sub mt-1"><?= clean(mb_substr($k['lokasi_kolam'], 0, 30)) ?>...</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="pnc-kolam-status <?= $k['is_aktif'] ? 'aktif' : 'nonaktif' ?>">
                                <?= $k['is_aktif'] ? 'Aktif' : 'Selesai' ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <!-- Edit -->
                                <a href="kolam.php?edit=<?= $k['id'] ?>"
                                   class="btn-pnc-action btn-edit" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <!-- Toggle aktif/selesai -->
                                <form method="post" action="kolam.php" style="display:inline;">
                                    <input type="hidden" name="_action" value="toggle">
                                    <input type="hidden" name="tog_id" value="<?= $k['id'] ?>">
                                    <button type="submit" class="btn-pnc-action btn-edit"
                                            title="<?= $k['is_aktif'] ? 'Tandai Selesai' : 'Aktifkan Kembali' ?>">
                                        <i class="bi <?= $k['is_aktif'] ? 'bi-check2-circle' : 'bi-arrow-counterclockwise' ?>"></i>
                                    </button>
                                </form>
                                <!-- Hapus -->
                                <form method="post" action="kolam.php" style="display:inline;"
                                      onsubmit="return confirm('Hapus kolam <?= clean($k['nama_kolam']) ?>?\nData pakan & panen terkait juga akan terhapus!')">
                                    <input type="hidden" name="_action" value="delete">
                                    <input type="hidden" name="del_id"  value="<?= $k['id'] ?>">
                                    <button type="submit" class="btn-pnc-action btn-del" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="pnc-card mt-4">
            <div class="pnc-empty">
                <i class="bi bi-water"></i>
                <p>Belum ada kolam tercatat. Isi form di atas untuk menambahkan kolam pertama Anda.</p>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php
$extraJs = '<script>
function hitungModalBibit() {
    var jumlah = parseInt(document.getElementById("inputJumlahBibit").value) || 0;
    var harga  = parseFloat(document.getElementById("inputHargaBibit").value) || 0;
    var total  = jumlah * harga;
    document.getElementById("previewModalBibit").value =
        "Rp " + total.toLocaleString("id-ID");
}
// Jalankan saat halaman load (mode edit)
hitungModalBibit();
</script>';
include __DIR__ . '/../includes/footer.php';
?>
