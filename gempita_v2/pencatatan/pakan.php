<?php
/**
 * GEMPITA v2 - Pencatatan: Data Pakan
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$pageTitle = 'Data Pakan';
$extraCss  = '<link rel="stylesheet" href="' . APP_URL . '/pencatatan/pencatatan.css">';
$user      = currentUser();
$uid       = $user['id'];
$db        = getDB();
$errors    = [];

// ── Kolam aktif milik user (untuk select) ────────────────────
$stmtK = $db->prepare("
    SELECT id, nama_kolam, jenis_ikan
    FROM data_kolam_bibit
    WHERE user_id = ? AND is_aktif = TRUE
    ORDER BY nama_kolam
");
$stmtK->execute([$uid]);
$kolams = $stmtK->fetchAll();

// ── Handle DELETE ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    $del_id = (int)($_POST['del_id'] ?? 0);
    if ($del_id > 0) {
        $db->prepare("DELETE FROM data_pakan WHERE id = ? AND user_id = ?")
           ->execute([$del_id, $uid]);
        setFlash('success', 'Data pakan berhasil dihapus.');
    }
    header('Location: pakan.php'); exit;
}

// ── Handle SAVE ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'save') {
    $edit_id      = (int)($_POST['edit_id']      ?? 0);
    $kolam_id     = (int)($_POST['kolam_id']     ?? 0);
    $tanggal      = trim($_POST['tanggal']        ?? '');
    $jenis_pakan  = trim($_POST['jenis_pakan']   ?? '');
    $merek        = trim($_POST['merek']          ?? '');
    $jumlah_kg    = (float)str_replace(',', '.', $_POST['jumlah_kg']    ?? '0');
    $harga_per_kg = (float)str_replace(',', '.', $_POST['harga_per_kg'] ?? '0');
    $catatan      = trim($_POST['catatan']        ?? '');

    // Validasi
    if (!$kolam_id)        $errors[] = 'Pilih kolam terlebih dahulu.';
    if (!$tanggal)         $errors[] = 'Tanggal wajib diisi.';
    if (!$jenis_pakan)     $errors[] = 'Jenis pakan wajib diisi.';
    if ($jumlah_kg <= 0)   $errors[] = 'Jumlah pakan harus lebih dari 0.';
    if ($harga_per_kg < 0) $errors[] = 'Harga tidak boleh negatif.';

    // Pastikan kolam milik user
    if ($kolam_id) {
        $chkK = $db->prepare("SELECT id FROM data_kolam_bibit WHERE id = ? AND user_id = ?");
        $chkK->execute([$kolam_id, $uid]);
        if (!$chkK->fetch()) $errors[] = 'Kolam tidak valid.';
    }

    if (empty($errors)) {
        if ($edit_id > 0) {
            $db->prepare("
                UPDATE data_pakan SET
                    kolam_id=?, tanggal=?, jenis_pakan=?, merek=?,
                    jumlah_kg=?, harga_per_kg=?, catatan=?
                WHERE id=? AND user_id=?
            ")->execute([
                $kolam_id, $tanggal, $jenis_pakan, $merek ?: null,
                $jumlah_kg, $harga_per_kg, $catatan ?: null,
                $edit_id, $uid
            ]);
            setFlash('success', 'Data pakan berhasil diperbarui.');
        } else {
            $db->prepare("
                INSERT INTO data_pakan
                    (user_id, kolam_id, tanggal, jenis_pakan, merek, jumlah_kg, harga_per_kg, catatan)
                VALUES (?,?,?,?,?,?,?,?)
            ")->execute([
                $uid, $kolam_id, $tanggal, $jenis_pakan,
                $merek ?: null, $jumlah_kg, $harga_per_kg, $catatan ?: null
            ]);
            setFlash('success', 'Data pakan berhasil disimpan!');
        }
        header('Location: pakan.php'); exit;
    }
}

// ── Mode edit ────────────────────────────────────────────────
$editData = null;
if (!empty($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM data_pakan WHERE id = ? AND user_id = ?");
    $stmt->execute([(int)$_GET['edit'], $uid]);
    $editData = $stmt->fetch() ?: null;
}

// ── Daftar pakan milik user ──────────────────────────────────
$stmtP = $db->prepare("
    SELECT p.*, k.nama_kolam, k.jenis_ikan
    FROM data_pakan p
    JOIN data_kolam_bibit k ON k.id = p.kolam_id
    WHERE p.user_id = ?
    ORDER BY p.tanggal DESC, p.created_at DESC
");
$stmtP->execute([$uid]);
$pakans = $stmtP->fetchAll();

// ── Total biaya pakan keseluruhan ────────────────────────────
$totalBiayaPakan = array_sum(array_column($pakans, 'total_biaya'));

// Kolam semua (termasuk nonaktif) untuk filter dropdown
$stmtKAll = $db->prepare("SELECT DISTINCT k.id, k.nama_kolam FROM data_pakan p JOIN data_kolam_bibit k ON k.id=p.kolam_id WHERE p.user_id=? ORDER BY k.nama_kolam");
$stmtKAll->execute([$uid]);
$kolamFilter = $stmtKAll->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="pnc-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="pnc-main">

        <!-- Page Header -->
        <div class="pnc-page-header">
            <div>
                <h1 class="pnc-page-title">Data Pakan</h1>
                <p class="pnc-page-sub">Pencatatan Budidaya Ikan</p>
            </div>
            <?php if ($totalBiayaPakan > 0): ?>
            <div class="pnc-stat-pill">
                <span class="pnc-stat-pill-label">Total Biaya Pakan</span>
                <span class="pnc-stat-pill-val text-red"><?= rupiah($totalBiayaPakan) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Form Input / Edit -->
        <div class="pnc-card">
            <div class="pnc-card-header">
                <h2 class="pnc-card-title">
                    <?= $editData ? 'Edit Data Pakan' : 'Input Data Pakan' ?>
                </h2>
                <?php if ($editData): ?>
                <a href="pakan.php" class="btn-pnc-outline" style="padding:.35rem .9rem;font-size:.8rem;">
                    <i class="bi bi-x-lg me-1"></i>Batal Edit
                </a>
                <?php endif; ?>
            </div>
            <div class="pnc-card-body">
                <p class="pnc-form-intro">Silakan masukkan detail data pakan terbaru Anda.</p>

                <?php if (!empty($errors)): ?>
                <div class="pnc-alert pnc-alert-danger">
                    <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:.1rem;"></i>
                    <div><?= implode('<br>', array_map('clean', $errors)) ?></div>
                </div>
                <?php endif; ?>

                <?php if (empty($kolams) && !$editData): ?>
                <div class="pnc-alert pnc-alert-info">
                    <i class="bi bi-info-circle-fill" style="flex-shrink:0;"></i>
                    <div>Anda belum memiliki kolam aktif.
                        <a href="kolam.php" class="fw-600">Tambah kolam dulu →</a>
                    </div>
                </div>
                <?php else: ?>

                <form method="post" action="pakan.php">
                    <input type="hidden" name="_action" value="save">
                    <?php if ($editData): ?>
                    <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
                    <?php endif; ?>

                    <div class="pnc-form-grid">

                        <!-- Pilih Kolam -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Pilih Kolam <span style="color:var(--red)">*</span></label>
                            <select name="kolam_id" class="pnc-select" required>
                                <option value="">-- Pilih Kolam --</option>
                                <?php
                                // Saat edit, tampilkan kolam manapun (aktif/nonaktif)
                                $listKolam = $kolams;
                                if ($editData && empty($kolams)) {
                                    $tmpK = $db->prepare("SELECT id, nama_kolam, jenis_ikan FROM data_kolam_bibit WHERE id=? AND user_id=?");
                                    $tmpK->execute([$editData['kolam_id'], $uid]);
                                    $listKolam = $tmpK->fetchAll();
                                }
                                foreach ($listKolam as $k):
                                ?>
                                <option value="<?= $k['id'] ?>"
                                    <?= ($editData['kolam_id'] ?? '') == $k['id'] ? 'selected' : '' ?>>
                                    <?= clean($k['nama_kolam']) ?> (<?= clean($k['jenis_ikan']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tanggal -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Tanggal <span style="color:var(--red)">*</span></label>
                            <input type="date" name="tanggal" class="pnc-input"
                                   value="<?= $editData['tanggal'] ?? date('Y-m-d') ?>" required>
                        </div>

                        <!-- Jenis Pakan -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Jenis Pakan <span style="color:var(--red)">*</span></label>
                            <select name="jenis_pakan" class="pnc-select" required>
                                <option value="">-- Pilih Pakan --</option>
                                <?php
                                $daftarPakan = ['Pelet Apung','Pelet Tenggelam','Cacing','Jangkrik','Dedak','Ampas Tahu','Lainnya'];
                                foreach ($daftarPakan as $jp):
                                    $sel = ($editData['jenis_pakan'] ?? '') === $jp ? 'selected' : '';
                                ?>
                                <option value="<?= $jp ?>" <?= $sel ?>><?= $jp ?></option>
                                <?php endforeach; ?>
                                <?php if ($editData && !in_array($editData['jenis_pakan'], $daftarPakan)): ?>
                                <option value="<?= clean($editData['jenis_pakan']) ?>" selected>
                                    <?= clean($editData['jenis_pakan']) ?>
                                </option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Merek -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Merek (opsional)</label>
                            <input type="text" name="merek" class="pnc-input"
                                   placeholder="Contoh: Comfeed, Hi-Pro, dll"
                                   value="<?= clean($editData['merek'] ?? '') ?>">
                        </div>

                        <!-- Jumlah kg -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Jumlah (kg) <span style="color:var(--red)">*</span></label>
                            <input type="number" name="jumlah_kg" class="pnc-input"
                                   id="inputJumlahKg"
                                   placeholder="Contoh: 5" min="0.1" step="0.1"
                                   value="<?= $editData['jumlah_kg'] ?? '' ?>"
                                   oninput="hitungTotal()" required>
                        </div>

                        <!-- Harga per kg -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Harga / kg (Rp)</label>
                            <input type="number" name="harga_per_kg" class="pnc-input"
                                   id="inputHargaKg"
                                   placeholder="Contoh: 8000" min="0" step="100"
                                   value="<?= $editData['harga_per_kg'] ?? '0' ?>"
                                   oninput="hitungTotal()">
                        </div>

                        <!-- Total Biaya (readonly) -->
                        <div class="pnc-form-group pnc-form-full">
                            <label class="pnc-label">Total Biaya (otomatis)</label>
                            <input type="text" id="previewTotalBiaya" class="pnc-input pnc-input-readonly"
                                   readonly
                                   value="<?php
                                       if ($editData)
                                           echo rupiah((float)$editData['jumlah_kg'] * (float)$editData['harga_per_kg']);
                                       else echo 'Rp 0';
                                   ?>">
                        </div>

                        <!-- Catatan -->
                        <div class="pnc-form-group pnc-form-full">
                            <label class="pnc-label">Catatan (opsional)</label>
                            <textarea name="catatan" class="pnc-textarea" rows="2"
                                      placeholder="Catatan tambahan..."><?= clean($editData['catatan'] ?? '') ?></textarea>
                        </div>

                    </div><!-- /pnc-form-grid -->

                    <div class="pnc-form-actions">
                        <button type="submit" class="btn-pnc-primary btn-pnc-lg">
                            <i class="bi bi-floppy-fill"></i>
                            <?= $editData ? 'Perbarui Data' : 'Simpan' ?>
                        </button>
                        <?php if ($editData): ?>
                        <a href="pakan.php" class="btn-pnc-outline">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
                <?php endif; ?>

                <div class="pnc-form-note">
                    <i class="bi bi-info-circle"></i>
                    Pastikan data yang diinput sudah sesuai
                </div>
            </div>
        </div>

        <!-- Riwayat Pakan -->
        <?php if (!empty($pakans)): ?>
        <div class="pnc-card mt-4">
            <div class="pnc-card-header">
                <h2 class="pnc-card-title">Riwayat Pemberian Pakan</h2>
                <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($kolamFilter)): ?>
                    <select id="filterKolam" class="pnc-select" style="width:auto;font-size:.8rem;padding:.3rem .8rem;"
                            onchange="filterByKolam(this.value)">
                        <option value="">Semua Kolam</option>
                        <?php foreach ($kolamFilter as $kf): ?>
                        <option value="<?= $kf['id'] ?>"><?= clean($kf['nama_kolam']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    <span class="pnc-badge pnc-badge-yellow"><?= count($pakans) ?> Data</span>
                </div>
            </div>
            <div class="pnc-table-wrap">
                <table class="pnc-table" id="tblPakan">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kolam</th>
                            <th>Jenis Pakan</th>
                            <th class="text-end">Jumlah (kg)</th>
                            <th class="text-end">Harga/kg</th>
                            <th class="text-end">Total Biaya</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pakans as $p): ?>
                    <tr data-kolam="<?= $p['kolam_id'] ?>">
                        <td class="pnc-td-date">
                            <?= date('d M Y', strtotime($p['tanggal'])) ?>
                        </td>
                        <td>
                            <div class="pnc-td-title"><?= clean($p['nama_kolam']) ?></div>
                            <div class="pnc-td-sub"><?= clean($p['jenis_ikan']) ?></div>
                        </td>
                        <td>
                            <div class="pnc-td-title"><?= clean($p['jenis_pakan']) ?></div>
                            <?php if ($p['merek']): ?>
                            <div class="pnc-td-sub"><?= clean($p['merek']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-600">
                            <?= number_format((float)$p['jumlah_kg'], 1, ',', '.') ?> kg
                        </td>
                        <td class="text-end"><?= rupiah((float)$p['harga_per_kg']) ?></td>
                        <td class="text-end pnc-td-amount text-red">
                            <?= rupiah((float)$p['total_biaya']) ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="pakan.php?edit=<?= $p['id'] ?>"
                                   class="btn-pnc-action btn-edit" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="pakan.php"
                                      onsubmit="return confirm('Hapus data pakan ini?')">
                                    <input type="hidden" name="_action" value="delete">
                                    <input type="hidden" name="del_id"  value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-pnc-action btn-del" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--bg);">
                            <td colspan="5" class="pnc-td-title text-end" style="padding:.85rem 1rem;">
                                Total Keseluruhan
                            </td>
                            <td class="text-end pnc-td-amount text-red" style="padding:.85rem 1rem;">
                                <?= rupiah($totalBiayaPakan) ?>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="pnc-card mt-4">
            <div class="pnc-empty">
                <i class="bi bi-bag-x"></i>
                <p>Belum ada data pakan tercatat.<br>Isi form di atas untuk mulai mencatat.</p>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php
$extraJs = '<script>
function hitungTotal() {
    var kg    = parseFloat(document.getElementById("inputJumlahKg").value)  || 0;
    var harga = parseFloat(document.getElementById("inputHargaKg").value)   || 0;
    document.getElementById("previewTotalBiaya").value =
        "Rp " + (kg * harga).toLocaleString("id-ID");
}
function filterByKolam(kolamId) {
    document.querySelectorAll("#tblPakan tbody tr").forEach(function(tr) {
        tr.style.display = (!kolamId || tr.dataset.kolam === kolamId) ? "" : "none";
    });
}
hitungTotal();
</script>';
include __DIR__ . '/../includes/footer.php';
?>
