<?php
/**
 * GEMPITA - Halaman Konsultasi
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pageTitle = 'Konsultasi';

// Topik konsultasi
$topik = $_GET['topik'] ?? '';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header-wa">
    <div class="container">
        <div class="row align-items-center">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="<?= APP_URL ?>/index.php">Beranda</a></li>
                        <li class="breadcrumb-item active">Konsultasi</li>
                    </ol>
                </nav>
                <h1 class="page-title">Konsultasi via WhatsApp</h1>
                <p class="page-subtitle">Tim ahli GEMPITA siap membantu permasalahan budidaya Anda</p>
            </div>
            <div class="col-auto d-none d-md-block">
                <div class="wa-header-icon">
                    <i class="bi bi-whatsapp"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-5">

            <!-- Kiri: Info & Form Pesan -->
            <div class="col-lg-7">

                <!-- Info layanan -->
                <div class="mb-4 animate-fade-up">
                    <span class="section-badge">Layanan Konsultasi</span>
                    <h2 class="section-title mt-2">Ceritakan Masalah Anda,<br>Kami Siap Bantu!</h2>
                    <div class="divider-wave"></div>
                    <p class="text-muted">
                        Layanan konsultasi GEMPITA tersedia untuk semua pembudidaya ikan,
                        baik pemula maupun yang sudah berpengalaman. Tidak perlu ragu untuk bertanya!
                    </p>
                </div>

                <!-- Pilih topik konsultasi -->
                <div class="mb-4 animate-fade-up delay-1">
                    <h5 class="fw-700 mb-3">Pilih Topik Konsultasi</h5>
                    <div class="row g-3" id="topikGrid">
                        <?php
                        $topikList = [
                            ['kode'=>'penyakit',    'icon'=>'bi-bug',              'warna'=>'#e74c3c', 'judul'=>'Penyakit Ikan',      'desc'=>'Gejala, diagnosis, dan pengobatan'],
                            ['kode'=>'kualitas-air','icon'=>'bi-droplet-half',     'warna'=>'#3498db', 'judul'=>'Kualitas Air',        'desc'=>'pH, suhu, DO, amonia kolam'],
                            ['kode'=>'pakan',       'icon'=>'bi-bag',              'warna'=>'#f0a500', 'judul'=>'Manajemen Pakan',    'desc'=>'Jenis pakan, dosis, frekuensi'],
                            ['kode'=>'benih',       'icon'=>'bi-egg',              'warna'=>'#17a589', 'judul'=>'Pemilihan Benih',    'desc'=>'Kualitas, ukuran, asal benih'],
                            ['kode'=>'panen',       'icon'=>'bi-basket3',          'warna'=>'#0e7c6e', 'judul'=>'Strategi Panen',     'desc'=>'Waktu panen, teknik, pemasaran'],
                            ['kode'=>'program',     'icon'=>'bi-award',            'warna'=>'#8e44ad', 'judul'=>'Info Program',       'desc'=>'Pendaftaran & syarat program'],
                            ['kode'=>'kolam',       'icon'=>'bi-grid',             'warna'=>'#2980b9', 'judul'=>'Konstruksi Kolam',   'desc'=>'Desain, ukuran, material kolam'],
                            ['kode'=>'lainnya',     'icon'=>'bi-chat-dots',        'warna'=>'#7f8c8d', 'judul'=>'Pertanyaan Lain',    'desc'=>'Topik yang belum tercantum'],
                        ];
                        foreach($topikList as $t): ?>
                        <div class="col-6 col-md-3">
                            <div class="topik-card <?= $topik===$t['kode']?'active':'' ?>"
                                 data-kode="<?= $t['kode'] ?>"
                                 data-judul="<?= $t['judul'] ?>"
                                 style="--topik-color:<?= $t['warna'] ?>;"
                                 onclick="pilihTopik(this)">
                                <i class="bi <?= $t['icon'] ?>" style="font-size:1.6rem;color:<?= $t['warna'] ?>;margin-bottom:.5rem;display:block;"></i>
                                <div class="topik-judul"><?= $t['judul'] ?></div>
                                <div class="topik-desc"><?= $t['desc'] ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Form pesan -->
                <div class="card-gempita animate-fade-up delay-2">
                    <div class="p-4 border-bottom">
                        <h5 class="mb-0 fw-700"><i class="bi bi-chat-text me-2 text-primary"></i>Tulis Pesan Anda</h5>
                    </div>
                    <div class="p-4">
                        <div class="mb-3">
                            <label class="form-label fw-600">Nama Anda</label>
                            <input type="text" id="inputNama" class="form-control" placeholder="Masukkan nama Anda"
                                   value="<?= isLoggedIn() ? clean(currentUser()['nama']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-600">Topik Konsultasi</label>
                            <input type="text" id="inputTopik" class="form-control" placeholder="Pilih topik di atas atau ketik manual"
                                   value="<?= $topik ? ucfirst(str_replace('-',' ',$topik)) : '' ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-600">Ceritakan Permasalahan Anda</label>
                            <textarea id="inputPesan" class="form-control" rows="5"
                                      placeholder="Contoh: Ikan lele saya banyak yang mati mendadak sejak 2 hari lalu. Air berwarna hijau pekat dan berbau. Sudah 2 minggu sejak penebaran 500 ekor..."></textarea>
                            <div class="form-text">Semakin detail, semakin akurat solusi yang diberikan tim kami.</div>
                        </div>
                        <button onclick="kirimWA()" class="btn btn-wa btn-lg w-100 py-3">
                            <i class="bi bi-whatsapp me-2 fs-5"></i>Kirim via WhatsApp
                        </button>
                        <div class="text-center mt-2 small text-muted">
                            Akan membuka WhatsApp dengan pesan yang sudah terisi otomatis
                        </div>
                    </div>
                </div>

            </div>

            <!-- Kanan: Info tambahan -->
            <div class="col-lg-5">

                <!-- Jam operasional -->
                <div class="card-gempita mb-4 animate-fade-up delay-1">
                    <div class="p-4 border-bottom">
                        <h5 class="mb-0 fw-700"><i class="bi bi-clock me-2 text-primary"></i>Jam Operasional</h5>
                    </div>
                    <div class="p-4">
                        <?php $jadwal = [
                            ['Senin – Jumat',  '08.00 – 16.00 WIB', true],
                            ['Sabtu',          '08.00 – 12.00 WIB', true],
                            ['Minggu & Libur', 'Tutup',             false],
                        ];
                        foreach($jadwal as $j): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="fw-600 small"><?= $j[0] ?></span>
                            <span class="small <?= $j[2]?'text-success fw-600':'text-muted' ?>"><?= $j[1] ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div class="mt-3 p-3 rounded" style="background:rgba(14,124,110,.08);">
                            <div class="small text-primary fw-600 mb-1">
                                <i class="bi bi-lightning-charge me-1"></i>Respon Darurat
                            </div>
                            <div class="small text-muted">
                                Untuk kasus darurat (ikan mati massal), tim kami berusaha merespon
                                dalam 1–3 jam meskipun di luar jam operasional.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kontak langsung -->
                <div class="card-gempita mb-4 animate-fade-up delay-2">
                    <div class="p-4 border-bottom">
                        <h5 class="mb-0 fw-700"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Hubungi Langsung</h5>
                    </div>
                    <div class="p-4 d-flex flex-column gap-3">
                        <a href="https://wa.me/<?= WA_NUMBER ?>" target="_blank" class="kontak-item">
                            <div class="kontak-icon" style="background:#25D36618;color:#25D366;">
                                <i class="bi bi-whatsapp"></i>
                            </div>
                            <div>
                                <div class="fw-600 small">WhatsApp</div>
                                <div class="text-muted small">+62 <?= substr(WA_NUMBER,2,3).'-'.substr(WA_NUMBER,5,4).'-'.substr(WA_NUMBER,9) ?></div>
                            </div>
                            <i class="bi bi-arrow-right ms-auto text-muted"></i>
                        </a>
                        <a href="mailto:konsultasi@gempita.id" class="kontak-item">
                            <div class="kontak-icon" style="background:rgba(26,111,168,.1);color:var(--blue);">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div>
                                <div class="fw-600 small">Email</div>
                                <div class="text-muted small">konsultasi@gempita.id</div>
                            </div>
                            <i class="bi bi-arrow-right ms-auto text-muted"></i>
                        </a>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="card-gempita animate-fade-up delay-3">
                    <div class="p-4 border-bottom">
                        <h5 class="mb-0 fw-700"><i class="bi bi-question-circle me-2 text-primary"></i>FAQ</h5>
                    </div>
                    <div class="accordion accordion-flush" id="faqAccordion">
                        <?php $faqs = [
                            ['Apakah konsultasi berbayar?', 'Tidak! Layanan konsultasi GEMPITA 100% gratis untuk semua pembudidaya ikan.'],
                            ['Berapa lama waktu respon?', 'Rata-rata 1–3 jam pada jam kerja. Untuk kasus darurat, kami berusaha lebih cepat.'],
                            ['Harus punya akun dulu?', 'Tidak perlu. Konsultasi bisa langsung via WhatsApp tanpa perlu daftar akun.'],
                            ['Bisa konsultasi tentang apa saja?', 'Semua hal terkait budidaya ikan: penyakit, pakan, kualitas air, panen, program bantuan, dan lainnya.'],
                        ];
                        foreach($faqs as $i=>$f): ?>
                        <div class="accordion-item border-0 border-bottom">
                            <h6 class="accordion-header">
                                <button class="accordion-button <?= $i>0?'collapsed':'' ?> small fw-600 py-3" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                                    <?= $f[0] ?>
                                </button>
                            </h6>
                            <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i===0?'show':'' ?>"
                                 data-bs-parent="#faqAccordion">
                                <div class="accordion-body small text-muted py-2"><?= $f[1] ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- Floating WA Button -->
<a href="https://wa.me/<?= WA_NUMBER ?>" target="_blank" class="wa-float" title="Chat WhatsApp">
    <i class="bi bi-whatsapp"></i>
</a>

<style>
.page-header-wa{background:linear-gradient(135deg,#075e54,#25D366);color:#fff;padding:40px 0;}
.page-header-wa .breadcrumb-item,.page-header-wa .breadcrumb-item a{color:rgba(255,255,255,.75);font-size:.85rem;}
.page-header-wa .breadcrumb-item.active{color:#fff;}
.page-header-wa .breadcrumb-item+.breadcrumb-item::before{color:rgba(255,255,255,.5);}
.page-title{font-family:var(--font-ui);font-weight:800;font-size:1.9rem;margin-bottom:.35rem;}
.page-subtitle{opacity:.85;margin-bottom:0;}
.wa-header-icon{width:80px;height:80px;background:rgba(255,255,255,.2);border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#fff;}
/* Topik Cards */
.topik-card{background:#fff;border:2px solid var(--border);border-radius:12px;padding:1rem .75rem;text-align:center;cursor:pointer;transition:var(--transition);height:100%;}
.topik-card:hover{border-color:var(--topik-color);transform:translateY(-2px);box-shadow:var(--shadow);}
.topik-card.active{border-color:var(--topik-color);background:color-mix(in srgb, var(--topik-color) 8%, white);}
.topik-judul{font-size:.82rem;font-weight:700;color:var(--text);margin-bottom:.2rem;}
.topik-desc{font-size:.7rem;color:var(--text-muted);}
/* Kontak */
.kontak-item{display:flex;align-items:center;gap:.85rem;padding:.85rem;border-radius:10px;border:1.5px solid var(--border);text-decoration:none;color:var(--text);transition:var(--transition);}
.kontak-item:hover{border-color:var(--primary);background:rgba(14,124,110,.04);color:var(--text);}
.kontak-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
.fw-600{font-weight:600;}
.fw-700{font-weight:700;}
/* Floating WA */
.wa-float{position:fixed;bottom:2rem;right:2rem;width:58px;height:58px;background:#25D366;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.7rem;box-shadow:0 4px 20px rgba(37,211,102,.4);transition:var(--transition);z-index:999;text-decoration:none;}
.wa-float:hover{transform:scale(1.1);color:#fff;box-shadow:0 6px 28px rgba(37,211,102,.5);}
/* Accordion */
.accordion-button:not(.collapsed){color:var(--primary);background:rgba(14,124,110,.05);box-shadow:none;}
.accordion-button:focus{box-shadow:none;}
</style>

<script>
function pilihTopik(el) {
    document.querySelectorAll('.topik-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('inputTopik').value = el.dataset.judul;
}

function kirimWA() {
    const nama  = document.getElementById('inputNama').value.trim();
    const topik = document.getElementById('inputTopik').value.trim();
    const pesan = document.getElementById('inputPesan').value.trim();

    if (!pesan) {
        alert('Mohon tulis pertanyaan atau permasalahan Anda terlebih dahulu.');
        document.getElementById('inputPesan').focus();
        return;
    }

    let msg = 'Halo GEMPITA, saya ingin konsultasi budidaya ikan.\n\n';
    if (nama)  msg += `*Nama:* ${nama}\n`;
    if (topik) msg += `*Topik:* ${topik}\n`;
    msg += `\n*Pertanyaan/Masalah:*\n${pesan}\n\nTerima kasih 🙏`;

    const url = `https://wa.me/<?= WA_NUMBER ?>?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
}

// Auto-pilih topik dari URL
document.addEventListener('DOMContentLoaded', () => {
    const topik = new URLSearchParams(window.location.search).get('topik');
    if (topik) {
        const card = document.querySelector(`.topik-card[data-kode="${topik}"]`);
        if (card) pilihTopik(card);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
