<?php
require_once 'db.php';
require_once 'assets/thai_text_renderer.class.php';  // Thai text renderer for proper Thai support
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ตรวจสอบว่า image extension พร้อมใช้งานหรือไม่ (GD หรือ Imagick)
if (!extension_loaded('gd') && !extension_loaded('imagick')) {
    $_SESSION['alert'] = 'PHP GD Library หรือ Imagick Extension ไม่พร้อมใช้งาน';
    header('Location: index.php');
    exit;
}

// รับค่าการตั้งค่าจากฟอร์ม
$pos_x = isset($_POST['pos_x']) ? (int)$_POST['pos_x'] : 200;
$pos_y = isset($_POST['pos_y']) ? (int)$_POST['pos_y'] : 300;
$font_size = isset($_POST['font_size']) ? (int)$_POST['font_size'] : 32;
$font_family = $_POST['font_family'] ?? 'sans-serif';
$font_color = $_POST['font_color'] ?? '#222222';
$font_align = $_POST['font_align'] ?? 'left';

// แปลงสี hex เป็น RGB
function hex2rgb($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return array($r, $g, $b);
}

// ดึงรายชื่อจากฐานข้อมูล
$result = $conn->query('SELECT name FROM certificate_names');
if (!$result || $result->num_rows == 0) {
    $_SESSION['alert'] = 'ไม่พบรายชื่อในระบบ กรุณาอัปโหลดไฟล์ Excel ก่อน';
    header('Location: index.php');
    exit;
}

// สร้างโฟลเดอร์ temp หากไม่มี
$temp_dir = 'temp_png';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

// ลบไฟล์เก่าในโฟลเดอร์ temp
array_map('unlink', glob("$temp_dir/*"));

$zip = new ZipArchive();
$zip_filename = 'certificates_' . date('Y-m-d_H-i-s') . '.zip';
$zip_path = $temp_dir . '/' . $zip_filename;

if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
    $_SESSION['alert'] = 'ไม่สามารถสร้างไฟล์ ZIP ได้';
    header('Location: index.php');
    exit;
}

// กำหนดขนาดใบประกาศ (A4 landscape: 1123x794 pixels ที่ 96 DPI)
$cert_width = 1123;
$cert_height = 794;

// แปลงสีข้อความ
list($text_r, $text_g, $text_b) = hex2rgb($font_color);

// สร้างใบประกาศสำหรับแต่ละคน
while ($row = $result->fetch_assoc()) {
    $name = $row['name'];
    $safe_name = preg_replace('/[^a-zA-Z0-9ก-๙\s]/', '', $name);
    
    // สร้าง canvas
    $image = imagecreatetruecolor($cert_width, $cert_height);
    
    // สีพื้นหลัง (ขาว)
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $white);
    
    // โหลดภาพพื้นหลัง (ถ้ามี)
    if (isset($_SESSION['bg_path']) && file_exists($_SESSION['bg_path'])) {
        $bg_path = $_SESSION['bg_path'];
        $bg_ext = strtolower(pathinfo($bg_path, PATHINFO_EXTENSION));
        
        $bg_image = null;
        switch ($bg_ext) {
            case 'jpg':
            case 'jpeg':
                // JPEG support disabled in GD - try Imagick or skip
                if (extension_loaded('imagick')) {
                    try {
                        $imagick = new Imagick($bg_path);
                        $temp_png = tempnam(sys_get_temp_dir(), 'bg_') . '.png';
                        $imagick->setImageFormat('png');
                        $imagick->writeImage($temp_png);
                        $bg_image = @imagecreatefrompng($temp_png);
                        @unlink($temp_png);
                    } catch (Exception $e) {
                        // Imagick failed, skip background
                        $bg_image = null;
                    }
                }
                break;
            case 'png':
                $bg_image = @imagecreatefrompng($bg_path);
                break;
            case 'gif':
                $bg_image = @imagecreatefromgif($bg_path);
                break;
            case 'webp':
                if (function_exists('imagecreatefromwebp')) {
                    $bg_image = @imagecreatefromwebp($bg_path);
                }
                break;
        }
        
        if ($bg_image) {
            // ปรับขนาดภาพพื้นหลังให้พอดีกับ canvas
            $bg_width = imagesx($bg_image);
            $bg_height = imagesy($bg_image);
            imagecopyresampled($image, $bg_image, 0, 0, 0, 0, $cert_width, $cert_height, $bg_width, $bg_height);
            imagedestroy($bg_image);
        }
    }
    
    // สร้างสีข้อความ
    $text_color = imagecolorallocate($image, $text_r, $text_g, $text_b);
    
    // ใช้ ThaiTextRenderer สำหรับแสดงผลภาษาไทยที่ถูกต้อง
    $thai_renderer = new ThaiTextRenderer();
    
    // ตั้งค่าฟอนต์ตาม font_family
    $font_paths = [
        'sans-serif' => 'assets/fonts/THSarabunNew.ttf',
        'serif' => 'assets/fonts/tahoma.ttf',
        'THSarabunNew' => 'assets/fonts/THSarabunNew.ttf',
        'Tahoma' => 'assets/fonts/tahoma.ttf'
    ];
    
    $selected_font = $font_paths[$font_family] ?? $font_paths['sans-serif'];
    
    // หา font file ที่มีอยู่
    $font_file = null;
    $search_paths = [
        $selected_font,
        'assets/fonts/THSarabunNew.ttf',
        'assets/fonts/Sarabun.ttf',
        'assets/fonts/tahoma.ttf',
        'C:/Windows/Fonts/tahoma.ttf',
        'C:/Windows/Fonts/arial.ttf'
    ];
    
    foreach ($search_paths as $path) {
        if (file_exists($path)) {
            $font_file = $path;
            break;
        }
    }
    
    if ($font_file) {
        $thai_renderer->setFontPath($font_file);
        // ใช้ ThaiTextRenderer ที่รองรับภาษาไทยอย่างถูกต้อง
        $thai_renderer->drawThaiText($image, $name, $pos_x, $pos_y, $font_size, [$text_r, $text_g, $text_b], $font_align);
    } else {
        // Fallback: ใช้ฟอนต์ built-in
        $built_in_font = 5;
        
        if ($font_align == 'center') {
            $text_width = imagefontwidth($built_in_font) * strlen($name);
            $actual_x = ($cert_width - $text_width) / 2;
        } elseif ($font_align == 'right') {
            $text_width = imagefontwidth($built_in_font) * strlen($name);
            $actual_x = $cert_width - $text_width - 50;
        } else {
            $actual_x = $pos_x;
        }
        
        imagestring($image, $built_in_font, $actual_x, $pos_y - 20, $name, $text_color);
    }
    
    // บันทึกเป็นไฟล์ PNG
    $filename = 'certificate_' . $safe_name . '.png';
    $filepath = $temp_dir . '/' . $filename;
    
    imagepng($image, $filepath);
    imagedestroy($image);
    
    // เพิ่มไฟล์เข้า ZIP
    $zip->addFile($filepath, $filename);
}

$zip->close();

// ส่งไฟล์ ZIP ให้ผู้ใช้ดาวน์โหลด
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
header('Content-Length: ' . filesize($zip_path));
readfile($zip_path);

// ลบไฟล์ temp
unlink($zip_path);
array_map('unlink', glob("$temp_dir/*"));
rmdir($temp_dir);

exit;
