<?php
$activeMenu = $activeMenu ?? '';
$menus = [
    ['key'=>'dashboard','icon'=>'bi-speedometer2','label'=>'Dashboard','url'=>APP_URL.'/pencatatan/dashboard.php'],
    ['key'=>'kolam','icon'=>'bi-water','label'=>'Data Kolam & Bibit','url'=>APP_URL.'/pencatatan/data-kolam-bibit.php'],
    ['key'=>'pakan','icon'=>'bi-basket','label'=>'Data Pakan','url'=>'#'],
    ['key'=>'panen','icon'=>'bi-box-seam','label'=>'Hasil Panen','url'=>'#'],
    ['key'=>'harga','icon'=>'bi-graph-up','label'=>'Pasaran Harga','url'=>'#'],
];
?>
<aside class="pc-sidebar">
    <div class="pc-brand">GEMPITA</div>
    <div class="pc-subtitle">Pencatatan Budidaya</div>
    <nav class="pc-menu">
        <?php foreach ($menus as $m): ?>
            <a class="pc-menu-item <?= $activeMenu === $m['key'] ? 'active' : '' ?>" href="<?= $m['url'] ?>">
                <i class="bi <?= $m['icon'] ?>"></i><span><?= $m['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="pc-user-mini">
        <div class="pc-avatar"><?= strtoupper(substr(currentUser()['nama'] ?? 'U', 0, 1)) ?></div>
        <div>
            <b><?= clean(currentUser()['nama'] ?? 'User') ?></b>
            <small>Pembudidaya</small>
        </div>
    </div>
</aside>
