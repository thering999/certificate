<?php
// Batch Certificate Generation
// 1. Upload CSV
// 2. Validate CSV
// 3. Loop create certificates
// 4. Show progress bar

session_start();
require_once "db.php";
require_once "assets/validation.class.php";
require_once "assets/error_handler.php";

$progress = 0;
$error = "";
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $validator = new Validation();
    
    // Validate file using Validation class (Phase 2)
    $validator->file('csv_file', $_FILES['csv_file'], 
                    ['text/csv', 'application/vnd.ms-excel', 'application/csv', 'text/plain'],
                    1048576, // 1MB
                    'ไฟล์ CSV');
    
    if (!$validator->isValid()) {
        $errors = $validator->getErrors();
        $error = "✗ โปรดตรวจสอบไฟล์ที่อัปโหลด";
        ErrorHandler::log('Batch file validation failed', 'INFO');
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        try {
            $csv = array_map('str_getcsv', file($file));
            // Validate CSV format: at least 1 column, no empty rows
            $valid = true;
            $invalid_rows = [];
            foreach ($csv as $row_idx => $row) {
                if (count($row) < 1 || trim($row[0]) === "") {
                    $valid = false;
                    $invalid_rows[] = $row_idx + 1;
                }
            }
            if (!$valid || count($csv) < 1) {
                $error = "✗ ไฟล์ CSV ไม่ถูกต้อง แถวที่ว่าง: " . implode(', ', $invalid_rows);
                ErrorHandler::log('Batch CSV validation failed: ' . implode(', ', $invalid_rows), 'INFO');
            } else {
                $total = count($csv);
                $inserted = 0;
                foreach ($csv as $i => $row) {
                    $name = trim($row[0]);
                    if ($name === "") continue;
                    
                    $name = Validation::sanitizeForDB($name);
                    try {
                        $stmt = $conn->prepare("INSERT INTO certificate_names (user_id, name, created_at) VALUES (?, ?, NOW())");
                        if (!$stmt) throw new Exception($conn->error);
                        $stmt->bind_param("is", $_SESSION['user_id'], $name);
                        if ($stmt->execute()) {
                            $inserted++;
                        }
                        $stmt->close();
                    } catch (Exception $e) {
                        ErrorHandler::logDB('Batch insert error', $e->getMessage());
                    }
                    $progress = intval((($i+1)/$total)*100);
                }
                if ($inserted > 0) {
                    $success = true;
                    $_SESSION['alert'] = "✓ เพิ่มรายชื่อ $inserted รายได้สำเร็จ";
                    ErrorHandler::log('Batch upload success: ' . $inserted . ' records', 'INFO');
                } else {
                    $error = "✗ ไม่สามารถเพิ่มรายชื่อได้";
                }
            }
        } catch (Exception $e) {
            $error = "✗ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage());
            ErrorHandler::logDB('Batch processing error', $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Batch Certificate Generator</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-cogs me-2"></i>Batch Processing
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
    <h2 class="mb-4">สร้างใบประกาศแบบกลุ่ม (Batch)</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">สร้างใบประกาศทั้งหมดเรียบร้อยแล้ว!</div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="mb-4">
        <div class="mb-3">
            <label for="csv_file" class="form-label">อัปโหลดไฟล์ CSV รายชื่อ</label>
            <input type="file" name="csv_file" id="csv_file" accept=".csv" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">สร้างใบประกาศ</button>
    </form>
    <?php if ($progress > 0 && !$success): ?>
        <div class="progress mb-3">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= $progress ?>%">
                <?= $progress ?>%
            </div>
        </div>
    <?php endif; ?>
    <div class="card mt-4">
        <div class="card-body">
            <h5>วิธีใช้งาน</h5>
            <ul>
                <li>เตรียมไฟล์ CSV ที่มีรายชื่อในแต่ละบรรทัด</li>
                <li>อัปโหลดไฟล์ แล้วกด "สร้างใบประกาศ"</li>
                <li>ระบบจะเพิ่มรายชื่อทั้งหมดลงฐานข้อมูล</li>
            </ul>
        </div>
    </div>
</div>
</body>
</html>
