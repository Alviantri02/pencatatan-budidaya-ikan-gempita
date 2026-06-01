<?php
/**
 * GEMPITA v2 - Halaman Informasi
 * Dual mode: daftar artikel + detail artikel (?slug=)
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pdo  = getDB();
$slug = trim($_GET['slug'] ?? '');

/* ================================================================
   MODE DETAIL ARTIKEL
   ================================================================ */
if ($slug !== '') {
    $stmt = $pdo->prepare("
        SELECT a.*, k.nama AS kat_nama, k.warna AS kat_warna
        FROM artikel a
        LEFT JOIN kategori_artikel k ON a.kategori_id = k.id
        WHERE a.slug = ? AND a.is_publish = TRUE LIMIT 1
    ");
    $stmt->execute([$slug]);
    $art = $stmt->fetch();
    if (!$art) { header('Location: ' . APP_URL . '/informasi.php'); exit; }

    $terkait = $pdo->prepare("
        SELECT a.slug, a.judul, a.estimasi_baca, k.nama AS kat_nama, k.warna AS kat_warna
        FROM artikel a LEFT JOIN kategori_artikel k ON a.kategori_id=k.id
        WHERE a.kategori_id=? AND a.slug!=? AND a.is_publish=TRUE
        ORDER BY a.created_at DESC LIMIT 3
    ");
    $terkait->execute([$art['kategori_id'], $slug]);
    $artikelTerkait = $terkait->fetchAll();

    $kategoriList = $pdo->query("SELECT k.*,COUNT(a.id) AS jml FROM kategori_artikel k LEFT JOIN artikel a ON a.kategori_id=k.id AND a.is_publish=TRUE GROUP BY k.id ORDER BY jml DESC")->fetchAll();

    $pageTitle = $art['judul'];
    require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php">Beranda</a></li>
            <li class="breadcrumb-item"><a href="<?= APP_URL ?>/informasi.php">Informasi</a></li>
            <li class="breadcrumb-item active"><?= clean($art['kat_nama']??'Artikel') ?></li>
        </ol></nav>
    </div>
</section>

<section class="section" style="background:var(--bg);">
    <div class="container">
        <div class="row g-5">

            <!-- Artikel -->
            <div class="col-lg-8 fade-up">
                <div class="card-g overflow-hidden">
                    <!-- Banner -->
                    <div class="dtl-banner" style="background:linear-gradient(135deg,<?= $art['kat_warna']??'#1a6fd4' ?>33,<?= $art['kat_warna']??'#1a6fd4' ?>55);">
                        <?php if($art['gambar_url']): ?>
                        <img src="<?= clean($art['gambar_url']) ?>" alt="" class="dtl-banner-img">
                        <?php else: ?>
                        <i class="bi bi-newspaper" style="font-size:4rem;color:<?= $art['kat_warna']??'#1a6fd4' ?>;opacity:.3;"></i>
                        <?php endif; ?>
                        <span class="dtl-badge" style="background:<?= $art['kat_warna']??'#1a6fd4' ?>;"><?= clean(strtoupper($art['kat_nama']??'ARTIKEL')) ?></span>
                    </div>

                    <div class="p-4 p-md-5">
                        <!-- Meta -->
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            <span class="small text-muted"><i class="bi bi-person-circle me-1"></i><?= clean($art['penulis']) ?></span>
                            <span class="small text-muted"><i class="bi bi-clock me-1"></i><?= $art['estimasi_baca'] ?> menit baca</span>
                            <span class="small text-muted"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($art['created_at'])) ?></span>
                        </div>
                        <!-- Judul -->
                        <h1 class="dtl-judul"><?= clean($art['judul']) ?></h1>
                        <?php if($art['ringkasan']): ?>
                        <p class="dtl-ringkasan"><?= clean($art['ringkasan']) ?></p>
                        <?php endif; ?>
                        <hr style="border-color:var(--border-light);margin:1.5rem 0;">
                        <!-- Konten -->
                        <div class="art-konten"><?= $art['konten'] ?></div>

                        <!-- CTA bawah -->
                        <div class="dtl-cta mt-4">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div>
                                    <div class="fw-700" style="font-family:var(--font-d);font-size:.9rem;">Artikel ini membantu?</div>
                                    <div class="small text-muted">Konsultasikan masalah budidaya langsung ke tim ahli.</div>
                                </div>
                                <a href="https://wa.me/<?= WA_NUMBER ?>?text=Halo+GEMPITA,+ingin+konsultasi+soal+<?= urlencode($art['judul']) ?>"
                                   target="_blank" class="btn btn-wa btn-sm px-3 ms-auto flex-shrink-0">
                                    <i class="bi bi-whatsapp me-1"></i>Konsultasi WA
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terkait -->
                <?php if(!empty($artikelTerkait)): ?>
                <div class="mt-4">
                    <h5 class="fw-700 mb-3">Artikel Terkait</h5>
                    <div class="row g-3">
                        <?php foreach($artikelTerkait as $t): ?>
                        <div class="col-md-4">
                            <a href="<?= APP_URL ?>/informasi.php?slug=<?= clean($t['slug']) ?>" class="text-decoration-none">
                                <div class="mini-card">
                                    <div class="mini-img" style="background:linear-gradient(135deg,<?= $t['kat_warna']??'#1a6fd4' ?>22,<?= $t['kat_warna']??'#1a6fd4' ?>44);">
                                        <span class="mini-badge" style="background:<?= $t['kat_warna']??'#1a6fd4' ?>;"><?= clean(strtoupper($t['kat_nama']??'')) ?></span>
                                    </div>
                                    <div class="p-3">
                                        <div class="mini-judul"><?= clean($t['judul']) ?></div>
                                        <div class="mini-meta"><?= $t['estimasi_baca'] ?> menit baca</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card-g p-4 mb-4 fade-up delay-1">
                    <h6 class="fw-700 mb-3">Kategori</h6>
                    <?php foreach($kategoriList as $kat): ?>
                    <a href="<?= APP_URL ?>/informasi.php?kategori=<?= clean($kat['slug']) ?>"
                       class="d-flex align-items-center justify-content-between py-2 border-bottom text-decoration-none" style="color:var(--text-body);">
                        <span class="d-flex align-items-center gap-2 small fw-600">
                            <span style="width:9px;height:9px;border-radius:50%;background:<?= $kat['warna'] ?>;display:inline-block;"></span>
                            <?= clean($kat['nama']) ?>
                        </span>
                        <span class="badge-g badge-gray"><?= $kat['jml'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php if(!isLoggedIn()): ?>
                <div class="sidebar-cta fade-up delay-2">
                    <i class="bi bi-journal-text" style="font-size:2rem;color:#fff;opacity:.85;margin-bottom:.65rem;display:block;"></i>
                    <div class="fw-700 text-white mb-1" style="font-family:var(--font-d);">Mulai Mencatat</div>
                    <p class="text-white small mb-3" style="opacity:.8;line-height:1.6;">Daftar gratis dan catat semua data budidaya secara digital.</p>
                    <a href="<?= APP_URL ?>/daftar-akun.php" class="btn btn-yellow btn-sm px-3 fw-700">Daftar Sekarang</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

/* ================================================================
   MODE DAFTAR ARTIKEL
   ================================================================ */
$pageTitle  = 'Informasi Budidaya';
$filterKat  = trim($_GET['kategori'] ?? '');
$cari       = trim($_GET['cari'] ?? '');
$page       = max(1, intval($_GET['page'] ?? 1));
$perPage    = 8;
$offset     = ($page - 1) * $perPage;

$where = ['a.is_publish = TRUE']; $params = [];
if ($filterKat) { $where[] = 'k.slug = ?'; $params[] = $filterKat; }
if ($cari)      { $where[] = '(a.judul ILIKE ? OR a.ringkasan ILIKE ?)'; $params[] = "%$cari%"; $params[] = "%$cari%"; }
$wsql = implode(' AND ', $where);

$totalCount = $pdo->prepare("SELECT COUNT(*) FROM artikel a LEFT JOIN kategori_artikel k ON a.kategori_id=k.id WHERE $wsql");
$totalCount->execute($params);
$total     = $totalCount->fetchColumn();
$totalPage = ceil($total / $perPage);

$artStmt = $pdo->prepare("SELECT a.*,k.nama AS kat_nama,k.warna AS kat_warna FROM artikel a LEFT JOIN kategori_artikel k ON a.kategori_id=k.id WHERE $wsql ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset");
$artStmt->execute($params);
$artikelList = $artStmt->fetchAll();

$kategoriList = $pdo->query("SELECT k.*,COUNT(a.id) AS jml FROM kategori_artikel k LEFT JOIN artikel a ON a.kategori_id=k.id AND a.is_publish=TRUE GROUP BY k.id ORDER BY jml DESC")->fetchAll();

$featured = $pdo->query("SELECT a.*,k.nama AS kat_nama,k.warna AS kat_warna FROM artikel a LEFT JOIN kategori_artikel k ON a.kategori_id=k.id WHERE a.is_publish=TRUE ORDER BY a.created_at DESC LIMIT 1")->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col">
                <nav aria-label="breadcrumb"><ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php">Beranda</a></li>
                    <li class="breadcrumb-item active">Informasi</li>
                </ol></nav>
                <h1 class="page-title">Informasi Budidaya</h1>
                <p class="page-subtitle">Tips, panduan & artikel budidaya ikan dari para ahli</p>
            </div>
            <div class="col-auto d-none d-md-block">
                <div style="width:64px;height:64px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.85rem;color:#fff;">
                    <i class="bi bi-newspaper"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background:var(--bg);">
    <div class="container">

        <!-- Featured -->
        <?php if($featured && !$filterKat && !$cari && $page===1): ?>
        <div class="feat-card mb-5 fade-up">
            <div class="row g-0 align-items-stretch">
                <div class="col-md-5">
                    <div class="feat-img" style="background:linear-gradient(135deg,<?= $featured['kat_warna']??'#1a6fd4' ?>33,<?= $featured['kat_warna']??'#1a6fd4' ?>66);">
                        <?php if($featured['gambar_url']): ?>
                        <img src="<?= clean($featured['gambar_url']) ?>" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                        <i class="bi bi-book-fill" style="font-size:4rem;color:<?= $featured['kat_warna']??'#1a6fd4' ?>;opacity:.3;"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-7 d-flex flex-column justify-content-center p-4 p-md-5">
                    <span class="badge-g mb-3" style="background:<?= $featured['kat_warna']??'#1a6fd4' ?>22;color:<?= $featured['kat_warna']??'#1a6fd4' ?>;width:fit-content;">
                        <?= clean($featured['kat_nama']??'ARTIKEL') ?>
                    </span>
                    <h3 style="font-family:var(--font-d);font-weight:800;font-size:1.25rem;line-height:1.3;margin-bottom:.5rem;"><?= clean($featured['judul']) ?></h3>
                    <p class="text-muted small mb-3" style="line-height:1.65;"><?= clean($featured['ringkasan']??'') ?></p>
                    <div class="d-flex gap-3 mb-4">
                        <span class="small text-muted"><i class="bi bi-person me-1"></i><?= clean($featured['penulis']) ?></span>
                        <span class="small text-muted"><i class="bi bi-clock me-1"></i><?= $featured['estimasi_baca'] ?> menit</span>
                    </div>
                    <a href="<?= APP_URL ?>/informasi.php?slug=<?= clean($featured['slug']) ?>" class="btn btn-primary btn-sm fw-700 px-4" style="width:fit-content;">
                        Baca Selengkapnya <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Utama -->
            <div class="col-lg-8">
                <!-- Search -->
                <div class="d-flex gap-2 flex-wrap mb-4 align-items-center">
                    <form method="GET" class="d-flex gap-2 flex-grow-1" style="max-width:380px;">
                        <?php if($filterKat): ?><input type="hidden" name="kategori" value="<?= clean($filterKat) ?>"><?php endif; ?>
                        <div class="input-group">
                            <span class="input-group-text bg-white" style="border:1.5px solid var(--border);border-right:none;border-radius:var(--r-sm) 0 0 var(--r-sm);">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" name="cari" class="form-control"
                                   style="border:1.5px solid var(--border);border-left:none;border-radius:0 var(--r-sm) var(--r-sm) 0;"
                                   placeholder="Cari artikel..." value="<?= clean($cari) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary px-3 flex-shrink-0">Cari</button>
                    </form>
                    <?php if($filterKat || $cari): ?>
                    <a href="<?= APP_URL ?>/informasi.php" class="btn btn-outline-secondary btn-sm flex-shrink-0">
                        <i class="bi bi-x me-1"></i>Reset
                    </a>
                    <?php endif; ?>
                </div>
                <?php if($filterKat || $cari): ?>
                <div class="mb-3 small text-muted">
                    <strong><?= $total ?></strong> artikel ditemukan
                    <?= $filterKat ? ' — kategori <strong>'.clean($filterKat).'</strong>' : '' ?>
                    <?= $cari ? ' — "<strong>'.clean($cari).'</strong>"' : '' ?>
                </div>
                <?php endif; ?>

                <!-- Grid -->
                <?php if(empty($artikelList)): ?>
                <div class="empty-state"><i class="bi bi-search"></i><p class="text-muted mt-2 mb-0">Tidak ada artikel.</p><a href="<?= APP_URL ?>/informasi.php" class="btn btn-primary btn-sm mt-3">Lihat Semua</a></div>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach($artikelList as $i => $a): ?>
                    <div class="col-md-6 fade-up" style="animation-delay:<?= ($i%6)*.07 ?>s">
                        <a href="<?= APP_URL ?>/informasi.php?slug=<?= clean($a['slug']) ?>" class="text-decoration-none">
                            <div class="lst-card">
                                <div class="lst-img" style="background:linear-gradient(135deg,<?= $a['kat_warna']??'#1a6fd4' ?>22,<?= $a['kat_warna']??'#1a6fd4' ?>44);">
                                    <?php if($a['gambar_url']): ?><img src="<?= clean($a['gambar_url']) ?>" alt="" class="lst-img-cover"><?php else: ?><i class="bi bi-image opacity-20" style="font-size:2rem;color:<?= $a['kat_warna']??'#1a6fd4' ?>;"></i><?php endif; ?>
                                    <span class="lst-badge" style="background:<?= $a['kat_warna']??'#1a6fd4' ?>;"><?= clean(strtoupper($a['kat_nama']??'ARTIKEL')) ?></span>
                                </div>
                                <div class="lst-body">
                                    <div class="lst-meta"><?= $a['estimasi_baca'] ?> Menit &bull; <?= clean($a['penulis']) ?></div>
                                    <h6 class="lst-judul"><?= clean($a['judul']) ?></h6>
                                    <p class="lst-ringkasan"><?= clean($a['ringkasan']??'') ?></p>
                                    <span class="lst-more">Baca Selengkapnya →</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- Pagination -->
                <?php if($totalPage > 1): ?>
                <nav class="mt-4 d-flex justify-content-center">
                    <ul class="pagination pagination-sm gap-1 mb-0">
                        <?php if($page>1): ?><li class="page-item"><a class="page-link rounded-2" href="?page=<?= $page-1 ?><?= $filterKat?"&kategori=$filterKat":'' ?><?= $cari?"&cari=".urlencode($cari):'' ?>"><i class="bi bi-chevron-left"></i></a></li><?php endif; ?>
                        <?php for($p=1;$p<=$totalPage;$p++): ?>
                        <li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link rounded-2" href="?page=<?= $p ?><?= $filterKat?"&kategori=$filterKat":'' ?><?= $cari?"&cari=".urlencode($cari):'' ?>"><?= $p ?></a></li>
                        <?php endfor; ?>
                        <?php if($page<$totalPage): ?><li class="page-item"><a class="page-link rounded-2" href="?page=<?= $page+1 ?><?= $filterKat?"&kategori=$filterKat":'' ?><?= $cari?"&cari=".urlencode($cari):'' ?>"><i class="bi bi-chevron-right"></i></a></li><?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="card-g p-4 mb-4 fade-up delay-1">
                    <h6 class="fw-700 mb-3">Kategori</h6>
                    <a href="<?= APP_URL ?>/informasi.php" class="kat-btn <?= !$filterKat?'kat-active':'' ?>">
                        <span><span class="kat-dot" style="background:var(--blue);"></span>Semua</span>
                        <span class="badge-g badge-gray"><?= $total ?></span>
                    </a>
                    <?php foreach($kategoriList as $kat): ?>
                    <a href="<?= APP_URL ?>/informasi.php?kategori=<?= clean($kat['slug']) ?>"
                       class="kat-btn <?= $filterKat===$kat['slug']?'kat-active':'' ?>">
                        <span class="d-flex align-items-center gap-2">
                            <span class="kat-dot" style="background:<?= $kat['warna'] ?>;"></span>
                            <?= clean($kat['nama']) ?>
                        </span>
                        <span class="badge-g badge-gray"><?= $kat['jml'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="card-g p-4 mb-4 fade-up delay-2">
                    <h6 class="fw-700 mb-3">Artikel Terbaru</h6>
                    <?php $pop=$pdo->query("SELECT a.slug,a.judul,a.estimasi_baca FROM artikel a WHERE a.is_publish=TRUE ORDER BY a.created_at DESC LIMIT 4")->fetchAll();
                    foreach($pop as $i=>$p): ?>
                    <a href="<?= APP_URL ?>/informasi.php?slug=<?= clean($p['slug']) ?>"
                       class="d-flex gap-3 py-2 border-bottom text-decoration-none" style="color:var(--text-body);">
                        <span class="fw-800 flex-shrink-0" style="font-size:1rem;color:var(--border-light);font-family:var(--font-d);width:24px;min-width:24px;"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></span>
                        <div>
                            <div class="small fw-600 lh-sm" style="font-family:var(--font-d);"><?= clean($p['judul']) ?></div>
                            <div style="font-size:.7rem;color:var(--text-muted);margin-top:.18rem;"><?= $p['estimasi_baca'] ?> menit baca</div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="sidebar-cta fade-up delay-3">
                    <i class="bi bi-whatsapp" style="font-size:2rem;color:#fff;margin-bottom:.65rem;display:block;"></i>
                    <div class="fw-700 text-white mb-1" style="font-family:var(--font-d);">Masih Bingung?</div>
                    <p class="text-white small mb-3" style="opacity:.82;line-height:1.6;">Konsultasikan langsung masalah budidaya Anda via WhatsApp.</p>
                    <a href="https://wa.me/<?= WA_NUMBER ?>?text=Halo+GEMPITA,+ingin+konsultasi+budidaya" target="_blank" class="btn btn-wa btn-sm px-3 fw-700">
                        <i class="bi bi-whatsapp me-2"></i>Chat Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if(isAdmin()): ?>
<div style="position:fixed;bottom:2rem;right:2rem;z-index:100;">
    <a href="<?= APP_URL ?>/admin/artikel.php" class="btn btn-primary fw-700 shadow-lg" style="border-radius:50px;padding:.65rem 1.5rem;">
        <i class="bi bi-plus-lg me-2"></i>Kelola Artikel
    </a>
</div>
<?php endif; ?>

<style>
/* Featured */
.feat-card{background:#fff;border:1.5px solid var(--border);border-radius:var(--r-xl);overflow:hidden;box-shadow:var(--shadow-xs);}
.feat-img{min-height:220px;height:100%;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;}
/* List card */
.lst-card{background:#fff;border:1.5px solid var(--border);border-radius:var(--r-lg);overflow:hidden;transition:var(--ease);height:100%;}
.lst-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md);border-color:rgba(26,111,212,.2);}
.lst-img{height:148px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;}
.lst-img-cover{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;}
.lst-badge{position:absolute;top:.5rem;left:.5rem;color:#fff;font-family:var(--font-d);font-size:.62rem;font-weight:700;padding:.16rem .55rem;border-radius:50px;letter-spacing:.4px;}
.lst-body{padding:.85rem 1rem 1rem;}
.lst-meta{font-size:.7rem;color:var(--text-muted);margin-bottom:.3rem;}
.lst-judul{font-family:var(--font-d);font-size:.9rem;font-weight:700;color:var(--text);line-height:1.35;margin-bottom:.3rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.lst-ringkasan{font-size:.78rem;color:var(--text-muted);line-height:1.55;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:.45rem;}
.lst-more{font-size:.78rem;font-weight:600;color:var(--blue);font-family:var(--font-d);}
/* Sidebar kategori */
.kat-btn{display:flex;align-items:center;justify-content:space-between;padding:.46rem .35rem;border-radius:var(--r-sm);text-decoration:none;color:var(--text-body);font-size:.86rem;font-family:var(--font-d);transition:var(--ease);margin-bottom:.08rem;}
.kat-btn:hover{background:var(--blue-soft);color:var(--blue);}
.kat-active{background:var(--blue-soft);color:var(--blue);font-weight:600;}
.kat-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;display:inline-block;}
/* Sidebar CTA */
.sidebar-cta{background:linear-gradient(135deg,var(--blue-deep),var(--blue));border-radius:var(--r-lg);padding:1.5rem;}
/* Pagination */
.page-link{font-family:var(--font-d);font-weight:600;color:var(--blue);border:1.5px solid var(--border);}
.page-item.active .page-link{background:var(--blue);border-color:var(--blue);color:#fff;}
/* Detail */
.dtl-banner{height:240px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;}
.dtl-banner-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;}
.dtl-badge{position:absolute;top:.8rem;left:.8rem;color:#fff;font-family:var(--font-d);font-size:.67rem;font-weight:700;padding:.2rem .7rem;border-radius:50px;letter-spacing:.5px;}
.dtl-judul{font-family:var(--font-d);font-size:1.5rem;font-weight:800;line-height:1.28;margin-bottom:.65rem;letter-spacing:-.3px;}
.dtl-ringkasan{color:var(--text-muted);font-size:.95rem;line-height:1.72;}
.dtl-cta{background:var(--blue-soft);border:1.5px solid var(--blue-mid);border-radius:var(--r-md);padding:1rem 1.25rem;}
/* Konten artikel */
.art-konten{font-size:.93rem;line-height:1.78;color:var(--text-body);}
.art-konten h4{font-family:var(--font-d);font-weight:700;font-size:1.05rem;color:var(--text);margin:1.5rem 0 .6rem;}
.art-konten h5{font-family:var(--font-d);font-weight:700;font-size:.96rem;color:var(--text);margin:1.2rem 0 .5rem;}
.art-konten p{margin-bottom:.85rem;}
.art-konten ul,.art-konten ol{padding-left:1.4rem;margin-bottom:.85rem;}
.art-konten li{margin-bottom:.35rem;}
.art-konten strong{color:var(--text);font-weight:700;}
/* Mini terkait */
.mini-card{background:#fff;border:1.5px solid var(--border);border-radius:var(--r-md);overflow:hidden;transition:var(--ease);}
.mini-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-sm);}
.mini-img{height:88px;display:flex;align-items:center;justify-content:center;position:relative;}
.mini-badge{position:absolute;top:.4rem;left:.4rem;color:#fff;font-family:var(--font-d);font-size:.59rem;font-weight:700;padding:.14rem .48rem;border-radius:50px;}
.mini-judul{font-family:var(--font-d);font-size:.8rem;font-weight:700;color:var(--text);line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:.2rem;}
.mini-meta{font-size:.68rem;color:var(--text-muted);}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
