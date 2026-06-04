<?php
/**
 * GEMPITA v2 - Daftar Akun
 * Layout 2 kolom: kiri (info) + kanan (form)
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isLoggedIn()) redirect(APP_URL . '/index.php');

$pageTitle = 'Daftar Akun';
$errors    = [];
$input     = [
    'nama_lengkap'  => '',
    'username'      => '',
    'email'         => '',
    'no_hp'         => '',
    'alamat'        => '',
    'password'      => '',
    'konfirmasi'    => '',
    'nama_tambak'   => '',
    'lokasi_tambak' => '',
    'jumlah_kolam'  => '',
    'jenis_ikan'    => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'nama_lengkap'  => trim($_POST['nama_lengkap']  ?? ''),
        'username'      => trim($_POST['username']       ?? ''),
        'email'         => trim($_POST['email']          ?? ''),
        'no_hp'         => trim($_POST['no_hp']          ?? ''),
        'alamat'        => trim($_POST['alamat']         ?? ''),
        'password'      => $_POST['password']            ?? '',
        'konfirmasi'    => $_POST['konfirmasi']          ?? '',
        'nama_tambak'   => trim($_POST['nama_tambak']    ?? ''),
        'lokasi_tambak' => trim($_POST['lokasi_tambak']  ?? ''),
        'jumlah_kolam'  => trim($_POST['jumlah_kolam']   ?? ''),
        'jenis_ikan'    => $_POST['jenis_ikan']          ?? [],
    ];

    // Validasi
    if (empty($input['nama_lengkap']))
        $errors['nama_lengkap'] = 'Nama lengkap wajib diisi.';

    if (empty($input['email']))
        $errors['email'] = 'Email wajib diisi.';
    elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'Format email tidak valid.';

    if (empty($input['no_hp']))
        $errors['no_hp'] = 'Nomor WhatsApp wajib diisi.';

    if (empty($input['password']))
        $errors['password'] = 'Kata sandi wajib diisi.';
    elseif (strlen($input['password']) < 8)
        $errors['password'] = 'Kata sandi minimal 8 karakter.';

    if ($input['password'] !== $input['konfirmasi'])
        $errors['konfirmasi'] = 'Konfirmasi kata sandi tidak cocok.';

    // Cek email sudah dipakai
    if (empty($errors['email'])) {
        $pdo  = getDB();
        $cek  = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $cek->execute([$input['email']]);
        if ($cek->fetch()) $errors['email'] = 'Email sudah terdaftar.';
    }

    if (empty($errors)) {
        $pdo      = getDB();
        // Generate username dari email jika kosong
        $username = !empty($input['username'])
            ? $input['username']
            : explode('@', $input['email'])[0] . rand(10,99);

        $hash = password_hash($input['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare("
            INSERT INTO users (nama_lengkap, username, email, password, no_hp, alamat, role)
            VALUES (?, ?, ?, ?, ?, ?, 'pembudidaya')
        ");
        $stmt->execute([
            $input['nama_lengkap'],
            $username,
            $input['email'],
            $hash,
            $input['no_hp'],
            $input['alamat'],
        ]);

        setFlash('success', 'Akun berhasil dibuat! Silakan login dengan email Anda.');
        redirect(APP_URL . '/login.php');
    }
}

$jenisIkanOpsi = ['Ikan Lele', 'Ikan Nila', 'Ikan Gurame', 'Ikan Patin', 'Lainnya'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body style="background:var(--bg);font-family:var(--font-b);">

<!-- Tombol Kembali -->
<div style="position:fixed;top:1rem;left:1rem;z-index:100;">
    <a href="<?= APP_URL ?>/index.php"
       class="btn btn-sm"
       style="background:#fff;border:1.5px solid var(--border);color:var(--text-body);font-family:var(--font-d);font-weight:600;border-radius:8px;padding:.35rem .85rem;box-shadow:var(--shadow-xs);">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="container py-5">
    <div class="row g-0 min-vh-100 align-items-start" style="padding-top:2rem;">

        <!-- KIRI: Info & Ilustrasi -->
        <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-center pe-5 py-5">
            <div style="max-width:380px;">
                <div class="mb-3">
                    <span class="section-badge">AYO BERGABUNG</span>
                </div>
                <h2 style="font-family:var(--font-d);font-weight:800;font-size:2rem;line-height:1.2;color:var(--text);margin-bottom:1rem;">
                    Daftar Akun<br>Website GEMPITA
                </h2>
                <p class="text-muted" style="font-size:.92rem;line-height:1.7;margin-bottom:2rem;">
                    Lengkapi data Anda untuk mulai pencatatan budidaya ikan dalam website untuk memantau dan mengoptimalkan budidaya ikan secara digital.
                </p>

                <!-- Keunggulan -->
                <div class="d-flex flex-column gap-3">
                    <?php $keunggulan = [
                        ['bi-journal-check',  'var(--blue)',       'var(--blue-soft)',    'Pencatatan Digital',    'Catat semua data budidaya dengan mudah'],
                        ['bi-graph-up-arrow', 'var(--green)',      'var(--green-soft)',   'Hitung Keuntungan',     'Kalkulasi otomatis modal & pendapatan'],
                        ['bi-whatsapp',       '#16a34a',           'var(--green-soft)',   'Konsultasi Ahli',       'Terhubung langsung dengan tim GEMPITA'],
                    ];
                    foreach ($keunggulan as $k): ?>
                    <div class="d-flex align-items-start gap-3">
                        <div class="icon-box icon-box-md flex-shrink-0"
                             style="background:<?= $k[2] ?>;color:<?= $k[1] ?>;">
                            <i class="bi <?= $k[0] ?>"></i>
                        </div>
                        <div>
                            <div style="font-family:var(--font-d);font-weight:700;font-size:.9rem;color:var(--text);"><?= $k[3] ?></div>
                            <div class="text-muted" style="font-size:.8rem;"><?= $k[4] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- KANAN: Form -->
        <div class="col-lg-7">
            <div class="card-g p-4 p-md-5" style="border-radius:var(--r-xl);">

                <!-- Error global -->
                <?php if (!empty($errors)): ?>
                <div class="soft-alert soft-red mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Terdapat <?= count($errors) ?> kesalahan. Periksa kembali form di bawah.
                </div>
                <?php endif; ?>

                <form method="POST" class="form-g" novalidate>

                    <!-- ── Informasi Pribadi ── -->
                    <div class="form-section-title">Informasi Pribadi</div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap"
                                   class="form-control <?= isset($errors['nama_lengkap'])?'is-invalid':'' ?>"
                                   placeholder="Masukkan nama sesuai KTP"
                                   value="<?= clean($input['nama_lengkap']) ?>" required>
                            <?php if(isset($errors['nama_lengkap'])): ?>
                            <div class="invalid-feedback"><?= $errors['nama_lengkap'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kota Asal</label>
                            <input type="text" name="alamat"
                                   class="form-control"
                                   placeholder="Kota domisili Anda"
                                   value="<?= clean($input['alamat']) ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Nomor WhatsApp <span class="text-danger">*</span></label>
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
                        <div class="col-md-6">
                            <label class="form-label">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" name="email"
                                   class="form-control <?= isset($errors['email'])?'is-invalid':'' ?>"
                                   placeholder="email@domain.com"
                                   value="<?= clean($input['email']) ?>" required>
                            <?php if(isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= $errors['email'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ── Informasi Tambak ── -->
                    <div class="form-section-title">Informasi Tambak</div>

                    <div class="mb-3">
                        <label class="form-label">Nama Tambak / Usaha</label>
                        <input type="text" name="nama_tambak"
                               class="form-control"
                               placeholder="Contoh: Tambak Berkah Jaya"
                               value="<?= clean($input['nama_tambak']) ?>">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Alamat / Lokasi Tambak</label>
                            <input type="text" name="lokasi_tambak"
                                   class="form-control"
                                   placeholder="Kecamatan, Kabupaten"
                                   value="<?= clean($input['lokasi_tambak']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jumlah Kolam</label>
                            <input type="number" name="jumlah_kolam"
                                   class="form-control"
                                   placeholder="0" min="0"
                                   value="<?= clean($input['jumlah_kolam']) ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Jenis Ikan yang Dibudidayakan</label>
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            <?php foreach ($jenisIkanOpsi as $ji): ?>
                            <label class="ikan-chip">
                                <input type="checkbox" name="jenis_ikan[]"
                                       value="<?= $ji ?>"
                                       <?= in_array($ji, $input['jenis_ikan']) ? 'checked' : '' ?>>
                                <span><?= $ji ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- ── Keamanan Akun ── -->
                    <div class="form-section-title">Keamanan Akun</div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Kata Sandi <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="pw1"
                                       class="form-control <?= isset($errors['password'])?'is-invalid':'' ?>"
                                       placeholder="Minimal 8 karakter" required>
                                <button type="button" class="input-group-text bg-white"
                                        data-toggle-pw="pw1" style="cursor:pointer;">
                                    <i class="bi bi-eye text-muted"></i>
                                </button>
                            </div>
                            <?php if(isset($errors['password'])): ?>
                            <div class="text-danger mt-1" style="font-size:.8rem;"><?= $errors['password'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="konfirmasi" id="pw2"
                                       class="form-control <?= isset($errors['konfirmasi'])?'is-invalid':'' ?>"
                                       placeholder="Ulangi kata sandi" required>
                                <button type="button" class="input-group-text bg-white"
                                        data-toggle-pw="pw2" style="cursor:pointer;">
                                    <i class="bi bi-eye text-muted"></i>
                                </button>
                            </div>
                            <?php if(isset($errors['konfirmasi'])): ?>
                            <div class="text-danger mt-1" style="font-size:.8rem;"><?= $errors['konfirmasi'] ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Checkbox syarat -->
                    <div class="mb-4">
                        <label class="d-flex align-items-start gap-2" style="cursor:pointer;">
                            <input type="checkbox" name="setuju" required
                                   style="margin-top:.25rem;accent-color:var(--blue);">
                            <span class="small text-muted">
                                Dengan mendaftar, saya menyatakan data yang diisi benar dan
                                menyetujui
                                <a href="#" style="color:var(--blue);font-weight:600;">Syarat Penggunaan</a>
                                serta
                                <a href="#" style="color:var(--blue);font-weight:600;">Kebijakan Privasi</a>
                                GEMPITA.
                            </span>
                        </label>
                    </div>

                    <button type="submit"
                            class="btn btn-primary w-100 py-2 fw-700"
                            style="border-radius:10px;font-size:.95rem;">
                        <i class="bi bi-person-check me-2"></i>Daftar Akun
                    </button>
                </form>

                <div class="text-center mt-4 small" style="color:var(--text-muted);">
                    Sudah memiliki akun GEMPITA?
                    <a href="<?= APP_URL ?>/login.php" class="fw-700" style="color:var(--blue);">Masuk di sini</a>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
/* Section divider dalam form */
.form-section-title {
    font-family: var(--font-d);
    font-weight: 700;
    font-size: .8rem;
    color: var(--blue);
    text-transform: uppercase;
    letter-spacing: .8px;
    margin-bottom: 1rem;
    padding-bottom: .5rem;
    border-bottom: 2px solid var(--blue-soft);
    margin-top: .5rem;
}
/* Checkbox ikan */
.ikan-chip { cursor: pointer; }
.ikan-chip input[type=checkbox] { display: none; }
.ikan-chip span {
    display: inline-block;
    padding: .3rem .9rem;
    border-radius: 50px;
    border: 1.5px solid var(--border);
    font-family: var(--font-d);
    font-size: .8rem;
    font-weight: 600;
    color: var(--text-muted);
    background: #fff;
    transition: var(--ease);
    user-select: none;
}
.ikan-chip input:checked + span {
    background: var(--blue-soft);
    border-color: var(--blue);
    color: var(--blue);
}
.ikan-chip span:hover { border-color: var(--blue-light); color: var(--blue); }
/* soft alert */
.soft-alert { padding: .65rem 1rem; border-radius: 10px; font-size: .84rem; font-family: var(--font-d); font-weight: 500; }
.soft-red   { background: var(--red-soft); color: var(--red); border: 1px solid #fecaca; }
/* input group fix */
.form-g .input-group .form-control { border-left: none !important; }
.form-g .input-group .input-group-text:last-child { border-left: none !important; cursor: pointer; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
