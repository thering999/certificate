<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_path = $_POST['template_path'] ?? '';
    $template_name = $_POST['template_name'] ?? '';
    
    if (!empty($template_path) && file_exists($template_path)) {
        $_SESSION['bg_path'] = $template_path;
        $_SESSION['template_name'] = $template_name;
        $_SESSION['alert'] = 'ตั้งค่าเทมเพลต "' . htmlspecialchars($template_name) . '" เป็นพื้นหลังปัจจุบันแล้ว';
        echo 'success';
    } else {
        echo 'error: Template file not found';
    }
} else {
    http_response_code(405);
    echo 'Method not allowed';
}
?>