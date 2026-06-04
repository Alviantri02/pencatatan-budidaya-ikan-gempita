<?php
/**
 * GEMPITA v2 - Admin Middleware
 * Wajib di-include di semua halaman admin
 * Cek: sudah login + role = admin
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if (!isLoggedIn()) {
    header('Location: ' . APP_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user = currentUser();

if (($user['role'] ?? '') !== 'admin') {
    // Bukan admin — lempar ke beranda dengan pesan
    header('Location: ' . APP_URL . '/index.php?msg=forbidden');
    exit;
}

// Helper: bersihkan string untuk output
if (!function_exists('clean')) {
    function clean($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

// Helper: format rupiah
if (!function_exists('rupiah')) {
    function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
}

// Helper: buat slug dari judul
if (!function_exists('makeSlug')) {
    function makeSlug($str) {
        $str = mb_strtolower(trim($str));
        $str = preg_replace('/[^a-z0-9\s\-]/', '', $str);
        $str = preg_replace('/[\s\-]+/', '-', $str);
        return trim($str, '-');
    }
}

// Helper: slug unik (cek db)
if (!function_exists('uniqueSlug')) {
    function uniqueSlug(PDO $db, string $slug, int $excludeId = 0): string {
        $base = $slug;
        $i    = 1;
        while (true) {
            $s = $db->prepare("SELECT id FROM artikel WHERE slug=? AND id!=?");
            $s->execute([$slug, $excludeId]);
            if (!$s->fetch()) break;
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
