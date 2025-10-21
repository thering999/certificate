<?php
require_once 'db.php';
require_once 'assets/thai_text_renderer.class.php';  // Thai text renderer for proper Thai support
session_start();

// ตรวจสอบว่ามี image extension (GD หรือ Imagick)
if (!extension_loaded('gd') && !extension_loaded('imagick')) {
    die('ไม่สามารถใช้งานได้: ต้องการ GD Library หรือ Imagick Extension');
}

// รับข้อมูล text elements จาก POST
$textElementsJson = $_POST['text_elements'] ?? '[]';
$textElements = json_decode($textElementsJson, true);

if (empty($textElements)) {
    die('ไม่มีข้อมูลข้อความที่จะส่งออก');
}

$result = $conn->query('SELECT name FROM certificate_names');
if (!$result || $result->num_rows == 0) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ไม่มีรายชื่อ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card text-center">
                <div class="card-body py-5">
                    <h1 class="mb-3">📭 ไม่มีรายชื่อในฐานข้อมูล</h1>
                    <p class="text-muted mb-4">ขณะนี้ยังไม่มีรายชื่อใบประกาศในระบบ</p>
                    
                    <div class="alert alert-info">
                        <p>👉 ต้องเพิ่มรายชื่อก่อน จึงจะสามารถส่งออกได้</p>
                    </div>
                    
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                        <a href="dashboard.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home"></i> กลับ Dashboard
                        </a>
                        <a href="import.php" class="btn btn-success btn-lg">
                            <i class="fas fa-upload"></i> เพิ่มรายชื่อ
                        </a>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5>วิธีเพิ่มรายชื่อ:</h5>
                    <ol class="text-start" style="max-width: 300px; margin: auto;">
                        <li>คลิก "เพิ่มรายชื่อ"</li>
                        <li>อัปโหลดไฟล์ CSV หรือ Excel</li>
                        <li>หรือเพิ่มทีละคนใน Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
    <?php
    exit;
}

// หาภาพพื้นหลัง
$bg_img = '';
if (isset($_SESSION['bg_path']) && file_exists($_SESSION['bg_path'])) {
    $bg_img = $_SESSION['bg_path'];
} else {
    foreach(['jpg','png','gif','webp'] as $ext) {
        if (file_exists("assets/certificate_bg.$ext")) {
            $bg_img = "assets/certificate_bg.$ext";
            break;
        }
    }
}

// แปลงสีจาก hex เป็น RGB
function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2) . str_repeat(substr($hex,1,1), 2) . str_repeat(substr($hex,2,1), 2);
    }
    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2))
    ];
}

// Initialize Thai text renderer
$thai_renderer = new ThaiTextRenderer();

// ฟังก์ชันการวาดข้อความแบบ advanced ที่รองรับภาษาไทย
function drawTextAdvanced($image, $element, $text, $color, $imageWidth, $thai_renderer = null) {
    $fontSize = $element['fontSize'];
    $x = $element['x'];
    $y = $element['y'];
    $align = $element['align'];
    
    // แยกข้อความเป็นหลายบรรทัด
    $lines = explode("\n", $text);
    $lineHeight = (int)($fontSize + 8);
    
    foreach ($lines as $index => $line) {
        $currentY = (int)($y + ($index * $lineHeight));
        
        // คำนวณตำแหน่ง X ตาม alignment
        $textX = (int)$x;
        if ($align === 'center') {
            $textWidth = mb_strlen($line, 'UTF-8') * ($fontSize * 0.7); // ประมาณการความกว้าง
            $textX = (int)(($imageWidth - $textWidth) / 2);
        } elseif ($align === 'right') {
            $textWidth = mb_strlen($line, 'UTF-8') * ($fontSize * 0.7);
            $textX = (int)($imageWidth - $textWidth - 50);
        }
        
        // เพิ่มเอฟเฟค shadow ถ้ามี
        $shadow = $element['textShadow'] ?? 'none';
        if ($shadow !== 'none') {
            $shadowColor = imagecolorallocate($image, 100, 100, 100); // เงาสีเทา
            $shadowOffset = 2;
            
            switch($shadow) {
                case 'light': $shadowOffset = 1; break;
                case 'medium': $shadowOffset = 2; break;
                case 'strong': $shadowOffset = 3; break;
                case 'glow': 
                    // วาดเงาหลายชั้นสำหรับเอฟเฟค glow
                    if ($thai_renderer) {
                        $thai_renderer->drawThaiText($image, $line, (int)($textX + 2), (int)($currentY + 2), $fontSize, [100, 100, 100], 'left');
                    } else {
                        for ($i = 1; $i <= 3; $i++) {
                            imagestring($image, 5, (int)($textX + $i), (int)($currentY + $i), $line, $shadowColor);
                            imagestring($image, 5, (int)($textX - $i), (int)($currentY - $i), $line, $shadowColor);
                        }
                    }
                    break;
                default:
                    if ($thai_renderer) {
                        $thai_renderer->drawThaiText($image, $line, (int)($textX + $shadowOffset), (int)($currentY + $shadowOffset), $fontSize, [100, 100, 100], 'left');
                    } else {
                        imagestring($image, 5, (int)($textX + $shadowOffset), (int)($currentY + $shadowOffset), $line, $shadowColor);
                    }
            }
        }
        
        // วาดข้อความหลัก (ใช้ ThaiTextRenderer สำหรับเนื้อไทย)
        if ($thai_renderer) {
            // ใช้ Thai Text Renderer ที่รองรับ TTF font
            $color_rgb = [($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF];
            $thai_renderer->drawThaiText($image, $line, (int)$textX, (int)$currentY, $fontSize, $color_rgb, 'left');
        } else {
            // Fallback: ใช้ built-in font
            $gdFontSize = min(5, max(1, intval($fontSize / 10)));
            imagestring($image, $gdFontSize, (int)$textX, (int)$currentY, $line, $color);
        }
        
        // เพิ่มเอฟเฟค text decoration
        $decoration = $element['textDecoration'] ?? 'none';
        if ($decoration !== 'none') {
            $textWidth = mb_strlen($line, 'UTF-8') * ($fontSize * 0.7);
            switch($decoration) {
                case 'underline':
                    imageline($image, (int)$textX, (int)($currentY + $fontSize + 2), (int)($textX + $textWidth), (int)($currentY + $fontSize + 2), $color);
                    break;
                case 'overline':
                    imageline($image, (int)$textX, (int)($currentY - 2), (int)($textX + $textWidth), (int)($currentY - 2), $color);
                    break;
                case 'line-through':
                    imageline($image, (int)$textX, (int)($currentY + $fontSize/2), (int)($textX + $textWidth), (int)($currentY + $fontSize/2), $color);
                    break;
            }
        }
    }
}

$image_files = [];

foreach ($result as $row) {
    $name = trim($row['name']);
    
    // สร้างภาพใบประกาศ
    if ($bg_img && file_exists($bg_img)) {
        // ใช้ภาพพื้นหลังที่อัปโหลด
        $ext = strtolower(pathinfo($bg_img, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                // JPEG support disabled in GD - try Imagick or create blank
                if (extension_loaded('imagick')) {
                    try {
                        $imagick = new Imagick($bg_img);
                        $temp_png = tempnam(sys_get_temp_dir(), 'cert_') . '.png';
                        $imagick->setImageFormat('png');
                        $imagick->writeImage($temp_png);
                        $image = imagecreatefrompng($temp_png);
                        @unlink($temp_png);
                    } catch (Exception $e) {
                        // Imagick failed, create blank
                        $image = imagecreate(1200, 800);
                        $white = imagecolorallocate($image, 255, 255, 255);
                        imagefill($image, 0, 0, $white);
                    }
                } else {
                    // No imagick, create blank
                    $image = imagecreate(1200, 800);
                    $white = imagecolorallocate($image, 255, 255, 255);
                    imagefill($image, 0, 0, $white);
                }
                break;
            case 'png':
                $image = imagecreatefrompng($bg_img);
                break;
            case 'gif':
                $image = imagecreatefromgif($bg_img);
                break;
            case 'webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = imagecreatefromwebp($bg_img);
                } else {
                    // WebP not supported, create blank
                    $image = imagecreate(1200, 800);
                    $white = imagecolorallocate($image, 255, 255, 255);
                    imagefill($image, 0, 0, $white);
                }
                break;
            default:
                // สร้างภาพเปล่าถ้าไฟล์ไม่รองรับ
                $image = imagecreate(1200, 800);
                $white = imagecolorallocate($image, 255, 255, 255);
                imagefill($image, 0, 0, $white);
        }
    } else {
        // สร้างภาพเปล่าถ้าไม่มีพื้นหลัง
        $image = imagecreate(1200, 800);
        $white = imagecolorallocate($image, 255, 255, 255);
        $blue = imagecolorallocate($image, 70, 130, 180);
        imagefill($image, 0, 0, $white);
        
        // วาดขอบ
        imagerectangle($image, 50, 50, 1150, 750, $blue);
        imagerectangle($image, 60, 60, 1140, 740, $blue);
    }
    
    if ($image) {
        $image_width = imagesx($image);
        $image_height = imagesy($image);
        
        // วาดข้อความทั้งหมดตาม textElements
        foreach ($textElements as $element) {
            $rgb = hexToRgb($element['color']);
            $text_color = imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);
            
            // แทนที่ {name} ด้วยชื่อจริง
            $displayText = str_replace('{name}', $name, $element['content']);
            
            // วาดข้อความ (ใช้ Thai Text Renderer สำหรับเนื้อไทย)
            drawTextAdvanced($image, $element, $displayText, $text_color, $image_width, $thai_renderer);
        }
        
        // บันทึกไฟล์ PNG
        $safe_name = preg_replace('/[^a-zA-Z0-9ก-๙\s_-]/', '_', $name);
        $filename = 'certificate_' . $safe_name . '_' . date('YmdHis') . '.png';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        if (imagepng($image, $filepath)) {
            $image_files[] = [
                'path' => $filepath, 
                'name' => 'ใบประกาศ_' . $name . '.png'
            ];
        }
        
        imagedestroy($image);
    }
}

// ตรวจสอบว่ามีไฟล์ที่สร้างได้หรือไม่
if (empty($image_files)) {
    die('ไม่สามารถสร้างใบประกาศได้');
}

// สร้าง ZIP
$zip_path = sys_get_temp_dir() . '/certificates_dragdrop_' . time() . '.zip';
$zip = new ZipArchive();

if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
    foreach ($image_files as $img) {
        if (file_exists($img['path'])) {
            $zip->addFile($img['path'], $img['name']);
        }
    }
    $zip->close();
    
    // ลบไฟล์ PNG ชั่วคราว
    foreach ($image_files as $img) {
        @unlink($img['path']);
    }
    
    // ตรวจสอบว่าไฟล์ ZIP สร้างสำเร็จหรือไม่
    if (file_exists($zip_path) && filesize($zip_path) > 0) {
        // ส่งไฟล์ ZIP ให้ดาวน์โหลด
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="certificates_dragdrop.zip"');
        header('Content-Length: ' . filesize($zip_path));
        header('Pragma: public');
        header('Cache-Control: must-revalidate');
        
        // อ่านและส่งไฟล์
        readfile($zip_path);
        
        // ลบไฟล์ ZIP ชั่วคราว
        @unlink($zip_path);
        exit;
    } else {
        die('ไม่สามารถสร้างไฟล์ ZIP ได้');
    }
} else {
    // ลบไฟล์ PNG ชั่วคราวหากสร้าง ZIP ไม่สำเร็จ
    foreach ($image_files as $img) {
        @unlink($img['path']);
    }
    die('ไม่สามารถเปิดไฟล์ ZIP สำหรับเขียนได้');
}
?>