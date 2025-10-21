$font_align = isset($_GET['font_align']) ? $_GET['font_align'] : 'left';
<?php
require_once 'db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$pos_x = isset($_GET['pos_x']) ? intval($_GET['pos_x']) : 200;
$pos_y = isset($_GET['pos_y']) ? intval($_GET['pos_y']) : 300;
$font_size = isset($_GET['font_size']) ? intval($_GET['font_size']) : 32;
$font_family = isset($_GET['font_family']) ? $_GET['font_family'] : 'sans-serif';
$font_color = isset($_GET['font_color']) ? $_GET['font_color'] : '#222';
$name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'ตัวอย่างชื่อ';
$bg = '';
foreach(['jpg','png'] as $ext) {
    if (file_exists("assets/certificate_bg.$ext")) {
        $bg = "assets/certificate_bg.$ext";
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Preview ใบประกาศ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .preview-cert {
            position: relative;
            width: 800px;
            height: 600px;
            background: #eee;
        }
        .preview-cert img {
            position: absolute;
            left: 0; top: 0;
            width: 100%; height: 100%; object-fit: cover;
        }
        .preview-cert .cert-name {
            position: absolute;
            left: <?= $pos_x ?>px;
            top: <?= $pos_y ?>px;
            font-size: <?= $font_size ?>px;
            color: <?= $font_color ?>;
            font-family: <?= $font_family ?>;
            font-weight: bold;
            width: 100%;
            text-align: <?= $font_align ?>;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <h2>Preview ใบประกาศ</h2>
    <div class="preview-cert mb-3">
        <?php if ($bg): ?>
        <img src="<?= $bg ?>" alt="bg">
        <?php endif; ?>
        <div class="cert-name"><?= $name ?></div>
    </div>
    <form method="get" class="row g-2">
        <div class="col-md-2">
            <label class="form-label">ชื่อ</label>
            <input type="text" name="name" class="form-control" value="<?= $name ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">ตำแหน่ง X</label>
            <input type="number" name="pos_x" class="form-control" value="<?= $pos_x ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">ตำแหน่ง Y</label>
            <input type="number" name="pos_y" class="form-control" value="<?= $pos_y ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">ขนาดฟอนต์</label>
            <input type="number" name="font_size" class="form-control" value="<?= $font_size ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">ฟอนต์</label>
            <select class="form-select" name="font_family">
                <option value="sans-serif" <?= $font_family=='sans-serif'?'selected':'' ?>>Sans-serif</option>
                <option value="serif" <?= $font_family=='serif'?'selected':'' ?>>Serif</option>
                <option value="THSarabunNew" <?= $font_family=='THSarabunNew'?'selected':'' ?>>TH Sarabun New</option>
                <option value="Tahoma" <?= $font_family=='Tahoma'?'selected':'' ?>>Tahoma</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">สีข้อความ</label>
            <input type="color" name="font_color" class="form-control form-control-color" value="<?= $font_color ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">จัดตำแหน่ง</label>
            <select class="form-select" name="font_align">
                <option value="left" <?= $font_align=='left'?'selected':'' ?>>ซ้าย</option>
                <option value="center" <?= $font_align=='center'?'selected':'' ?>>กลาง</option>
                <option value="right" <?= $font_align=='right'?'selected':'' ?>>ขวา</option>
            </select>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary mt-2">ดูตัวอย่าง</button>
            <a href="index.php" class="btn btn-link mt-2">กลับหน้าหลัก</a>
        </div>
    </form>
</div>
</body>
</html>
