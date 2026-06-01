<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$pageTitle = 'Dashboard Pencatatan';
$activeMenu = 'dashboard';
$pdo = getDB();
$userId = currentUser()['id'];
$stmt = $pdo->prepare("SELECT COUNT(*) AS kolam, COALESCE(SUM(jumlah_bibit),0) AS bibit, COALESCE(SUM(total_modal_bibit),0) AS modal FROM data_kolam_bibit WHERE user_id = ? AND is_aktif = TRUE");
$stmt->execute([$userId]);
$sum = $stmt->fetch();
include __DIR__ . '/../includes/header.php';
?>
<div class="pc-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="pc-main">
        <section class="pc-hero"><h1>Dashboard Pencatatan</h1><p>Ringkasan data budidaya ikan Anda.</p></section>
        <div class="pc-stats">
            <div class="pc-stat"><span>Total Kolam Aktif</span><b><?= (int)$sum['kolam'] ?></b></div>
            <div class="pc-stat"><span>Total Bibit</span><b><?= number_format((int)$sum['bibit'],0,',','.') ?></b></div>
            <div class="pc-stat"><span>Modal Bibit</span><b><?= rupiah((float)$sum['modal']) ?></b></div>
        </div>
        <div class="pc-card mt-4">
            <h2>Mulai Pencatatan</h2>
            <p class="text-muted mb-3">Tambahkan data kolam, jenis ikan, jumlah bibit, dan harga bibit sebagai modal awal.</p>
            <a class="pc-btn" href="<?= APP_URL ?>/pencatatan/data-kolam-bibit.php"><i class="bi bi-plus-circle"></i> Input Data Bibit & Kolam</a>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
