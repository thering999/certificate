<?php
// dashboard.php - แดชบอร์ดและสถิติ
require_once 'db.php';
session_start();

// Prevent caching - always fresh content
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Refresh user role from database
if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare('SELECT role FROM users WHERE id = ?');
  if ($stmt) {
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $_SESSION['role'] = $row['role'];
    }
    $stmt->close();
  }
}

// สถิติรวม
$totalNames = $conn->query("SELECT COUNT(*) as count FROM certificate_names")->fetch_assoc()['count'];
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// สถิติเดือนนี้ (ถ้ามี created_at column)
// $thisMonth = $conn->query("SELECT COUNT(*) as count FROM certificate_names WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetch_assoc()['count'];

// Top 5 ชื่อล่าสุด
$recentNames = $conn->query("SELECT id, name FROM certificate_names ORDER BY id DESC LIMIT 5");

// Mock data สำหรับกราฟ (เดือนล่าสุด 6 เดือน)
$chartData = [
    ['month' => 'พ.ค. 67', 'count' => 45],
    ['month' => 'มิ.ย. 67', 'count' => 67],
    ['month' => 'ก.ค. 67', 'count' => 89],
    ['month' => 'ส.ค. 67', 'count' => 52],
    ['month' => 'ก.ย. 67', 'count' => 78],
    ['month' => 'ต.ค. 67', 'count' => $totalNames > 100 ? 95 : $totalNames],
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="cache-control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="expires" content="0">
    <title>Dashboard - ระบบใบประกาศ</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f8fafc; }
        .sidebar { min-width: 250px; max-width: 250px; background: #343a40; color: #fff; min-height: 100vh; }
        .sidebar a { color: #fff; text-decoration: none; display: block; padding: 12px 20px; transition: all 0.3s; }
        .sidebar a.active, .sidebar a:hover { background: #007bff; color: #fff; }
        .sidebar .sidebar-heading { padding: 20px; font-size: 1.2rem; font-weight: bold; }
        .content-wrapper { flex: 1; }
        .stat-card { border-radius: 15px; padding: 25px; color: white; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon { font-size: 3rem; opacity: 0.8; }
        .chart-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        @media (max-width: 768px) {
            .sidebar { min-width: 100%; max-width: 100%; min-height: auto; }
            .d-flex { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="d-flex">
    <div class="sidebar py-4">
        <div class="sidebar-heading">
            <i class="fas fa-certificate"></i> ระบบใบประกาศ
        </div>
        <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="admin_manage_users.php"><i class="fas fa-users-cog"></i> จัดการผู้ใช้</a>
        <a href="batch_certificates.php"><i class="fas fa-layer-group"></i> Batch Processing</a>
        <a href="batch_queue.php"><i class="fas fa-tasks"></i> Batch Queue</a>
        <a href="template_manage.php"><i class="fas fa-file-alt"></i> Template Management</a>
        <a href="ocr_upload.php"><i class="fas fa-image"></i> OCR Name Recognition</a>
        <a href="ai_template_recommend.php"><i class="fas fa-robot"></i> AI Template</a>
        <a href="auto_font_color.php"><i class="fas fa-font"></i> Auto Font</a>
        <a href="quality_check.php"><i class="fas fa-check-circle"></i> Quality Check</a>
        <a href="qr_signature.php"><i class="fas fa-qrcode"></i> QR & Signature</a>
        <hr class="text-white">
        <a href="organization_manage.php"><i class="fas fa-building"></i> Organization</a>
        <a href="audit_logs.php"><i class="fas fa-shield-alt"></i> Security & Audit</a>
        <a href="export_integration.php"><i class="fas fa-download"></i> Export</a>
        <a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a>
        <a href="api_doc.php"><i class="fas fa-code"></i> API Documentation</a>
        <hr class="text-white">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>
    <div class="content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="dashboard.php">
                    <i class="fas fa-certificate"></i> ระบบใบประกาศ
                </a>
                <div class="navbar-nav ms-auto">
                    <span class="navbar-text me-3">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <span class="badge bg-danger ms-1">Admin</span>
                        <?php endif; ?>
                    </span>
                    <a href="index.php" class="btn btn-outline-primary btn-sm me-2">หน้าหลัก</a>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">ออกจากระบบ</a>
                </div>
            </div>
        </nav>

    <div class="container pb-5">
        <h2 class="mb-4"><i class="fas fa-chart-line me-2"></i>Dashboard - ภาพรวมระบบ</h2>
        
        <!-- Stat Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?= $totalNames ?></h3>
                            <p class="mb-0">รายชื่อทั้งหมด</p>
                        </div>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?= $totalUsers ?></h3>
                            <p class="mb-0">ผู้ใช้งาน</p>
                        </div>
                        <i class="fas fa-user-circle stat-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">-</h3>
                            <p class="mb-0">เดือนนี้</p>
                        </div>
                        <i class="fas fa-calendar-check stat-icon"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">100%</h3>
                            <p class="mb-0">ออนไลน์</p>
                        </div>
                        <i class="fas fa-check-circle stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row g-4">
            <div class="col-md-8">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>สถิติการสร้างใบประกาศ (6 เดือนล่าสุด)</h5>
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-list me-2"></i>รายชื่อล่าสุด</h5>
                    <ul class="list-group">
                        <?php if ($recentNames->num_rows > 0): ?>
                            <?php while ($row = $recentNames->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= htmlspecialchars($row['name']) ?></span>
                                    <span class="badge bg-primary rounded-pill">#<?= $row['id'] ?></span>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted">ไม่มีข้อมูล</li>
                        <?php endif; ?>
                    </ul>
                    <div class="mt-3 text-center">
                        <a href="search_names.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-search"></i> ดูทั้งหมด
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-4 mt-4">
            <div class="col-12">
                <div class="chart-container">
                    <h5 class="mb-3"><i class="fas fa-bolt me-2"></i>การดำเนินการด่วน</h5>
                    <div class="row g-3">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <div class="col-md-3">
                            <a href="admin_manage_users.php" class="btn btn-danger w-100 py-3" style="border: 2px solid #dc3545;">
                                <i class="fas fa-users-cog d-block mb-2" style="font-size: 2rem;"></i>
                                👑 จัดการผู้ใช้
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-3">
                            <a href="designer.php" class="btn btn-primary w-100 py-3">
                                <i class="fas fa-magic d-block mb-2" style="font-size: 2rem;"></i>
                                ออกแบบใบประกาศ
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="search_names.php" class="btn btn-info w-100 py-3">
                                <i class="fas fa-search d-block mb-2" style="font-size: 2rem;"></i>
                                ค้นหารายชื่อ
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="send_certificates_email.php" class="btn btn-success w-100 py-3">
                                <i class="fas fa-envelope d-block mb-2" style="font-size: 2rem;"></i>
                                ส่ง Email
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="index.php" class="btn btn-warning w-100 py-3">
                                <i class="fas fa-upload d-block mb-2" style="font-size: 2rem;"></i>
                                อัปโหลดข้อมูล
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <script>
        // Monthly Chart
        const ctx = document.getElementById('monthlyChart');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($chartData, 'month')) ?>,
                datasets: [{
                    label: 'จำนวนใบประกาศ',
                    data: <?= json_encode(array_column($chartData, 'count')) ?>,
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true },
                    title: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>
