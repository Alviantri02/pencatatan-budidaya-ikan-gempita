<?php
/**
 * GEMPITA v2 - Pencatatan Sidebar
 * Include di semua halaman dalam folder /pencatatan/
 */
$curPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="pnc-sidebar">
    <div class="pnc-sidebar-inner">

        <!-- User Info -->
        <div class="pnc-user-info">
            <div class="pnc-user-avatar">
                <?= strtoupper(substr($user['nama'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="pnc-user-detail">
                <div class="pnc-user-name"><?= clean(explode(' ', $user['nama'] ?? 'User')[0]) ?></div>
                <div class="pnc-user-role">Pembudidaya</div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="pnc-nav">
            <div class="pnc-nav-label">MENU</div>

            <a href="<?= APP_URL ?>/pencatatan/dashboard.php"
               class="pnc-nav-item <?= $curPage === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>

            <a href="<?= APP_URL ?>/pencatatan/kolam.php"
               class="pnc-nav-item <?= $curPage === 'kolam' ? 'active' : '' ?>">
                <i class="bi bi-water"></i>
                <span>Data Kolam &amp; Bibit</span>
            </a>

            <a href="<?= APP_URL ?>/pencatatan/pakan.php"
               class="pnc-nav-item <?= $curPage === 'pakan' ? 'active' : '' ?>">
                <i class="bi bi-bag-fill"></i>
                <span>Data Pakan</span>
            </a>

            <a href="<?= APP_URL ?>/pencatatan/panen.php"
               class="pnc-nav-item <?= $curPage === 'panen' ? 'active' : '' ?>">
                <i class="bi bi-basket3-fill"></i>
                <span>Hasil Panen</span>
            </a>

            <a href="<?= APP_URL ?>/pencatatan/kalkulasi.php"
               class="pnc-nav-item <?= $curPage === 'kalkulasi' ? 'active' : '' ?>">
                <i class="bi bi-calculator-fill"></i>
                <span>Kalkulasi &amp; Laporan</span>
            </a>

            <div class="pnc-nav-divider"></div>

            <a href="<?= APP_URL ?>/index.php" class="pnc-nav-item">
                <i class="bi bi-house"></i>
                <span>Beranda</span>
            </a>

            <a href="<?= APP_URL ?>/logout.php" class="pnc-nav-item pnc-nav-logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>
</aside>
