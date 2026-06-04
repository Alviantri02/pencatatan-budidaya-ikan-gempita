<?php
/**
 * GEMPITA v2 — Admin: Form Tulis / Edit Artikel
 * Mode tambah  : artikel-form.php
 * Mode edit    : artikel-form.php?id=N
 */
require_once __DIR__ . '/admin_middleware.php';

$db     = getDB();
$editId = (int)($_GET['id'] ?? 0);
$errors = [];
$editData = null;

// ── Ambil data saat edit ─────────────────────────────────────
if ($editId > 0) {
    $s = $db->prepare("SELECT * FROM artikel WHERE id = ?");
    $s->execute([$editId]);
    $editData = $s->fetch() ?: null;
    if (!$editData) {
        header('Location: artikel.php'); exit;
    }
}

$pageTitle = $editData ? 'Edit Artikel' : 'Tulis Artikel Baru';

// ── Kategori untuk select ────────────────────────────────────
$kategoriList = $db->query("SELECT * FROM kategori_artikel ORDER BY nama")->fetchAll();

// ── Handle SAVE ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'save') {
    $judul         = trim($_POST['judul']         ?? '');
    $slug_input    = trim($_POST['slug']          ?? '');
    $kategori_id   = (int)($_POST['kategori_id'] ?? 0) ?: null;
    $ringkasan     = trim($_POST['ringkasan']     ?? '');
    $konten        = trim($_POST['konten']        ?? '');
    $penulis       = trim($_POST['penulis']       ?? 'Tim GEMPITA');
    $estimasi      = max(1, (int)($_POST['estimasi_baca'] ?? 5));
    $is_publish    = isset($_POST['is_publish']) ? true : false;

    // Validasi
    if (!$judul)  $errors[] = 'Judul artikel wajib diisi.';
    if (!$konten) $errors[] = 'Konten artikel wajib diisi.';

    // Slug
    $slug = $slug_input ? makeSlug($slug_input) : makeSlug($judul);
    if (!$slug) $errors[] = 'Slug tidak valid.';

    // Upload gambar
    $gambar_url = $editData['gambar_url'] ?? null;
    if (!empty($_FILES['gambar']['name'])) {
        $allowed   = ['image/jpeg','image/png','image/webp'];
        $maxSize   = 2 * 1024 * 1024; // 2 MB
        $fileType  = mime_content_type($_FILES['gambar']['tmp_name']);
        $fileSize  = $_FILES['gambar']['size'];

        if (!in_array($fileType, $allowed)) {
            $errors[] = 'Format gambar harus JPG, PNG, atau WebP.';
        } elseif ($fileSize > $maxSize) {
            $errors[] = 'Ukuran gambar maksimal 2 MB.';
        } else {
            $ext      = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$fileType];
            $filename = 'artikel_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $uploadDir = __DIR__ . '/../assets/uploads/artikel/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $dest = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $dest)) {
                // Hapus gambar lama
                if ($gambar_url) {
                    $old = __DIR__ . '/../' . ltrim($gambar_url, '/');
                    if (file_exists($old)) @unlink($old);
                }
                $gambar_url = '/assets/uploads/artikel/' . $filename;
            } else {
                $errors[] = 'Gagal upload gambar.';
            }
        }
    }

    // Hapus gambar jika user centang remove
    if (isset($_POST['remove_gambar']) && $gambar_url) {
        $old = __DIR__ . '/../' . ltrim($gambar_url, '/');
        if (file_exists($old)) @unlink($old);
        $gambar_url = null;
    }

    if (empty($errors)) {
        $slug = uniqueSlug($db, $slug, $editId);

        if ($editId > 0) {
            $db->prepare("
                UPDATE artikel SET
                    judul=?, slug=?, kategori_id=?, ringkasan=?, konten=?,
                    penulis=?, estimasi_baca=?, gambar_url=?, is_publish=?
                WHERE id=?
            ")->execute([
                $judul, $slug, $kategori_id, $ringkasan ?: null, $konten,
                $penulis, $estimasi, $gambar_url, $is_publish,
                $editId
            ]);
            setFlash('success', 'Artikel berhasil diperbarui!');
            header('Location: artikel-form.php?id=' . $editId); exit;
        } else {
            $db->prepare("
                INSERT INTO artikel
                    (kategori_id, dibuat_oleh, judul, slug, ringkasan, konten,
                     penulis, estimasi_baca, gambar_url, is_publish)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $kategori_id, $user['id'], $judul, $slug,
                $ringkasan ?: null, $konten,
                $penulis, $estimasi, $gambar_url, $is_publish
            ]);
            $newId = $db->lastInsertId();
            setFlash('success', 'Artikel berhasil disimpan!');
            header('Location: artikel-form.php?id=' . $newId); exit;
        }
    }
}

require_once __DIR__ . '/admin_header.php';

// Ambil flash
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="adm-layout">
    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <main class="adm-main">

        <!-- Page Header -->
        <div class="adm-page-header">
            <div>
                <h1 class="adm-page-title"><?= $editData ? 'Edit Artikel' : 'Tulis Artikel Baru' ?></h1>
                <p class="adm-page-sub">
                    <?php if ($editData): ?>
                    Terakhir diubah: <?= date('d M Y H:i', strtotime($editData['updated_at'])) ?>
                    <?php else: ?>
                    Isi semua kolom yang diperlukan
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($editData): ?>
                <a href="<?= APP_URL ?>/informasi.php?slug=<?= clean($editData['slug']) ?>"
                   target="_blank" class="btn-adm-outline">
                    <i class="bi bi-eye me-1"></i>Preview
                </a>
                <?php endif; ?>
                <a href="artikel.php" class="btn-adm-outline">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="adm-alert adm-alert-<?= $flash['type'] ?>">
            <i class="bi bi-<?= $flash['type']==='success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
            <?= clean($flash['msg']) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="adm-alert adm-alert-danger">
            <i class="bi bi-exclamation-triangle-fill me-2" style="flex-shrink:0;margin-top:.1rem;"></i>
            <div><?= implode('<br>', array_map('clean', $errors)) ?></div>
        </div>
        <?php endif; ?>

        <form method="post" action="artikel-form.php<?= $editId ? '?id='.$editId : '' ?>"
              enctype="multipart/form-data" id="formArtikel">
            <input type="hidden" name="_action" value="save">

            <div class="adm-form-layout">

                <!-- ── Kolom Kiri: Konten Utama ── -->
                <div class="adm-form-main">

                    <!-- Judul -->
                    <div class="adm-card adm-form-card">
                        <div class="adm-card-header">
                            <h2 class="adm-card-title">Konten Artikel</h2>
                        </div>
                        <div class="adm-card-body">
                            <div class="adm-form-group">
                                <label class="adm-label">
                                    Judul Artikel <span class="adm-required">*</span>
                                </label>
                                <input type="text" name="judul" id="inputJudul"
                                       class="adm-input adm-input-lg"
                                       placeholder="Tulis judul yang menarik..."
                                       value="<?= clean($editData['judul'] ?? '') ?>"
                                       oninput="autoSlug(this.value)"
                                       required>
                            </div>

                            <!-- Slug -->
                            <div class="adm-form-group">
                                <label class="adm-label">
                                    Slug URL
                                    <span class="adm-label-hint">otomatis dari judul</span>
                                </label>
                                <div class="adm-slug-wrap">
                                    <span class="adm-slug-prefix">/informasi.php?slug=</span>
                                    <input type="text" name="slug" id="inputSlug"
                                           class="adm-input adm-slug-input"
                                           placeholder="slug-artikel"
                                           value="<?= clean($editData['slug'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Ringkasan -->
                            <div class="adm-form-group">
                                <label class="adm-label">
                                    Ringkasan
                                    <span class="adm-label-hint">tampil di daftar artikel</span>
                                </label>
                                <textarea name="ringkasan" class="adm-textarea" rows="3"
                                          placeholder="Deskripsi singkat artikel (1–2 kalimat)..."
                                          maxlength="300"
                                          oninput="countChar(this,'ctrRingkasan',300)"
                                          ><?= clean($editData['ringkasan'] ?? '') ?></textarea>
                                <div class="adm-char-count">
                                    <span id="ctrRingkasan">
                                        <?= mb_strlen($editData['ringkasan'] ?? '') ?>
                                    </span>/300 karakter
                                </div>
                            </div>

                            <!-- Konten / Editor -->
                            <div class="adm-form-group">
                                <label class="adm-label">
                                    Konten Artikel <span class="adm-required">*</span>
                                    <span class="adm-label-hint">mendukung HTML</span>
                                </label>

                                <!-- Toolbar -->
                                <div class="adm-editor-toolbar">
                                    <button type="button" class="adm-tb-btn" onclick="insertTag('<h4>','</h4>')" title="H4"><b>H4</b></button>
                                    <button type="button" class="adm-tb-btn" onclick="insertTag('<h5>','</h5>')" title="H5"><b>H5</b></button>
                                    <button type="button" class="adm-tb-btn" onclick="insertTag('<p>','</p>')" title="Paragraf"><i class="bi bi-paragraph"></i></button>
                                    <button type="button" class="adm-tb-btn" onclick="insertTag('<strong>','</strong>')" title="Bold"><b>B</b></button>
                                    <button type="button" class="adm-tb-btn" onclick="insertTag('<em>','</em>')" title="Italic"><i>I</i></button>
                                    <div class="adm-tb-sep"></div>
                                    <button type="button" class="adm-tb-btn" onclick="insertTag('<ul>\n<li>','</li>\n</ul>')" title="Bullet List"><i class="bi bi-list-ul"></i></button>
                                    <button type="button" class="adm-tb-btn" onclick="insertTag('<ol>\n<li>','</li>\n</ol>')" title="Numbered List"><i class="bi bi-list-ol"></i></button>
                                    <button type="button" class="adm-tb-btn" onclick="insertTag('<li>','</li>')" title="List Item">• item</button>
                                    <div class="adm-tb-sep"></div>
                                    <button type="button" class="adm-tb-btn" onclick="insertTag('<a href=&quot;&quot;>','</a>')" title="Link"><i class="bi bi-link-45deg"></i></button>
                                    <div class="adm-tb-sep"></div>
                                    <button type="button" class="adm-tb-btn adm-tb-preview" onclick="togglePreview()" title="Preview">
                                        <i class="bi bi-eye me-1"></i>Preview
                                    </button>
                                </div>

                                <!-- Textarea editor -->
                                <textarea name="konten" id="editorKonten"
                                          class="adm-editor" rows="18"
                                          placeholder="Tulis konten artikel di sini menggunakan HTML..."
                                          required><?= clean($editData['konten'] ?? '') ?></textarea>

                                <!-- Preview panel (hidden default) -->
                                <div id="previewPanel" class="adm-preview-panel" style="display:none;">
                                    <div class="adm-preview-label">
                                        <i class="bi bi-eye me-1"></i>Preview Konten
                                        <button type="button" class="adm-tb-btn ms-auto" onclick="togglePreview()">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                    <div id="previewContent" class="art-konten adm-preview-content"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /adm-form-main -->

                <!-- ── Kolom Kanan: Metadata ── -->
                <div class="adm-form-aside">

                    <!-- Publish -->
                    <div class="adm-card adm-form-card">
                        <div class="adm-card-header">
                            <h2 class="adm-card-title">Publikasi</h2>
                        </div>
                        <div class="adm-card-body">
                            <!-- Status toggle -->
                            <div class="adm-publish-toggle">
                                <label class="adm-toggle-wrap">
                                    <input type="checkbox" name="is_publish" id="chkPublish"
                                           <?= ($editData['is_publish'] ?? true) ? 'checked' : '' ?>>
                                    <span class="adm-toggle-slider"></span>
                                </label>
                                <div>
                                    <div class="adm-toggle-label" id="publishLabel">
                                        <?= ($editData['is_publish'] ?? true) ? 'Dipublikasikan' : 'Disimpan sebagai Draft' ?>
                                    </div>
                                    <div class="adm-toggle-hint">
                                        <?= ($editData['is_publish'] ?? true) ? 'Artikel terlihat oleh pengunjung' : 'Artikel tidak terlihat oleh pengunjung' ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol save -->
                            <div class="adm-publish-actions">
                                <button type="submit" class="btn-adm-primary btn-adm-lg w-100">
                                    <i class="bi bi-floppy-fill me-1"></i>
                                    <?= $editData ? 'Perbarui Artikel' : 'Simpan Artikel' ?>
                                </button>
                                <?php if ($editData): ?>
                                <a href="<?= APP_URL ?>/informasi.php?slug=<?= clean($editData['slug']) ?>"
                                   target="_blank" class="btn-adm-outline w-100 text-center mt-2"
                                   style="display:block;">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>Lihat di Website
                                </a>
                                <?php endif; ?>
                            </div>

                            <?php if ($editData): ?>
                            <div class="adm-form-note mt-3">
                                <i class="bi bi-info-circle me-1"></i>
                                Dibuat: <?= date('d M Y', strtotime($editData['created_at'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Kategori & Penulis -->
                    <div class="adm-card adm-form-card">
                        <div class="adm-card-header">
                            <h2 class="adm-card-title">Detail</h2>
                        </div>
                        <div class="adm-card-body">
                            <div class="adm-form-group">
                                <label class="adm-label">Kategori</label>
                                <select name="kategori_id" class="adm-select">
                                    <option value="">-- Tanpa Kategori --</option>
                                    <?php foreach ($kategoriList as $k): ?>
                                    <option value="<?= $k['id'] ?>"
                                        <?= ($editData['kategori_id'] ?? '') == $k['id'] ? 'selected' : '' ?>>
                                        <?= clean($k['nama']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="adm-form-group">
                                <label class="adm-label">Penulis</label>
                                <input type="text" name="penulis" class="adm-input"
                                       placeholder="Tim GEMPITA"
                                       value="<?= clean($editData['penulis'] ?? 'Tim GEMPITA') ?>">
                            </div>
                            <div class="adm-form-group">
                                <label class="adm-label">Estimasi Baca (menit)</label>
                                <input type="number" name="estimasi_baca" class="adm-input"
                                       min="1" max="60"
                                       value="<?= $editData['estimasi_baca'] ?? 5 ?>"
                                       id="inputEstimasi">
                                <div class="adm-label-hint mt-1">
                                    Otomatis: <span id="autoEstimasi">—</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gambar -->
                    <div class="adm-card adm-form-card">
                        <div class="adm-card-header">
                            <h2 class="adm-card-title">Gambar Artikel</h2>
                        </div>
                        <div class="adm-card-body">
                            <!-- Preview gambar saat ini -->
                            <?php if (!empty($editData['gambar_url'])): ?>
                            <div class="adm-img-preview" id="currentImgWrap">
                                <img src="<?= clean($editData['gambar_url']) ?>"
                                     alt="Gambar artikel" id="currentImg">
                                <label class="adm-img-remove">
                                    <input type="checkbox" name="remove_gambar" id="chkRemoveGambar">
                                    <span><i class="bi bi-trash me-1"></i>Hapus Gambar</span>
                                </label>
                            </div>
                            <?php endif; ?>

                            <!-- Upload area -->
                            <div class="adm-upload-area" id="uploadArea">
                                <input type="file" name="gambar" id="inputGambar"
                                       accept="image/jpeg,image/png,image/webp"
                                       style="display:none;"
                                       onchange="previewUpload(this)">
                                <label for="inputGambar" class="adm-upload-label">
                                    <i class="bi bi-cloud-upload" style="font-size:1.75rem;color:var(--blue-mid);margin-bottom:.5rem;display:block;"></i>
                                    <span class="adm-upload-text">Klik atau drag gambar</span>
                                    <span class="adm-upload-hint">JPG, PNG, WebP · maks 2 MB</span>
                                </label>
                                <div id="uploadPreview" style="display:none;text-align:center;">
                                    <img id="uploadPreviewImg" style="max-width:100%;max-height:160px;border-radius:6px;">
                                    <div id="uploadPreviewName" class="adm-label-hint mt-1"></div>
                                    <button type="button" class="btn-adm-outline mt-2"
                                            style="font-size:.75rem;padding:.25rem .6rem;"
                                            onclick="clearUpload()">
                                        <i class="bi bi-x-lg me-1"></i>Batal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /adm-form-aside -->

            </div><!-- /adm-form-layout -->
        </form>

    </main>
</div>

<script>
// Auto slug dari judul
var slugEdited = <?= $editId ? 'true' : 'false' ?>;
function autoSlug(val) {
    if (slugEdited) return;
    var s = val.toLowerCase()
        .replace(/[^a-z0-9\s\-]/g,'')
        .replace(/[\s\-]+/g,'-')
        .replace(/^-+|-+$/g,'');
    document.getElementById('inputSlug').value = s;
}
document.getElementById('inputSlug').addEventListener('input', function(){
    slugEdited = this.value.length > 0;
});

// Counter karakter ringkasan
function countChar(el, counterId, max) {
    var len = el.value.length;
    document.getElementById(counterId).textContent = len;
    document.getElementById(counterId).style.color = len > max * 0.9 ? 'var(--red)' : '';
}

// Insert tag ke editor
function insertTag(open, close) {
    var ta    = document.getElementById('editorKonten');
    var start = ta.selectionStart;
    var end   = ta.selectionEnd;
    var sel   = ta.value.substring(start, end);
    ta.value  = ta.value.substring(0, start) + open + sel + close + ta.value.substring(end);
    ta.focus();
    ta.selectionStart = start + open.length;
    ta.selectionEnd   = start + open.length + sel.length;
    autoEstimasi();
}

// Preview konten
function togglePreview() {
    var panel = document.getElementById('previewPanel');
    var ta    = document.getElementById('editorKonten');
    if (panel.style.display === 'none') {
        document.getElementById('previewContent').innerHTML = ta.value;
        panel.style.display = 'block';
    } else {
        panel.style.display = 'none';
    }
}

// Auto estimasi baca (250 kata per menit)
function autoEstimasi() {
    var konten = document.getElementById('editorKonten').value;
    var words  = konten.replace(/<[^>]+>/g,' ').split(/\s+/).filter(Boolean).length;
    var mnt    = Math.max(1, Math.round(words / 250));
    document.getElementById('autoEstimasi').textContent = mnt + ' menit (~' + words + ' kata)';
    document.getElementById('inputEstimasi').value = mnt;
}
document.getElementById('editorKonten').addEventListener('input', autoEstimasi);
autoEstimasi();

// Toggle label publish
document.getElementById('chkPublish').addEventListener('change', function(){
    document.getElementById('publishLabel').textContent =
        this.checked ? 'Dipublikasikan' : 'Disimpan sebagai Draft';
});

// Upload gambar preview
function previewUpload(input) {
    var file = input.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('uploadPreviewImg').src = e.target.result;
        document.getElementById('uploadPreviewName').textContent = file.name;
        document.getElementById('uploadPreview').style.display = 'block';
        document.querySelector('.adm-upload-label').style.display = 'none';
    };
    reader.readAsDataURL(file);
}
function clearUpload() {
    document.getElementById('inputGambar').value = '';
    document.getElementById('uploadPreview').style.display = 'none';
    document.querySelector('.adm-upload-label').style.display = 'block';
}
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
