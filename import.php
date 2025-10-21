<?php
/**
 * Import Certificate Names
 * สำหรับนำเข้ารายชื่อจากไฟล์ CSV/Excel
 */

session_start();
require_once 'db.php';
require_once 'assets/validation.class.php';
require_once 'assets/error_handler.php';

header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = "";
$success = "";
$preview_data = [];

// Handle file import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $validator = new Validation();
    
    // Validate file
    $validator->file('import_file', $_FILES['import_file'] ?? [], 
                    ['text/csv', 'application/vnd.ms-excel', 'application/csv', 
                     'text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                    5242880, // 5MB
                    'ไฟล์ CSV/Excel');
    
    if (!$validator->isValid()) {
        $errors = $validator->getErrors();
        $error = "✗ โปรดตรวจสอบไฟล์ที่อัปโหลด: " . implode(', ', $errors);
        ErrorHandler::log('Import file validation failed: ' . implode(', ', $errors), 'INFO');
    } else {
        $file = $_FILES['import_file']['tmp_name'];
        $filename = $_FILES['import_file']['name'];
        
        try {
            // Detect file type
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $data = [];
            
            if ($ext === 'csv' || $ext === 'txt') {
                // CSV import
                $csv = array_map('str_getcsv', file($file));
                foreach ($csv as $row) {
                    if (!empty($row) && !empty(trim($row[0]))) {
                        $data[] = trim($row[0]);
                    }
                }
            } elseif ($ext === 'xlsx') {
                // Excel import - using simple method
                // For real XLSX parsing, consider using PhpSpreadsheet library
                $error = "ไฟล์ XLSX ต้องติดตั้ง PhpSpreadsheet library";
                ErrorHandler::log('XLSX import attempted without library', 'INFO');
            } else {
                $error = "ประเภทไฟล์ไม่รองรับ";
            }
            
            if (empty($error) && !empty($data)) {
                // Show preview
                $preview_data = array_slice($data, 0, 10);
                $total_records = count($data);
                $success = "✓ อ่านไฟล์เรียบร้อย พบ $total_records รายชื่อ (แสดงตัวอย่าง 10 อันดับแรก)";
                
                // Store data in session for confirmation
                $_SESSION['import_data'] = $data;
                $_SESSION['import_total'] = $total_records;
            }
        } catch (Exception $e) {
            $error = "✗ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage());
            ErrorHandler::logDB('Import processing error', $e->getMessage());
        }
    }
}

// Handle confirmation (actually import)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import']) && isset($_SESSION['import_data'])) {
    $data = $_SESSION['import_data'];
    $inserted = 0;
    $failed = 0;
    $skipped = 0;
    
    foreach ($data as $name) {
        $name = trim($name);
        if (empty($name)) {
            $skipped++;
            continue;
        }
        
        // Sanitize
        $name = Validation::sanitizeForDB($name);
        
        try {
            $stmt = $conn->prepare("INSERT INTO certificate_names (user_id, name, created_at) VALUES (?, ?, NOW())");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            
            $stmt->bind_param("is", $_SESSION['user_id'], $name);
            if ($stmt->execute()) {
                $inserted++;
            } else {
                $failed++;
            }
            $stmt->close();
        } catch (Exception $e) {
            $failed++;
            ErrorHandler::logDB('Import insert error', $e->getMessage());
        }
    }
    
    $_SESSION['alert'] = "✓ นำเข้าเสร็จสิ้น: $inserted รายชื่อ (ล้มเหลว: $failed, ข้ามไป: $skipped)";
    unset($_SESSION['import_data']);
    unset($_SESSION['import_total']);
    
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>นำเข้ารายชื่อ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-file-import"></i> นำเข้ารายชื่อ</h2>
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> กลับ</a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($preview_data) && !isset($_SESSION['import_data'])): ?>
                <!-- Upload Form -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">📤 เลือกไฟล์เพื่อนำเข้า</h5>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="importFile" class="form-label">เลือกไฟล์</label>
                                <input type="file" class="form-control" id="importFile" name="import_file" 
                                       accept=".csv,.txt,.xlsx" required>
                                <small class="text-muted">
                                    รูปแบบที่รองรับ: CSV, TXT, XLSX (สูงสุด 5MB)
                                </small>
                            </div>
                            
                            <div class="alert alert-info">
                                <strong>📋 รูปแบบไฟล์:</strong>
                                <ul class="mb-0">
                                    <li><strong>CSV/TXT:</strong> ชื่อละหนึ่งบรรทัด (UTF-8 encoding)</li>
                                    <li><strong>XLSX:</strong> คอลัมน์แรกเป็นชื่อ</li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-upload"></i> อ่านไฟล์
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="alert alert-secondary">
                            <strong>💡 ทางเลือก:</strong>
                            <a href="add_sample_data.php" class="alert-link">เพิ่มข้อมูลตัวอย่าง</a> (สำหรับทดสอบ)
                        </div>
                    </div>
                </div>
            <?php elseif (isset($_SESSION['import_data']) && !empty($preview_data)): ?>
                <!-- Preview & Confirmation -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            ✓ ตรวจสอบข้อมูลก่อนนำเข้า
                        </h5>
                        
                        <div class="alert alert-info">
                            จำนวนรายชื่อทั้งหมด: <strong><?= $_SESSION['import_total'] ?></strong> คน
                        </div>
                        
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">ลำดับ</th>
                                        <th>ชื่อ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($preview_data as $idx => $name): ?>
                                        <tr>
                                            <td><?= $idx + 1 ?></td>
                                            <td><?= htmlspecialchars($name) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($_SESSION['import_total'] > count($preview_data)): ?>
                            <div class="alert alert-secondary">
                                แสดงเพียง <?= count($preview_data) ?> รายการแรก...
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="d-grid gap-2 d-sm-flex">
                            <button type="submit" name="confirm_import" value="1" class="btn btn-success btn-lg" 
                                    onclick="return confirm('ยืนยันการนำเข้า <?= $_SESSION['import_total'] ?> รายชื่อ?')">
                                <i class="fas fa-check-circle"></i> ยืนยันการนำเข้า
                            </button>
                            <a href="import.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times-circle"></i> ยกเลิก
                            </a>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
