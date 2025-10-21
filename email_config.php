<?php
// email_config.php - ตั้งค่า Email
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls', // tls หรือ ssl
    'smtp_username' => 'your-email@gmail.com', // แก้ไขตรงนี้
    'smtp_password' => 'your-app-password', // ใช้ App Password ของ Gmail
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'ระบบใบประกาศ สสจ.มุกดาหาร',
    'reply_to' => 'your-email@gmail.com',
];
