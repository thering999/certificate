<?php
/**
 * SECURITY AUDIT: ตรวจสอบไฟล์เสี่ยง
 * สำหรับตัดสินใจลบหรือปกป้องไฟล์ที่เสี่ยงต่อการโดนแฮก
 */

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('<h2>❌ Access Denied - Admin Only</h2>');
}

// ตรวจสอบไฟล์เสี่ยง
$risk_assessment = [
    
    // 🔴 CRITICAL RISK - ต้องลบทันที
    'CRITICAL' => [
        'migration_tool.php' => 'ปรับเปลี่ยนโครงสร้าง DB โดยตรง, ไม่มี admin check',
        'delete_all_names.php' => 'ลบข้อมูลทั้งหมดโดยไม่มีการแจ้งเตือนหรือ backup',
        'set_admin.php' => 'ตั้งค่า user เป็น admin โดยตรง',
        'set_template.php' => 'เปลี่ยนเทมเพลต โดยไม่มี validation เพียงพอ',
        'install_phpmailer.php' => 'ติดตั้ง dependency โดยตรง - อาจให้เข้าถึง file system',
        'install_fonts.php' => 'ติดตั้ง fonts - อาจให้ write permission ต่อ file system',
        'api_doc.php' => 'เปิดเผย API endpoints อาจให้ hint สำหรับการโจมตี',
        'export_integration.backup.php' => 'Backup file - ฟ้องออกเซ่น sensitive data',
        'cache_clear.php' => 'ล้าง cache - อาจใช้ปรับเปลี่ยน state ของระบบ',
        'ocr_upload.php' => 'อัปโหลด file มีความเสี่ยงกับ RCE',
        'phpmailer_autoload.php' => 'Autoload file อาจสำหรับทำให้ PHP load library เสี่ยง',
    ],
    
    // 🟠 HIGH RISK - ควรปกป้องหรือลบ
    'HIGH' => [
        'logo_editor.php' => 'แก้ไข logo โดยตรง - ไม่มี proper permission check',
        'qr_signature.php' => 'สร้าง QR code - อาจให้ข้อมูล sensitive',
        'organization_manage.php' => 'จัดการ organization - ไม่อ่านในการ access control',
        'ai_template_recommend.php' => 'AI template - ไม่ชัดว่า input validation เพียงพอหรือไม่',
        'batch_queue.php' => 'Queue processing - อาจให้ DOS attack',
        'batch_certificates.php' => 'Batch processing - resource intensive',
        'import.php' => 'อัปโหลด/import file - RCE risk',
        'ocr_upload.php' => 'OCR upload - file upload risk',
    ],
    
    // 🟡 MEDIUM RISK - ต้องเพิ่ม permission check
    'MEDIUM' => [
        'analytics.php' => 'ควรจำกัดเฉพาะ admin',
        'audit_logs.php' => 'ควรจำกัดเฉพาะ admin',
        'export_integration.php' => 'Export เยอะๆ อาจ DOS',
        'export_pdf.php' => 'PDF generation - resource intensive',
        'export_png.php' => 'PNG generation - resource intensive',
    ],
];

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>🔒 Security Audit Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 30px 0; }
        .card { border-radius: 1rem; box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: bold; }
        .risk-critical { background: #f8d7da; border-left: 5px solid #dc3545; }
        .risk-high { background: #fff3cd; border-left: 5px solid #ff8c00; }
        .risk-medium { background: #d1ecf1; border-left: 5px solid #17a2b8; }
        .risk-item { padding: 15px; margin-bottom: 10px; border-radius: 0.5rem; }
        .risk-title { font-weight: bold; font-size: 1.1rem; }
    </style>
</head>
<body>
<div class="container-lg mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-shield-alt"></i> 🔒 Security Audit Report
                </div>
                <div class="card-body">
                    
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> ⚠️ ความเสี่ยงที่พบ</h5>
                        <p class="mb-0">ระบบมีไฟล์เสี่ยง <strong><?php echo count($risk_assessment['CRITICAL']) + count($risk_assessment['HIGH']) + count($risk_assessment['MEDIUM']); ?> ไฟล์</strong> ที่อาจให้ผู้บุคคลไม่หวังดีเจาะระบบ</p>
                    </div>
                    
                    <?php foreach ($risk_assessment as $level => $files): ?>
                        <h4 class="mt-4 mb-3">
                            <?php 
                                if ($level === 'CRITICAL') echo '🔴 CRITICAL RISK ('.count($files).' ไฟล์) - ต้องลบทันที';
                                elseif ($level === 'HIGH') echo '🟠 HIGH RISK ('.count($files).' ไฟล์) - ควรลบหรือปกป้อง';
                                else echo '🟡 MEDIUM RISK ('.count($files).' ไฟล์) - เพิ่ม admin check';
                            ?>
                        </h4>
                        
                        <?php foreach ($files as $filename => $description): ?>
                            <div class="risk-item risk-<?php echo strtolower($level); ?>">
                                <div class="risk-title">
                                    <i class="fas fa-file-code"></i> <?php echo $filename; ?>
                                </div>
                                <div class="text-muted mt-2">
                                    <?php echo $description; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    
                    <hr class="my-4">
                    
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> การแนะนำ:</h5>
                        <ol>
                            <li><strong>ลบไฟล์ CRITICAL ทันที:</strong> ลบไฟล์ที่มี CRITICAL risk ด้วยเครื่องมือลบไฟล์เสี่ยง</li>
                            <li><strong>ปกป้องไฟล์ HIGH RISK:</strong> 
                                <ul>
                                    <li>ลบไฟล์ที่ไม่จำเป็น (logo_editor, qr_signature, batch_* เป็นต้น)</li>
                                    <li>เก็บเฉพาะไฟล์ที่จำเป็นและเพิ่ม admin permission check</li>
                                </ul>
                            </li>
                            <li><strong>เพิ่มความมั่นคงให้ MEDIUM RISK:</strong> เพิ่ม admin role check ที่ด้านบนของไฟล์</li>
                        </ol>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="remove_dangerous_files.php" class="btn btn-danger w-100 btn-lg">
                                <i class="fas fa-trash"></i> ลบไฟล์เสี่ยง
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="index.php" class="btn btn-secondary w-100 btn-lg">
                                <i class="fas fa-home"></i> กลับหน้าหลัก
                            </a>
                        </div>
                    </div>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
