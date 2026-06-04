<?php
/**
 * GEMPITA v2 - Halaman Login
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isLoggedIn()) redirect(APP_URL . '/index.php');

$pageTitle = 'Login';
$error     = '';
$inputUser = '';
$pesan     = $_GET['pesan'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputUser = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (empty($inputUser) || empty($password)) {
        $error = 'Email/username dan password wajib diisi.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND is_active=TRUE LIMIT 1");
        $stmt->execute([$inputUser, $inputUser]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            doLogin($user);
            $redirect = $_SESSION['redirect_after_login'] ?? (APP_URL . '/index.php');
            unset($_SESSION['redirect_after_login']);
            setFlash('success', 'Selamat datang, ' . $user['nama_lengkap'] . '! 👋');
            redirect($redirect);
        } else {
            $error = 'Email/username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body class="auth-page">
<div class="container" style="position:relative;z-index:1;">
    <div class="row justify-content-center">
        <div class="col-11 col-sm-8 col-md-6 col-lg-5 col-xl-4">

            <div class="auth-card fade-up">
                <!-- Ikon bulat biru -->
                <div class="auth-icon"><i class="bi bi-water"></i></div>
                <h4 class="auth-title">Selamat Datang</h4>
                <p class="auth-subtitle">Masuk ke akun <?= APP_NAME ?> Anda</p>

                <?php if ($pesan === 'harap_login'): ?>
                <div class="soft-alert soft-blue mb-3">
                    <i class="bi bi-lock-fill me-2"></i>
                    Silakan login untuk mengakses <strong>Pencatatan</strong>.
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="soft-alert soft-red mb-3">
                    <i class="bi bi-exclamation-circle-fill me-2"></i><?= clean($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="form-g" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
                            <input type="text" name="username" class="form-control"
                                   placeholder="Masukkan email"
                                   value="<?= clean($inputUser) ?>"
                                   autocomplete="username" autofocus required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
                            <input type="password" name="password" id="pw" class="form-control"
                                   placeholder="Masukkan password"
                                   autocomplete="current-password" required>
                            <button type="button" class="input-group-text bg-white pw-toggle"
                                    data-toggle-pw="pw">
                                <i class="bi bi-eye text-muted"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-700" style="border-radius:10px;font-size:.95rem;">
                        Masuk
                    </button>
                </form>

                <div class="text-center mt-4 small" style="color:var(--text-muted);">
                    Belum punya akun?
                    <a href="<?= APP_URL ?>/daftar-akun.php" class="fw-700" style="color:var(--blue);">Daftar Sekarang</a>
                </div>
            </div>

            <a href="<?= APP_URL ?>/index.php" class="auth-back">← Kembali ke Beranda</a>
        </div>
    </div>
</div>
<style>
.soft-alert{padding:.65rem 1rem;border-radius:10px;font-size:.84rem;font-family:var(--font-d);font-weight:500;}
.soft-blue{background:var(--blue-soft);color:var(--blue-dark);border:1px solid var(--blue-mid);}
.soft-red {background:var(--red-soft); color:var(--red);      border:1px solid #fecaca;}
/* input group fix */
.form-g .input-group .form-control{border-left:none!important;}
.form-g .input-group .input-group-text:last-child{border-left:none!important;cursor:pointer;}
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>
</html>
