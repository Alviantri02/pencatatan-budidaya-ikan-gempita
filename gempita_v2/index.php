<?php
/**
 * GEMPITA v2 - Halaman Beranda
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'Beranda';
$pdo       = getDB();

$totalPembudidaya = $pdo->query("SELECT COUNT(*) FROM users WHERE role='pembudidaya'")->fetchColumn();
$totalPendaftar   = $pdo->query("SELECT COUNT(*) FROM pendaftaran")->fetchColumn();
$totalArtikel     = $pdo->query("SELECT COUNT(*) FROM artikel WHERE is_publish=TRUE")->fetchColumn();

$artikelTerbaru = $pdo->query("
    SELECT a.*, k.nama AS kat_nama, k.warna AS kat_warna
    FROM artikel a
    LEFT JOIN kategori_artikel k ON a.kategori_id = k.id
    WHERE a.is_publish = TRUE
    ORDER BY a.created_at DESC LIMIT 3
")->fetchAll();


require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="hero-wrap">
    <div class="container position-relative" style="z-index:1;">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="h-badge fade-up">🐟 Program Resmi Budidaya Ikan</span>
                <h1 class="h-title fade-up delay-1">
                    Panduan Lengkap<br>Program <span>GEMPITA</span>
                </h1>
                <p class="h-sub fade-up delay-2">
                    Temukan panduan lengkap terkait program Gerakan Masyarakat
                    Pembudidaya Ikan Terpadu. Pelatihan, pencatatan digital, dan
                    akses program bantuan dalam satu platform.
                </p>
                <div class="d-flex gap-3 flex-wrap fade-up delay-2">
                    <a href="<?= APP_URL ?>/pendaftaran.php"
                       class="btn btn-yellow btn-lg px-4 fw-700">Daftar</a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block fade-up delay-2">
                <div class="h-stats-card">
                    <div class="row g-3">
                        <?php foreach ([
                            [number_format($totalPembudidaya).'+','Pembudidaya Terdaftar','bi-people-fill',     '#93c5fd'],
                            [number_format($totalPendaftar).'+', 'Peserta Program',       'bi-award-fill',      '#fcd34d'],
                            [number_format($totalArtikel),       'Artikel Budidaya',      'bi-book-fill',       '#6ee7b7'],
                            ['24/7',                             'Konsultasi WA',         'bi-whatsapp',        '#6ee7b7'],
                        ] as $s): ?>
                        <div class="col-6">
                            <div class="h-stat">
                                <i class="bi <?= $s[2] ?>" style="color:<?= $s[3] ?>;font-size:1.3rem;display:block;margin-bottom:.35rem;"></i>
                                <div class="h-stat-n"><?= $s[0] ?></div>
                                <div class="h-stat-l"><?= $s[1] ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ARTIKEL TERBARU -->
<section class="section" style="background:#fff;">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 class="fw-800 mb-0">Artikel Terbaru</h4>
            <a href="<?= APP_URL ?>/informasi.php" class="small fw-600 text-blue">LIHAT SEMUA</a>
        </div>
        <?php if (empty($artikelTerbaru)): ?>
        <div class="empty-state"><i class="bi bi-newspaper"></i><p class="text-muted small mt-2 mb-0">Belum ada artikel.</p></div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($artikelTerbaru as $i_art => $art): ?>
            <div class="col-md-4 fade-up" style="animation-delay:<?= $i_art*.08 ?>s">
                <a href="<?= APP_URL ?>/informasi.php?slug=<?= clean($art['slug']) ?>" class="art-link">
                    <div class="art-card">
                        <div class="art-img" style="background:linear-gradient(135deg,<?= $art['kat_warna']??'#1a6fd4' ?>22,<?= $art['kat_warna']??'#1a6fd4' ?>44);">
                            <?php if ($art['gambar_url']): ?>
                            <img src="<?= clean($art['gambar_url']) ?>" alt="" class="art-img-real">
                            <?php else: ?>
                            <i class="bi bi-image opacity-20" style="font-size:2rem;color:<?= $art['kat_warna']??'#1a6fd4' ?>;"></i>
                            <?php endif; ?>
                            <span class="art-badge" style="background:<?= $art['kat_warna']??'#1a6fd4' ?>;"><?= clean(strtoupper($art['kat_nama']??'ARTIKEL')) ?></span>
                        </div>
                        <div class="art-body">
                            <div class="art-meta"><?= $art['estimasi_baca'] ?> Menit Baca &bull; Oleh <?= clean($art['penulis']) ?></div>
                            <h6 class="art-judul"><?= clean($art['judul']) ?></h6>
                            <p class="art-ringkasan"><?= clean($art['ringkasan']??'') ?></p>
                            <span class="art-more">Baca Selengkapnya &rarr;</span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA PENCATATAN -->
<section class="section" style="background:var(--bg);">
    <div class="container">
        <div class="cta-pencatatan fade-up">
            <div class="cta-pnc-left">
                <div class="cta-pnc-icon">
                    <i class="bi bi-journal-check"></i>
                </div>
                <div>
                    <h4 class="cta-pnc-title">Mulai Catat Budidaya Anda</h4>
                    <p class="cta-pnc-sub">Rekam data kolam, pakan, dan panen secara digital. Hitung otomatis untung &amp; rugi setiap siklus budidaya Anda.</p>
                    <div class="cta-pnc-features">
                        <span><i class="bi bi-check-circle-fill"></i> Data Kolam &amp; Bibit</span>
                        <span><i class="bi bi-check-circle-fill"></i> Catatan Pakan Harian</span>
                        <span><i class="bi bi-check-circle-fill"></i> Kalkulasi Panen Otomatis</span>
                    </div>
                </div>
            </div>
            <div class="cta-pnc-right">
                <?php if (isLoggedIn()): ?>
                <a href="<?= APP_URL ?>/pencatatan/dashboard.php" class="btn-cta-pnc">
                    <i class="bi bi-speedometer2 me-2"></i>Buka Dashboard Pencatatan
                </a>
                <?php else: ?>
                <a href="<?= APP_URL ?>/login.php" class="btn-cta-pnc">
                    <i class="bi bi-pencil-square me-2"></i>Mulai Pencatatan
                </a>
                <div class="cta-pnc-hint">Belum punya akun? <a href="<?= APP_URL ?>/daftar-akun.php">Daftar gratis &rarr;</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<?php if (!isLoggedIn()): ?>
<section class="cta-wrap">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7 text-white">
                <h4 class="fw-800 mb-1">Gabung Bersama Ribuan Pembudidaya Sukses</h4>
                <p class="mb-0 small" style="opacity:.85;">
                    Dapatkan akses pencatatan digital dan mulai hitung keuntungan budidaya Anda hari ini.
                </p>
                <div class="d-flex align-items-center gap-2 mt-3">
                    <div class="d-flex">
                        <?php for ($i=1;$i<=3;$i++): ?>
                        <div style="width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.3);border:2px solid rgba(255,255,255,.5);display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:700;color:#fff;font-family:var(--font-d);margin-right:-7px;"><?= chr(64+$i) ?></div>
                        <?php endfor; ?>
                    </div>
                    <span class="small" style="opacity:.75;"><?= number_format($totalPembudidaya) ?>+ pengguna aktif</span>
                </div>
            </div>
            <div class="col-lg-5 d-flex justify-content-lg-end">
                <a href="<?= APP_URL ?>/daftar-akun.php" class="btn btn-yellow btn-lg px-4 fw-700">
                    Daftar Gratis Sekarang
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
/* Hero */
.hero-wrap{background:linear-gradient(135deg,var(--blue-deep) 0%,var(--blue) 55%,#2563eb 100%);color:#fff;padding:72px 0 64px;position:relative;overflow:hidden;}
.hero-wrap::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 65% 55% at 80% 50%,rgba(255,255,255,.07) 0%,transparent 55%);pointer-events:none;}
.h-badge{display:inline-block;background:rgba(255,255,255,.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.22);color:#fff;font-family:var(--font-d);font-size:.78rem;font-weight:600;padding:.35rem 1rem;border-radius:50px;margin-bottom:1.1rem;}
.h-title{font-family:var(--font-d);font-size:clamp(1.9rem,4.5vw,2.85rem);font-weight:800;line-height:1.18;margin-bottom:1rem;letter-spacing:-.5px;}
.h-title span{color:#fdd078;}
.h-sub{font-size:1rem;opacity:.88;max-width:480px;margin-bottom:1.75rem;line-height:1.72;}
.h-stats-card{background:rgba(255,255,255,.12);backdrop-filter:blur(16px);border:1.5px solid rgba(255,255,255,.2);border-radius:var(--r-xl);padding:1.75rem;}
.h-stat{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:var(--r-md);padding:1.1rem;text-align:center;color:#fff;}
.h-stat-n{font-family:var(--font-d);font-size:1.55rem;font-weight:800;line-height:1;}
.h-stat-l{font-size:.7rem;opacity:.76;font-weight:500;margin-top:.22rem;}
/* Artikel */
.art-link{text-decoration:none;color:inherit;display:block;}
.art-card{background:#fff;border:1.5px solid var(--border);border-radius:var(--r-lg);overflow:hidden;transition:var(--ease);}
.art-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md);border-color:rgba(26,111,212,.2);}
.art-img{height:148px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;}
.art-img-real{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;}
.art-badge{position:absolute;top:.55rem;left:.55rem;color:#fff;font-family:var(--font-d);font-size:.64rem;font-weight:700;padding:.18rem .6rem;border-radius:50px;letter-spacing:.4px;}
.art-body{padding:.85rem 1rem 1rem;}
.art-meta{font-size:.7rem;color:var(--text-muted);margin-bottom:.32rem;}
.art-judul{font-family:var(--font-d);font-size:.9rem;font-weight:700;color:var(--text);line-height:1.35;margin-bottom:.32rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.art-ringkasan{font-size:.78rem;color:var(--text-muted);line-height:1.55;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:.5rem;}
.art-more{font-size:.78rem;font-weight:600;color:var(--blue);font-family:var(--font-d);}
/* CTA Pencatatan */
.cta-pencatatan{background:#fff;border:1.5px solid var(--border);border-radius:var(--r-xl);padding:2.5rem 2.75rem;display:flex;align-items:center;justify-content:space-between;gap:2.5rem;box-shadow:var(--shadow-xs);}
.cta-pnc-left{display:flex;align-items:flex-start;gap:1.5rem;flex:1;}
.cta-pnc-icon{width:56px;height:56px;background:linear-gradient(135deg,var(--blue-light),var(--blue));border-radius:var(--r-lg);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#fff;flex-shrink:0;}
.cta-pnc-title{font-family:var(--font-d);font-weight:800;font-size:1.2rem;color:var(--text);margin-bottom:.4rem;letter-spacing:-.2px;}
.cta-pnc-sub{font-size:.875rem;color:var(--text-muted);line-height:1.65;margin-bottom:.85rem;max-width:480px;}
.cta-pnc-features{display:flex;flex-wrap:wrap;gap:.5rem .95rem;}
.cta-pnc-features span{font-family:var(--font-d);font-size:.78rem;font-weight:600;color:var(--text-body);display:flex;align-items:center;gap:.3rem;}
.cta-pnc-features i{color:var(--green);font-size:.78rem;}
.cta-pnc-right{display:flex;flex-direction:column;align-items:center;gap:.65rem;flex-shrink:0;}
.btn-cta-pnc{display:inline-flex;align-items:center;background:var(--blue);color:#fff;font-family:var(--font-d);font-weight:700;font-size:.92rem;padding:.78rem 1.75rem;border-radius:var(--r-sm);text-decoration:none;transition:var(--ease);white-space:nowrap;box-shadow:var(--shadow-blue);}
.btn-cta-pnc:hover{background:var(--blue-dark);color:#fff;transform:translateY(-2px);box-shadow:0 6px 20px rgba(26,111,212,.35);}
.cta-pnc-hint{font-size:.78rem;color:var(--text-muted);text-align:center;}
.cta-pnc-hint a{color:var(--blue);font-weight:600;text-decoration:none;}
.cta-pnc-hint a:hover{text-decoration:underline;}
/* CTA */
.cta-wrap{background:linear-gradient(135deg,var(--blue-deep),var(--blue));padding:48px 0;}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
