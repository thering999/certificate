<?php
// Batch Processing Queue System
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// สร้างตาราง batch_jobs ถ้ายังไม่มี
$conn->query("CREATE TABLE IF NOT EXISTS batch_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    filename VARCHAR(255),
    total_records INT DEFAULT 0,
    processed INT DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$error = "";
$success = "";
$job_id = null;

// Handle CSV Upload and Create Job
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $file_size = $_FILES['csv_file']['size'];
    $file_type = $_FILES['csv_file']['type'];
    $filename = $_FILES['csv_file']['name'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $allowed_types = ['text/csv', 'application/vnd.ms-excel', 'application/csv', 'text/plain'];

    if ($file_size > $max_size) {
        $error = "ขนาดไฟล์เกิน 5MB";
    } elseif (!in_array($file_type, $allowed_types)) {
        $error = "ประเภทไฟล์ไม่ถูกต้อง ต้องเป็น .csv เท่านั้น";
    } else {
        $csv = array_map('str_getcsv', file($file));
        $valid = true;
        $total = 0;
        
        foreach ($csv as $row) {
            if (count($row) >= 1 && trim($row[0]) !== "") {
                $total++;
            }
        }
        
        if ($total < 1) {
            $error = "ไฟล์ CSV ไม่มีข้อมูลที่ถูกต้อง";
        } else {
            // สร้าง batch job
            $user_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO batch_jobs (user_id, filename, total_records, status) VALUES (?, ?, ?, 'pending')");
            $stmt->bind_param("isi", $user_id, $filename, $total);
            $stmt->execute();
            $job_id = $conn->insert_id;
            
            // บันทึกข้อมูล CSV ลง session สำหรับ process
            $_SESSION['batch_data_' . $job_id] = $csv;
            
            $success = "สร้าง Batch Job สำเร็จ! รหัสงาน: {$job_id}";
        }
    }
}

// Process Job (สามารถเรียกจาก AJAX)
if (isset($_GET['process']) && isset($_GET['job_id'])) {
    $job_id = intval($_GET['job_id']);
    
    // ตรวจสอบว่า job นี้เป็นของ user คนนี้
    $stmt = $conn->prepare("SELECT * FROM batch_jobs WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $job_id, $_SESSION['user_id']);
    $stmt->execute();
    $job = $stmt->get_result()->fetch_assoc();
    
    if ($job) {
        // อัปเดตสถานะเป็น processing - ใช้ Prepared Statement
        $stmt_update = $conn->prepare("UPDATE batch_jobs SET status = 'processing' WHERE id = ?");
        $stmt_update->bind_param("i", $job_id);
        $stmt_update->execute();
        
        // ดึงข้อมูล CSV จาก session
        $csv = $_SESSION['batch_data_' . $job_id] ?? [];
        $processed = 0;
        $errors = [];
        
        foreach ($csv as $i => $row) {
            $name = trim($row[0] ?? '');
            if ($name === "") continue;
            
            try {
                $stmt = $conn->prepare("INSERT INTO certificate_names (name) VALUES (?)");
                $stmt->bind_param("s", $name);
                $stmt->execute();
                $processed++;
            } catch (Exception $e) {
                $errors[] = "Row " . ($i+1) . ": " . $e->getMessage();
            }
            
            // อัปเดต progress ทุก 10 record - ใช้ Prepared Statement
            if ($processed % 10 === 0) {
                $stmt_progress = $conn->prepare("UPDATE batch_jobs SET processed = ? WHERE id = ?");
                $stmt_progress->bind_param("ii", $processed, $job_id);
                $stmt_progress->execute();
            }
        }
        
        // อัปเดตสถานะเป็น completed
        $status = count($errors) > 0 ? 'failed' : 'completed';
        $error_msg = count($errors) > 0 ? implode("\n", $errors) : null;
        $stmt = $conn->prepare("UPDATE batch_jobs SET processed = ?, status = ?, error_message = ? WHERE id = ?");
        $stmt->bind_param("issi", $processed, $status, $error_msg, $job_id);
        $stmt->execute();
        
        // ลบข้อมูลจาก session
        unset($_SESSION['batch_data_' . $job_id]);
        
        header("Location: batch_queue.php?success=1&job_id={$job_id}");
        exit;
    }
}

// ดึงรายการ jobs ของ user - ใช้ Prepared Statement
$user_id = $_SESSION['user_id'];
$jobsRes = $conn->prepare("SELECT * FROM batch_jobs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$jobsRes->bind_param("i", $user_id);
$jobsRes->execute();
$jobsRes = $jobsRes->get_result();
$jobs = $jobsRes ? $jobsRes->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Batch Processing Queue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .job-card { transition: all 0.3s; }
        .job-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .status-badge { font-size: 0.85rem; }
    </style>
</head>
<body class="bg-light">
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-list-check me-2"></i>Batch Queue
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-layer-group"></i> Batch Processing Queue</h2>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Upload Form -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title"><i class="fas fa-upload"></i> อัปโหลดไฟล์ CSV</h5>
            <form method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <input type="file" name="csv_file" accept=".csv" class="form-control" required>
                        <small class="text-muted">รองรับไฟล์ CSV ขนาดไม่เกิน 5MB</small>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus-circle"></i> สร้าง Batch Job
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Jobs List -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">รายการ Batch Jobs (<?= count($jobs) ?>)</h5>
            <div class="row g-3">
                <?php if (count($jobs) > 0): ?>
                    <?php foreach ($jobs as $job): ?>
                        <div class="col-md-6">
                            <div class="card job-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-subtitle mb-0"><?= htmlspecialchars($job['filename']) ?></h6>
                                        <?php
                                        $statusColors = [
                                            'pending' => 'secondary',
                                            'processing' => 'primary',
                                            'completed' => 'success',
                                            'failed' => 'danger'
                                        ];
                                        $color = $statusColors[$job['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?> status-badge"><?= strtoupper($job['status']) ?></span>
                                    </div>
                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-clock"></i> <?= $job['created_at'] ?>
                                    </p>
                                    <div class="progress mb-2" style="height: 20px;">
                                        <?php $percent = $job['total_records'] > 0 ? ($job['processed'] / $job['total_records']) * 100 : 0; ?>
                                        <div class="progress-bar" role="progressbar" style="width: <?= $percent ?>%">
                                            <?= number_format($percent, 1) ?>%
                                        </div>
                                    </div>
                                    <p class="mb-2">
                                        <strong><?= $job['processed'] ?></strong> / <?= $job['total_records'] ?> records
                                    </p>
                                    <?php if ($job['status'] === 'pending'): ?>
                                        <a href="?process=1&job_id=<?= $job['id'] ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-play"></i> เริ่มประมวลผล
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($job['error_message']): ?>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#errorModal<?= $job['id'] ?>">
                                            <i class="fas fa-exclamation-circle"></i> ดู Error
                                        </button>
                                        
                                        <!-- Error Modal -->
                                        <div class="modal fade" id="errorModal<?= $job['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Error Messages</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <pre><?= htmlspecialchars($job['error_message']) ?></pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-center text-muted">ยังไม่มี Batch Jobs</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
