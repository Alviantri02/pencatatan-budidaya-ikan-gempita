<?php
/**
 * GEMPITA v2 - Header & Navbar
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$user    = currentUser();
$flash   = getFlash();
$curPage = basename($_SERVER['PHP_SELF'], '.php');
// Deteksi folder aktif untuk highlight nav
$curDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? clean($pageTitle) . ' — ' : '' ?><?= APP_NAME ?></title>
    <meta name="description" content="GEMPITA - Gerakan Masyarakat Pembudidaya Ikan Terpadu">

    <!-- Fonts: Sora + DM Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Global CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">

    <?php if (isset($extraCss)) echo $extraCss; ?>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg navbar-gempita">
    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand" href="<?= APP_URL ?>/index.php">GEMPITA</a>

        <!-- Toggle Mobile -->
        <button class="navbar-toggler border-0 shadow-none" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu -->
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav mx-auto gap-lg-1 align-items-lg-center">

                <li class="nav-item">
                    <a class="nav-link <?= $curPage === 'index' ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/index.php">Beranda</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $curPage === 'informasi' ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/informasi.php">Informasi</a>
                </li>

                <!-- Pencatatan: terkunci jika belum login -->
                <li class="nav-item">
                    <?php if (isLoggedIn()): ?>
                        <a class="nav-link <?= ($curPage === 'pencatatan' || $curDir === 'pencatatan') ? 'active' : '' ?>"
                           href="<?= APP_URL ?>/pencatatan/dashboard.php">Pencatatan</a>
                    <?php else: ?>
                        <a class="nav-link nav-locked"
                           href="<?= APP_URL ?>/login.php?pesan=harap_login"
                           title="Login untuk akses Pencatatan">
                            <i class="bi bi-lock-fill me-1" style="font-size:.7rem;"></i>Pencatatan
                        </a>
                    <?php endif; ?>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $curPage === 'pendaftaran' ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/pendaftaran.php">Pendaftaran</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $curPage === 'konsultasi' ? 'active' : '' ?>"
                       href="<?= APP_URL ?>/konsultasi.php">Konsultasi</a>
                </li>

            </ul>

            <!-- Auth Buttons -->
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <button class="btn-user-nav dropdown-toggle" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                            </div>
                            <span class="d-none d-lg-inline"><?= clean(explode(' ', $user['nama'])[0]) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end nav-dropdown shadow">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-700 small"><?= clean($user['nama']) ?></div>
                                <div class="text-muted" style="font-size:.75rem;"><?= clean($user['email']) ?></div>
                                <?php if (isAdmin()): ?>
                                <span class="badge-role-admin">Admin</span>
                                <?php endif; ?>
                            </li>
                            <?php if (isAdmin()): ?>
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/admin/artikel.php">
                                    <i class="bi bi-pencil-square me-2 text-primary"></i>Kelola Artikel
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php else: ?>
                            <li>
                                <a class="dropdown-item" href="<?= APP_URL ?>/pencatatan/dashboard.php">
                                    <i class="bi bi-journal-text me-2 text-primary"></i>Pencatatan Saya
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/login.php" class="btn-nav-login">Login</a>
                    <a href="<?= APP_URL ?>/daftar-akun.php" class="btn-nav-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- ===== FLASH MESSAGE ===== -->
<?php if ($flash): ?>
<div class="flash-wrapper">
    <div class="container">
        <div class="flash-alert flash-<?= $flash['type'] ?>" role="alert">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
            <?= clean($flash['message']) ?>
            <button type="button" class="flash-close" onclick="this.parentElement.parentElement.parentElement.remove()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>
