<?php
// send_certificates_email.php - ส่งใบประกาศทาง Email
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$config = require 'email_config.php';

// ดาวน์โหลด PHPMailer (ถ้ายังไม่มี)
// composer require phpmailer/phpmailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ตรวจสอบว่ามี PHPMailer หรือไม่
$phpmailerPath = __DIR__ . '/vendor/phpmailer/autoload.php';
if (!file_exists($phpmailerPath)) {
    die('PHPMailer ไม่พบ! ให้ติดตั้งด้วย vendor/phpmailer');
}

require $phpmailerPath;

$success = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipients = $_POST['recipients'] ?? []; // Array of [name, email]
    $certificateFile = $_POST['certificate_file'] ?? '';
    
    foreach ($recipients as $recipient) {
        $name = $recipient['name'];
        $email = $recipient['email'];
        
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_username'];
            $mail->Password = $config['smtp_password'];
            $mail->SMTPSecure = $config['smtp_secure'];
            $mail->Port = $config['smtp_port'];
            $mail->CharSet = 'UTF-8';
            
            // Recipients
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($email, $name);
            $mail->addReplyTo($config['reply_to'], $config['from_name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = '🏆 ใบประกาศของคุณพร้อมแล้ว - ' . $name;
            
            // Load template
            $emailBody = file_get_contents('email_template.html');
            $emailBody = str_replace('{{RECIPIENT_NAME}}', $name, $emailBody);
            $emailBody = str_replace('{{DATE_CREATED}}', date('d/m/Y H:i'), $emailBody);
            
            $mail->Body = $emailBody;
            $mail->AltBody = "เรียน คุณ{$name}\n\nใบประกาศของคุณพร้อมแล้ว กรุณาดาวน์โหลดจากไฟล์แนบ";
            
            // Attachment (ถ้ามี)
            if ($certificateFile && file_exists($certificateFile)) {
                $mail->addAttachment($certificateFile, "certificate_{$name}.png");
            }
            
            $mail->send();
            $success[] = "✅ ส่งอีเมลไปยัง {$name} ({$email}) สำเร็จ";
            
        } catch (Exception $e) {
            $errors[] = "❌ ส่งอีเมลไปยัง {$name} ({$email}) ล้มเหลว: {$mail->ErrorInfo}";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ส่งใบประกาศทาง Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-envelope"></i> ส่งใบประกาศทาง Email</h4>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <?php foreach ($success as $msg): ?>
                        <div class="alert alert-success"><?= $msg ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ($errors): ?>
                    <?php foreach ($errors as $err): ?>
                        <div class="alert alert-danger"><?= $err ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">รายชื่อผู้รับ (ชื่อ, Email)</label>
                        <textarea name="recipients_csv" class="form-control" rows="5" placeholder="ชื่อ-นามสกุล, email@example.com&#10;สมชาย ใจดี, somchai@email.com"></textarea>
                        <div class="form-text">ใส่รายชื่อทีละบรรทัด รูปแบบ: ชื่อ-นามสกุล, email@example.com</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> ส่งอีเมลทั้งหมด
                    </button>
                    <a href="index.php" class="btn btn-secondary">กลับหน้าหลัก</a>
                </form>
                
                <hr class="my-4">
                
                <div class="alert alert-info">
                    <strong>📝 วิธีตั้งค่า Gmail SMTP:</strong>
                    <ol>
                        <li>เข้า Google Account > Security</li>
                        <li>เปิด 2-Step Verification</li>
                        <li>สร้าง App Password (เลือก Mail และ Other)</li>
                        <li>คัดลอก App Password ไปใส่ใน email_config.php</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
