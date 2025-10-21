<?php
// Smart Template Recommendation (AI Mockup)
// รับข้อมูลการใช้งาน/หน่วยงาน/ผู้ใช้ แล้วแนะนำ template ที่เหมาะสม
session_start();
require_once "db.php";

// Mock: ดึงข้อมูล template ทั้งหมด
$res = $conn->query("SELECT * FROM certificate_templates ORDER BY name");
$templates = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Mock: รับข้อมูล usage/organization/user
$usage = $_POST['usage'] ?? '';
$org = $_POST['organization'] ?? '';
$user = $_POST['user'] ?? '';

// Mock AI Logic: แนะนำ template ตามการใช้งาน
$recommend = [];
foreach ($templates as $tpl) {
    if ($usage && stripos($tpl['name'], $usage) !== false) {
        $recommend[] = $tpl;
    }
}
if (!$recommend) $recommend = $templates;

// OpenAI API Template Recommendation (ตัวอย่าง)
// require_once 'vendor/autoload.php';
// $openaiApiKey = 'YOUR_OPENAI_API_KEY';
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $usage = $_POST['usage'] ?? '';
//     $org = $_POST['organization'] ?? '';
//     $user = $_POST['user'] ?? '';
//     $prompt = "แนะนำเทมเพลตใบประกาศที่เหมาะสมสำหรับการใช้งาน: $usage, หน่วยงาน: $org, ผู้ใช้: $user";
//     $ch = curl_init('https://api.openai.com/v1/chat/completions');
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_POST, true);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, [
//         'Content-Type: application/json',
//         'Authorization: Bearer ' . $openaiApiKey
//     ]);
//     $json = json_encode([
//         'model' => 'gpt-3.5-turbo',
//         'messages' => [
//             ['role' => 'user', 'content' => $prompt]
//         ]
//     ]);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
//     $response = curl_exec($ch);
//     curl_close($ch);
//     $data = json_decode($response, true);
//     $ai_recommend = $data['choices'][0]['message']['content'] ?? '';
// }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>AI Template Recommendation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-robot me-2"></i>AI Template
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
    <h2 class="mb-4">AI แนะนำ Template ที่เหมาะสม</h2>
    <form method="post" class="mb-4">
        <div class="row g-2">
            <div class="col-md-4">
                <input type="text" name="usage" class="form-control" placeholder="ประเภทการใช้งาน (ราชการ, โมเดิร์น, หรูหรา)">
            </div>
            <div class="col-md-4">
                <input type="text" name="organization" class="form-control" placeholder="ชื่อหน่วยงาน">
            </div>
            <div class="col-md-4">
                <input type="text" name="user" class="form-control" placeholder="ชื่อผู้ใช้">
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-2">แนะนำ Template</button>
    </form>
    <div class="row">
        <?php foreach ($recommend as $tpl): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if ($tpl['preview']): ?>
                        <img src="<?= $tpl['preview'] ?>" class="card-img-top" alt="Preview">
                    <?php else: ?>
                        <div class="card-img-top bg-secondary text-white d-flex align-items-center justify-content-center" style="height:180px;">ไม่มีรูป Preview</div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($tpl['name']) ?></h5>
                        <p class="card-text">หมวดหมู่: <?= htmlspecialchars($tpl['category']) ?></p>
                        <p class="card-text"><small class="text-muted">สร้างเมื่อ <?= $tpl['created_at'] ?></small></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
