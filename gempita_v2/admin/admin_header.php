<?php
/**
 * GEMPITA v2 — Admin Header
 */
if (session_status() === PHP_SESSION_NONE) session_start();
// Ambil & hapus flash message
$_flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= clean($pageTitle ?? 'Admin') ?> — GEMPITA Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="<?= APP_URL ?>/admin/admin.css">
</head>
<body class="adm-body">

<!-- Top Navbar -->
<header class="adm-topbar">
    <div class="adm-topbar-inner">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="btnMenuToggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <a href="<?= APP_URL ?>/admin/artikel.php" class="adm-brand">
                <span class="adm-brand-text">GEMPITA</span>
                <span class="adm-brand-badge">Admin</span>
            </a>
        </div>
        <div class="adm-topbar-right">
            <a href="<?= APP_URL ?>/index.php" class="adm-topbar-link" target="_blank" title="Lihat website">
                <i class="bi bi-box-arrow-up-right"></i>
            </a>
            <div class="adm-topbar-user">
                <div class="adm-topbar-avatar">
                    <?= strtoupper(substr($user['nama_lengkap'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="adm-topbar-info">
                    <div class="adm-topbar-name"><?= clean(explode(' ', $user['nama_lengkap'] ?? 'Admin')[0]) ?></div>
                    <div class="adm-topbar-role">Administrator</div>
                </div>
                <a href="<?= APP_URL ?>/logout.php" class="adm-topbar-logout" title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</header>

<?php if ($_flash): ?>
<div class="adm-flash adm-flash-<?= $_flash['type'] ?>">
    <i class="bi bi-<?= $_flash['type']==='success'?'check-circle-fill':'exclamation-triangle-fill' ?> me-2"></i>
    <?= clean($_flash['msg']) ?>
    <button type="button" onclick="this.parentElement.remove()" class="adm-flash-close">
        <i class="bi bi-x-lg"></i>
    </button>
</div>
<?php endif; ?>
