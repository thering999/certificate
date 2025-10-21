<?php
// Auto Font Selection (AI Mockup)
// เลือกฟอนต์และสีอัตโนมัติให้เหมาะกับ Template
session_start();
require_once "db.php";

// Mock: ดึงข้อมูล template ทั้งหมด
$res = $conn->query("SELECT * FROM certificate_templates ORDER BY name");
$templates = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Mock: รับ template ที่เลือก
$template_id = $_POST['template_id'] ?? '';
$selected_tpl = null;
foreach ($templates as $tpl) {
    if ($tpl['id'] == $template_id) {
        $selected_tpl = $tpl;
        break;
    }
}

// Mock AI Logic: เลือกฟอนต์และสี
$font = 'TH Sarabun New';
$color = '#2c3e50';
if ($selected_tpl) {
    if (stripos($selected_tpl['name'], 'โมเดิร์น') !== false) {
        $font = 'Kanit';
        $color = '#007bff';
    } elseif (stripos($selected_tpl['name'], 'หรูหรา') !== false) {
        $font = 'Prompt';
        $color = '#bfa76f';
    }
}

// OpenAI API Font Selection (ตัวอย่าง)
// require_once 'vendor/autoload.php';
// $openaiApiKey = 'YOUR_OPENAI_API_KEY';
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['template_id'])) {
//     $template_id = $_POST['template_id'];
//     $prompt = "แนะนำฟอนต์และสีที่เหมาะสมสำหรับเทมเพลตใบประกาศ ID: $template_id";
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
//     $ai_font_color = $data['choices'][0]['message']['content'] ?? '';
// }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>AI Auto Font & Color</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .preview-text { font-size: 2rem; margin-top: 2rem; }
    </style>
</head>
<body class="bg-light">
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-font me-2"></i>Auto Font & Color
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
    <h2 class="mb-4">AI เลือกฟอนต์และสีอัตโนมัติ</h2>
    <form method="post" class="mb-4">
        <div class="mb-3">
            <label for="template_id" class="form-label">เลือก Template</label>
            <select name="template_id" id="template_id" class="form-select" required>
                <option value="">-- เลือก Template --</option>
                <?php foreach ($templates as $tpl): ?>
                    <option value="<?= $tpl['id'] ?>" <?= ($tpl['id'] == $template_id) ? 'selected' : '' ?>><?= htmlspecialchars($tpl['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">เลือกฟอนต์และสี</button>
    </form>
    <?php if ($selected_tpl): ?>
        <div class="card mt-4">
            <div class="card-body">
                <h5>ฟอนต์ที่แนะนำ: <?= $font ?></h5>
                <h5>สีที่แนะนำ: <span style="color:<?= $color ?>; font-weight:bold;"><?= $color ?></span></h5>
                <div class="preview-text" style="font-family:'<?= $font ?>'; color:<?= $color ?>;">ตัวอย่างข้อความใบประกาศ</div>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
