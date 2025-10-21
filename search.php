<?php
/**
 * Advanced Search & Filter Page - Phase 4
 * 
 * UI for searching and filtering certificates with advanced filters
 * Features: Full-text search, filters, sorting, pagination
 */

require_once 'db.php';
require_once 'assets/validation.class.php';
require_once 'assets/error_handler.php';
require_once 'assets/search.class.php';

session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = null;
$results = [];
$pagination = [];
$filters = [];
$statistics = [];

// Initialize search
$search = new Search($conn);
$filters = $search->getAvailableFilters();
$statistics = $search->getStatistics();

// Get search parameters from form/URL
$searchTerm = $_GET['q'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$status = $_GET['status'] ?? '';
$templateId = $_GET['template_id'] ?? '';
$organizationId = $_GET['organization_id'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$sortBy = $_GET['sort'] ?? 'c.created_at';
$sortOrder = $_GET['order'] ?? 'DESC';

// Execute search if parameters provided
if (!empty($searchTerm) || !empty($status) || !empty($templateId) || !empty($organizationId) || !empty($dateFrom) || !empty($dateTo)) {
    $searchResult = $search
        ->search($searchTerm)
        ->setPage($page)
        ->setPerPage(20)
        ->filterByStatus($status)
        ->filterByTemplate($templateId)
        ->filterByOrganization($organizationId)
        ->filterByDateRange($dateFrom, $dateTo)
        ->sort($sortBy, $sortOrder)
        ->execute();
    
    $results = $searchResult['data'] ?? [];
    $pagination = $searchResult['pagination'] ?? [];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ค้นหาและกรองข้อมูล - Certificate System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .search-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .search-title {
            color: white;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .search-subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 1rem;
        }
        .filter-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .filter-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .filter-group {
            margin-bottom: 1rem;
        }
        .filter-group label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
            display: block;
        }
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 0.375rem;
            font-size: 0.95rem;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .result-count {
            font-size: 1rem;
            color: #666;
            font-weight: 500;
        }
        .sort-options {
            display: flex;
            gap: 0.5rem;
        }
        .sort-options select {
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 0.375rem;
            font-size: 0.95rem;
        }
        .result-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .result-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .result-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .result-meta {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }
        .result-meta-item {
            font-size: 0.9rem;
            color: #666;
        }
        .result-meta-label {
            font-weight: 500;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-processing {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-draft {
            background-color: #e7e7e7;
            color: #383d41;
        }
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .pagination {
            margin-top: 2rem;
            justify-content: center;
        }
        .no-results {
            text-align: center;
            padding: 3rem;
            color: #999;
        }
        .no-results-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }
        .btn-advanced {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-advanced:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        .btn-reset {
            background: #e0e0e0;
            color: #333;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
        }
        .btn-reset:hover {
            background: #d0d0d0;
            color: #333;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="search-header">
            <div class="container">
                <div class="search-title">
                    <i class="fas fa-search"></i> ค้นหาและกรองข้อมูล
                </div>
                <div class="search-subtitle">
                    ค้นหาใบประกาศด้วยคำค้นหา ตัวกรอง และการเรียงลำดับขั้นสูง
                </div>
            </div>
        </div>

        <div class="container">
            <!-- Statistics -->
            <?php if (!empty($statistics)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $statistics['total'] ?? 0; ?></div>
                    <div class="stat-label">ทั้งหมด</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $statistics['by_status']['completed'] ?? 0; ?></div>
                    <div class="stat-label">เสร็จสิ้น</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $statistics['by_status']['processing'] ?? 0; ?></div>
                    <div class="stat-label">กำลังประมวลผล</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $statistics['this_month'] ?? 0; ?></div>
                    <div class="stat-label">เดือนนี้</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filter Form -->
            <div class="filter-card">
                <div class="filter-title">
                    <i class="fas fa-sliders-h"></i> ตัวกรองขั้นสูง
                </div>
                <form method="GET" id="searchForm">
                    <div class="row">
                        <!-- Search Term -->
                        <div class="col-md-6 filter-group">
                            <label><i class="fas fa-search"></i> ค้นหา (ชื่อ, อีเมล, องค์กร)</label>
                            <input type="text" name="q" class="form-control" placeholder="กรอกคำค้นหา..." 
                                   value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-6 filter-group">
                            <label><i class="fas fa-circle"></i> สถานะ</label>
                            <select name="status" class="form-control">
                                <option value="">-- ทั้งหมด --</option>
                                <?php foreach ($filters['statuses'] ?? [] as $s): ?>
                                <option value="<?php echo $s['value']; ?>" <?php echo $status === $s['value'] ? 'selected' : ''; ?>>
                                    <?php echo $s['label']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Template Filter -->
                        <div class="col-md-6 filter-group">
                            <label><i class="fas fa-file-image"></i> เทมเพลต</label>
                            <select name="template_id" class="form-control">
                                <option value="">-- ทั้งหมด --</option>
                                <?php foreach ($filters['templates'] ?? [] as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo $templateId == $t['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Organization Filter -->
                        <div class="col-md-6 filter-group">
                            <label><i class="fas fa-building"></i> องค์กร</label>
                            <select name="organization_id" class="form-control">
                                <option value="">-- ทั้งหมด --</option>
                                <?php foreach ($filters['organizations'] ?? [] as $o): ?>
                                <option value="<?php echo $o['id']; ?>" <?php echo $organizationId == $o['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($o['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="col-md-6 filter-group">
                            <label><i class="fas fa-calendar"></i> จากวันที่</label>
                            <input type="date" name="date_from" class="form-control" 
                                   value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>

                        <div class="col-md-6 filter-group">
                            <label><i class="fas fa-calendar"></i> ถึงวันที่</label>
                            <input type="date" name="date_to" class="form-control" 
                                   value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>

                        <!-- Sorting -->
                        <div class="col-md-6 filter-group">
                            <label><i class="fas fa-sort"></i> เรียงลำดับตาม</label>
                            <select name="sort" class="form-control">
                                <option value="c.created_at" <?php echo $sortBy === 'c.created_at' ? 'selected' : ''; ?>>วันที่สร้าง</option>
                                <option value="c.recipient_name" <?php echo $sortBy === 'c.recipient_name' ? 'selected' : ''; ?>>ชื่อผู้รับ</option>
                                <option value="c.status" <?php echo $sortBy === 'c.status' ? 'selected' : ''; ?>>สถานะ</option>
                                <option value="c.updated_at" <?php echo $sortBy === 'c.updated_at' ? 'selected' : ''; ?>>วันที่อัปเดต</option>
                            </select>
                        </div>

                        <div class="col-md-6 filter-group">
                            <label><i class="fas fa-arrow-up-down"></i> ลำดับ</label>
                            <select name="order" class="form-control">
                                <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>จากใหม่ไปเก่า</option>
                                <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>จากเก่าไปใหม่</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-advanced">
                                <i class="fas fa-search"></i> ค้นหา
                            </button>
                            <a href="search.php" class="btn btn-reset ms-2">
                                <i class="fas fa-redo"></i> ล้างตัวกรอง
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Results -->
            <?php if (!empty($results) || !empty($searchTerm) || !empty($status) || !empty($templateId)): ?>
                <div class="results-header">
                    <div class="result-count">
                        <i class="fas fa-info-circle"></i>
                        พบ <strong><?php echo $pagination['total_records'] ?? 0; ?></strong> ผลลัพธ์
                        <?php if (!empty($searchTerm)): ?>
                            สำหรับ "<strong><?php echo htmlspecialchars($searchTerm); ?></strong>"
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($results)): ?>
                    <!-- Result Items -->
                    <?php foreach ($results as $item): ?>
                    <div class="result-item">
                        <div class="result-name">
                            <i class="fas fa-certificate"></i> 
                            <?php echo htmlspecialchars($item['recipient_name'] ?? 'N/A'); ?>
                        </div>
                        
                        <div class="result-meta">
                            <div class="result-meta-item">
                                <span class="result-meta-label">อีเมล:</span>
                                <?php echo htmlspecialchars($item['recipient_email'] ?? 'N/A'); ?>
                            </div>
                            
                            <div class="result-meta-item">
                                <span class="result-meta-label">องค์กร:</span>
                                <?php echo htmlspecialchars($item['organization_name'] ?? 'N/A'); ?>
                            </div>
                            
                            <div class="result-meta-item">
                                <span class="result-meta-label">เทมเพลต:</span>
                                <?php echo htmlspecialchars($item['template_name'] ?? 'N/A'); ?>
                            </div>
                        </div>

                        <div class="result-meta">
                            <div class="result-meta-item">
                                <span class="result-meta-label">สถานะ:</span>
                                <span class="status-badge status-<?php echo $item['status']; ?>">
                                    <?php 
                                    $statusLabels = [
                                        'draft' => 'ร่าง',
                                        'processing' => 'กำลังประมวลผล',
                                        'completed' => 'เสร็จสิ้น',
                                        'failed' => 'ล้มเหลว'
                                    ];
                                    echo $statusLabels[$item['status']] ?? $item['status'];
                                    ?>
                                </span>
                            </div>
                            
                            <div class="result-meta-item">
                                <span class="result-meta-label">วันที่สร้าง:</span>
                                <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?>
                            </div>
                        </div>

                        <div class="mt-2">
                            <a href="designer.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> แก้ไข
                            </a>
                            <?php if ($item['file_path']): ?>
                            <a href="<?php echo htmlspecialchars($item['file_path']); ?>" class="btn btn-sm btn-success" download>
                                <i class="fas fa-download"></i> ดาวน์โหลด
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($pagination['has_prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i> ก่อนหน้า
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php 
                            $start = max(1, $pagination['current_page'] - 2);
                            $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                            
                            for ($i = $start; $i <= $end; $i++): 
                            ?>
                            <li class="page-item <?php echo $i === $pagination['current_page'] ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>

                            <?php if ($pagination['has_next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])); ?>">
                                    ถัดไป <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4>ไม่พบผลลัพธ์</h4>
                        <p>ไม่พบใบประกาศที่ตรงกับเงื่อนไขการค้นหา</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-results">
                    <div class="no-results-icon">
                        <i class="fas fa-filter"></i>
                    </div>
                    <h4>เริ่มการค้นหาของคุณ</h4>
                    <p>ใช้ตัวกรองด้านบนเพื่อค้นหาใบประกาศ</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
