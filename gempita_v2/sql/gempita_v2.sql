-- ============================================================
--  GEMPITA v2 - Database Schema PostgreSQL (REVISED)
--  - Harga pasaran diinput oleh USER masing-masing
--  - Admin hanya bisa kelola artikel
--  - Pencatatan wajib login
-- ============================================================

-- DROP semua tabel lama jika ada
DROP TABLE IF EXISTS laporan           CASCADE;
DROP TABLE IF EXISTS hasil_panen       CASCADE;
DROP TABLE IF EXISTS harga_pasaran     CASCADE;
DROP TABLE IF EXISTS data_pakan        CASCADE;
DROP TABLE IF EXISTS data_kolam_bibit  CASCADE;
DROP TABLE IF EXISTS pendaftaran       CASCADE;
DROP TABLE IF EXISTS artikel           CASCADE;
DROP TABLE IF EXISTS kategori_artikel  CASCADE;
DROP TABLE IF EXISTS users             CASCADE;

-- ============================================================
-- 1. USERS
--    role: 'pembudidaya' = bisa pencatatan
--          'admin'       = hanya bisa kelola artikel
-- ============================================================
CREATE TABLE users (
    id            SERIAL       PRIMARY KEY,
    nama_lengkap  VARCHAR(150) NOT NULL,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    no_hp         VARCHAR(20),
    alamat        TEXT,
    role          VARCHAR(20)  NOT NULL DEFAULT 'pembudidaya',
    is_active     BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. PENDAFTARAN PROGRAM GEMPITA
--    Hanya 1 program, tanpa NIK, tanpa sub-program
--    Siapapun bisa daftar (tidak perlu login)
-- ============================================================
CREATE TABLE pendaftaran (
    id            SERIAL       PRIMARY KEY,
    nama_lengkap  VARCHAR(150) NOT NULL,
    email         VARCHAR(150) NOT NULL,
    no_hp         VARCHAR(20)  NOT NULL,
    alamat        TEXT,
    status        VARCHAR(20)  NOT NULL DEFAULT 'menunggu', -- menunggu/diterima/ditolak
    catatan_admin TEXT,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 3. KATEGORI ARTIKEL
-- ============================================================
CREATE TABLE kategori_artikel (
    id    SERIAL       PRIMARY KEY,
    nama  VARCHAR(100) NOT NULL,
    slug  VARCHAR(100) NOT NULL UNIQUE,
    warna VARCHAR(20)  DEFAULT '#1a6fd4'
);

-- ============================================================
-- 4. ARTIKEL
--    Hanya admin yang bisa buat / edit / hapus
--    Semua pengunjung bisa baca
-- ============================================================
CREATE TABLE artikel (
    id             SERIAL       PRIMARY KEY,
    kategori_id    INT          REFERENCES kategori_artikel(id) ON DELETE SET NULL,
    dibuat_oleh    INT          REFERENCES users(id) ON DELETE SET NULL, -- harus admin
    judul          VARCHAR(255) NOT NULL,
    slug           VARCHAR(255) NOT NULL UNIQUE,
    ringkasan      TEXT,
    konten         TEXT         NOT NULL,
    penulis        VARCHAR(100) DEFAULT 'Tim GEMPITA',
    estimasi_baca  INT          DEFAULT 5,
    gambar_url     VARCHAR(255),
    is_publish     BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 5. DATA KOLAM & BIBIT
--    Wajib login sebagai pembudidaya
--    Harga bibit diinput user → jadi komponen modal
-- ============================================================
CREATE TABLE data_kolam_bibit (
    id                 SERIAL        PRIMARY KEY,
    user_id            INT           NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    nama_kolam         VARCHAR(100)  NOT NULL,
    jenis_ikan         VARCHAR(100)  NOT NULL,
    tanggal_tebar      DATE          NOT NULL,
    jumlah_bibit       INT           NOT NULL,
    harga_bibit        DECIMAL(12,2) NOT NULL DEFAULT 0,
    -- total_modal_bibit dihitung otomatis
    total_modal_bibit  DECIMAL(14,2) GENERATED ALWAYS AS (jumlah_bibit * harga_bibit) STORED,
    ukuran_kolam       DECIMAL(10,2),           -- m²
    jenis_kolam        VARCHAR(50),             -- tanah/terpal/beton/keramba
    lokasi_kolam       TEXT,
    -- Kondisi air: bahasa sederhana, bukan parameter teknis
    kondisi_air        VARCHAR(20)   DEFAULT 'baik',    -- baik/cukup/buruk
    kejernihan_air     VARCHAR(20)   DEFAULT 'jernih',  -- jernih/keruh/sangat keruh
    catatan            TEXT,
    is_aktif           BOOLEAN       NOT NULL DEFAULT TRUE,
    created_at         TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 6. DATA PAKAN
--    Wajib login - diinput oleh user sendiri
--    Harga pakan diinput user → komponen modal
-- ============================================================
CREATE TABLE data_pakan (
    id           SERIAL        PRIMARY KEY,
    user_id      INT           NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    kolam_id     INT           NOT NULL REFERENCES data_kolam_bibit(id) ON DELETE CASCADE,
    tanggal      DATE          NOT NULL,
    jenis_pakan  VARCHAR(100)  NOT NULL,
    merek        VARCHAR(100),
    jumlah_kg    DECIMAL(8,2)  NOT NULL,
    harga_per_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
    -- total_biaya dihitung otomatis
    total_biaya  DECIMAL(14,2) GENERATED ALWAYS AS (jumlah_kg * harga_per_kg) STORED,
    catatan      TEXT,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 7. HARGA PASARAN IKAN
--    Diinput oleh USER sendiri (bukan admin)
--    Dipakai sebagai acuan kalkulasi pendapatan panen
--    user_id = siapa yang menginput harga ini
-- ============================================================
CREATE TABLE harga_pasaran (
    id           SERIAL        PRIMARY KEY,
    user_id      INT           NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    jenis_ikan   VARCHAR(100)  NOT NULL,
    harga_per_kg DECIMAL(10,2) NOT NULL,
    tanggal      DATE          NOT NULL DEFAULT CURRENT_DATE,
    sumber       VARCHAR(150),  -- contoh: Pasar Induk Bogor, Pengepul, dll
    catatan      TEXT,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 8. HASIL PANEN
--    Wajib login - diinput user
--    Kalkulasi pendapatan & keuntungan otomatis lewat VIEW
-- ============================================================
CREATE TABLE hasil_panen (
    id               SERIAL        PRIMARY KEY,
    user_id          INT           NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    kolam_id         INT           NOT NULL REFERENCES data_kolam_bibit(id) ON DELETE CASCADE,
    harga_pasaran_id INT           REFERENCES harga_pasaran(id) ON DELETE SET NULL,
    tanggal_panen    DATE          NOT NULL,
    total_panen_kg   DECIMAL(10,2) NOT NULL,
    -- jumlah_ikan_hidup: pengganti "survival rate" → bahasa sederhana
    jumlah_ikan_hidup INT,
    harga_jual_kg    DECIMAL(10,2) NOT NULL DEFAULT 0,
    -- total_pendapatan dihitung otomatis
    total_pendapatan DECIMAL(14,2) GENERATED ALWAYS AS (total_panen_kg * harga_jual_kg) STORED,
    pembeli          VARCHAR(150),
    jenis_panen      VARCHAR(20)   DEFAULT 'total', -- total/sebagian
    catatan          TEXT,
    created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- VIEW 1: LAPORAN KEUANGAN PER KOLAM
--   Modal    = Bibit + Pakan
--   Untung   = Pendapatan - Modal
-- ============================================================
CREATE OR REPLACE VIEW v_laporan_keuangan AS
SELECT
    kb.id                                                   AS kolam_id,
    kb.user_id,
    u.nama_lengkap                                          AS nama_pembudidaya,
    kb.nama_kolam,
    kb.jenis_ikan,
    kb.tanggal_tebar,
    kb.jumlah_bibit,
    kb.harga_bibit,
    kb.total_modal_bibit                                    AS modal_bibit,
    COALESCE(SUM(DISTINCT dp.total_biaya),      0)          AS modal_pakan,
    kb.total_modal_bibit
        + COALESCE(SUM(DISTINCT dp.total_biaya), 0)         AS total_modal,
    COALESCE(SUM(DISTINCT hp.total_pendapatan), 0)          AS total_pendapatan,
    COALESCE(SUM(DISTINCT hp.total_pendapatan), 0)
        - kb.total_modal_bibit
        - COALESCE(SUM(DISTINCT dp.total_biaya), 0)         AS keuntungan_bersih,
    COALESCE(SUM(DISTINCT hp.total_panen_kg),   0)          AS total_panen_kg
FROM data_kolam_bibit kb
JOIN users u ON kb.user_id = u.id
LEFT JOIN data_pakan   dp ON dp.kolam_id = kb.id
LEFT JOIN hasil_panen  hp ON hp.kolam_id = kb.id
GROUP BY
    kb.id, kb.user_id, u.nama_lengkap, kb.nama_kolam,
    kb.jenis_ikan, kb.tanggal_tebar, kb.jumlah_bibit,
    kb.harga_bibit, kb.total_modal_bibit;

-- ============================================================
-- VIEW 2: RINGKASAN DASHBOARD PER USER
-- ============================================================
CREATE OR REPLACE VIEW v_dashboard_user AS
SELECT
    u.id                                                        AS user_id,
    u.nama_lengkap,
    COUNT(DISTINCT kb.id)                                       AS total_kolam,
    COALESCE(SUM(kb.total_modal_bibit), 0)                      AS total_modal_bibit,
    COALESCE(SUM(pk.total_pakan),       0)                      AS total_modal_pakan,
    COALESCE(SUM(kb.total_modal_bibit), 0)
        + COALESCE(SUM(pk.total_pakan), 0)                      AS total_modal,
    COALESCE(SUM(pn.total_pend),        0)                      AS total_pendapatan,
    COALESCE(SUM(pn.total_pend),        0)
        - COALESCE(SUM(kb.total_modal_bibit), 0)
        - COALESCE(SUM(pk.total_pakan),       0)                AS proyeksi_keuntungan
FROM users u
LEFT JOIN data_kolam_bibit kb
       ON kb.user_id = u.id
LEFT JOIN (
    SELECT kolam_id, SUM(total_biaya)      AS total_pakan
    FROM   data_pakan GROUP BY kolam_id
) pk ON pk.kolam_id = kb.id
LEFT JOIN (
    SELECT kolam_id, SUM(total_pendapatan) AS total_pend
    FROM   hasil_panen GROUP BY kolam_id
) pn ON pn.kolam_id = kb.id
WHERE u.role = 'pembudidaya'
GROUP BY u.id, u.nama_lengkap;

-- ============================================================
-- TRIGGER: auto-update kolom updated_at
-- ============================================================
CREATE OR REPLACE FUNCTION fn_set_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_users_upd
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE PROCEDURE fn_set_timestamp();

CREATE TRIGGER trg_pendaftaran_upd
    BEFORE UPDATE ON pendaftaran
    FOR EACH ROW EXECUTE PROCEDURE fn_set_timestamp();

CREATE TRIGGER trg_artikel_upd
    BEFORE UPDATE ON artikel
    FOR EACH ROW EXECUTE PROCEDURE fn_set_timestamp();

-- ============================================================
-- INDEX performa
-- ============================================================
CREATE INDEX idx_users_email       ON users(email);
CREATE INDEX idx_users_username    ON users(username);
CREATE INDEX idx_kolam_user        ON data_kolam_bibit(user_id);
CREATE INDEX idx_pakan_kolam       ON data_pakan(kolam_id);
CREATE INDEX idx_pakan_user        ON data_pakan(user_id);
CREATE INDEX idx_panen_kolam       ON hasil_panen(kolam_id);
CREATE INDEX idx_panen_user        ON hasil_panen(user_id);
CREATE INDEX idx_harga_user        ON harga_pasaran(user_id);
CREATE INDEX idx_harga_ikan        ON harga_pasaran(jenis_ikan);
CREATE INDEX idx_artikel_publish   ON artikel(is_publish);
CREATE INDEX idx_artikel_kategori  ON artikel(kategori_id);

-- ============================================================
-- SEED: Kategori Artikel
-- ============================================================
INSERT INTO kategori_artikel (nama, slug, warna) VALUES
('Produksi',   'produksi',   '#1a6fd4'),
('Nutrisi',    'nutrisi',    '#f5a623'),
('Kesehatan',  'kesehatan',  '#e74c3c'),
('Bisnis',     'bisnis',     '#27ae60'),
('Teknologi',  'teknologi',  '#8e44ad'),
('Tips & Trik','tips-trik',  '#0ea5a0');

-- ============================================================
-- SEED: Admin (password di-set via setup-admin.php)
-- ============================================================
INSERT INTO users (nama_lengkap, username, email, password, role) VALUES
('Administrator GEMPITA', 'admin', 'admin@gempita.id', 'HASH_PLACEHOLDER', 'admin');

-- ============================================================
-- SEED: Artikel (6 artikel siap pakai)
-- ============================================================
INSERT INTO artikel (kategori_id, dibuat_oleh, judul, slug, ringkasan, konten, penulis, estimasi_baca, is_publish) VALUES
(1, 1,
 'Cara Memilih Bibit Ikan Lele yang Berkualitas',
 'cara-memilih-bibit-lele-berkualitas',
 'Bibit yang baik adalah kunci keberhasilan budidaya lele. Pelajari ciri-ciri bibit unggul.',
 '<h4>Mengapa Pemilihan Bibit Penting?</h4>
<p>Kualitas bibit menentukan 60% keberhasilan budidaya. Bibit buruk akan tumbuh lambat, rentan penyakit, dan menurunkan hasil panen.</p>
<h4>Ciri-ciri Bibit Lele Berkualitas</h4>
<ul>
<li><strong>Gerakan aktif</strong> — bibit berenang lincah, tidak diam di dasar</li>
<li><strong>Warna cerah</strong> — abu kehitaman atau coklat kemerahan, tidak pucat</li>
<li><strong>Ukuran seragam</strong> — pilih bibit 5–7 cm untuk hasil optimal</li>
<li><strong>Tidak ada luka</strong> — sirip dan ekor utuh</li>
<li><strong>Responsif terhadap pakan</strong> — langsung mengejar saat diberi pakan</li>
</ul>
<h4>Dari Mana Membeli Bibit?</h4>
<p>Selalu beli dari Balai Benih Ikan (BBI) resmi atau penjual bersertifikat. Hindari bibit dari sumber tidak jelas karena berisiko membawa penyakit.</p>
<h4>Tips Aklimatisasi</h4>
<p>Sebelum ditebar, rendam plastik berisi bibit di air kolam selama 15–20 menit agar suhu menyesuaikan. Ini mencegah stres dan kematian massal saat penebaran.</p>',
 'Tim GEMPITA', 8, TRUE),

(2, 1,
 'Panduan Lengkap Pemberian Pakan Ikan yang Efisien',
 'panduan-pemberian-pakan-efisien',
 'Pakan adalah biaya terbesar dalam budidaya. Pelajari cara memberi pakan yang tepat dan hemat.',
 '<h4>Prinsip Dasar Pemberian Pakan</h4>
<p>Pakan berlebihan bukan berarti ikan tumbuh lebih cepat. Sebaliknya, sisa pakan mencemari air dan meningkatkan biaya.</p>
<h4>Aturan 3–5% Bobot Ikan per Hari</h4>
<p>Berikan pakan sebanyak 3–5% dari total berat ikan per hari. Contoh: total berat 10 kg → beri 300–500 gram pakan per hari.</p>
<h4>Frekuensi Pemberian Pakan</h4>
<ul>
<li>Lele: 2–3 kali sehari (pagi, siang, sore)</li>
<li>Nila: 2 kali sehari (pagi & sore)</li>
<li>Gurame: 2 kali sehari + pakan hijauan</li>
</ul>
<h4>Tanda Pakan Sudah Cukup</h4>
<p>Hentikan pemberian pakan jika ikan tidak agresif mengejar atau ada sisa pakan di permukaan setelah 10 menit.</p>',
 'Tim GEMPITA', 6, TRUE),

(3, 1,
 'Mengenali Penyakit Umum pada Ikan Lele dan Cara Mengatasinya',
 'penyakit-umum-ikan-lele',
 'Kenali gejala penyakit lele lebih awal agar penanganan lebih cepat dan kerugian diminimalkan.',
 '<h4>Penyakit Paling Sering Menyerang Lele</h4>
<h5>1. Bercak Merah</h5>
<p><strong>Gejala:</strong> Bercak merah di tubuh, sirip rusak, ikan lemas.<br>
<strong>Cara atasi:</strong> Ganti 30% air, tambah garam dapur 5 gr/liter.</p>
<h5>2. Jamur Putih</h5>
<p><strong>Gejala:</strong> Benang putih seperti kapas di tubuh ikan.<br>
<strong>Cara atasi:</strong> Isolasi ikan, rendam dalam larutan Methylene Blue.</p>
<h5>3. Perut Kembung</h5>
<p><strong>Gejala:</strong> Perut membesar, ikan berenang miring.<br>
<strong>Cara atasi:</strong> Kurangi pakan, perbaiki kebersihan air.</p>
<h4>Pencegahan</h4>
<p>Ganti air rutin 20–30% setiap 3–5 hari, hindari kepadatan ikan berlebihan.</p>',
 'Tim GEMPITA', 10, TRUE),

(6, 1,
 '5 Kesalahan Pemula dalam Budidaya Ikan yang Harus Dihindari',
 '5-kesalahan-pemula-budidaya-ikan',
 'Banyak pemula gagal bukan karena tidak bekerja keras, tapi karena kesalahan dasar yang mudah dihindari.',
 '<h4>Kesalahan yang Sering Terjadi</h4>
<h5>1. Terlalu Padat Menebar Bibit</h5>
<p>Kepadatan berlebihan → ikan stres, mudah sakit, pertumbuhan lambat.</p>
<h5>2. Tidak Menyiapkan Kolam dengan Benar</h5>
<p>Kolam baru harus dikeringkan, dikapur, dan didiamkan 5–7 hari sebelum diisi.</p>
<h5>3. Jarang Mengganti Air</h5>
<p>Air kolam yang tidak diganti menumpuk kotoran → kematian massal.</p>
<h5>4. Tidak Mencatat Pengeluaran</h5>
<p>Tanpa catatan, Anda tidak tahu apakah usaha ini untung atau rugi.</p>
<h5>5. Membeli Bibit Murah Sembarangan</h5>
<p>Bibit dari sumber tidak jelas sering membawa penyakit ke kolam.</p>',
 'Tim GEMPITA', 7, TRUE),

(4, 1,
 'Cara Menghitung Keuntungan Budidaya Ikan dengan Benar',
 'cara-menghitung-keuntungan-budidaya',
 'Banyak pembudidaya tidak tahu apakah usaha mereka untung atau rugi. Ini cara hitungnya.',
 '<h4>Komponen Modal</h4>
<ul>
<li><strong>Modal Bibit:</strong> Jumlah bibit × harga per ekor</li>
<li><strong>Modal Pakan:</strong> Total pakan (kg) × harga per kg</li>
</ul>
<h4>Pendapatan</h4>
<p>Pendapatan = Total panen (kg) × Harga jual per kg</p>
<h4>Rumus Keuntungan Bersih</h4>
<p><strong>Keuntungan = Pendapatan − (Modal Bibit + Modal Pakan)</strong></p>
<h4>Contoh Nyata</h4>
<p>Modal bibit: 1.000 ekor × Rp500 = Rp500.000<br>
Modal pakan: 50 kg × Rp8.000 = Rp400.000<br>
Total modal: Rp900.000<br>
Hasil panen: 80 kg × Rp20.000 = Rp1.600.000<br>
<strong>Keuntungan: Rp1.600.000 − Rp900.000 = Rp700.000 🎉</strong></p>',
 'Tim GEMPITA', 9, TRUE),

(5, 1,
 'Mengenal Sistem Bioflok untuk Pemula',
 'sistem-bioflok-untuk-pemula',
 'Bioflok bisa menghemat biaya pakan hingga 30%. Cocok untuk skala rumahan dan pemula.',
 '<h4>Apa itu Bioflok?</h4>
<p>Bioflok adalah kumpulan bakteri dan mikroorganisme di dalam air kolam yang menjadi pakan alami tambahan bagi ikan.</p>
<h4>Keuntungan Bioflok</h4>
<ul>
<li>Hemat pakan 20–30%</li>
<li>Air lebih bersih, jarang ganti air</li>
<li>Ikan tumbuh lebih cepat</li>
</ul>
<h4>Cara Memulai</h4>
<ol>
<li>Siapkan aerator kuat</li>
<li>Tambahkan molases (tetes tebu) sebagai sumber karbon</li>
<li>Tambahkan probiotik starter</li>
<li>Tunggu 5–7 hari hingga air berwarna hijau kecoklatan</li>
</ol>',
 'Tim GEMPITA', 12, TRUE);

-- ============================================================
-- SELESAI
-- Cara menjalankan:
--   1. Buka pgAdmin → buat database baru: gempita_db
--   2. Buka Query Tool → load file ini → Execute (F5)
--   3. Jalankan setup-admin.php untuk set password admin
-- ============================================================
