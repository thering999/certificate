<?php
// Name Recognition (OCR Mockup)
// อัปโหลดรูปภาพรายชื่อ แล้ว AI อ่านและสร้าง CSV อัตโนมัติ
session_start();
$error = "";
$csv_result = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image_file'])) {
    $file = $_FILES['image_file']['tmp_name'];
    $file_type = $_FILES['image_file']['type'];
    $allowed_types = ['image/png', 'image/jpeg', 'image/jpg'];
    if (!in_array($file_type, $allowed_types)) {
        $error = "ประเภทไฟล์ไม่ถูกต้อง ต้องเป็น PNG หรือ JPG";
    } else {
        // Mock OCR: return sample CSV
        $csv_result = "ชื่อ1\nชื่อ2\nชื่อ3";
    }
}
// Google Vision API OCR (ตัวอย่าง)
// require_once 'vendor/autoload.php';
// $apiKey = 'YOUR_GOOGLE_VISION_API_KEY';
// if ($file) {
//     $imageData = base64_encode(file_get_contents($file));
//     $json = json_encode([
//         'requests' => [[
//             'image' => ['content' => $imageData],
//             'features' => [['type' => 'TEXT_DETECTION']]
//         ]]
//     ]);
//     $ch = curl_init('https://vision.googleapis.com/v1/images:annotate?key=' . $apiKey);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_POST, true);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
//     $response = curl_exec($ch);
//     curl_close($ch);
//     $data = json_decode($response, true);
//     $csv_result = $data['responses'][0]['fullTextAnnotation']['text'] ?? '';
// }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>OCR Name Recognition</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-brain me-2"></i>OCR Name Recognition
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
    <h2 class="mb-4">AI อ่านรายชื่อจากรูปภาพ (OCR)</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="mb-4">
        <div class="mb-3">
            <label for="image_file" class="form-label">อัปโหลดรูปภาพรายชื่อ (PNG, JPG)</label>
            <input type="file" name="image_file" id="image_file" accept=".png,.jpg,.jpeg" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">อ่านรายชื่อ</button>
    </form>
    <?php if ($csv_result): ?>
        <div class="card mt-4">
            <div class="card-body">
                <h5>ผลลัพธ์ CSV ที่อ่านได้</h5>
                <pre><?= htmlspecialchars($csv_result) ?></pre>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
