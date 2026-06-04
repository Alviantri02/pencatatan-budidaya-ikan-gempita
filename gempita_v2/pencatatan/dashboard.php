<?php
/**
 * GEMPITA v2 - Pencatatan: Dashboard
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();

$pageTitle = 'Dashboard Pencatatan';
$extraCss  = '<link rel="stylesheet" href="' . APP_URL . '/pencatatan/pencatatan.css">';
$user      = currentUser();
$uid       = $user['id'];
$db        = getDB();


// ── Data dashboard dari view ─────────────────────────────────
$stmt = $db->prepare("SELECT * FROM v_dashboard_user WHERE user_id = ?");
$stmt->execute([$uid]);
$d = $stmt->fetch() ?: [
    'total_kolam'         => 0,
    'total_modal_bibit'   => 0,
    'total_modal_pakan'   => 0,
    'total_modal'         => 0,
    'total_pendapatan'    => 0,
    'proyeksi_keuntungan' => 0,
];

// ── Riwayat aktivitas terbaru (10 data) ──────────────────────
$stmt2 = $db->prepare("
    SELECT 'Bibit' AS kategori,
           k.nama_kolam AS deskripsi,
           k.tanggal_tebar AS tanggal,
           CONCAT(k.jumlah_bibit, ' ekor ', k.jenis_ikan) AS detail,
           k.total_modal_bibit AS jumlah,
           'pengeluaran' AS tipe
    FROM data_kolam_bibit k
    WHERE k.user_id = :u1

    UNION ALL

    SELECT 'Pakan',
           k.nama_kolam,
           p.tanggal,
           CONCAT(p.jenis_pakan, ' ', p.jumlah_kg, ' kg'),
           p.total_biaya,
           'pengeluaran'
    FROM data_pakan p
    JOIN data_kolam_bibit k ON k.id = p.kolam_id
    WHERE p.user_id = :u2

    UNION ALL

    SELECT 'Panen',
           k.nama_kolam,
           hp.tanggal_panen,
           CONCAT(hp.total_panen_kg, ' kg × Rp',
                  TO_CHAR(hp.harga_jual_kg, 'FM999,999,999')),
           hp.total_pendapatan,
           'pendapatan'
    FROM hasil_panen hp
    JOIN data_kolam_bibit k ON k.id = hp.kolam_id
    WHERE hp.user_id = :u3

    ORDER BY tanggal DESC, 1
    LIMIT 10
");
$stmt2->execute([':u1' => $uid, ':u2' => $uid, ':u3' => $uid]);
$rows = $stmt2->fetchAll();

// ── Ringkasan per kolam ──────────────────────────────────────
$stmt3 = $db->prepare("
    SELECT k.id, k.nama_kolam, k.jenis_ikan, k.jumlah_bibit,
           k.total_modal_bibit, k.is_aktif,
           COALESCE(SUM(DISTINCT p.total_biaya), 0)        AS modal_pakan,
           COALESCE(SUM(DISTINCT hp.total_pendapatan), 0)  AS pendapatan
    FROM data_kolam_bibit k
    LEFT JOIN data_pakan   p  ON p.kolam_id  = k.id
    LEFT JOIN hasil_panen  hp ON hp.kolam_id = k.id
    WHERE k.user_id = ?
    GROUP BY k.id, k.nama_kolam, k.jenis_ikan,
             k.jumlah_bibit, k.total_modal_bibit, k.is_aktif
    ORDER BY k.created_at DESC
    LIMIT 6
");
$stmt3->execute([$uid]);
$kolams = $stmt3->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="pnc-layout">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="pnc-main">

        <!-- Page Header -->
        <div class="pnc-page-header">
            <div>
                <h1 class="pnc-page-title">Laporan Budidaya</h1>
                <p class="pnc-page-sub">Dashboard Pencatatan Budidaya Ikan mu</p>
            </div>
            <a href="<?= APP_URL ?>/pencatatan/kolam.php" class="btn-pnc-primary">
                <i class="bi bi-plus-lg"></i> Tambah Kolam
            </a>
        </div>

        <!-- Stat Cards -->
        <div class="pnc-stats-grid">

            <div class="pnc-stat-card pnc-stat-red">
                <div class="pnc-stat-icon">
                    <i class="bi bi-arrow-down-circle-fill"></i>
                </div>
                <div class="pnc-stat-body">
                    <div class="pnc-stat-label">Biaya Operasional</div>
                    <div class="pnc-stat-value"><?= rupiah((float)$d['total_modal']) ?></div>
                    <div class="pnc-stat-note">
                        Pengeluaran: bibit &amp; pakan total
                    </div>
                </div>
            </div>

            <div class="pnc-stat-card pnc-stat-green">
                <div class="pnc-stat-icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="pnc-stat-body">
                    <div class="pnc-stat-label">Proyeksi Pendapatan</div>
                    <div class="pnc-stat-value"><?= rupiah((float)$d['total_pendapatan']) ?></div>
                    <div class="pnc-stat-note">
                        <?php $unt = (float)$d['proyeksi_keuntungan']; ?>
                        <?php if ($unt >= 0): ?>
                            Untung bersih: <strong class="text-green"><?= rupiah($unt) ?></strong>
                        <?php else: ?>
                            Rugi: <strong class="text-red"><?= rupiah(abs($unt)) ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Riwayat Aktivitas & Keuangan -->
        <div class="pnc-card">
            <div class="pnc-card-header">
                <h2 class="pnc-card-title">Riwayat Aktivitas &amp; Keuangan</h2>
                <div class="d-flex gap-2">
                    <button class="btn-pnc-icon" id="btnSearch" title="Cari">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>

            <div id="searchBar" class="pnc-search-bar" style="display:none;">
                <input type="text" id="searchInput" class="pnc-search-input"
                       placeholder="Cari nama kolam, kategori..."
                       oninput="filterTable(this.value)">
            </div>

            <?php if (empty($rows)): ?>
            <div class="pnc-empty">
                <i class="bi bi-journal-x"></i>
                <p>Belum ada data aktivitas.<br>Mulai tambah kolam pertama Anda!</p>
                <a href="<?= APP_URL ?>/pencatatan/kolam.php" class="btn-pnc-primary mt-3">
                    <i class="bi bi-plus-lg"></i> Tambah Kolam
                </a>
            </div>
            <?php else: ?>
            <div class="pnc-table-wrap">
                <table class="pnc-table" id="tblAktivitas">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Deskripsi</th>
                            <th>Status</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $badge_class = ['Bibit'=>'pnc-badge-blue','Pakan'=>'pnc-badge-yellow','Panen'=>'pnc-badge-green'];
                    $badge_icon  = ['Bibit'=>'bi-egg-fill','Pakan'=>'bi-bag-fill','Panen'=>'bi-basket3-fill'];
                    foreach ($rows as $r):
                        $kat = $r['kategori'];
                    ?>
                    <tr>
                        <td class="pnc-td-date">
                            <?= date('d M Y', strtotime($r['tanggal'])) ?>
                        </td>
                        <td>
                            <span class="pnc-badge <?= $badge_class[$kat] ?? 'pnc-badge-gray' ?>">
                                <i class="bi <?= $badge_icon[$kat] ?? 'bi-circle' ?> me-1"></i>
                                <?= $kat ?>
                            </span>
                        </td>
                        <td>
                            <div class="pnc-td-title"><?= clean($r['deskripsi']) ?></div>
                            <div class="pnc-td-sub"><?= clean($r['detail']) ?></div>
                        </td>
                        <td>
                            <?php if ($r['tipe'] === 'pendapatan'): ?>
                            <span class="pnc-status pnc-status-green">Pendapatan</span>
                            <?php else: ?>
                            <span class="pnc-status pnc-status-red">Pengeluaran</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pnc-td-amount <?= $r['tipe'] === 'pendapatan' ? 'text-green' : 'text-red' ?>">
                            <?= $r['tipe'] === 'pendapatan' ? '+ ' : '− ' ?>
                            <?= rupiah((float)$r['jumlah']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="pnc-card-footer">
                <a href="<?= APP_URL ?>/pencatatan/kalkulasi.php" class="pnc-link-all">
                    Lihat Semua Riwayat <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Ringkasan Kolam -->
        <?php if (!empty($kolams)): ?>
        <div class="pnc-card mt-4">
            <div class="pnc-card-header">
                <h2 class="pnc-card-title">Ringkasan Kolam</h2>
                <a href="<?= APP_URL ?>/pencatatan/kolam.php" class="pnc-link-all">
                    Kelola Kolam <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="pnc-kolam-grid">
            <?php foreach ($kolams as $k):
                $modal  = (float)$k['total_modal_bibit'] + (float)$k['modal_pakan'];
                $pend   = (float)$k['pendapatan'];
                $unt    = $pend - $modal;
            ?>
            <div class="pnc-kolam-card">
                <div class="pnc-kolam-header">
                    <div>
                        <div class="pnc-kolam-name"><?= clean($k['nama_kolam']) ?></div>
                        <div class="pnc-kolam-jenis">
                            <?= clean($k['jenis_ikan']) ?> &middot;
                            <?= number_format($k['jumlah_bibit']) ?> ekor
                        </div>
                    </div>
                    <span class="pnc-kolam-status <?= $k['is_aktif'] ? 'aktif' : 'nonaktif' ?>">
                        <?= $k['is_aktif'] ? 'Aktif' : 'Selesai' ?>
                    </span>
                </div>
                <div class="pnc-kolam-stats">
                    <div>
                        <div class="pnc-kolam-stat-label">Modal</div>
                        <div class="pnc-kolam-stat-val text-red"><?= rupiah($modal) ?></div>
                    </div>
                    <div>
                        <div class="pnc-kolam-stat-label">Pendapatan</div>
                        <div class="pnc-kolam-stat-val text-green"><?= rupiah($pend) ?></div>
                    </div>
                    <div>
                        <div class="pnc-kolam-stat-label">Untung/Rugi</div>
                        <div class="pnc-kolam-stat-val <?= $unt >= 0 ? 'text-green' : 'text-red' ?>">
                            <?= ($unt >= 0 ? '+' : '') . rupiah($unt) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php
$extraJs = '<script>
document.getElementById("btnSearch").addEventListener("click", function() {
    var bar = document.getElementById("searchBar");
    bar.style.display = bar.style.display === "none" ? "block" : "none";
    if (bar.style.display === "block") document.getElementById("searchInput").focus();
});
function filterTable(q) {
    q = q.toLowerCase();
    document.querySelectorAll("#tblAktivitas tbody tr").forEach(function(tr) {
        tr.style.display = tr.innerText.toLowerCase().includes(q) ? "" : "none";
    });
}
</script>';
include __DIR__ . '/../includes/footer.php';
?>
