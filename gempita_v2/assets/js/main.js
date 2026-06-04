/**
 * GEMPITA v2 — main.js
 */
document.addEventListener('DOMContentLoaded', () => {

    // Auto-dismiss flash setelah 5 detik
    document.querySelectorAll('.flash-alert').forEach(el => {
        setTimeout(() => el.closest('.flash-wrapper')?.remove(), 5000);
    });

    // Konfirmasi sebelum hapus
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || 'Yakin ingin menghapus data ini?')) {
                e.preventDefault();
            }
        });
    });

    // Format input angka jadi Rupiah saat blur
    document.querySelectorAll('.input-rp').forEach(input => {
        input.addEventListener('blur', function () {
            const val = parseFloat(this.value.replace(/\D/g, ''));
            if (!isNaN(val)) this.value = val.toLocaleString('id-ID');
        });
        input.addEventListener('focus', function () {
            this.value = this.value.replace(/\./g, '');
        });
    });

    // Toggle password show/hide
    document.querySelectorAll('[data-toggle-pw]').forEach(btn => {
        btn.addEventListener('click', function () {
            const target = document.getElementById(this.dataset.togglePw);
            const icon   = this.querySelector('i');
            if (!target) return;
            if (target.type === 'password') {
                target.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                target.type = 'password';
                icon.className = 'bi bi-eye';
            }
        });
    });

    // Hitung total otomatis (jumlah × harga)
    function hitungTotal(jumlahId, hargaId, totalId) {
        const jumlahEl = document.getElementById(jumlahId);
        const hargaEl  = document.getElementById(hargaId);
        const totalEl  = document.getElementById(totalId);
        if (!jumlahEl || !hargaEl || !totalEl) return;
        function update() {
            const j = parseFloat(jumlahEl.value) || 0;
            const h = parseFloat(hargaEl.value)  || 0;
            totalEl.value = (j * h).toLocaleString('id-ID');
        }
        jumlahEl.addEventListener('input', update);
        hargaEl.addEventListener('input', update);
    }

    // Kalkulasi otomatis untuk form bibit
    hitungTotal('jumlah_bibit', 'harga_bibit', 'total_modal_bibit');
    // Kalkulasi otomatis untuk form pakan
    hitungTotal('jumlah_kg', 'harga_per_kg', 'total_biaya_pakan');
    // Kalkulasi otomatis untuk form panen
    hitungTotal('total_panen_kg', 'harga_jual_kg', 'total_pendapatan');

});

/* ===== Sidebar Active ===== */

const currentPath = window.location.pathname;

document.querySelectorAll('.sidebar-link')
.forEach(link => {

    const href = link.getAttribute('href');

    if(href && currentPath.includes(href)){
        link.classList.add('active');
    }

});