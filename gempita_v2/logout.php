<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
doLogout();
setFlash('success', 'Anda berhasil logout. Sampai jumpa! 👋');
redirect(APP_URL . '/index.php');
