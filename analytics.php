<?php
header('Content-Type: text/html; charset=utf-8');
// Advanced Analytics Dashboard
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Set UTF-8 for all queries
$conn->query("SET NAMES utf8mb4");

// สถิติรวม
$totalCerts = $conn->query("SELECT COUNT(*) as count FROM certificate_names")->fetch_assoc()['count'];
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$totalOrgs = $conn->query("SELECT COUNT(*) as count FROM organizations")->fetch_assoc()['count'] ?? 0;
$totalTemplates = $conn->query("SELECT COUNT(*) as count FROM certificate_templates")->fetch_assoc()['count'] ?? 0;

// สถิติเดือนนี้
$thisMonth = $conn->query("SELECT COUNT(*) as count FROM certificate_names WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetch_assoc()['count'] ?? 0;

// สถิติ 7 วันล่าสุด
$last7Days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $count = $conn->query("SELECT COUNT(*) as count FROM certificate_names WHERE DATE(created_at) = '{$date}'")->fetch_assoc()['count'] ?? 0;
    $last7Days[] = [
        'date' => date('d/m', strtotime($date)),
        'count' => $count
    ];
}

// สถิติ 6 เดือนล่าสุด
$last6Months = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-{$i} months"));
    $count = $conn->query("SELECT COUNT(*) as count FROM certificate_names WHERE DATE_FORMAT(created_at, '%Y-%m') = '{$date}'")->fetch_assoc()['count'] ?? 0;
    $last6Months[] = [
        'month' => date('M Y', strtotime($date . '-01')),
        'count' => $count
    ];
}

// Top 10 ชื่อล่าสุด
$recentCerts = $conn->query("SELECT id, name, created_at FROM certificate_names ORDER BY created_at DESC LIMIT 10");
$recentCertsData = $recentCerts ? $recentCerts->fetch_all(MYSQLI_ASSOC) : [];

// สถิติตาม Organization (ถ้ามี)
$orgStats = $conn->query("SELECT o.name, COUNT(c.id) as cert_count 
    FROM organizations o 
    LEFT JOIN certificate_names c ON o.id = c.organization_id 
    GROUP BY o.id 
    ORDER BY cert_count DESC 
    LIMIT 5");
$orgStatsData = $orgStats ? $orgStats->fetch_all(MYSQLI_ASSOC) : [];

// Growth Rate (เทียบเดือนนี้กับเดือนที่แล้ว)
$lastMonth = $conn->query("SELECT COUNT(*) as count FROM certificate_names WHERE MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))")->fetch_assoc()['count'] ?? 0;
$growthRate = $lastMonth > 0 ? (($thisMonth - $lastMonth) / $lastMonth) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Advanced Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            transition: all 0.3s;
            border-radius: 15px;
            padding: 25px;
            color: white;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-line"></i> Advanced Analytics</h2>
        <div class="btn-group" role="group">
            <a href="index.php" class="btn btn-outline-primary"><i class="fas fa-home"></i> หน้าหลัก</a>
            <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-th-large"></i> Dashboard</a>
        </div>
    </div>
    
    <!-- Stat Cards Row 1 -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= number_format($totalCerts) ?></h3>
                        <p class="mb-0">ใบประกาศทั้งหมด</p>
                    </div>
                    <i class="fas fa-certificate" style="font-size: 3rem; opacity: 0.8;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= number_format($thisMonth) ?></h3>
                        <p class="mb-0">เดือนนี้</p>
                        <small><?= $growthRate > 0 ? '+' : '' ?><?= number_format($growthRate, 1) ?>% จากเดือนที่แล้ว</small>
                    </div>
                    <i class="fas fa-calendar-alt" style="font-size: 3rem; opacity: 0.8;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= number_format($totalOrgs) ?></h3>
                        <p class="mb-0">หน่วยงาน</p>
                    </div>
                    <i class="fas fa-building" style="font-size: 3rem; opacity: 0.8;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0"><?= number_format($totalUsers) ?></h3>
                        <p class="mb-0">ผู้ใช้งาน</p>
                    </div>
                    <i class="fas fa-users" style="font-size: 3rem; opacity: 0.8;"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="chart-container">
                <h5><i class="fas fa-chart-area text-primary"></i> สถิติการสร้างใบประกาศ (6 เดือนล่าสุด)</h5>
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-container">
                <h5><i class="fas fa-chart-pie text-success"></i> สัดส่วนตามหน่วยงาน</h5>
                <canvas id="orgChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-4">
        <div class="col-md-12">
            <div class="chart-container">
                <h5><i class="fas fa-chart-bar text-info"></i> สถิติ 7 วันล่าสุด</h5>
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-clock text-warning"></i> ใบประกาศล่าสุด</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ชื่อ</th>
                                    <th>วันที่สร้าง</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentCertsData as $cert): ?>
                                    <tr>
                                        <td><?= $cert['id'] ?></td>
                                        <td><?= htmlspecialchars($cert['name']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($cert['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-trophy text-warning"></i> Top 5 หน่วยงาน</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>หน่วยงาน</th>
                                    <th>จำนวนใบประกาศ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orgStatsData as $org): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($org['name']) ?></td>
                                        <td><span class="badge bg-primary"><?= $org['cert_count'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Monthly Chart (6 months)
const monthlyCtx = document.getElementById('monthlyChart');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($last6Months, 'month')) ?>,
        datasets: [{
            label: 'จำนวนใบประกาศ',
            data: <?= json_encode(array_column($last6Months, 'count')) ?>,
            borderColor: 'rgb(102, 126, 234)',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Weekly Chart (7 days)
const weeklyCtx = document.getElementById('weeklyChart');
new Chart(weeklyCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($last7Days, 'date')) ?>,
        datasets: [{
            label: 'จำนวนใบประกาศ',
            data: <?= json_encode(array_column($last7Days, 'count')) ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.6)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Organization Pie Chart
const orgCtx = document.getElementById('orgChart');
new Chart(orgCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($orgStatsData, 'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($orgStatsData, 'cert_count')) ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.6)',
                'rgba(54, 162, 235, 0.6)',
                'rgba(255, 206, 86, 0.6)',
                'rgba(75, 192, 192, 0.6)',
                'rgba(153, 102, 255, 0.6)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>
</body>
</html>
