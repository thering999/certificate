<?php
// Simple PHPMailer Loader - ใช้แทน Composer autoload
// ไฟล์นี้จะโหลด PHPMailer classes โดยไม่ต้องใช้ Composer

spl_autoload_register(function ($class) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    $base_dir = __DIR__ . '/vendor/phpmailer/PHPMailer-master/src/';
    
    // ตรวจสอบว่า class เป็นของ PHPMailer หรือไม่
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// สำหรับกรณีที่ดาวน์โหลดด้วยมือ
if (file_exists(__DIR__ . '/vendor/phpmailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/vendor/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/vendor/phpmailer/src/Exception.php';
}
