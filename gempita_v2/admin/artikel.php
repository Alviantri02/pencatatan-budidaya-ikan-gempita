<?php
/**
 * GEMPITA v2 — Admin: Kelola Artikel
 * Daftar artikel + filter + pagination + aksi cepat
 */
require_once __DIR__ . '/admin_middleware.php';

$pageTitle = 'Kelola Artikel';
$db        = getDB();

// ── Handle toggle publish ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'toggle_publish') {
    $id = (int)($_POST['art_id'] ?? 0);
    if ($id) {
        $db->prepare("UPDATE artikel SET is_publish = NOT is_publish WHERE id=?")
           ->execute([$id]);
    }
    header('Location: artikel.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// ── Handle delete ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    $id = (int)($_POST['art_id'] ?? 0);
    if ($id) {
        // Hapus gambar jika ada
        $art = $db->prepare("SELECT gambar_url FROM artikel WHERE id=?");
        $art->execute([$id]);
        $row = $art->fetch();
        if ($row && $row['gambar_url']) {
            $path = __DIR__ . '/../' . ltrim($row['gambar_url'], '/');
            if (file_exists($path)) @unlink($path);
        }
        $db->prepare("DELETE FROM artikel WHERE id=?")->execute([$id]);
        setFlash('success', 'Artikel berhasil dihapus.');
    }
    header('Location: artikel.php'); exit;
}

// ── Filter & pagination ──────────────────────────────────────
$filterKat     = (int)($_GET['kategori_id'] ?? 0);
$filterPublish = $_GET['publish'] ?? '';   // '' | '1' | '0'
$cari          = trim($_GET['cari'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 12;
$offset        = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($filterKat) {
    $where[]  = 'a.kategori_id = ?';
    $params[] = $filterKat;
}
if ($filterPublish !== '') {
    $where[]  = 'a.is_publish = ?';
    $params[] = ($filterPublish === '1');
}
if ($cari) {
    $where[]  = '(a.judul ILIKE ? OR a.ringkasan ILIKE ? OR a.penulis ILIKE ?)';
    $params[] = "%$cari%";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}
$wsql = implode(' AND ', $where);

// Count
$stmtCount = $db->prepare("SELECT COUNT(*) FROM artikel a WHERE $wsql");
$stmtCount->execute($params);
$total     = (int)$stmtCount->fetchColumn();
$totalPage = max(1, ceil($total / $perPage));
$page      = min($page, $totalPage);

// Data
$stmtArt = $db->prepare("
    SELECT a.*, k.nama AS kat_nama, k.warna AS kat_warna
    FROM artikel a
    LEFT JOIN kategori_artikel k ON a.kategori_id = k.id
    WHERE $wsql
    ORDER BY a.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmtArt->execute($params);
$artikelList = $stmtArt->fetchAll();

// Kategori (untuk filter)
$kategoriList = $db->query("
    SELECT k.*, COUNT(a.id) AS jml
    FROM kategori_artikel k
    LEFT JOIN artikel a ON a.kategori_id = k.id
    GROUP BY k.id ORDER BY k.nama
")->fetchAll();

// Stat ringkasan
$stats = $db->query("
    SELECT
        COUNT(*)                                    AS total,
        SUM(CASE WHEN is_publish THEN 1 ELSE 0 END) AS published,
        SUM(CASE WHEN NOT is_publish THEN 1 ELSE 0 END) AS draft
    FROM artikel
")->fetch();

// Query string untuk pagination
$qsBase = http_build_query(array_filter([
    'kategori_id' => $filterKat ?: null,
    'publish'     => $filterPublish !== '' ? $filterPublish : null,
    'cari'        => $cari ?: null,
]));

require_once __DIR__ . '/admin_header.php';
?>

<div class="adm-layout">
    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <main class="adm-main">

        <!-- Page Header -->
        <div class="adm-page-header">
            <div>
                <h1 class="adm-page-title">Kelola Artikel</h1>
                <p class="adm-page-sub">Buat, edit, dan kelola semua artikel informasi</p>
            </div>
            <a href="<?= APP_URL ?>/admin/artikel-form.php" class="btn-adm-primary">
                <i class="bi bi-plus-lg"></i> Tulis Artikel
            </a>
        </div>

        <!-- Stat Cards -->
        <div class="adm-stat-grid">
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:var(--blue-soft);color:var(--blue);">
                    <i class="bi bi-newspaper"></i>
                </div>
                <div>
                    <div class="adm-stat-val"><?= $stats['total'] ?></div>
                    <div class="adm-stat-label">Total Artikel</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:var(--green-soft);color:var(--green);">
                    <i class="bi bi-eye-fill"></i>
                </div>
                <div>
                    <div class="adm-stat-val"><?= $stats['published'] ?></div>
                    <div class="adm-stat-label">Dipublikasi</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:var(--yellow-light);color:var(--yellow-dark);">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div>
                    <div class="adm-stat-val"><?= $stats['draft'] ?></div>
                    <div class="adm-stat-label">Draft</div>
                </div>
            </div>
            <div class="adm-stat-card">
                <div class="adm-stat-icon" style="background:var(--blue-soft);color:var(--blue);">
                    <i class="bi bi-tags-fill"></i>
                </div>
                <div>
                    <div class="adm-stat-val"><?= count($kategoriList) ?></div>
                    <div class="adm-stat-label">Kategori</div>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="adm-card mb-0">
            <form method="GET" action="artikel.php" class="adm-filter-form">
                <!-- Search -->
                <div class="adm-filter-search">
                    <i class="bi bi-search adm-filter-search-icon"></i>
                    <input type="text" name="cari" class="adm-input adm-search-input"
                           placeholder="Cari judul, penulis..."
                           value="<?= clean($cari) ?>">
                </div>

                <!-- Kategori -->
                <select name="kategori_id" class="adm-select adm-filter-sel" onchange="this.form.submit()">
                    <option value="0">Semua Kategori</option>
                    <?php foreach ($kategoriList as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $filterKat == $k['id'] ? 'selected' : '' ?>>
                        <?= clean($k['nama']) ?> (<?= $k['jml'] ?>)
                    </option>
                    <?php endforeach; ?>
                </select>

                <!-- Status -->
                <select name="publish" class="adm-select adm-filter-sel" onchange="this.form.submit()">
                    <option value=""      <?= $filterPublish === ''  ? 'selected' : '' ?>>Semua Status</option>
                    <option value="1"     <?= $filterPublish === '1' ? 'selected' : '' ?>>Dipublikasi</option>
                    <option value="0"     <?= $filterPublish === '0' ? 'selected' : '' ?>>Draft</option>
                </select>

                <button type="submit" class="btn-adm-primary" style="padding:.52rem 1rem;white-space:nowrap;">
                    <i class="bi bi-search me-1"></i>Cari
                </button>

                <?php if ($cari || $filterKat || $filterPublish !== ''): ?>
                <a href="artikel.php" class="btn-adm-outline" style="padding:.52rem .9rem;white-space:nowrap;">
                    <i class="bi bi-x-lg"></i>
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Info jumlah -->
        <div class="adm-result-info">
            Menampilkan <strong><?= count($artikelList) ?></strong> dari
            <strong><?= $total ?></strong> artikel
            <?= $cari ? '— pencarian "<strong>'.clean($cari).'</strong>"' : '' ?>
        </div>

        <!-- Artikel List -->
        <?php if (empty($artikelList)): ?>
        <div class="adm-card">
            <div class="adm-empty">
                <i class="bi bi-newspaper"></i>
                <p>Belum ada artikel<?= $cari ? ' yang cocok dengan pencarian ini' : '' ?>.</p>
                <a href="artikel-form.php" class="btn-adm-primary mt-2">
                    <i class="bi bi-plus-lg me-1"></i>Tulis Artikel Pertama
                </a>
            </div>
        </div>
        <?php else: ?>

        <div class="adm-artikel-grid">
        <?php foreach ($artikelList as $a): ?>
        <div class="adm-art-card <?= !$a['is_publish'] ? 'adm-art-draft' : '' ?>">

            <!-- Thumbnail -->
            <div class="adm-art-thumb"
                 style="background:linear-gradient(135deg,<?= $a['kat_warna']??'#1a6fd4' ?>22,<?= $a['kat_warna']??'#1a6fd4' ?>44);">
                <?php if ($a['gambar_url']): ?>
                <img src="<?= clean($a['gambar_url']) ?>" alt="" class="adm-art-thumb-img">
                <?php else: ?>
                <i class="bi bi-newspaper" style="font-size:2.2rem;color:<?= $a['kat_warna']??'#1a6fd4' ?>;opacity:.3;"></i>
                <?php endif; ?>
                <!-- Status badge -->
                <?php if (!$a['is_publish']): ?>
                <span class="adm-art-status-badge draft">DRAFT</span>
                <?php else: ?>
                <span class="adm-art-status-badge published">PUBLISH</span>
                <?php endif; ?>
                <!-- Kategori -->
                <?php if ($a['kat_nama']): ?>
                <span class="adm-art-kat-badge"
                      style="background:<?= $a['kat_warna']??'#1a6fd4' ?>;">
                    <?= clean(strtoupper($a['kat_nama'])) ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Body -->
            <div class="adm-art-body">
                <h3 class="adm-art-judul"><?= clean($a['judul']) ?></h3>
                <?php if ($a['ringkasan']): ?>
                <p class="adm-art-ringkasan"><?= clean(mb_substr($a['ringkasan'], 0, 100)) ?>...</p>
                <?php endif; ?>
                <div class="adm-art-meta">
                    <span><i class="bi bi-person me-1"></i><?= clean($a['penulis']) ?></span>
                    <span><i class="bi bi-clock me-1"></i><?= $a['estimasi_baca'] ?> mnt</span>
                    <span><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($a['created_at'])) ?></span>
                </div>
            </div>

            <!-- Actions -->
            <div class="adm-art-actions">
                <!-- Preview -->
                <a href="<?= APP_URL ?>/informasi.php?slug=<?= clean($a['slug']) ?>"
                   target="_blank" class="btn-adm-action btn-adm-preview" title="Preview">
                    <i class="bi bi-eye"></i>
                </a>
                <!-- Edit -->
                <a href="artikel-form.php?id=<?= $a['id'] ?>"
                   class="btn-adm-action btn-adm-edit" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>
                <!-- Toggle publish -->
                <form method="post" action="artikel.php?<?= $qsBase ?>">
                    <input type="hidden" name="_action" value="toggle_publish">
                    <input type="hidden" name="art_id"  value="<?= $a['id'] ?>">
                    <button type="submit"
                            class="btn-adm-action <?= $a['is_publish'] ? 'btn-adm-unpublish' : 'btn-adm-publish' ?>"
                            title="<?= $a['is_publish'] ? 'Jadikan Draft' : 'Publikasikan' ?>">
                        <i class="bi <?= $a['is_publish'] ? 'bi-eye-slash' : 'bi-check-circle' ?>"></i>
                    </button>
                </form>
                <!-- Hapus -->
                <form method="post" action="artikel.php"
                      onsubmit="return confirm('Hapus artikel «<?= clean(addslashes($a['judul'])) ?>»?\nTindakan ini tidak dapat dibatalkan.')">
                    <input type="hidden" name="_action" value="delete">
                    <input type="hidden" name="art_id"  value="<?= $a['id'] ?>">
                    <button type="submit" class="btn-adm-action btn-adm-del" title="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPage > 1): ?>
        <nav class="adm-pagination">
            <?php if ($page > 1): ?>
            <a href="?<?= $qsBase ?>&page=<?= $page-1 ?>" class="adm-page-btn">
                <i class="bi bi-chevron-left"></i>
            </a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPage, $page + 2);
            if ($start > 1): ?><span class="adm-page-ellipsis">…</span><?php endif;
            for ($p = $start; $p <= $end; $p++):
            ?>
            <a href="?<?= $qsBase ?>&page=<?= $p ?>"
               class="adm-page-btn <?= $p === $page ? 'active' : '' ?>">
                <?= $p ?>
            </a>
            <?php endfor;
            if ($end < $totalPage): ?><span class="adm-page-ellipsis">…</span><?php endif; ?>

            <?php if ($page < $totalPage): ?>
            <a href="?<?= $qsBase ?>&page=<?= $page+1 ?>" class="adm-page-btn">
                <i class="bi bi-chevron-right"></i>
            </a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
