<?php
// Export & Integration System
session_start();
require_once "db.php";
require_once "assets/data_exporter.class.php";

// Set encoding
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = "";
$success = "";

// ============================================
// HANDLE EXPORTS - MUST BE BEFORE HTML OUTPUT
// ============================================

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        // Get data from database
        $result = $conn->query("SELECT id, name, qr_code, created_at FROM certificate_names ORDER BY id DESC");
        if (!$result) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            // Ensure Thai text is properly encoded
            $row['name'] = mb_convert_encoding($row['name'], 'UTF-8', 'UTF-8');
            $data[] = [
                $row['id'],
                $row['name'],
                $row['qr_code'] ?? '',
                $row['created_at'] ?? ''
            ];
        }
        
        // Use DataExporter
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="certificates_' . date('Y-m-d_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility with Thai text
        fwrite($output, "\xEF\xBB\xBF");
        
        // Write headers
        fputcsv($output, ['ID', 'ชื่อ', 'QR Code', 'วันที่สร้าง']);
        
        // Write data with proper encoding
        foreach ($data as $row) {
            fputcsv($output, $row, ',', '"', '\\');
        }
        
        fclose($output);
        exit;
    } catch (Exception $e) {
        $error = "❌ ไม่สามารถส่งออก CSV ได้: " . $e->getMessage();
    }
}

// Handle Export Excel (XLSX format with PhpSpreadsheet)
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    try {
        // Get data from database
        $result = $conn->query("SELECT id, name, qr_code, created_at FROM certificate_names ORDER BY id DESC");
        if (!$result) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            // Ensure Thai text is properly encoded
            $row['name'] = mb_convert_encoding($row['name'], 'UTF-8', 'UTF-8');
            $data[] = [
                $row['id'],
                $row['name'],
                $row['qr_code'] ?? '',
                $row['created_at'] ?? ''
            ];
        }
        
        // Check if PhpSpreadsheet is available
        if (class_exists('\\PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            // Use modern XLSX format
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Certificates');
            
            // Write headers
            $headers = ['ลำดับที่', 'ชื่อ', 'รหัส QR', 'วันที่สร้าง'];
            foreach ($headers as $col => $header) {
                $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
                // Style header
                $sheet->getStyleByColumnAndRow($col + 1, 1)->getFont()->setBold(true);
                $sheet->getStyleByColumnAndRow($col + 1, 1)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF366092');
                $sheet->getStyleByColumnAndRow($col + 1, 1)->getFont()->getColor()->setARGB('FFFFFFFF');
            }
            
            // Write data
            $row = 2;
            foreach ($data as $rowData) {
                foreach ($rowData as $col => $value) {
                    $sheet->setCellValueByColumnAndRow($col + 1, $row, $value);
                }
                $row++;
            }
            
            // Auto-size columns
            foreach (range(1, count($headers)) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }
            
            // Output
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="certificates_' . date('Y-m-d_His') . '.xlsx"');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } else {
            // Fallback to XML format (Excel compatibility)
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="certificates_' . date('Y-m-d_His') . '.xls"');
            
            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" 
                     xmlns:o="urn:schemas-microsoft-com:office:office" 
                     xmlns:x="urn:schemas-microsoft-com:office:excel" 
                     xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" 
                     xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
            echo '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">' . "\n";
            echo '  <Title>ใบประกาศ</Title>' . "\n";
            echo '  <Created>' . date('Y-m-dTH:i:sZ') . '</Created>' . "\n";
            echo '</DocumentProperties>' . "\n";
            echo '<Styles>' . "\n";
            echo '  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Bottom"/></Style>' . "\n";
            echo '  <Style ss:ID="Header" ss:Name="Header"><Font ss:Bold="1" ss:Size="14" ss:Color="FFFFFF"/><Interior ss:Color="366092" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>' . "\n";
            echo '</Styles>' . "\n";
            echo '<Worksheet ss:Name="Certificates">' . "\n";
            echo '  <Table>' . "\n";
            
            // Header row
            echo '    <Row ss:StyleID="Header" ss:Height="25">' . "\n";
            echo '      <Cell><Data ss:Type="String">ลำดับที่</Data></Cell>' . "\n";
            echo '      <Cell><Data ss:Type="String">ชื่อ</Data></Cell>' . "\n";
            echo '      <Cell><Data ss:Type="String">รหัส QR</Data></Cell>' . "\n";
            echo '      <Cell><Data ss:Type="String">วันที่สร้าง</Data></Cell>' . "\n";
            echo '    </Row>' . "\n";
            
            // Data rows
            foreach ($data as $row) {
                echo '    <Row>' . "\n";
                echo '      <Cell><Data ss:Type="Number">' . htmlspecialchars($row[0], ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
                echo '      <Cell><Data ss:Type="String">' . htmlspecialchars($row[1], ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
                echo '      <Cell><Data ss:Type="String">' . htmlspecialchars($row[2], ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
                echo '      <Cell><Data ss:Type="String">' . htmlspecialchars($row[3], ENT_XML1, 'UTF-8') . '</Data></Cell>' . "\n";
                echo '    </Row>' . "\n";
            }
            
            echo '  </Table>' . "\n";
            echo '</Worksheet>' . "\n";
            echo '</Workbook>' . "\n";
            
            exit;
        }
    } catch (Exception $e) {
        $error = "❌ ไม่สามารถส่งออก Excel ได้: " . $e->getMessage();
    }
}

// Handle Export JSON
if (isset($_GET['export']) && $_GET['export'] === 'json') {
    try {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="certificates_' . date('Y-m-d_His') . '.json"');
        
        $result = $conn->query("SELECT id, name, qr_code, created_at FROM certificate_names ORDER BY id DESC");
        if (!$result) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            // Ensure Thai text is properly encoded
            $row['name'] = mb_convert_encoding($row['name'], 'UTF-8', 'UTF-8');
            $data[] = $row;
        }
        
        // Export with Thai text unescaped
        echo json_encode([
            'status' => 'success',
            'timestamp' => date('Y-m-d H:i:s'),
            'total' => count($data),
            'certificates' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        exit;
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ============================================
// IF NO EXPORT, DISPLAY HTML
// ============================================

// Statistics
$totalCerts = $conn->query("SELECT COUNT(*) as count FROM certificate_names")->fetch_assoc()['count'] ?? 0;

// Try to get organizations count if table exists
$totalOrgs = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM organizations");
if ($result) {
    $totalOrgs = $result->fetch_assoc()['count'] ?? 0;
}

// Try to get templates count if table exists
$totalTemplates = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM certificate_templates");
if ($result) {
    $totalTemplates = $result->fetch_assoc()['count'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Export & Integration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-download me-2"></i>Export & Integration
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
        <h2><i class="fas fa-download"></i> Export & Integration</h2>
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
    
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-certificate"></i> ใบประกาศ</h5>
                    <h2><?= $totalCerts ?></h2>
                    <p class="mb-0">รายการทั้งหมด</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-building"></i> หน่วยงาน</h5>
                    <h2><?= $totalOrgs ?></h2>
                    <p class="mb-0">องค์กรทั้งหมด</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-file-alt"></i> เทมเพลต</h5>
                    <h2><?= $totalTemplates ?></h2>
                    <p class="mb-0">แม่แบบทั้งหมด</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-file-export"></i> Export Data</h5>
                    <p class="text-muted">ดาวน์โหลดข้อมูลในรูปแบบต่างๆ</p>
                    <div class="d-grid gap-2">
                        <a href="?export=csv" class="btn btn-success">
                            <i class="fas fa-file-csv"></i> Export as CSV
                        </a>
                        <a href="?export=excel" class="btn btn-primary">
                            <i class="fas fa-file-excel"></i> Export as Excel
                        </a>
                        <a href="?export=json" class="btn btn-info">
                            <i class="fas fa-file-code"></i> Export as JSON
                        </a>
                        <button class="btn btn-danger" disabled>
                            <i class="fas fa-file-pdf"></i> Export as PDF (Coming Soon)
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-plug"></i> API Integration</h5>
                    <p class="text-muted">เชื่อมต่อกับระบบภายนอก</p>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> API Endpoints</h6>
                        <small>
                            <strong>GET</strong> /api/certificates.php<br>
                            <strong>POST</strong> /api/certificates.php<br>
                            <strong>GET</strong> /api/verify.php?code=[code]
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">API Key</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?= hash('sha256', $_SESSION['user_id'] . '_' . date('Y-m-d')) ?>" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <small class="text-muted">ใช้สำหรับเชื่อมต่อ API</small>
                    </div>
                    
                    <a href="api_doc.php" class="btn btn-secondary w-100" target="_blank">
                        <i class="fas fa-book"></i> ดูเอกสาร API
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-cog"></i> Integration Options</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3">
                                <h6><i class="fas fa-webhook text-primary"></i> Webhooks</h6>
                                <p class="text-muted small">รับ notifications แบบ real-time</p>
                                <span class="badge bg-warning">Coming Soon</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3">
                                <h6><i class="fas fa-database text-success"></i> Database Sync</h6>
                                <p class="text-muted small">ซิงค์ข้อมูลระหว่างระบบ</p>
                                <span class="badge bg-warning">Coming Soon</span>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3">
                                <h6><i class="fas fa-cloud text-info"></i> Cloud Storage</h6>
                                <p class="text-muted small">เชื่อมต่อ Google Drive, Dropbox</p>
                                <span class="badge bg-warning">Coming Soon</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function copyToClipboard(btn) {
    const input = btn.previousElementSibling;
    input.select();
    document.execCommand('copy');
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i>';
    setTimeout(() => {
        btn.innerHTML = originalText;
    }, 2000);
}
</script>
</body>
</html>
