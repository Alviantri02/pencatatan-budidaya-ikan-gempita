<?php
/**
 * GEMPITA v2 - Auth & Session Helper
 */

if (session_status() === PHP_SESSION_NONE) session_start();

// ── Cek login ──────────────────────────────────────────────
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

// ── Wajib login — redirect ke login jika belum ─────────────
// Dipakai di SEMUA halaman pencatatan
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . APP_URL . '/login.php?pesan=harap_login');
        exit;
    }
}

// ── Cek apakah admin ───────────────────────────────────────
function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

// ── Wajib admin ────────────────────────────────────────────
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}

// ── Data user aktif ────────────────────────────────────────
function currentUser(): array {
    if (!isLoggedIn()) return [];
    return [
        'id'       => $_SESSION['user_id']       ?? null,
        'nama'     => $_SESSION['user_nama']      ?? '',
        'username' => $_SESSION['user_username']  ?? '',
        'email'    => $_SESSION['user_email']     ?? '',
        'role'     => $_SESSION['user_role']      ?? 'pembudidaya',
    ];
}

// ── Set session setelah login berhasil ─────────────────────
function doLogin(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_nama']     = $user['nama_lengkap'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_role']     = $user['role'];
}

// ── Logout ─────────────────────────────────────────────────
function doLogout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ── Flash message ──────────────────────────────────────────
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// ── Sanitasi ───────────────────────────────────────────────
function clean(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ── Redirect ───────────────────────────────────────────────
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// ── Format Rupiah ──────────────────────────────────────────
function rupiah(float $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
