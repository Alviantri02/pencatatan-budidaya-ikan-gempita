<?php
/**
 * GEMPITA v2 - Footer
 */
?>
<footer class="site-footer">
    <div class="footer-body">
        <div class="container">
            <div class="row g-4">

                <!-- Brand -->
                <div class="col-lg-4">
                    <div class="footer-brand">GEMPITA</div>
                    <p class="footer-desc">
                        Gerakan Masyarakat Pembudidaya Ikan Terpadu.<br>
                        Menghubungkan teknologi dengan kearifan lokal perikanan.
                    </p>
                </div>

                <!-- Website Links -->
                <div class="col-6 col-lg-2">
                    <div class="footer-heading">WEBSITE</div>
                    <ul class="footer-links">
                        <li><a href="<?= APP_URL ?>/index.php">Beranda</a></li>
                        <li><a href="<?= APP_URL ?>/informasi.php">Informasi</a></li>
                        <?php if (isLoggedIn()): ?>
                        <li><a href="<?= APP_URL ?>/pencatatan/dashboard.php">Pencatatan</a></li>
                        <?php endif; ?>
                        <li><a href="<?= APP_URL ?>/pendaftaran.php">Pendaftaran</a></li>
                        <li><a href="<?= APP_URL ?>/konsultasi.php">Konsultasi</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div class="col-6 col-lg-3">
                    <div class="footer-heading">SUPPORT</div>
                    <ul class="footer-links">
                        <li><a href="<?= APP_URL ?>/konsultasi.php">Contact Us</a></li>
                        <?php if (!isLoggedIn()): ?>
                        <li><a href="<?= APP_URL ?>/login.php">Login</a></li>
                        <li><a href="<?= APP_URL ?>/daftar-akun.php">Daftar Akun</a></li>
                        <?php else: ?>
                        <li><a href="<?= APP_URL ?>/logout.php">Logout</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Connect -->
                <div class="col-lg-3">
                    <div class="footer-heading">CONNECT</div>
                    <div class="footer-social">
                        <a href="https://wa.me/<?= WA_NUMBER ?>" target="_blank"
                           class="footer-social-btn" title="WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                        <a href="mailto:info@gempita.id"
                           class="footer-social-btn" title="Email">
                            <i class="bi bi-envelope"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <span>© <?= date('Y') ?> GEMPITA. GERAKAN MASYARAKAT PEMBUDIDAYA IKAN TERPADU</span>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global JS -->
<script src="<?= APP_URL ?>/assets/js/main.js"></script>

<?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>
