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

$pembaruan = [
    ['tgl'=>'12 Mei 2025','icon'=>'bi-megaphone-fill',    'warna'=>'var(--blue)',       'bg'=>'var(--blue-soft)',    'judul'=>'Pendaftaran Program GEMPITA Batch 5 Dibuka'],
    ['tgl'=>'10 Mei 2025','icon'=>'bi-arrow-clockwise',   'warna'=>'var(--yellow-dark)','bg'=>'var(--yellow-light)','judul'=>'Pembaruan Sistem Pencatatan Kolam v2.1'],
    ['tgl'=>'08 Mei 2025','icon'=>'bi-calendar-event-fill','warna'=>'var(--teal)',       'bg'=>'var(--teal-soft)',   'judul'=>'Webinar: Strategi Pasaran Harga Ikan Air Tawar'],
];

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
                    <a href="<?= APP_URL ?>/informasi.php"
                       class="btn-hero-outline btn-lg px-4">Lihat Guidebook</a>
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

<!-- PEMBARUAN + ARTIKEL -->
<section class="section" style="background:#fff;">
    <div class="container">
        <div class="row g-5">
            <!-- Pembaruan -->
            <div class="col-lg-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="fw-800 mb-0">Pembaruan</h4>
                    <a href="<?= APP_URL ?>/informasi.php" class="small fw-600 text-blue">LIHAT SEMUA</a>
                </div>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($pembaruan as $p): ?>
                    <div class="d-flex align-items-start gap-3">
                        <div style="width:38px;height:38px;border-radius:10px;background:<?= $p['bg'] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi <?= $p['icon'] ?>" style="color:<?= $p['warna'] ?>;font-size:.9rem;"></i>
                        </div>
                        <div>
                            <div style="font-size:.7rem;color:var(--text-muted);font-weight:500;margin-bottom:.18rem;"><?= $p['tgl'] ?></div>
                            <div style="font-family:var(--font-d);font-size:.86rem;font-weight:600;color:var(--text);line-height:1.38;"><?= $p['judul'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Artikel Terbaru -->
            <div class="col-lg-8">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="fw-800 mb-0">Artikel Terbaru</h4>
                    <a href="<?= APP_URL ?>/informasi.php" class="text-muted small"><i class="bi bi-grid-3x3-gap fs-5"></i></a>
                </div>
                <?php if (empty($artikelTerbaru)): ?>
                <div class="empty-state"><i class="bi bi-newspaper"></i><p class="text-muted small mt-2 mb-0">Belum ada artikel.</p></div>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($artikelTerbaru as $i => $art): ?>
                    <div class="col-md-6 fade-up" style="animation-delay:<?= $i*.08 ?>s">
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
                                    <span class="art-more">Baca Selengkapnya →</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- PANDUAN TEKNIK BUDIDAYA -->
<section class="section" style="background:var(--bg);">
    <div class="container">
        <h4 class="fw-800 mb-4">Panduan Teknik Budidaya</h4>
        <div class="row g-4">
            <!-- Card Besar -->
            <div class="col-lg-5 fade-up">
                <div class="teknik-big">
                    <div class="teknik-big-body">
                        <span class="teknik-level">TINGKAT LANJUT</span>
                        <h4 class="text-white fw-800 mt-2 mb-2">Sistem Bioflok 101</h4>
                        <p class="text-white small mb-3" style="opacity:.75;line-height:1.6;">
                            Panduan komprehensif mengelola mikroorganisme untuk efisiensi pakan yang maksimal.
                        </p>
                        <a href="<?= APP_URL ?>/informasi.php?slug=sistem-bioflok-untuk-pemula"
                           class="btn btn-light btn-sm fw-700 px-4">Pelajari Teknik</a>
                    </div>
                </div>
            </div>

            <!-- Kanan -->
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-md-6 fade-up delay-1">
                        <div class="card-g p-4 h-100">
                            <div class="icon-box icon-box-md mb-3" style="background:rgba(220,38,38,.08);color:var(--red);">
                                <i class="bi bi-shield-check-fill"></i>
                            </div>
                            <h6 class="fw-700 mb-2">Protokol Biosekuriti</h6>
                            <p class="small text-muted mb-3" style="line-height:1.6;">Langkah preventif wajib untuk mencegah wabah penyakit di area peternakan.</p>
                            <a href="<?= APP_URL ?>/informasi.php" class="small fw-600 text-blue">Lihat Detail <i class="bi bi-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                    <div class="col-md-6 fade-up delay-2">
                        <div class="card-g p-4 h-100">
                            <div class="icon-box icon-box-md mb-3" style="background:rgba(14,165,160,.08);color:var(--teal);">
                                <i class="bi bi-wind"></i>
                            </div>
                            <h6 class="fw-700 mb-2">Oksigenasi Terpadu</h6>
                            <p class="small text-muted mb-3" style="line-height:1.6;">Penyusunan sistem aerasi yang efisien namun tetap bertenaga untuk kolam Anda.</p>
                            <a href="<?= APP_URL ?>/informasi.php" class="small fw-600 text-blue">Lihat Detail <i class="bi bi-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                    <!-- Harga Pasaran -->
                    <div class="col-12 fade-up delay-3">
                        <div class="card-g p-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box icon-box-lg icon-box-blue flex-shrink-0">
                                    <i class="bi bi-graph-up-arrow"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-700" style="font-size:.95rem;">Update Pasaran Harga Mingguan</div>
                                    <div class="small text-muted mt-1">Update fluktuasi harga komoditas ikan air tawar di tingkat nasional.</div>
                                    <div class="d-flex gap-2 mt-2 flex-wrap">
                                        <span class="badge-g badge-blue">NILA ↑ 2.4%</span>
                                        <span class="badge-g badge-teal">LELE — STABIL</span>
                                    </div>
                                </div>
                                <a href="<?= APP_URL ?>/<?= isLoggedIn() ? 'pencatatan/harga-pasaran.php' : 'login.php?pesan=harap_login' ?>"
                                   class="harga-go flex-shrink-0" title="<?= isLoggedIn() ? 'Input Harga' : 'Login dulu' ?>">
                                    <i class="bi <?= isLoggedIn() ? 'bi-arrow-right' : 'bi-lock-fill' ?>" style="font-size:<?= isLoggedIn() ? '1rem' : '.85rem' ?>;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
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
.btn-hero-outline{border:2px solid rgba(255,255,255,.5);color:#fff;background:transparent;font-family:var(--font-d);font-weight:600;border-radius:var(--r-sm);transition:var(--ease);display:inline-flex;align-items:center;}
.btn-hero-outline:hover{background:rgba(255,255,255,.15);border-color:#fff;color:#fff;}
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
/* Teknik */
.teknik-big{background:linear-gradient(135deg,var(--blue-deep),var(--blue));border-radius:var(--r-lg);min-height:280px;display:flex;align-items:flex-end;position:relative;overflow:hidden;}
.teknik-big::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='40' cy='40' r='32' fill='none' stroke='%23fff' stroke-width='1' stroke-opacity='.07'/%3E%3C/svg%3E");}
.teknik-big-body{padding:1.5rem;position:relative;z-index:1;width:100%;}
.teknik-level{font-family:var(--font-d);font-size:.65rem;font-weight:700;letter-spacing:1.2px;color:#fdd078;text-transform:uppercase;}
.harga-go{width:38px;height:38px;background:var(--blue);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;transition:var(--ease);text-decoration:none;}
.harga-go:hover{background:var(--blue-dark);color:#fff;transform:scale(1.08);}
/* CTA */
.cta-wrap{background:linear-gradient(135deg,var(--blue-deep),var(--blue));padding:48px 0;}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
