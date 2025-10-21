<?php
require_once 'db.php';
require_once 'assets/thai_text_renderer.class.php';
require_once 'assets/image_adapter.class.php';
require_once 'assets/pdf_generator.class.php';
session_start();

// ตรวจสอบว่ามี image extension (GD หรือ Imagick)
if (!extension_loaded('gd') && !extension_loaded('imagick')) {
    die('ไม่สามารถใช้งานได้: ต้องการ GD Library หรือ Imagick Extension');
}

$pos_x = isset($_POST['pos_x']) ? intval($_POST['pos_x']) : 200;
$pos_y = isset($_POST['pos_y']) ? intval($_POST['pos_y']) : 300;
$font_size = isset($_POST['font_size']) ? intval($_POST['font_size']) : 32;
$font_family = isset($_POST['font_family']) ? $_POST['font_family'] : 'sans-serif';
$font_color = isset($_POST['font_color']) ? $_POST['font_color'] : '#222';
$font_align = isset($_POST['font_align']) ? $_POST['font_align'] : 'left';

// Ensure proper UTF-8 encoding
header('Content-Type: application/pdf; charset=utf-8');
mb_internal_encoding('UTF-8');

$result = $conn->query('SELECT name FROM certificate_names');
if (!$result || $result->num_rows == 0) {
    die('ไม่มีรายชื่อในฐานข้อมูล');
}

// หาภาพพื้นหลัง
$bg_img = '';
if (isset($_SESSION['bg_path']) && file_exists($_SESSION['bg_path'])) {
    $bg_img = $_SESSION['bg_path'];
} else {
    foreach(['jpg','png','webp','gif'] as $ext) {
        if (file_exists("assets/certificate_bg.$ext")) {
            $bg_img = "assets/certificate_bg.$ext";
            break;
        }
    }
}

// แปลงสีจาก hex เป็น RGB
function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2))
    ];
}

$png_files = [];
$rgb = hexToRgb($font_color);

// Initialize Thai text renderer
$thai_renderer = new ThaiTextRenderer();

foreach ($result as $row) {
    $name = trim($row['name']);
    // Ensure name is UTF-8
    $name = mb_convert_encoding($name, 'UTF-8', 'UTF-8');
    
    // สร้างภาพใบประกาศ
    if ($bg_img && file_exists($bg_img)) {
        // ใช้ภาพพื้นหลังที่อัปโหลด
        $ext = strtolower(pathinfo($bg_img, PATHINFO_EXTENSION));
        try {
            if ($ext === 'jpg' || $ext === 'jpeg') {
                // JPEG support disabled in GD - convert to PNG first
                // Try to use imagick if available, otherwise create blank
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
                        $image = imagecreatetruecolor(1200, 800);
                        $white = imagecolorallocate($image, 255, 255, 255);
                        imagefill($image, 0, 0, $white);
                    }
                } else {
                    // No imagick, create blank image
                    $image = imagecreatetruecolor(1200, 800);
                    $white = imagecolorallocate($image, 255, 255, 255);
                    imagefill($image, 0, 0, $white);
                }
            } elseif ($ext === 'png') {
                $image = imagecreatefrompng($bg_img);
            } elseif ($ext === 'gif') {
                $image = imagecreatefromgif($bg_img);
            } elseif ($ext === 'webp') {
                if (function_exists('imagecreatefromwebp')) {
                    $image = imagecreatefromwebp($bg_img);
                } else {
                    // WebP not supported, create blank
                    $image = imagecreatetruecolor(1200, 800);
                    $white = imagecolorallocate($image, 255, 255, 255);
                    imagefill($image, 0, 0, $white);
                }
            } else {
                // ถ้าไฟล์ไม่รองรับ สร้างภาพเปล่า
                $image = imagecreatetruecolor(1200, 800);
                $white = imagecolorallocate($image, 255, 255, 255);
                imagefill($image, 0, 0, $white);
            }
        } catch (Exception $e) {
            // Error loading image, create blank
            $image = imagecreatetruecolor(1200, 800);
            $white = imagecolorallocate($image, 255, 255, 255);
            imagefill($image, 0, 0, $white);
        }
    } else {
        // สร้างภาพเปล่าถ้าไม่มีพื้นหลัง
        $image = imagecreatetruecolor(1200, 800);
        $white = imagecolorallocate($image, 255, 255, 255);
        $blue = imagecolorallocate($image, 70, 130, 180);
        imagefill($image, 0, 0, $white);
        
        // วาดขอบ
        imagerectangle($image, 50, 50, 1150, 750, $blue);
        imagerectangle($image, 60, 60, 1140, 740, $blue);
    }
    
    if ($image) {
        // Draw Thai text using Thai text renderer with TTF font support
        $thai_renderer->drawThaiText($image, $name, $pos_x, $pos_y, $font_size, [$rgb['r'], $rgb['g'], $rgb['b']], $font_align);
        
        // Create sanitized filename (for compatibility)
        $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        $filename = 'certificate_' . $safe_name . '_' . time() . '.png';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        if (imagepng($image, $filepath)) {
            $png_files[] = $filepath;
        }
        
        imagedestroy($image);
    }
}

// ตรวจสอบว่ามีไฟล์ที่สร้างได้หรือไม่
if (empty($png_files)) {
    die('ไม่สามารถสร้างใบประกาศได้');
}

// สร้าง PDF
$pdf_path = sys_get_temp_dir() . '/certificates_pdf_' . time() . '.pdf';

// Try to convert PNG(s) to PDF
$pdf_created = false;

if (count($png_files) === 1) {
    // Single certificate - convert PNG to PDF
    $pdf_created = CertificatePdfExporter::convertPngToPdf($png_files[0], $pdf_path);
} else {
    // Multiple certificates - create multi-page PDF
    $pdf_created = CertificatePdfExporter::createMultiPagePdf($png_files, $pdf_path);
}

// Clean up PNG files
foreach ($png_files as $png) {
    @unlink($png);
}

if ($pdf_created && file_exists($pdf_path) && filesize($pdf_path) > 0) {
    // ส่งไฟล์ PDF ให้ดาวน์โหลด
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="certificates.pdf"');
    header('Content-Length: ' . filesize($pdf_path));
    header('Pragma: public');
    header('Cache-Control: must-revalidate');
    
    // อ่านและส่งไฟล์
    readfile($pdf_path);
    
    // ลบไฟล์ PDF ชั่วคราว
    @unlink($pdf_path);
    exit;
} else {
    // PDF creation failed - fallback to PNG ZIP
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="certificates.zip"');
    
    // Clean up PDF attempt
    @unlink($pdf_path);
    
    // Create ZIP with PNG files as fallback
    $zip_path = sys_get_temp_dir() . '/certificates_png_' . time() . '.zip';
    $zip = new ZipArchive();
    
    if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
        $result = $conn->query('SELECT name FROM certificate_names');
        $index = 0;
        
        foreach ($result as $row) {
            if ($index < count($png_files)) {
                $name = trim($row['name']);
                $display_name = 'ใบประกาศ_' . $name . '.png';
                $zip->addFile($png_files[$index], $display_name);
                $index++;
            }
        }
        
        $zip->close();
        
        if (file_exists($zip_path) && filesize($zip_path) > 0) {
            header('Content-Length: ' . filesize($zip_path));
            readfile($zip_path);
            @unlink($zip_path);
            exit;
        }
    }
    
    die('ไม่สามารถสร้างไฟล์ PDF หรือ ZIP ได้');
}
?>
