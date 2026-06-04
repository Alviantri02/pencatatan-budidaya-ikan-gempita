<?php
/**
 * GEMPITA v2 — Admin: Kelola Kategori Artikel
 */
require_once __DIR__ . '/admin_middleware.php';
$pageTitle = 'Kelola Kategori';
$db        = getDB();
$errors    = [];

// Delete
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['_action']??'')==='delete') {
    $id = (int)($_POST['kat_id']??0);
    // Cek ada artikel?
    $jml = $db->prepare("SELECT COUNT(*) FROM artikel WHERE kategori_id=?");
    $jml->execute([$id]);
    if ($jml->fetchColumn() > 0) {
        setFlash('danger','Kategori tidak bisa dihapus karena masih dipakai oleh artikel.');
    } else {
        $db->prepare("DELETE FROM kategori_artikel WHERE id=?")->execute([$id]);
        setFlash('success','Kategori dihapus.');
    }
    header('Location: kategori.php'); exit;
}

// Save
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['_action']??'')==='save') {
    $editId = (int)($_POST['edit_id']??0);
    $nama   = trim($_POST['nama']  ?? '');
    $warna  = trim($_POST['warna'] ?? '#1a6fd4');
    $slug   = makeSlug($nama);
    if (!$nama)  $errors[] = 'Nama kategori wajib diisi.';
    if (!$slug)  $errors[] = 'Nama tidak valid untuk slug.';
    if (empty($errors)) {
        // cek slug unik
        $chk = $db->prepare("SELECT id FROM kategori_artikel WHERE slug=? AND id!=?");
        $chk->execute([$slug, $editId]);
        if ($chk->fetch()) $errors[] = 'Kategori dengan nama serupa sudah ada.';
    }
    if (empty($errors)) {
        if ($editId>0) {
            $db->prepare("UPDATE kategori_artikel SET nama=?,slug=?,warna=? WHERE id=?")
               ->execute([$nama,$slug,$warna,$editId]);
            setFlash('success','Kategori diperbarui.');
        } else {
            $db->prepare("INSERT INTO kategori_artikel (nama,slug,warna) VALUES (?,?,?)")
               ->execute([$nama,$slug,$warna]);
            setFlash('success','Kategori ditambahkan.');
        }
        header('Location: kategori.php'); exit;
    }
}

$editData = null;
if (!empty($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM kategori_artikel WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editData = $s->fetch() ?: null;
}

$katList = $db->query("
    SELECT k.*, COUNT(a.id) AS jml_artikel
    FROM kategori_artikel k
    LEFT JOIN artikel a ON a.kategori_id = k.id
    GROUP BY k.id ORDER BY k.nama
")->fetchAll();

require_once __DIR__ . '/admin_header.php';
?>
<div class="adm-layout">
    <?php include __DIR__ . '/admin_sidebar.php'; ?>
    <main class="adm-main">

        <div class="adm-page-header">
            <div>
                <h1 class="adm-page-title">Kategori Artikel</h1>
                <p class="adm-page-sub">Kelola kategori untuk mengorganisir artikel</p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:340px 1fr;gap:1.5rem;align-items:start;">

            <!-- Form -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <h2 class="adm-card-title"><?= $editData ? 'Edit Kategori' : 'Tambah Kategori' ?></h2>
                    <?php if ($editData): ?>
                    <a href="kategori.php" class="btn-adm-outline" style="padding:.3rem .7rem;font-size:.78rem;">Batal</a>
                    <?php endif; ?>
                </div>
                <div class="adm-card-body">
                    <?php if (!empty($errors)): ?>
                    <div class="adm-alert adm-alert-danger mb-3">
                        <?= implode('<br>', array_map('clean',$errors)) ?>
                    </div>
                    <?php endif; ?>
                    <form method="post" action="kategori.php">
                        <input type="hidden" name="_action" value="save">
                        <?php if ($editData): ?>
                        <input type="hidden" name="edit_id" value="<?= $editData['id'] ?>">
                        <?php endif; ?>
                        <div class="adm-form-group">
                            <label class="adm-label">Nama Kategori <span class="adm-required">*</span></label>
                            <input type="text" name="nama" class="adm-input"
                                   placeholder="Contoh: Tips & Trik"
                                   value="<?= clean($editData['nama'] ?? '') ?>" required>
                        </div>
                        <div class="adm-form-group">
                            <label class="adm-label">Warna Label</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="color" name="warna" class="adm-color-input"
                                       value="<?= clean($editData['warna'] ?? '#1a6fd4') ?>">
                                <span class="adm-label-hint">Warna badge kategori</span>
                            </div>
                        </div>
                        <button type="submit" class="btn-adm-primary w-100">
                            <i class="bi bi-floppy-fill me-1"></i>
                            <?= $editData ? 'Perbarui' : 'Tambah Kategori' ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Daftar Kategori -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <h2 class="adm-card-title">Daftar Kategori</h2>
                    <span class="adm-badge adm-badge-blue"><?= count($katList) ?></span>
                </div>
                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Slug</th>
                                <th class="text-center">Artikel</th>
                                <th>Warna</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($katList as $k): ?>
                        <tr>
                            <td>
                                <span class="fw-600"><?= clean($k['nama']) ?></span>
                            </td>
                            <td><code style="font-size:.78rem;"><?= clean($k['slug']) ?></code></td>
                            <td class="text-center">
                                <span class="adm-badge adm-badge-gray"><?= $k['jml_artikel'] ?></span>
                            </td>
                            <td>
                                <div style="width:28px;height:28px;border-radius:6px;background:<?= clean($k['warna']) ?>;border:1.5px solid rgba(0,0,0,.1);"></div>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="kategori.php?edit=<?= $k['id'] ?>"
                                       class="btn-adm-action btn-adm-edit" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" action="kategori.php"
                                          onsubmit="return confirm('Hapus kategori «<?= clean($k['nama']) ?>»?')">
                                        <input type="hidden" name="_action" value="delete">
                                        <input type="hidden" name="kat_id"  value="<?= $k['id'] ?>">
                                        <button type="submit" class="btn-adm-action btn-adm-del"
                                                <?= $k['jml_artikel'] > 0 ? 'disabled title="Masih ada artikel"' : '' ?>>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>
<?php require_once __DIR__ . '/admin_footer.php'; ?>
