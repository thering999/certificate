<?php
/**
 * Certificate PNG/JPEG Export with Thai Text Support
 * Main export handler with automatic format selection
 * REQUIRES: Freetype support in GD library or mPDF installed
 */

require_once 'db.php';
require_once 'assets/thai_text_renderer.class.php';
session_start();

// ======================================
// DIAGNOSTIC: Check if Freetype is available
// ======================================
$has_freetype = function_exists('imagettftext');
if (!$has_freetype) {
    echo '<div style="background: #ffebee; border: 1px solid #c62828; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial;">';
    echo '<h2 style="color: #c62828;">⚠️ ข้อผิดพลาด: ไม่พบ Freetype Support</h2>';
    echo '<p>ระบบต้องการ GD Library ที่มี Freetype เพื่อแสดงภาษาไทย</p>';
    echo '<h4>วิธีแก้ไข:</h4>';
    echo '<ol>';
    echo '<li>หยุด Docker container: <code>docker-compose down</code></li>';
    echo '<li>สร้าง Image ใหม่ด้วย Freetype: <code>docker-compose -f docker-compose-freetype.yml up -d --build</code></li>';
    echo '<li>รอจนกว่า container พร้อม แล้วลองอีกครั้ง</li>';
    echo '</ol>';
    echo '<p><a href="export.php?diagnose=1">ตรวจสอบ Diagnostics</a></p>';
    echo '</div>';
    exit;
}

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

// Get parameters
$pos_x = isset($_POST['pos_x']) ? intval($_POST['pos_x']) : 200;
$pos_y = isset($_POST['pos_y']) ? intval($_POST['pos_y']) : 300;
$font_size = isset($_POST['font_size']) ? intval($_POST['font_size']) : 32;
$font_family = isset($_POST['font_family']) ? $_POST['font_family'] : 'sans-serif';
$font_color = isset($_POST['font_color']) ? $_POST['font_color'] : '#222';
$font_align = isset($_POST['font_align']) ? $_POST['font_align'] : 'left';
$export_format = isset($_POST['export_format']) ? $_POST['export_format'] : 'png';

// Query database
$result = $conn->query('SELECT id, name FROM certificate_names ORDER BY id DESC');
if (!$result || $result->num_rows == 0) {
    ?>
    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial;">
        <h3 style="color: #856404;">⚠️ ไม่มีรายชื่อในฐานข้อมูล</h3>
        <p>กรุณา<a href="import.php">เพิ่มรายชื่อผู้เข้ารับประกาศนียบัตร</a>ก่อน</p>
    </div>
    <?php
    exit;
}

// หาภาพพื้นหลัง
$bg_img = '';
if (isset($_SESSION['bg_path']) && file_exists($_SESSION['bg_path'])) {
    $bg_img = $_SESSION['bg_path'];
} else {
    foreach(['jpg','png'] as $ext) {
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

$image_files = [];
$rgb = hexToRgb($font_color);

// Initialize Thai text renderer
$thai_renderer = new ThaiTextRenderer();

foreach ($result as $row) {
    $cert_id = $row['id'];
    $name = trim($row['name']);
    // Ensure name is UTF-8
    $name = mb_convert_encoding($name, 'UTF-8', 'UTF-8');
    
    // สร้างภาพใบประกาศ
    if ($bg_img && file_exists($bg_img)) {
        // ใช้ภาพพื้นหลังที่อัปโหลด
        $ext = strtolower(pathinfo($bg_img, PATHINFO_EXTENSION));
        try {
            if ($ext === 'jpg' || $ext === 'jpeg') {
                $image = @imagecreatefromjpeg($bg_img);
            } elseif ($ext === 'png') {
                $image = @imagecreatefrompng($bg_img);
            } elseif ($ext === 'gif') {
                $image = @imagecreatefromgif($bg_img);
            } else {
                $image = null;
            }
            
            if (!$image) {
                // Create blank image as fallback
                $image = imagecreatetruecolor(1000, 700);
                $white = imagecolorallocate($image, 255, 255, 255);
                imagefill($image, 0, 0, $white);
            }
        } catch (Exception $e) {
            // Error loading image, create blank
            $image = imagecreatetruecolor(1000, 700);
            $white = imagecolorallocate($image, 255, 255, 255);
            imagefill($image, 0, 0, $white);
        }
    } else {
        // สร้างภาพเปล่าถ้าไม่มีพื้นหลัง
        $image = imagecreatetruecolor(1000, 700);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
    }
    
    if ($image) {
        // Draw Thai text using Thai text renderer WITH FREETYPE SUPPORT
        try {
            $result_draw = $thai_renderer->drawThaiText(
                $image, 
                $name, 
                $pos_x, 
                $pos_y, 
                $font_size, 
                [$rgb['r'], $rgb['g'], $rgb['b']], 
                $font_align
            );
            
            if (!$result_draw) {
                error_log("Warning: Failed to draw Thai text for: $name");
            }
        } catch (Exception $e) {
            // If drawing fails, still save the image (without text)
            error_log('Thai text rendering error: ' . $e->getMessage());
        }
        
        // Create sanitized filename
        $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        
        // Save as PNG or JPEG
        if ($export_format === 'jpeg') {
            $filename = 'certificate_' . $safe_name . '_' . time() . '.jpg';
            $filepath = sys_get_temp_dir() . '/' . $filename;
            
            if (imagejpeg($image, $filepath, 90)) {
                // Use Thai filename for the archive
                $display_name = 'ใบประกาศ_' . $name . '.jpg';
                $image_files[] = [
                    'path' => $filepath, 
                    'name' => $display_name
                ];
            }
        } else {
            // Default: PNG
            $filename = 'certificate_' . $safe_name . '_' . time() . '.png';
            $filepath = sys_get_temp_dir() . '/' . $filename;
            
            if (imagepng($image, $filepath)) {
                // Use Thai filename for the archive
                $display_name = 'ใบประกาศ_' . $name . '.png';
                $image_files[] = [
                    'path' => $filepath, 
                    'name' => $display_name
                ];
            }
        }
        
        imagedestroy($image);
    }
}

// ตรวจสอบว่ามีไฟล์ที่สร้างได้หรือไม่
if (empty($image_files)) {
    die('ไม่สามารถสร้างใบประกาศได้');
}

// สร้าง ZIP
$zip_path = sys_get_temp_dir() . '/certificates_' . $export_format . '_' . time() . '.zip';
$zip = new ZipArchive();

if ($zip->open($zip_path, ZipArchive::CREATE) === TRUE) {
    foreach ($image_files as $img) {
        if (file_exists($img['path'])) {
            // Use UTF-8 filename in ZIP
            $zip->addFile($img['path'], $img['name']);
        }
    }
    $zip->close();
    
    // ลบไฟล์ชั่วคราว
    foreach ($image_files as $img) {
        @unlink($img['path']);
    }
    
    // ตรวจสอบว่าไฟล์ ZIP สร้างสำเร็จหรือไม่
    if (file_exists($zip_path) && filesize($zip_path) > 0) {
        // ส่งไฟล์ ZIP ให้ดาวน์โหลด
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="certificates_' . $export_format . '.zip"');
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
