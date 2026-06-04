<?php
/**
 * GEMPITA v2 - Pencatatan: Hasil Panen
 * Termasuk input harga pasaran (harga_pasaran) yang dipakai sebagai acuan harga jual
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$pageTitle = 'Hasil Panen';
$extraCss  = '<link rel="stylesheet" href="' . APP_URL . '/pencatatan/pencatatan.css">';
$user      = currentUser();
$uid       = $user['id'];
$db        = getDB();
$errors    = [];
$tabAktif  = $_GET['tab'] ?? 'panen'; // 'panen' | 'harga'

// ────────────────────────────────────────────────────────────
//  HARGA PASARAN — handle DELETE & SAVE
// ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'del_harga') {
    $id = (int)($_POST['del_id'] ?? 0);
    if ($id) {
        $db->prepare("DELETE FROM harga_pasaran WHERE id=? AND user_id=?")->execute([$id, $uid]);
        setFlash('success', 'Harga pasaran dihapus.');
    }
    header('Location: panen.php?tab=harga'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'save_harga') {
    $edit_h    = (int)($_POST['edit_id']     ?? 0);
    $jenis     = trim($_POST['jenis_ikan']   ?? '');
    $harga     = (float)str_replace(',','.', $_POST['harga_per_kg'] ?? '0');
    $tgl       = trim($_POST['tanggal']      ?? '');
    $sumber    = trim($_POST['sumber']       ?? '');
    $catatan_h = trim($_POST['catatan']      ?? '');

    if (!$jenis)    $errors[] = 'Jenis ikan wajib diisi.';
    if ($harga <= 0) $errors[] = 'Harga per kg harus lebih dari 0.';
    if (!$tgl)      $errors[] = 'Tanggal wajib diisi.';

    if (empty($errors)) {
        if ($edit_h > 0) {
            $db->prepare("UPDATE harga_pasaran SET jenis_ikan=?,harga_per_kg=?,tanggal=?,sumber=?,catatan=? WHERE id=? AND user_id=?")
               ->execute([$jenis,$harga,$tgl,$sumber?:null,$catatan_h?:null,$edit_h,$uid]);
            setFlash('success','Harga pasaran diperbarui.');
        } else {
            $db->prepare("INSERT INTO harga_pasaran (user_id,jenis_ikan,harga_per_kg,tanggal,sumber,catatan) VALUES (?,?,?,?,?,?)")
               ->execute([$uid,$jenis,$harga,$tgl,$sumber?:null,$catatan_h?:null]);
            setFlash('success','Harga pasaran disimpan!');
        }
        header('Location: panen.php?tab=harga'); exit;
    }
    $tabAktif = 'harga';
}

// ────────────────────────────────────────────────────────────
//  HASIL PANEN — handle DELETE & SAVE
// ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'del_panen') {
    $id = (int)($_POST['del_id'] ?? 0);
    if ($id) {
        $db->prepare("DELETE FROM hasil_panen WHERE id=? AND user_id=?")->execute([$id,$uid]);
        setFlash('success','Data panen dihapus.');
    }
    header('Location: panen.php?tab=panen'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'save_panen') {
    $edit_p       = (int)($_POST['edit_id']         ?? 0);
    $kolam_id     = (int)($_POST['kolam_id']        ?? 0);
    $harga_pas_id = (int)($_POST['harga_pasaran_id']?? 0) ?: null;
    $tgl_panen    = trim($_POST['tanggal_panen']    ?? '');
    $total_kg     = (float)str_replace(',','.', $_POST['total_panen_kg']    ?? '0');
    $jml_hidup    = (int)($_POST['jumlah_ikan_hidup']?? 0) ?: null;
    $harga_jual   = (float)str_replace(',','.', $_POST['harga_jual_kg']     ?? '0');
    $pembeli      = trim($_POST['pembeli']           ?? '');
    $jenis_panen  = trim($_POST['jenis_panen']      ?? 'total');
    $catatan_p    = trim($_POST['catatan']           ?? '');

    if (!$kolam_id)   $errors[] = 'Pilih kolam.';
    if (!$tgl_panen)  $errors[] = 'Tanggal panen wajib diisi.';
    if ($total_kg<=0) $errors[] = 'Total panen harus lebih dari 0 kg.';
    if ($harga_jual<0)$errors[] = 'Harga jual tidak boleh negatif.';

    if ($kolam_id) {
        $chkK = $db->prepare("SELECT id FROM data_kolam_bibit WHERE id=? AND user_id=?");
        $chkK->execute([$kolam_id,$uid]);
        if (!$chkK->fetch()) $errors[] = 'Kolam tidak valid.';
    }

    if (empty($errors)) {
        if ($edit_p > 0) {
            $db->prepare("
                UPDATE hasil_panen SET
                    kolam_id=?,harga_pasaran_id=?,tanggal_panen=?,total_panen_kg=?,
                    jumlah_ikan_hidup=?,harga_jual_kg=?,pembeli=?,jenis_panen=?,catatan=?
                WHERE id=? AND user_id=?
            ")->execute([
                $kolam_id,$harga_pas_id,$tgl_panen,$total_kg,
                $jml_hidup,$harga_jual,$pembeli?:null,$jenis_panen,$catatan_p?:null,
                $edit_p,$uid
            ]);
            setFlash('success','Data panen diperbarui.');
        } else {
            $db->prepare("
                INSERT INTO hasil_panen
                    (user_id,kolam_id,harga_pasaran_id,tanggal_panen,total_panen_kg,
                     jumlah_ikan_hidup,harga_jual_kg,pembeli,jenis_panen,catatan)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $uid,$kolam_id,$harga_pas_id,$tgl_panen,$total_kg,
                $jml_hidup,$harga_jual,$pembeli?:null,$jenis_panen,$catatan_p?:null
            ]);
            setFlash('success','Data panen berhasil disimpan!');
        }
        header('Location: panen.php?tab=panen'); exit;
    }
    $tabAktif = 'panen';
}

// ── Edit mode Harga ──────────────────────────────────────────
$editHarga = null;
if (!empty($_GET['edit_h'])) {
    $s = $db->prepare("SELECT * FROM harga_pasaran WHERE id=? AND user_id=?");
    $s->execute([(int)$_GET['edit_h'], $uid]);
    $editHarga = $s->fetch() ?: null;
    $tabAktif  = 'harga';
}

// ── Edit mode Panen ──────────────────────────────────────────
$editPanen = null;
if (!empty($_GET['edit_p'])) {
    $s = $db->prepare("SELECT * FROM hasil_panen WHERE id=? AND user_id=?");
    $s->execute([(int)$_GET['edit_p'], $uid]);
    $editPanen = $s->fetch() ?: null;
    $tabAktif  = 'panen';
}

// ── Data pendukung ───────────────────────────────────────────
// Kolam aktif untuk select panen
$stmtKolam = $db->prepare("SELECT id,nama_kolam,jenis_ikan FROM data_kolam_bibit WHERE user_id=? AND is_aktif=TRUE ORDER BY nama_kolam");
$stmtKolam->execute([$uid]);
$kolams = $stmtKolam->fetchAll();

// Harga pasaran terbaru per jenis (milik user)
$stmtHP = $db->prepare("SELECT * FROM harga_pasaran WHERE user_id=? ORDER BY tanggal DESC, created_at DESC");
$stmtHP->execute([$uid]);
$daftarHarga = $stmtHP->fetchAll();

// Daftar hasil panen
$stmtPN = $db->prepare("
    SELECT hp.*, k.nama_kolam, k.jenis_ikan,
           h.jenis_ikan AS hp_jenis, h.harga_per_kg AS hp_harga,
           -- modal kolam
           k.total_modal_bibit,
           COALESCE((SELECT SUM(total_biaya) FROM data_pakan WHERE kolam_id=k.id),0) AS modal_pakan
    FROM hasil_panen hp
    JOIN data_kolam_bibit k ON k.id = hp.kolam_id
    LEFT JOIN harga_pasaran h ON h.id = hp.harga_pasaran_id
    WHERE hp.user_id = ?
    ORDER BY hp.tanggal_panen DESC, hp.created_at DESC
");
$stmtPN->execute([$uid]);
$panens = $stmtPN->fetchAll();

$totalPendapatan = array_sum(array_column($panens, 'total_pendapatan'));
$totalKg         = array_sum(array_column($panens, 'total_panen_kg'));

include __DIR__ . '/../includes/header.php';
?>

<div class="pnc-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="pnc-main">

        <!-- Page Header -->
        <div class="pnc-page-header">
            <div>
                <h1 class="pnc-page-title">Hasil Panen</h1>
                <p class="pnc-page-sub">Pencatatan Budidaya Ikan</p>
            </div>
            <?php if ($totalPendapatan > 0): ?>
            <div class="pnc-stat-pill">
                <span class="pnc-stat-pill-label">Total Pendapatan</span>
                <span class="pnc-stat-pill-val text-green"><?= rupiah($totalPendapatan) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tab Nav -->
        <div class="pnc-tab-nav">
            <a href="panen.php?tab=panen"
               class="pnc-tab <?= $tabAktif === 'panen' ? 'active' : '' ?>">
                <i class="bi bi-basket3-fill me-1"></i>Hasil Panen
            </a>
            <a href="panen.php?tab=harga"
               class="pnc-tab <?= $tabAktif === 'harga' ? 'active' : '' ?>">
                <i class="bi bi-tags-fill me-1"></i>Harga Pasaran
            </a>
        </div>

        <!-- ══════════════════════════════════════════════════
             TAB: HARGA PASARAN
        ══════════════════════════════════════════════════ -->
        <?php if ($tabAktif === 'harga'): ?>

        <div class="pnc-card">
            <div class="pnc-card-header">
                <h2 class="pnc-card-title">
                    <?= $editHarga ? 'Edit Harga Pasaran' : 'Input Harga Pasaran' ?>
                </h2>
                <?php if ($editHarga): ?>
                <a href="panen.php?tab=harga" class="btn-pnc-outline" style="padding:.35rem .9rem;font-size:.8rem;">
                    <i class="bi bi-x-lg me-1"></i>Batal
                </a>
                <?php endif; ?>
            </div>
            <div class="pnc-card-body">
                <p class="pnc-form-intro">Silakan masukkan detail harga pasaran terbaru.</p>

                <?php if (!empty($errors)): ?>
                <div class="pnc-alert pnc-alert-danger">
                    <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:.1rem;"></i>
                    <div><?= implode('<br>', array_map('clean', $errors)) ?></div>
                </div>
                <?php endif; ?>

                <form method="post" action="panen.php">
                    <input type="hidden" name="_action" value="save_harga">
                    <?php if ($editHarga): ?>
                    <input type="hidden" name="edit_id" value="<?= $editHarga['id'] ?>">
                    <?php endif; ?>

                    <div class="pnc-form-grid">

                        <!-- Jenis Ikan -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Jenis Ikan <span style="color:var(--red)">*</span></label>
                            <input type="text" name="jenis_ikan" class="pnc-input"
                                   placeholder="Contoh: Ikan Nila"
                                   value="<?= clean($editHarga['jenis_ikan'] ?? '') ?>"
                                   list="listIkanHarga" required>
                            <datalist id="listIkanHarga">
                                <option value="Ikan Nila">
                                <option value="Ikan Lele">
                                <option value="Ikan Gurame">
                                <option value="Ikan Mas">
                                <option value="Ikan Patin">
                                <option value="Ikan Bawal">
                            </datalist>
                        </div>

                        <!-- Harga per kg -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Harga per kg (Rp) <span style="color:var(--red)">*</span></label>
                            <input type="number" name="harga_per_kg" class="pnc-input"
                                   placeholder="Contoh: 25000" min="1" step="100"
                                   value="<?= $editHarga['harga_per_kg'] ?? '' ?>" required>
                        </div>

                        <!-- Tanggal -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Tanggal <span style="color:var(--red)">*</span></label>
                            <input type="date" name="tanggal" class="pnc-input"
                                   value="<?= $editHarga['tanggal'] ?? date('Y-m-d') ?>" required>
                        </div>

                        <!-- Sumber -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Sumber Informasi</label>
                            <input type="text" name="sumber" class="pnc-input"
                                   placeholder="Contoh: Pasar Induk Bogor"
                                   value="<?= clean($editHarga['sumber'] ?? '') ?>">
                        </div>

                        <!-- Catatan -->
                        <div class="pnc-form-group pnc-form-full">
                            <label class="pnc-label">Catatan (opsional)</label>
                            <textarea name="catatan" class="pnc-textarea" rows="2"
                                      placeholder="Catatan tambahan..."><?= clean($editHarga['catatan'] ?? '') ?></textarea>
                        </div>

                    </div>

                    <div class="pnc-form-actions">
                        <button type="submit" class="btn-pnc-primary btn-pnc-lg">
                            <i class="bi bi-floppy-fill"></i>
                            <?= $editHarga ? 'Perbarui Harga' : 'Simpan' ?>
                        </button>
                        <?php if ($editHarga): ?>
                        <a href="panen.php?tab=harga" class="btn-pnc-outline">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="pnc-form-note">
                    <i class="bi bi-info-circle"></i>
                    Harga ini akan dipakai sebagai referensi saat input hasil panen
                </div>
            </div>
        </div>

        <!-- Daftar Harga Pasaran -->
        <?php if (!empty($daftarHarga)): ?>
        <div class="pnc-card mt-4">
            <div class="pnc-card-header">
                <h2 class="pnc-card-title">Daftar Harga Pasaran</h2>
                <span class="pnc-badge pnc-badge-blue"><?= count($daftarHarga) ?> Data</span>
            </div>
            <div class="pnc-table-wrap">
                <table class="pnc-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jenis Ikan</th>
                            <th class="text-end">Harga / kg</th>
                            <th>Sumber</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($daftarHarga as $h): ?>
                    <tr>
                        <td class="pnc-td-date"><?= date('d M Y', strtotime($h['tanggal'])) ?></td>
                        <td>
                            <div class="pnc-td-title"><?= clean($h['jenis_ikan']) ?></div>
                            <?php if ($h['catatan']): ?>
                            <div class="pnc-td-sub"><?= clean(mb_substr($h['catatan'],0,40)) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pnc-td-amount text-green">
                            <?= rupiah((float)$h['harga_per_kg']) ?>
                        </td>
                        <td class="pnc-td-sub"><?= clean($h['sumber'] ?? '-') ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="panen.php?edit_h=<?= $h['id'] ?>&tab=harga"
                                   class="btn-pnc-action btn-edit" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="panen.php"
                                      onsubmit="return confirm('Hapus data harga ini?')">
                                    <input type="hidden" name="_action" value="del_harga">
                                    <input type="hidden" name="del_id"  value="<?= $h['id'] ?>">
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
                <i class="bi bi-tags"></i>
                <p>Belum ada harga pasaran. Tambahkan untuk memudahkan input panen.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; // end tab harga ?>

        <!-- ══════════════════════════════════════════════════
             TAB: HASIL PANEN
        ══════════════════════════════════════════════════ -->
        <?php if ($tabAktif === 'panen'): ?>

        <div class="pnc-card">
            <div class="pnc-card-header">
                <h2 class="pnc-card-title">
                    <?= $editPanen ? 'Edit Data Panen' : 'Input Hasil Panen' ?>
                </h2>
                <?php if ($editPanen): ?>
                <a href="panen.php?tab=panen" class="btn-pnc-outline" style="padding:.35rem .9rem;font-size:.8rem;">
                    <i class="bi bi-x-lg me-1"></i>Batal
                </a>
                <?php endif; ?>
            </div>
            <div class="pnc-card-body">
                <p class="pnc-form-intro">Silakan masukkan detail hasil panen terbaru Anda.</p>

                <?php if (!empty($errors)): ?>
                <div class="pnc-alert pnc-alert-danger">
                    <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:.1rem;"></i>
                    <div><?= implode('<br>', array_map('clean', $errors)) ?></div>
                </div>
                <?php endif; ?>

                <?php if (empty($kolams) && !$editPanen): ?>
                <div class="pnc-alert pnc-alert-info">
                    <i class="bi bi-info-circle-fill" style="flex-shrink:0;"></i>
                    <div>Belum ada kolam aktif. <a href="kolam.php" class="fw-600">Tambah kolam →</a></div>
                </div>
                <?php else: ?>

                <form method="post" action="panen.php" id="formPanen">
                    <input type="hidden" name="_action" value="save_panen">
                    <?php if ($editPanen): ?>
                    <input type="hidden" name="edit_id" value="<?= $editPanen['id'] ?>">
                    <?php endif; ?>

                    <div class="pnc-form-grid">

                        <!-- Pilih Kolam -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Pilih Kolam <span style="color:var(--red)">*</span></label>
                            <select name="kolam_id" class="pnc-select" required>
                                <option value="">-- Pilih Kolam --</option>
                                <?php
                                $listK = $kolams;
                                if ($editPanen && empty($kolams)) {
                                    $tmpK = $db->prepare("SELECT id,nama_kolam,jenis_ikan FROM data_kolam_bibit WHERE id=? AND user_id=?");
                                    $tmpK->execute([$editPanen['kolam_id'],$uid]);
                                    $listK = $tmpK->fetchAll();
                                }
                                foreach ($listK as $k):
                                ?>
                                <option value="<?= $k['id'] ?>"
                                    <?= ($editPanen['kolam_id'] ?? '') == $k['id'] ? 'selected' : '' ?>>
                                    <?= clean($k['nama_kolam']) ?> (<?= clean($k['jenis_ikan']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tanggal Panen -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Tanggal Panen <span style="color:var(--red)">*</span></label>
                            <input type="date" name="tanggal_panen" class="pnc-input"
                                   value="<?= $editPanen['tanggal_panen'] ?? date('Y-m-d') ?>" required>
                        </div>

                        <!-- Total Panen (kg) -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Total Panen (kg) <span style="color:var(--red)">*</span></label>
                            <input type="number" name="total_panen_kg" class="pnc-input"
                                   id="inputTotalKg"
                                   placeholder="Contoh: 80" min="0.1" step="0.1"
                                   value="<?= $editPanen['total_panen_kg'] ?? '' ?>"
                                   oninput="hitungPendapatan()" required>
                        </div>

                        <!-- Jumlah Ikan Hidup -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Jumlah Ikan Hidup (ekor)</label>
                            <input type="number" name="jumlah_ikan_hidup" class="pnc-input"
                                   placeholder="Opsional" min="0"
                                   value="<?= $editPanen['jumlah_ikan_hidup'] ?? '' ?>">
                        </div>

                        <!-- Harga Pasaran Referensi -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Referensi Harga Pasaran</label>
                            <select name="harga_pasaran_id" class="pnc-select" id="selectHargaPas"
                                    onchange="pakaiHargaPasaran(this)">
                                <option value="">-- Pilih atau isi manual --</option>
                                <?php foreach ($daftarHarga as $h): ?>
                                <option value="<?= $h['id'] ?>"
                                        data-harga="<?= $h['harga_per_kg'] ?>"
                                    <?= ($editPanen['harga_pasaran_id'] ?? '') == $h['id'] ? 'selected' : '' ?>>
                                    <?= clean($h['jenis_ikan']) ?> —
                                    <?= rupiah((float)$h['harga_per_kg']) ?>/kg
                                    (<?= date('d M Y', strtotime($h['tanggal'])) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Harga Jual per kg -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Harga Jual / kg (Rp) <span style="color:var(--red)">*</span></label>
                            <input type="number" name="harga_jual_kg" class="pnc-input"
                                   id="inputHargaJual"
                                   placeholder="Contoh: 25000" min="0" step="100"
                                   value="<?= $editPanen['harga_jual_kg'] ?? '' ?>"
                                   oninput="hitungPendapatan()" required>
                        </div>

                        <!-- Total Pendapatan (readonly) -->
                        <div class="pnc-form-group pnc-form-full">
                            <label class="pnc-label">Total Pendapatan (otomatis)</label>
                            <input type="text" id="previewPendapatan" class="pnc-input pnc-input-readonly"
                                   readonly
                                   value="<?php
                                       if ($editPanen)
                                           echo rupiah((float)$editPanen['total_panen_kg'] * (float)$editPanen['harga_jual_kg']);
                                       else echo 'Rp 0';
                                   ?>">
                        </div>

                        <!-- Jenis Panen -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Jenis Panen</label>
                            <select name="jenis_panen" class="pnc-select">
                                <option value="total"    <?= ($editPanen['jenis_panen'] ?? 'total') === 'total'    ? 'selected':'' ?>>Total (Panen Habis)</option>
                                <option value="sebagian" <?= ($editPanen['jenis_panen'] ?? '')      === 'sebagian' ? 'selected':'' ?>>Sebagian</option>
                            </select>
                        </div>

                        <!-- Pembeli -->
                        <div class="pnc-form-group">
                            <label class="pnc-label">Pembeli / Tujuan</label>
                            <input type="text" name="pembeli" class="pnc-input"
                                   placeholder="Contoh: Pengepul, Pasar, dll"
                                   value="<?= clean($editPanen['pembeli'] ?? '') ?>">
                        </div>

                        <!-- Catatan -->
                        <div class="pnc-form-group pnc-form-full">
                            <label class="pnc-label">Catatan (opsional)</label>
                            <textarea name="catatan" class="pnc-textarea" rows="2"
                                      placeholder="Catatan tambahan tentang panen ini..."><?= clean($editPanen['catatan'] ?? '') ?></textarea>
                        </div>

                    </div><!-- /pnc-form-grid -->

                    <div class="pnc-form-actions">
                        <button type="submit" class="btn-pnc-primary btn-pnc-lg">
                            <i class="bi bi-floppy-fill"></i>
                            <?= $editPanen ? 'Perbarui Data' : 'Simpan' ?>
                        </button>
                        <?php if ($editPanen): ?>
                        <a href="panen.php?tab=panen" class="btn-pnc-outline">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
                <?php endif; ?>

                <div class="pnc-form-note">
                    <i class="bi bi-info-circle"></i>
                    Pastikan data yang diinput sudah sesuai.
                    Belum ada harga pasaran?
                    <a href="panen.php?tab=harga" class="fw-600">Tambah harga pasaran →</a>
                </div>
            </div>
        </div>

        <!-- Riwayat Panen -->
        <?php if (!empty($panens)): ?>
        <div class="pnc-card mt-4">
            <div class="pnc-card-header">
                <h2 class="pnc-card-title">Riwayat Hasil Panen</h2>
                <div class="d-flex align-items-center gap-2">
                    <span class="pnc-badge pnc-badge-green">
                        <?= number_format($totalKg,1,',','.') ?> kg total
                    </span>
                    <span class="pnc-badge pnc-badge-blue"><?= count($panens) ?> Panen</span>
                </div>
            </div>
            <div class="pnc-table-wrap">
                <table class="pnc-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kolam</th>
                            <th class="text-end">Hasil (kg)</th>
                            <th class="text-end">Harga/kg</th>
                            <th class="text-end">Pendapatan</th>
                            <th class="text-end">Untung/Rugi</th>
                            <th>Jenis</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($panens as $p):
                        $modal = (float)$p['total_modal_bibit'] + (float)$p['modal_pakan'];
                        $pend  = (float)$p['total_pendapatan'];
                        $unt   = $pend - $modal;
                    ?>
                    <tr>
                        <td class="pnc-td-date">
                            <?= date('d M Y', strtotime($p['tanggal_panen'])) ?>
                        </td>
                        <td>
                            <div class="pnc-td-title"><?= clean($p['nama_kolam']) ?></div>
                            <div class="pnc-td-sub"><?= clean($p['jenis_ikan']) ?></div>
                            <?php if ($p['pembeli']): ?>
                            <div class="pnc-td-sub">→ <?= clean($p['pembeli']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-600">
                            <?= number_format((float)$p['total_panen_kg'],1,',','.') ?> kg
                            <?php if ($p['jumlah_ikan_hidup']): ?>
                            <div class="pnc-td-sub text-end"><?= number_format($p['jumlah_ikan_hidup']) ?> ekor</div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= rupiah((float)$p['harga_jual_kg']) ?></td>
                        <td class="text-end pnc-td-amount text-green">
                            <?= rupiah($pend) ?>
                        </td>
                        <td class="text-end pnc-td-amount <?= $unt >= 0 ? 'text-green' : 'text-red' ?>">
                            <?= ($unt >= 0 ? '+' : '') . rupiah($unt) ?>
                            <div class="pnc-td-sub text-end" style="font-weight:400;font-size:.7rem;">
                                Modal: <?= rupiah($modal) ?>
                            </div>
                        </td>
                        <td>
                            <span class="pnc-badge <?= $p['jenis_panen']==='total' ? 'pnc-badge-blue' : 'pnc-badge-yellow' ?>">
                                <?= $p['jenis_panen'] === 'total' ? 'Total' : 'Sebagian' ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="panen.php?edit_p=<?= $p['id'] ?>&tab=panen"
                                   class="btn-pnc-action btn-edit" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="panen.php"
                                      onsubmit="return confirm('Hapus data panen ini?')">
                                    <input type="hidden" name="_action" value="del_panen">
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
                            <td colspan="4" class="pnc-td-title text-end" style="padding:.85rem 1rem;">
                                Total Pendapatan
                            </td>
                            <td class="text-end pnc-td-amount text-green" style="padding:.85rem 1rem;">
                                <?= rupiah($totalPendapatan) ?>
                            </td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="pnc-card mt-4">
            <div class="pnc-empty">
                <i class="bi bi-basket3"></i>
                <p>Belum ada data panen tercatat.<br>Isi form di atas untuk mulai mencatat.</p>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; // end tab panen ?>

    </main>
</div>

<?php
$extraJs = '<script>
function hitungPendapatan() {
    var kg    = parseFloat(document.getElementById("inputTotalKg").value)    || 0;
    var harga = parseFloat(document.getElementById("inputHargaJual").value)  || 0;
    document.getElementById("previewPendapatan").value =
        "Rp " + (kg * harga).toLocaleString("id-ID");
}
function pakaiHargaPasaran(sel) {
    var opt = sel.options[sel.selectedIndex];
    var harga = opt.dataset.harga;
    if (harga) {
        document.getElementById("inputHargaJual").value = harga;
        hitungPendapatan();
    }
}
hitungPendapatan();
</script>';
include __DIR__ . '/../includes/footer.php';
?>
