<?php
// QR Code Integration & Digital Signature (Phase 2 Enhanced)
session_start();
require_once "db.php";
require_once "assets/validation.class.php";
require_once "assets/error_handler.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ฟังก์ชันสร้าง QR Code โดยใช้ Google Charts API
function generateQRCode($data, $size = 200) {
    return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($data);
}

// ฟังก์ชันสร้าง unique verification code
function generateVerificationCode($certificate_id) {
    return hash('sha256', $certificate_id . time() . $_SERVER['HTTP_HOST']);
}

$certificate_id = $_GET['id'] ?? '';
$action = $_GET['action'] ?? '';
$success = '';
$error = '';
$errors = [];

// สร้าง QR Code สำหรับใบประกาศ (Phase 2 Enhanced)
if ($action === 'generate' && $certificate_id) {
    $validator = new Validation();
    
    // Validate certificate ID
    $validator->integer('id', $certificate_id, 'ไอดีใบประกาศ');
    
    if (!$validator->isValid()) {
        $errors = $validator->getErrors();
        $error = "✗ ข้อมูลไม่ถูกต้อง";
        ErrorHandler::log('QR generation validation failed', 'INFO');
    } else {
        $certificate_id = intval($certificate_id);
        try {
            // ตรวจสอบว่าใบประกาศมีอยู่จริง
            $stmt = $conn->prepare("SELECT * FROM certificate_names WHERE id = ? AND user_id = ?");
            if (!$stmt) throw new Exception($conn->error);
            $stmt->bind_param("ii", $certificate_id, $_SESSION['user_id']);
            $stmt->execute();
            $cert = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($cert) {
                // สร้าง verification code
                $verification_code = generateVerificationCode($certificate_id);
                
                // สร้าง QR Code URL (Google Charts API)
                $verify_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php?code=" . urlencode($verification_code);
                $qr_code_url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($verify_url) . "&choe=UTF-8";
                
                // บันทึก verification code ลงฐานข้อมูล
                $stmt = $conn->prepare("UPDATE certificate_names SET qr_code = ?, verification_code = ?, issued_date = CURDATE(), updated_at = NOW() WHERE id = ?");
                if (!$stmt) throw new Exception($conn->error);
                $stmt->bind_param("ssi", $qr_code_url, $verification_code, $certificate_id);
                
                if ($stmt->execute()) {
                    $success = "✓ สร้าง QR Code สำเร็จ (ID: {$certificate_id})";
                    ErrorHandler::log('QR code generated for certificate: ' . $certificate_id, 'INFO');
                } else {
                    throw new Exception($stmt->error);
                }
                $stmt->close();
            } else {
                $error = "✗ ไม่พบใบประกาศหรือคุณไม่มีสิทธิ์";
                ErrorHandler::logSecurity('UNAUTHORIZED_QR_ATTEMPT', 'Certificate ID: ' . $certificate_id);
            }
        } catch (Exception $e) {
            $error = "✗ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage());
            ErrorHandler::logDB('QR generation error', $e->getMessage());
        }
    }
}

// ดึงข้อมูลใบประกาศทั้งหมด
$certsRes = $conn->query("SELECT id, name, qr_code FROM certificate_names ORDER BY id DESC LIMIT 20");
$certificates = $certsRes ? $certsRes->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Code & Digital Signature</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-qrcode me-2"></i>QR & Signature
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
        <h2><i class="fas fa-qrcode"></i> QR Code & Digital Signature</h2>
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
    
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">รายการใบประกาศ</h5>
                    <p class="text-muted">สร้าง QR Code และ Digital Signature สำหรับใบประกาศ</p>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ชื่อ</th>
                                    <th>QR Code</th>
                                    <th>สถานะ</th>
                                    <th>การกระทำ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($certificates as $cert): ?>
                                    <tr>
                                        <td><?= $cert['id'] ?></td>
                                        <td><?= htmlspecialchars($cert['name']) ?></td>
                                        <td>
                                            <?php if ($cert['qr_code']): ?>
                                                <img src="<?= generateQRCode($cert['qr_code'], 80) ?>" alt="QR Code" width="80">
                                            <?php else: ?>
                                                <span class="text-muted">ยังไม่สร้าง</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($cert['qr_code']): ?>
                                                <span class="badge bg-success">มี QR Code</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">ยังไม่มี</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$cert['qr_code']): ?>
                                                <a href="?action=generate&id=<?= $cert['id'] ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-qrcode"></i> สร้าง QR Code
                                                </a>
                                            <?php else: ?>
                                                <a href="verify.php?code=<?= $cert['qr_code'] ?>" class="btn btn-success btn-sm" target="_blank">
                                                    <i class="fas fa-check-circle"></i> ตรวจสอบ
                                                </a>
                                                <a href="?action=generate&id=<?= $cert['id'] ?>" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-sync"></i> สร้างใหม่
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-qrcode text-primary"></i> QR Code Integration</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success"></i> สร้าง QR Code อัตโนมัติ</li>
                        <li><i class="fas fa-check text-success"></i> ตรวจสอบความถูกต้อง</li>
                        <li><i class="fas fa-check text-success"></i> Scan เพื่อยืนยัน</li>
                        <li><i class="fas fa-check text-success"></i> Anti-counterfeit</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-signature text-success"></i> Digital Signature (Coming Soon)</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-clock text-warning"></i> ลายเซ็นดิจิทัล (e-Signature)</li>
                        <li><i class="fas fa-clock text-warning"></i> เชื่อมต่อ DGA Thailand</li>
                        <li><i class="fas fa-clock text-warning"></i> PKI Infrastructure</li>
                        <li><i class="fas fa-clock text-warning"></i> Legal Compliance</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
