<?php
require_once __DIR__ . '/config/db.php';
$password = 'Admin@Gempita2025';
$hash     = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$pdo      = getDB();
$stmt     = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->execute([$hash]);
if ($stmt->rowCount() > 0) {
    echo "<div style='font-family:sans-serif;max-width:460px;margin:60px auto;padding:2rem;border:2px solid #1a6fd4;border-radius:16px;'>";
    echo "<h2 style='color:#1a6fd4'>✅ Password Admin Berhasil!</h2>";
    echo "<p><b>Username:</b> admin</p><p><b>Password:</b> $password</p>";
    echo "<p style='color:red;font-weight:bold;'>⚠️ HAPUS file ini sekarang!</p>";
    echo "<a href='" . APP_URL . "/login.php' style='background:#1a6fd4;color:#fff;padding:.6rem 1.5rem;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block;margin-top:.5rem;'>→ Login</a>";
    echo "</div>";
} else {
    echo "<p style='color:red;font-family:sans-serif;padding:2rem;'>❌ Gagal. Jalankan SQL terlebih dahulu.</p>";
}
?>
