<?php
/**
 * GEMPITA v2 - Pencatatan: Kalkulasi & Laporan
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$pageTitle = 'Kalkulasi & Laporan';
$extraCss  = '<link rel="stylesheet" href="' . APP_URL . '/pencatatan/pencatatan.css">';
$user      = currentUser();
$uid       = $user['id'];
$db        = getDB();

// ── Filter ───────────────────────────────────────────────────
$filterKolam = (int)($_GET['kolam_id'] ?? 0);
$filterTahun = (int)($_GET['tahun']    ?? date('Y'));

// ── Daftar kolam milik user (untuk filter) ───────────────────
$stmtK = $db->prepare("SELECT id, nama_kolam, jenis_ikan FROM data_kolam_bibit WHERE user_id=? ORDER BY nama_kolam");
$stmtK->execute([$uid]);
$semuaKolam = $stmtK->fetchAll();

// ── Tahun tersedia ───────────────────────────────────────────
$stmtTh = $db->prepare("
    SELECT DISTINCT EXTRACT(YEAR FROM tanggal_tebar)::int AS tahun
    FROM data_kolam_bibit WHERE user_id=?
    UNION
    SELECT DISTINCT EXTRACT(YEAR FROM tanggal_panen)::int
    FROM hasil_panen WHERE user_id=?
    ORDER BY tahun DESC
");
$stmtTh->execute([$uid, $uid]);
$tahunList = $stmtTh->fetchAll(PDO::FETCH_COLUMN);
if (empty($tahunList)) $tahunList = [date('Y')];

// ── Query kondisi kolam ──────────────────────────────────────
$whereKolam = $filterKolam ? "AND k.id = $filterKolam" : '';

// ── Rekap per kolam ──────────────────────────────────────────
$stmtRekap = $db->prepare("
    SELECT
        k.id,
        k.nama_kolam,
        k.jenis_ikan,
        k.jumlah_bibit,
        k.tanggal_tebar,
        k.is_aktif,

        -- Modal bibit
        k.total_modal_bibit,

        -- Modal pakan
        COALESCE((
            SELECT SUM(p.total_biaya)
            FROM data_pakan p
            WHERE p.kolam_id = k.id
        ), 0) AS modal_pakan,

        -- Total panen kg & pendapatan
        COALESCE((
            SELECT SUM(hp.total_panen_kg)
            FROM hasil_panen hp
            WHERE hp.kolam_id = k.id
        ), 0) AS total_kg_panen,

        COALESCE((
            SELECT SUM(hp.total_pendapatan)
            FROM hasil_panen hp
            WHERE hp.kolam_id = k.id
        ), 0) AS total_pendapatan,

        -- Jumlah entri
        (SELECT COUNT(*) FROM data_pakan   WHERE kolam_id = k.id) AS jml_pakan,
        (SELECT COUNT(*) FROM hasil_panen  WHERE kolam_id = k.id) AS jml_panen,

        -- Tanggal panen terakhir
        (SELECT MAX(tanggal_panen) FROM hasil_panen WHERE kolam_id = k.id) AS tgl_panen_terakhir

    FROM data_kolam_bibit k
    WHERE k.user_id = :uid $whereKolam
    ORDER BY k.created_at DESC
");
$stmtRekap->execute([':uid' => $uid]);
$rekapKolam = $stmtRekap->fetchAll();

// ── Grand total ──────────────────────────────────────────────
$grandModalBibit = 0;
$grandModalPakan = 0;
$grandPendapatan = 0;
foreach ($rekapKolam as $r) {
    $grandModalBibit += (float)$r['total_modal_bibit'];
    $grandModalPakan += (float)$r['modal_pakan'];
    $grandPendapatan += (float)$r['total_pendapatan'];
}
$grandModal = $grandModalBibit + $grandModalPakan;
$grandUntung = $grandPendapatan - $grandModal;

// ── Riwayat semua aktivitas (untuk tab riwayat) ──────────────
$whereK2 = $filterKolam ? "AND kolam_id_ref = $filterKolam" : '';
$stmtRiwayat = $db->prepare("
    SELECT * FROM (
        SELECT
            'Bibit'           AS kategori,
            k.id              AS kolam_id_ref,
            k.nama_kolam      AS nama_kolam,
            k.jenis_ikan      AS jenis_ikan,
            k.tanggal_tebar   AS tanggal,
            CONCAT(k.jumlah_bibit, ' ekor') AS detail,
            k.total_modal_bibit AS jumlah,
            'pengeluaran'     AS tipe
        FROM data_kolam_bibit k
        WHERE k.user_id = :u1

        UNION ALL

        SELECT
            'Pakan',
            p.kolam_id,
            k2.nama_kolam,
            k2.jenis_ikan,
            p.tanggal,
            CONCAT(p.jenis_pakan, ' ', p.jumlah_kg, ' kg'),
            p.total_biaya,
            'pengeluaran'
        FROM data_pakan p
        JOIN data_kolam_bibit k2 ON k2.id = p.kolam_id
        WHERE p.user_id = :u2

        UNION ALL

        SELECT
            'Panen',
            hp.kolam_id,
            k3.nama_kolam,
            k3.jenis_ikan,
            hp.tanggal_panen,
            CONCAT(hp.total_panen_kg, ' kg × Rp', TO_CHAR(hp.harga_jual_kg,'FM999,999,999')),
            hp.total_pendapatan,
            'pendapatan'
        FROM hasil_panen hp
        JOIN data_kolam_bibit k3 ON k3.id = hp.kolam_id
        WHERE hp.user_id = :u3
    ) sub
    WHERE EXTRACT(YEAR FROM tanggal)::int = :tahun $whereK2
    ORDER BY tanggal DESC, kategori
");
$stmtRiwayat->execute([
    ':u1' => $uid, ':u2' => $uid, ':u3' => $uid,
    ':tahun' => $filterTahun
]);
$riwayat = $stmtRiwayat->fetchAll();

// Hitung totals riwayat
$totalPengeluaranRiwayat = 0;
$totalPendapatanRiwayat  = 0;
foreach ($riwayat as $r) {
    if ($r['tipe'] === 'pengeluaran') $totalPengeluaranRiwayat += (float)$r['jumlah'];
    else                              $totalPendapatanRiwayat  += (float)$r['jumlah'];
}

// ── Tab aktif ────────────────────────────────────────────────
$tabAktif = $_GET['tab'] ?? 'rekap';

include __DIR__ . '/../includes/header.php';
?>

<div class="pnc-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="pnc-main">

        <!-- Page Header -->
        <div class="pnc-page-header">
            <div>
                <h1 class="pnc-page-title">Kalkulasi &amp; Laporan</h1>
                <p class="pnc-page-sub">Rekap keuangan budidaya ikan Anda</p>
            </div>
            <!-- Export -->
            <a href="?tab=<?= $tabAktif ?>&kolam_id=<?= $filterKolam ?>&tahun=<?= $filterTahun ?>&export=1"
               class="btn-pnc-outline">
                <i class="bi bi-download me-1"></i> Export CSV
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="pnc-filter-bar">
            <form method="get" action="kalkulasi.php" class="pnc-filter-form">
                <input type="hidden" name="tab" value="<?= $tabAktif ?>">

                <div class="pnc-filter-group">
                    <label class="pnc-filter-label">Kolam</label>
                    <select name="kolam_id" class="pnc-select pnc-filter-select" onchange="this.form.submit()">
                        <option value="0">Semua Kolam</option>
                        <?php foreach ($semuaKolam as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $filterKolam == $k['id'] ? 'selected' : '' ?>>
                            <?= clean($k['nama_kolam']) ?> (<?= clean($k['jenis_ikan']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pnc-filter-group">
                    <label class="pnc-filter-label">Tahun</label>
                    <select name="tahun" class="pnc-select pnc-filter-select" onchange="this.form.submit()">
                        <?php foreach ($tahunList as $th): ?>
                        <option value="<?= $th ?>" <?= $filterTahun == $th ? 'selected' : '' ?>>
                            <?= $th ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <!-- Grand Total Cards -->
        <div class="pnc-stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">

            <div class="pnc-stat-card pnc-stat-red">
                <div class="pnc-stat-icon"><i class="bi bi-egg-fill"></i></div>
                <div class="pnc-stat-body">
                    <div class="pnc-stat-label">Modal Bibit</div>
                    <div class="pnc-stat-value"><?= rupiah($grandModalBibit) ?></div>
                </div>
            </div>

            <div class="pnc-stat-card pnc-stat-yellow">
                <div class="pnc-stat-icon"><i class="bi bi-bag-fill"></i></div>
                <div class="pnc-stat-body">
                    <div class="pnc-stat-label">Modal Pakan</div>
                    <div class="pnc-stat-value"><?= rupiah($grandModalPakan) ?></div>
                </div>
            </div>

            <div class="pnc-stat-card pnc-stat-red">
                <div class="pnc-stat-icon"><i class="bi bi-wallet2"></i></div>
                <div class="pnc-stat-body">
                    <div class="pnc-stat-label">Total Modal</div>
                    <div class="pnc-stat-value"><?= rupiah($grandModal) ?></div>
                </div>
            </div>

            <div class="pnc-stat-card pnc-stat-green">
                <div class="pnc-stat-icon"><i class="bi bi-basket3-fill"></i></div>
                <div class="pnc-stat-body">
                    <div class="pnc-stat-label">Total Pendapatan</div>
                    <div class="pnc-stat-value"><?= rupiah($grandPendapatan) ?></div>
                </div>
            </div>

            <div class="pnc-stat-card <?= $grandUntung >= 0 ? 'pnc-stat-green' : 'pnc-stat-red' ?>">
                <div class="pnc-stat-icon">
                    <i class="bi <?= $grandUntung >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' ?>"></i>
                </div>
                <div class="pnc-stat-body">
                    <div class="pnc-stat-label">
                        <?= $grandUntung >= 0 ? 'Keuntungan Bersih' : 'Kerugian Bersih' ?>
                    </div>
                    <div class="pnc-stat-value">
                        <?= ($grandUntung >= 0 ? '+' : '') . rupiah(abs($grandUntung)) ?>
                    </div>
                    <?php if ($grandPendapatan > 0): ?>
                    <div class="pnc-stat-note">
                        ROI: <?= number_format(($grandUntung / ($grandModal ?: 1)) * 100, 1) ?>%
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Tab Nav -->
        <div class="pnc-tab-nav">
            <a href="?tab=rekap&kolam_id=<?= $filterKolam ?>&tahun=<?= $filterTahun ?>"
               class="pnc-tab <?= $tabAktif === 'rekap' ? 'active' : '' ?>">
                <i class="bi bi-table me-1"></i>Rekap per Kolam
            </a>
            <a href="?tab=riwayat&kolam_id=<?= $filterKolam ?>&tahun=<?= $filterTahun ?>"
               class="pnc-tab <?= $tabAktif === 'riwayat' ? 'active' : '' ?>">
                <i class="bi bi-clock-history me-1"></i>Riwayat Lengkap
            </a>
        </div>

        <!-- ══════════════════════════════════════════
             TAB: REKAP PER KOLAM
        ══════════════════════════════════════════ -->
        <?php if ($tabAktif === 'rekap'): ?>

        <?php if (empty($rekapKolam)): ?>
        <div class="pnc-card">
            <div class="pnc-empty">
                <i class="bi bi-calculator"></i>
                <p>Belum ada data kolam untuk ditampilkan.<br>
                   Mulai dari <a href="kolam.php">tambah kolam</a>.</p>
            </div>
        </div>
        <?php else: ?>

        <?php foreach ($rekapKolam as $r):
            $modal       = (float)$r['total_modal_bibit'] + (float)$r['modal_pakan'];
            $pend        = (float)$r['total_pendapatan'];
            $unt         = $pend - $modal;
            $roi         = $modal > 0 ? ($unt / $modal) * 100 : 0;
            $pct_bibit   = $modal > 0 ? round(((float)$r['total_modal_bibit'] / $modal) * 100) : 0;
            $pct_pakan   = 100 - $pct_bibit;
        ?>
        <div class="pnc-card pnc-rekap-card">

            <!-- Card Header -->
            <div class="pnc-card-header">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h2 class="pnc-card-title"><?= clean($r['nama_kolam']) ?></h2>
                    <span class="pnc-badge pnc-badge-blue"><?= clean($r['jenis_ikan']) ?></span>
                    <span class="pnc-kolam-status <?= $r['is_aktif'] ? 'aktif' : 'nonaktif' ?>">
                        <?= $r['is_aktif'] ? 'Aktif' : 'Selesai' ?>
                    </span>
                </div>
                <div class="pnc-td-sub">
                    Tebar: <?= date('d M Y', strtotime($r['tanggal_tebar'])) ?>
                    · <?= number_format($r['jumlah_bibit']) ?> ekor
                    <?php if ($r['tgl_panen_terakhir']): ?>
                    · Panen terakhir: <?= date('d M Y', strtotime($r['tgl_panen_terakhir'])) ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rekap Angka -->
            <div class="pnc-rekap-body">

                <!-- Kiri: rincian biaya & pendapatan -->
                <div class="pnc-rekap-detail">

                    <div class="pnc-rekap-row">
                        <span class="pnc-rekap-label">
                            <i class="bi bi-egg-fill me-1 text-blue"></i>Modal Bibit
                        </span>
                        <span class="pnc-rekap-val text-red">
                            − <?= rupiah((float)$r['total_modal_bibit']) ?>
                        </span>
                    </div>
                    <div class="pnc-rekap-row">
                        <span class="pnc-rekap-label">
                            <i class="bi bi-bag-fill me-1 text-yellow"></i>
                            Modal Pakan
                            <small class="text-muted">(<?= $r['jml_pakan'] ?> entri)</small>
                        </span>
                        <span class="pnc-rekap-val text-red">
                            − <?= rupiah((float)$r['modal_pakan']) ?>
                        </span>
                    </div>

                    <div class="pnc-rekap-divider"></div>

                    <div class="pnc-rekap-row pnc-rekap-subtotal">
                        <span class="pnc-rekap-label">Total Modal</span>
                        <span class="pnc-rekap-val text-red fw-700"><?= rupiah($modal) ?></span>
                    </div>

                    <div class="pnc-rekap-row" style="margin-top:.5rem;">
                        <span class="pnc-rekap-label">
                            <i class="bi bi-basket3-fill me-1 text-green"></i>
                            Pendapatan Panen
                            <small class="text-muted">(<?= $r['jml_panen'] ?> panen
                            · <?= number_format((float)$r['total_kg_panen'],1,',','.') ?> kg)</small>
                        </span>
                        <span class="pnc-rekap-val text-green">
                            + <?= rupiah($pend) ?>
                        </span>
                    </div>

                    <div class="pnc-rekap-divider"></div>

                    <div class="pnc-rekap-row pnc-rekap-total">
                        <span class="pnc-rekap-label">
                            <?= $unt >= 0 ? '🟢 Keuntungan Bersih' : '🔴 Kerugian Bersih' ?>
                        </span>
                        <span class="pnc-rekap-val <?= $unt >= 0 ? 'text-green' : 'text-red' ?> fw-800"
                              style="font-size:1.1rem;">
                            <?= ($unt >= 0 ? '+' : '') . rupiah($unt) ?>
                        </span>
                    </div>

                    <?php if ($modal > 0): ?>
                    <div class="pnc-rekap-roi">
                        <span>ROI</span>
                        <span class="<?= $roi >= 0 ? 'text-green' : 'text-red' ?> fw-700">
                            <?= ($roi >= 0 ? '+' : '') . number_format($roi, 1) ?>%
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Kanan: progress bar komposisi modal -->
                <?php if ($modal > 0): ?>
                <div class="pnc-rekap-chart">
                    <div class="pnc-rekap-chart-title">Komposisi Modal</div>

                    <div class="pnc-progress-bar-wrap">
                        <div class="pnc-progress-label">
                            <span>Bibit</span>
                            <span class="fw-600"><?= $pct_bibit ?>%</span>
                        </div>
                        <div class="pnc-progress-track">
                            <div class="pnc-progress-fill pnc-fill-blue"
                                 style="width:<?= $pct_bibit ?>%"></div>
                        </div>
                    </div>

                    <div class="pnc-progress-bar-wrap">
                        <div class="pnc-progress-label">
                            <span>Pakan</span>
                            <span class="fw-600"><?= $pct_pakan ?>%</span>
                        </div>
                        <div class="pnc-progress-track">
                            <div class="pnc-progress-fill pnc-fill-yellow"
                                 style="width:<?= $pct_pakan ?>%"></div>
                        </div>
                    </div>

                    <!-- Pendapatan vs Modal bar -->
                    <?php if ($pend > 0): ?>
                    <div style="margin-top:1rem;">
                        <div class="pnc-rekap-chart-title">Pendapatan vs Modal</div>
                        <?php
                        $maxVal = max($pend, $modal);
                        $pctModal = round(($modal / $maxVal) * 100);
                        $pctPend  = round(($pend  / $maxVal) * 100);
                        ?>
                        <div class="pnc-progress-bar-wrap">
                            <div class="pnc-progress-label">
                                <span>Modal</span>
                                <span class="fw-600 text-red"><?= rupiah($modal) ?></span>
                            </div>
                            <div class="pnc-progress-track">
                                <div class="pnc-progress-fill pnc-fill-red"
                                     style="width:<?= $pctModal ?>%"></div>
                            </div>
                        </div>
                        <div class="pnc-progress-bar-wrap">
                            <div class="pnc-progress-label">
                                <span>Pendapatan</span>
                                <span class="fw-600 text-green"><?= rupiah($pend) ?></span>
                            </div>
                            <div class="pnc-progress-track">
                                <div class="pnc-progress-fill pnc-fill-green"
                                     style="width:<?= $pctPend ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
                <?php endif; ?>

            </div><!-- /pnc-rekap-body -->

            <!-- Quick actions -->
            <div class="pnc-rekap-footer">
                <a href="pakan.php" class="pnc-link-all" style="font-size:.8rem;">
                    <i class="bi bi-bag me-1"></i>Tambah Pakan
                </a>
                <a href="panen.php" class="pnc-link-all" style="font-size:.8rem;">
                    <i class="bi bi-basket3 me-1"></i>Catat Panen
                </a>
                <a href="kolam.php?edit=<?= $r['id'] ?>" class="pnc-link-all" style="font-size:.8rem;">
                    <i class="bi bi-pencil me-1"></i>Edit Kolam
                </a>
            </div>

        </div><!-- /pnc-rekap-card -->
        <?php endforeach; ?>
        <?php endif; ?>

        <?php endif; // end tab rekap ?>

        <!-- ══════════════════════════════════════════
             TAB: RIWAYAT LENGKAP
        ══════════════════════════════════════════ -->
        <?php if ($tabAktif === 'riwayat'): ?>

        <div class="pnc-card">
            <div class="pnc-card-header">
                <h2 class="pnc-card-title">
                    Riwayat Lengkap
                    <?= $filterKolam ? '— ' . clean(array_column($semuaKolam,'nama_kolam','id')[$filterKolam] ?? '') : '' ?>
                    (<?= $filterTahun ?>)
                </h2>
                <div class="d-flex gap-2 align-items-center">
                    <span class="pnc-badge pnc-badge-red">
                        − <?= rupiah($totalPengeluaranRiwayat) ?>
                    </span>
                    <span class="pnc-badge pnc-badge-green">
                        + <?= rupiah($totalPendapatanRiwayat) ?>
                    </span>
                </div>
            </div>

            <?php if (empty($riwayat)): ?>
            <div class="pnc-empty">
                <i class="bi bi-journal-x"></i>
                <p>Tidak ada data untuk filter yang dipilih.</p>
            </div>
            <?php else: ?>
            <div class="pnc-table-wrap">
                <table class="pnc-table" id="tblRiwayat">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Kolam</th>
                            <th>Detail</th>
                            <th>Status</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $badge_class = ['Bibit'=>'pnc-badge-blue','Pakan'=>'pnc-badge-yellow','Panen'=>'pnc-badge-green'];
                    $badge_icon  = ['Bibit'=>'bi-egg-fill','Pakan'=>'bi-bag-fill','Panen'=>'bi-basket3-fill'];
                    $saldoBerjalan = 0;
                    // Balik urutan untuk saldo berjalan (lama ke baru)
                    $riwayatUrut = array_reverse($riwayat);
                    foreach ($riwayatUrut as $rw):
                        if ($rw['tipe'] === 'pendapatan') $saldoBerjalan += (float)$rw['jumlah'];
                        else                              $saldoBerjalan -= (float)$rw['jumlah'];
                    endforeach;
                    // Tampilkan urutan terbaru dulu tapi hitung saldo dari bawah
                    $saldoArr = [];
                    $running  = 0;
                    foreach (array_reverse($riwayat) as $idx => $rw) {
                        if ($rw['tipe'] === 'pendapatan') $running += (float)$rw['jumlah'];
                        else                              $running -= (float)$rw['jumlah'];
                        $saldoArr[$idx] = $running;
                    }
                    $saldoArr = array_reverse($saldoArr, true);

                    foreach ($riwayat as $idx => $rw):
                        $kat = $rw['kategori'];
                    ?>
                    <tr>
                        <td class="pnc-td-date">
                            <?= date('d M Y', strtotime($rw['tanggal'])) ?>
                        </td>
                        <td>
                            <span class="pnc-badge <?= $badge_class[$kat] ?? 'pnc-badge-gray' ?>">
                                <i class="bi <?= $badge_icon[$kat] ?? 'bi-circle' ?> me-1"></i>
                                <?= $kat ?>
                            </span>
                        </td>
                        <td>
                            <div class="pnc-td-title"><?= clean($rw['nama_kolam']) ?></div>
                            <div class="pnc-td-sub"><?= clean($rw['jenis_ikan']) ?></div>
                        </td>
                        <td class="pnc-td-sub"><?= clean($rw['detail']) ?></td>
                        <td>
                            <?php if ($rw['tipe'] === 'pendapatan'): ?>
                            <span class="pnc-status pnc-status-green">Pendapatan</span>
                            <?php else: ?>
                            <span class="pnc-status pnc-status-red">Pengeluaran</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pnc-td-amount <?= $rw['tipe'] === 'pendapatan' ? 'text-green' : 'text-red' ?>">
                            <?= $rw['tipe'] === 'pendapatan' ? '+' : '−' ?>
                            <?= rupiah((float)$rw['jumlah']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--bg);">
                            <td colspan="4"></td>
                            <td class="pnc-td-title text-end" style="padding:.85rem 1rem;">
                                Saldo Bersih
                            </td>
                            <?php $saldoFinal = $totalPendapatanRiwayat - $totalPengeluaranRiwayat; ?>
                            <td class="text-end pnc-td-amount <?= $saldoFinal >= 0 ? 'text-green' : 'text-red' ?>"
                                style="padding:.85rem 1rem;">
                                <?= ($saldoFinal >= 0 ? '+' : '') . rupiah($saldoFinal) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <?php endif; // end tab riwayat ?>

    </main>
</div>

<?php
// ── Export CSV ───────────────────────────────────────────────
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="laporan_gempita_' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Tanggal','Kategori','Kolam','Jenis Ikan','Detail','Tipe','Jumlah (Rp)']);
    foreach ($riwayat as $rw) {
        fputcsv($out, [
            date('d/m/Y', strtotime($rw['tanggal'])),
            $rw['kategori'],
            $rw['nama_kolam'],
            $rw['jenis_ikan'],
            $rw['detail'],
            ucfirst($rw['tipe']),
            number_format((float)$rw['jumlah'], 0, ',', '.'),
        ]);
    }
    fclose($out);
    exit;
}
$extraJs = '';
include __DIR__ . '/../includes/footer.php';
?>
