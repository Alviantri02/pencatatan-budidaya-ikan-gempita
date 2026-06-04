<?php
$curAdm = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="adm-sidebar" id="admSidebar">
    <nav class="adm-sidebar-nav">
        <div class="adm-nav-section">KONTEN</div>

        <a href="<?= APP_URL ?>/admin/artikel.php"
           class="adm-nav-item <?= in_array($curAdm,['artikel','artikel-form']) ? 'active' : '' ?>">
            <i class="bi bi-newspaper"></i>
            <span>Kelola Artikel</span>
        </a>

        <a href="<?= APP_URL ?>/admin/kategori.php"
           class="adm-nav-item <?= $curAdm==='kategori' ? 'active' : '' ?>">
            <i class="bi bi-tags-fill"></i>
            <span>Kategori</span>
        </a>

        <div class="adm-nav-section mt-2">WEBSITE</div>

        <a href="<?= APP_URL ?>/informasi.php" target="_blank" class="adm-nav-item">
            <i class="bi bi-eye"></i>
            <span>Lihat Website</span>
            <i class="bi bi-box-arrow-up-right ms-auto" style="font-size:.7rem;opacity:.5;"></i>
        </a>

        <div class="adm-nav-divider"></div>

        <a href="<?= APP_URL ?>/logout.php" class="adm-nav-item adm-nav-logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>
