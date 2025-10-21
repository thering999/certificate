<?php
require_once 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'ไม่ได้รับอนุญาต';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Display upload form
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>อัปโหลดพื้นหลัง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-image me-2"></i>Upload Background
        </a>
        <div class="navbar-nav ms-auto">
            <a href="index.php" class="nav-link"><i class="fas fa-home me-1"></i>หน้าหลัก</a>
            <a href="dashboard.php" class="nav-link"><i class="fas fa-th-large me-1"></i>Dashboard</a>
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ
            </a>
        </div>
    </div>
</nav>
<div class="container py-5">
    <h1>📸 อัปโหลดพื้นหลังใบประกาศ</h1>
    <div class="card">
        <div class="card-body">
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="bgImage" class="form-label">เลือกไฟล์รูปภาพ</label>
                    <input type="file" class="form-control" id="bgImage" name="bg_image" accept="image/*" required>
                    <small class="text-muted">รูปแบบที่รองรับ: JPG, PNG, GIF, WebP (สูงสุด 50MB)</small>
                </div>
                <button type="submit" class="btn btn-primary">อัปโหลด</button>
            </form>
            <div id="message" class="mt-3"></div>
        </div>
    </div>
</div>
<script>
document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData();
    formData.append('bg_image', document.getElementById('bgImage').files[0]);
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        const text = await response.text();
        const msgDiv = document.getElementById('message');
        
        if (response.ok) {
            msgDiv.innerHTML = '<div class="alert alert-success">✅ ' + text + '</div>';
            setTimeout(() => window.location.href = 'dashboard.php', 2000);
        } else {
            msgDiv.innerHTML = '<div class="alert alert-danger">❌ ' + text + '</div>';
        }
    } catch (error) {
        document.getElementById('message').innerHTML = '<div class="alert alert-danger">❌ เกิดข้อผิดพลาด: ' + error.message + '</div>';
    }
});
</script>
</body>
</html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bg_image'])) {
    $file = $_FILES['bg_image'];
    
    // ตรวจสอบ upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'ไฟล์ใหญ่เกินไป (เกินค่า upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE => 'ไฟล์ใหญ่เกินไป (เกินค่า MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'อัปโหลดไฟล์ไม่สมบูรณ์',
            UPLOAD_ERR_NO_FILE => 'ไม่มีไฟล์ที่อัปโหลด',
            UPLOAD_ERR_NO_TMP_DIR => 'ไม่มีโฟลเดอร์ temp',
            UPLOAD_ERR_CANT_WRITE => 'ไม่สามารถเขียนไฟล์ได้',
            UPLOAD_ERR_EXTENSION => 'การอัปโหลดถูกหยุดโดย extension'
        ];
        http_response_code(400);
        echo $error_messages[$file['error']] ?? 'เกิดข้อผิดพลาดในการอัปโหลด: ' . $file['error'];
        exit;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo 'ประเภทไฟล์ไม่ถูกต้อง ต้องเป็น JPG, PNG, GIF หรือ WebP';
        exit;
    }
    
    // ตรวจสอบขนาดไฟล์ (จำกัดที่ 50MB)
    $maxSize = 50 * 1024 * 1024; // 50MB
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo 'ไฟล์ใหญ่เกินไป (จำกัดที่ 50MB)';
        exit;
    }
    
    $targetDir = 'public/bg/';
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            http_response_code(500);
            echo 'ไม่สามารถสร้างโฟลเดอร์ได้: ' . $targetDir;
            exit;
        }
    }
    
    // ตรวจสอบว่าสามารถเขียนไฟล์ในโฟลเดอร์ได้หรือไม่
    if (!is_writable($targetDir)) {
        http_response_code(500);
        echo 'ไม่สามารถเขียนไฟล์ในโฟลเดอร์ได้: ' . $targetDir;
        exit;
    }
    
    $filename = 'bg_' . date('Ymd_His') . '_' . rand(1000,9999) . '.' . $ext;
    $targetPath = $targetDir . $filename;
    
    // ตรวจสอบว่าไฟล์ temp มีอยู่จริงหรือไม่
    if (!is_uploaded_file($file['tmp_name'])) {
        http_response_code(400);
        echo 'ไฟล์ temp ไม่ถูกต้อง';
        exit;
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $_SESSION['bg_path'] = $targetPath;
        http_response_code(200);
        echo 'อัปโหลดสำเร็จ! (' . $filename . ')';
    } else {
        $lastError = error_get_last();
        http_response_code(500);
        echo 'เกิดข้อผิดพลาดในการบันทึกไฟล์: ' . ($lastError['message'] ?? 'Unknown error');
    }
    exit;
}

// If not POST request
http_response_code(405);
echo 'Method Not Allowed';
?>
