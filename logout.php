<?php
session_start();

// บันทึก log
require_once 'assets/error_handler.php';
ErrorHandler::log('User logged out: ' . ($_SESSION['username'] ?? 'Unknown'), 'INFO');

// ล้าง session ทั้งหมด
$_SESSION = [];

// ลบ session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session อย่างถูกต้อง
session_regenerate_id(true); // regenerate ก่อน destroy
session_destroy();

// Clear all cookies
foreach ($_COOKIE as $key => $value) {
    setcookie($key, '', time() - 3600, '/');
}

// Redirect ไป login
header('Location: login.php');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
exit;
