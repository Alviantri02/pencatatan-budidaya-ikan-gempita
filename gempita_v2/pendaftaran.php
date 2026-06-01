<?php
/**
 * GEMPITA v2 - Pendaftaran Program
 * Hanya 1 program GEMPITA, form langsung, tanpa NIK
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'Pendaftaran Program';
$pdo       = getDB();
$errors    = [];
$sukses    = false;
$input     = ['nama_lengkap'=>'','email'=>'','no_hp'=>'','alamat'=>''];

// Pre-fill jika sudah login
if (isLoggedIn()) {
    $u = currentUser();
    $input['nama_lengkap'] = $u['nama'];
    $input['email']        = $u['email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
        'email'        => trim($_POST['email']        ?? ''),
        'no_hp'        => trim($_POST['no_hp']        ?? ''),
        'alamat'       => trim($_POST['alamat']       ?? ''),
    ];

    // Validasi
    if (empty($input['nama_lengkap']))
        $errors['nama_lengkap'] = 'Nama lengkap wajib diisi.';

    if (empty($input['email']))
        $errors['email'] = 'Email aktif wajib diisi.';
    elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'Format email tidak valid.';

    if (empty($input['no_hp']))
        $errors['no_hp'] = 'Nomor HP/WhatsApp wajib diisi.';

    // Cek apakah email sudah pernah mendaftar
    if (empty($errors['email'])) {
        $cek = $pdo->prepare("SELECT id FROM pendaftaran WHERE email = ?");
        $cek->execute([$input['email']]);
        if ($cek->fetch())
            $errors['email'] = 'Email ini sudah terdaftar dalam program GEMPITA.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO pendaftaran (nama_lengkap, email, no_hp, alamat)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['nama_lengkap'],
            $input['email'],
            $input['no_hp'],
            $input['alamat'],
        ]);
        $sukses = true;
        $input  = ['nama_lengkap'=>'','email'=>'','no_hp'=>'','alamat'=>''];
    }
}

// Hitung total pendaftar
$totalPendaftar = $pdo->query("SELECT COUNT(*) FROM pendaftaran")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col">
                <nav aria-label="breadcrumb"><ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php">Beranda</a></li>
                    <li class="breadcrumb-item active">Pendaftaran</li>
                </ol></nav>
                <h1 class="page-title">Pendaftaran Program GEMPITA</h1>
                <p class="page-subtitle">Bergabunglah dalam komunitas pembudidaya modern</p>
            </div>
            <div class="col-auto d-none d-md-block">
                <div style="width:64px;height:64px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.85rem;color:#fff;">
                    <i class="bi bi-award-fill"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background:var(--bg);">
    <div class="container">

        <?php if ($sukses): ?>
        <!-- ── Halaman Sukses ── -->
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center fade-up">
                <div class="sukses-wrap">
                    <div class="sukses-icon">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <h3 class="fw-800 mt-3 mb-2">Pendaftaran Berhasil! 🎉</h3>
                    <p class="text-muted mb-4" style="line-height:1.7;">
                        Terima kasih telah mendaftar program GEMPITA.<br>
                        Tim kami akan menghubungi Anda melalui email atau WhatsApp
                        untuk informasi selanjutnya.
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="<?= APP_URL ?>/index.php" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-house me-2"></i>Kembali ke Beranda
                        </a>
                        <a href="https://wa.me/<?= WA_NUMBER ?>?text=Halo+GEMPITA,+saya+baru+mendaftar+program+dan+ingin+konfirmasi"
                           target="_blank" class="btn btn-wa px-4 fw-700">
                            <i class="bi bi-whatsapp me-2"></i>Konfirmasi via WA
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- ── Layout 2 Kolom ── -->
        <div class="row g-5 align-items-start">

            <!-- KIRI: Info Program -->
            <div class="col-lg-6 fade-up">

                <!-- Badge -->
                <div class="mb-3">
                    <span class="section-badge">GEMPITA OPPORTUNITY</span>
                </div>
                <h2 class="fw-800 mb-3" style="font-family:var(--font-d);font-size:1.85rem;line-height:1.2;letter-spacing:-.3px;">
                    Pendaftaran Program<br>GEMPITA
                </h2>
                <p class="text-muted mb-4" style="line-height:1.72;">
                    Bergabunglah dalam semua komunitas pembudidaya modern.
                    Dapatkan akses ke materi eksklusif, bimbingan langsung,
                    dan edukasi program GEMPITA terpadu.
                </p>

                <!-- Gambar / Banner Program -->
                <div class="program-banner mb-4">
                    <div class="program-banner-img">
                        <div class="program-banner-overlay">
                            <div class="program-banner-tag">Workshop Budidaya Ikan</div>
                            <div class="program-banner-sub">Bagi praktis langsung di kolam edukasi GEMPITA</div>
                        </div>
                    </div>
                </div>

                <!-- Keunggulan Program -->
                <div class="row g-3 mb-4">
                    <?php $unggulan = [
                        ['bi-people-fill',   'var(--blue)',       'var(--blue-soft)',    'Edukasi Terpadu',    'Bimbingan yang dirangcang oleh ahli untuk memastikan pertumbuhan optimal.'],
                        ['bi-bar-chart-fill','var(--teal)',       'var(--teal-soft)',    'Manajemen Data',     'Belajar menggunakan dashboard digital untuk memantau perkembangan budidaya ikan.'],
                        ['bi-chat-dots-fill','var(--yellow-dark)','var(--yellow-light)','Komunitas Pembudidaya','Akses forum diskusi dan jaringan distribusi hasil panen serta informasi.'],
                    ];
                    foreach ($unggulan as $u): ?>
                    <div class="col-12">
                        <div class="d-flex align-items-start gap-3">
                            <div class="icon-box icon-box-md flex-shrink-0"
                                 style="background:<?= $u[2] ?>;color:<?= $u[1] ?>;">
                                <i class="bi <?= $u[0] ?>"></i>
                            </div>
                            <div>
                                <div class="fw-700 small" style="font-family:var(--font-d);"><?= $u[3] ?></div>
                                <div class="text-muted" style="font-size:.8rem;line-height:1.55;"><?= $u[4] ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Link Guidebook -->
                <div class="guidebook-box">
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-box icon-box-md flex-shrink-0 icon-box-yellow">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-700 small" style="font-family:var(--font-d);">Guidebook Program GEMPITA</div>
                            <div class="text-muted" style="font-size:.78rem;">Baca panduan lengkap sebelum mendaftar</div>
                        </div>
                        <a href="#"
                           target="_blank"
                           class="btn btn-outline-primary btn-sm fw-700 flex-shrink-0"
                           id="btnGuidebook">
                            <i class="bi bi-download me-1"></i>Unduh
                        </a>
                    </div>
                </div>
                <!-- Catatan guidebook (link menyusul) -->
                <div class="mt-2 small text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Link guidebook akan segera tersedia. Hubungi kami via WhatsApp untuk informasi lebih lanjut.
                </div>

                <!-- Statistik -->
                <div class="d-flex gap-4 mt-4">
                    <div>
                        <div class="fw-800" style="font-family:var(--font-d);font-size:1.5rem;color:var(--blue);"><?= number_format($totalPendaftar) ?>+</div>
                        <div class="small text-muted">Pendaftar</div>
                    </div>
                    <div>
                        <div class="fw-800" style="font-family:var(--font-d);font-size:1.5rem;color:var(--teal);">Gratis</div>
                        <div class="small text-muted">Biaya Pendaftaran</div>
                    </div>
                    <div>
                        <div class="fw-800" style="font-family:var(--font-d);font-size:1.5rem;color:var(--yellow-dark);">24/7</div>
                        <div class="small text-muted">Support WA</div>
                    </div>
                </div>

            </div>

            <!-- KANAN: Form -->
            <div class="col-lg-6 fade-up delay-1">
                <div class="card-g p-4 p-md-5" style="border-radius:var(--r-xl);">

                    <div class="mb-4">
                        <h5 class="fw-700 mb-1" style="font-family:var(--font-d);">Formulir Pendaftaran</h5>
                        <p class="text-muted small mb-0">Lengkapi data diri Anda untuk memulai perjalanan budidaya.</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                    <div class="soft-alert soft-red mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Periksa kembali data yang Anda isi.
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="form-g" novalidate>

                        <!-- Nama Lengkap -->
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap"
                                   class="form-control <?= isset($errors['nama_lengkap'])?'is-invalid':'' ?>"
                                   placeholder="Masukkan nama sesuai KTP"
                                   value="<?= clean($input['nama_lengkap']) ?>" required>
                            <?php if(isset($errors['nama_lengkap'])): ?>
                            <div class="invalid-feedback"><?= $errors['nama_lengkap'] ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label">Email Aktif <span class="text-danger">*</span></label>
                            <input type="email" name="email"
                                   class="form-control <?= isset($errors['email'])?'is-invalid':'' ?>"
                                   placeholder="email@contoh.com"
                                   value="<?= clean($input['email']) ?>" required>
                            <?php if(isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= $errors['email'] ?></div>
                            <?php else: ?>
                            <div class="form-text">Konfirmasi pendaftaran akan dikirim ke email ini.</div>
                            <?php endif; ?>
                        </div>

                        <!-- No HP -->
                        <div class="mb-3">
                            <label class="form-label">Nomor HP / WhatsApp <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">+62</span>
                                <input type="tel" name="no_hp"
                                       class="form-control <?= isset($errors['no_hp'])?'is-invalid':'' ?>"
                                       placeholder="812 3456 7890"
                                       value="<?= clean($input['no_hp']) ?>" required>
                            </div>
                            <?php if(isset($errors['no_hp'])): ?>
                            <div class="text-danger mt-1" style="font-size:.8rem;"><?= $errors['no_hp'] ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Pilihan Program (hanya 1, disabled) -->
                        <div class="mb-3">
                            <label class="form-label">Pilihan Program</label>
                            <select class="form-select" disabled>
                                <option selected>Budidaya Ikan Nila Modern</option>
                            </select>
                            <div class="form-text">Program GEMPITA saat ini hanya tersedia 1 pilihan.</div>
                        </div>

                        <!-- Alamat -->
                        <div class="mb-4">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2"
                                      placeholder="Jl. ..., Kecamatan, Kabupaten/Kota"><?= clean($input['alamat']) ?></textarea>
                        </div>

                        <!-- Checkbox Syarat -->
                        <div class="mb-4">
                            <label class="d-flex align-items-start gap-2" style="cursor:pointer;">
                                <input type="checkbox" name="setuju" required
                                       style="margin-top:.2rem;accent-color:var(--blue);flex-shrink:0;">
                                <span class="small text-muted">
                                    Saya menyetujui syarat dan ketentuan program GEMPITA serta
                                    <a href="#" style="color:var(--blue);font-weight:600;">Kebijakan Privasi</a>
                                    yang berlaku.
                                </span>
                            </label>
                        </div>

                        <button type="submit"
                                class="btn btn-yellow w-100 py-2 fw-700"
                                style="border-radius:10px;font-size:.95rem;">
                            <i class="bi bi-send me-2"></i>Daftar
                        </button>
                    </form>

                    <!-- Sudah punya akun? -->
                    <?php if (!isLoggedIn()): ?>
                    <div class="text-center mt-4 small text-muted">
                        Sudah punya akun?
                        <a href="<?= APP_URL ?>/login.php" class="fw-700" style="color:var(--blue);">Masuk di sini</a>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
        <?php endif; ?>

    </div>
</section>

<style>
/* Program Banner */
.program-banner-img {
    background: linear-gradient(135deg, var(--blue-deep) 0%, var(--blue) 100%);
    border-radius: var(--r-lg);
    height: 200px;
    display: flex;
    align-items: flex-end;
    position: relative;
    overflow: hidden;
}
.program-banner-img::before {
    content: '';
    position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='50' cy='50' r='40' fill='none' stroke='%23ffffff' stroke-width='1' stroke-opacity='0.08'/%3E%3C/svg%3E");
    background-size: 100px;
}
.program-banner-overlay {
    position: relative; z-index: 1;
    padding: 1.25rem;
    width: 100%;
}
.program-banner-tag {
    display: inline-block;
    background: rgba(255,255,255,.2);
    border: 1px solid rgba(255,255,255,.3);
    color: #fff;
    font-family: var(--font-d);
    font-size: .72rem; font-weight: 700;
    padding: .25rem .75rem; border-radius: 50px;
    margin-bottom: .5rem;
    letter-spacing: .3px;
}
.program-banner-sub {
    color: rgba(255,255,255,.8);
    font-size: .82rem;
    line-height: 1.5;
}

/* Guidebook box */
.guidebook-box {
    background: var(--yellow-light);
    border: 1.5px solid #fde68a;
    border-radius: var(--r-md);
    padding: 1rem 1.25rem;
}

/* Sukses */
.sukses-wrap {
    background: #fff;
    border: 1.5px solid var(--border);
    border-radius: var(--r-xl);
    padding: 3rem 2rem;
    box-shadow: var(--shadow-sm);
}
.sukses-icon {
    width: 72px; height: 72px;
    background: linear-gradient(135deg, var(--blue), var(--blue-dark));
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto;
    font-size: 2rem; color: #fff;
    box-shadow: var(--shadow-blue);
}

/* soft alert */
.soft-alert { padding: .65rem 1rem; border-radius: 10px; font-size: .84rem; font-family: var(--font-d); font-weight: 500; }
.soft-red   { background: var(--red-soft); color: var(--red); border: 1px solid #fecaca; }

/* Input group fix */
.form-g .input-group .form-control { border-left: none !important; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Tombol guidebook — sementara tampilkan info WA
document.getElementById('btnGuidebook')?.addEventListener('click', function(e) {
    e.preventDefault();
    alert('Guidebook belum tersedia.\nHubungi kami via WhatsApp untuk mendapatkan guidebook program GEMPITA.');
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
