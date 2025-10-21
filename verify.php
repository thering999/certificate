<?php
// Verify Certificate - ตรวจสอบใบประกาศจาก QR Code
require_once "db.php";

$code = $_GET['code'] ?? '';
$certificate = null;
$valid = false;

if ($code) {
    // ค้นหาใบประกาศจาก verification code
    $stmt = $conn->prepare("SELECT * FROM certificate_names WHERE qr_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $certificate = $stmt->get_result()->fetch_assoc();
    
    if ($certificate) {
        $valid = true;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ตรวจสอบความถูกต้องใบประกาศ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .verify-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .verify-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
        }
        .icon-large {
            font-size: 5rem;
        }
    </style>
</head>
<body>
<div class="verify-container">
    <div class="verify-card text-center">
        <?php if ($valid && $certificate): ?>
            <i class="fas fa-check-circle text-success icon-large mb-4"></i>
            <h2 class="text-success mb-4">ใบประกาศถูกต้อง</h2>
            <div class="alert alert-success">
                <h5>ข้อมูลใบประกาศ</h5>
                <hr>
                <p class="mb-1"><strong>ID:</strong> <?= htmlspecialchars($certificate['id']) ?></p>
                <p class="mb-1"><strong>ชื่อ:</strong> <?= htmlspecialchars($certificate['name']) ?></p>
                <p class="mb-0"><strong>สร้างเมื่อ:</strong> <?= htmlspecialchars($certificate['created_at'] ?? 'N/A') ?></p>
            </div>
            <p class="text-muted"><i class="fas fa-shield-alt"></i> ใบประกาศนี้ได้รับการตรวจสอบแล้ว</p>
        <?php elseif ($code): ?>
            <i class="fas fa-times-circle text-danger icon-large mb-4"></i>
            <h2 class="text-danger mb-4">ใบประกาศไม่ถูกต้อง</h2>
            <div class="alert alert-danger">
                <p class="mb-0">ไม่พบใบประกาศในระบบ หรือ QR Code ไม่ถูกต้อง</p>
            </div>
            <p class="text-muted"><i class="fas fa-exclamation-triangle"></i> กรุณาตรวจสอบอีกครั้ง</p>
        <?php else: ?>
            <i class="fas fa-question-circle text-secondary icon-large mb-4"></i>
            <h2 class="text-secondary mb-4">ระบบตรวจสอบใบประกาศ</h2>
            <p class="text-muted">Scan QR Code บนใบประกาศเพื่อตรวจสอบความถูกต้อง</p>
        <?php endif; ?>
        
        <hr>
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-home"></i> กลับหน้าหลัก
        </a>
    </div>
</div>
</body>
</html>
