<?php
/**
 * GEMPITA v2 - Konfigurasi Aplikasi & Database
 */

define('DB_HOST',    'localhost');
define('DB_PORT',    '5432');
define('DB_NAME',    'gempita_db');
define('DB_USER',    'postgres');
define('DB_PASS',    'admin1234'); // <-- GANTI INI

define('APP_NAME',    'GEMPITA');
define('APP_URL',     'http://localhost/gempita_v2');
define('APP_VERSION', '2.0');
define('WA_NUMBER',   '6289670719677'); // <-- GANTI INI

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        $detail = (strpos(APP_URL, 'localhost') !== false) ? $e->getMessage() : '';
        die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;">
            <h3>⚠️ Koneksi Database Gagal</h3>
            <p>' . htmlspecialchars($detail) . '</p>
            <p>Pastikan PostgreSQL berjalan dan password di <code>config/db.php</code> sudah benar.</p>
        </div>');
    }

    return $pdo;
}
