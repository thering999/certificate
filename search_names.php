<?php
// search_names.php - ค้นหาและกรองรายชื่อ
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// รับพารามิเตอร์
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 50;
$offset = ($page - 1) * $perPage;

// นับจำนวนทั้งหมด
$countSql = "SELECT COUNT(*) as total FROM certificate_names WHERE name LIKE ?";
$searchParam = "%{$search}%";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param("s", $searchParam);
$countStmt->execute();
$totalRecords = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $perPage);

// ดึงข้อมูล
$sql = "SELECT id, name FROM certificate_names WHERE name LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $searchParam, $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ค้นหาและกรองรายชื่อ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
    <style>
        .search-bar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px; margin-bottom: 20px; }
        .search-bar input { border-radius: 25px; padding: 12px 20px; font-size: 16px; }
        .table-hover tbody tr:hover { background-color: #f8f9fa; cursor: pointer; }
        .badge-count { font-size: 14px; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="search-bar text-white">
            <h3 class="mb-3"><i class="fas fa-search me-2"></i>ค้นหาและกรองรายชื่อ</h3>
            <form method="get" class="row g-2">
                <div class="col-md-8">
                    <input type="text" name="search" class="form-control" placeholder="พิมพ์ชื่อ-นามสกุล..." value="<?= htmlspecialchars($search) ?>" autofocus>
                </div>
                <div class="col-md-2">
                    <select name="per_page" class="form-select">
                        <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50 รายการ</option>
                        <option value="100" <?= $perPage == 100 ? 'selected' : '' ?>>100 รายการ</option>
                        <option value="200" <?= $perPage == 200 ? 'selected' : '' ?>>200 รายการ</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-light w-100">
                        <i class="fas fa-search"></i> ค้นหา
                    </button>
                </div>
            </form>
            <div class="mt-2">
                <span class="badge bg-light text-dark badge-count">
                    <i class="fas fa-users"></i> พบ <?= $totalRecords ?> รายการ
                </span>
                <?php if ($search): ?>
                    <a href="search_names.php" class="badge bg-warning text-dark ms-2">
                        <i class="fas fa-times"></i> ล้างการค้นหา
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list"></i> ผลการค้นหา (หน้า <?= $page ?>/<?= $totalPages ?>)
                </h5>
                <div>
                    <button class="btn btn-sm btn-warning" onclick="exportSelected()">
                        <i class="fas fa-download"></i> Export ที่เลือก
                    </button>
                    <a href="index.php" class="btn btn-sm btn-light">กลับหน้าหลัก</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                                </th>
                                <th>ID</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th width="150">การดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="name-checkbox" value="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['name']) ?>">
                                        </td>
                                        <td><?= $row['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                        <td>
                                            <a href="edit_name.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> แก้ไข
                                            </a>
                                            <a href="delete_name.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ลบรายการนี้?')">
                                                <i class="fas fa-trash"></i> ลบ
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                        ไม่พบข้อมูล
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>&per_page=<?= $perPage ?>">
                            <i class="fas fa-chevron-left"></i> ก่อนหน้า
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $i ?>&per_page=<?= $perPage ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>&per_page=<?= $perPage ?>">
                            ถัดไป <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <script>
        function toggleSelectAll(checkbox) {
            document.querySelectorAll('.name-checkbox').forEach(cb => {
                cb.checked = checkbox.checked;
            });
        }

        function exportSelected() {
            const selected = Array.from(document.querySelectorAll('.name-checkbox:checked')).map(cb => ({
                id: cb.value,
                name: cb.dataset.name
            }));
            
            if (selected.length === 0) {
                alert('กรุณาเลือกรายการที่ต้องการ Export');
                return;
            }
            
            // สร้างฟอร์มส่งข้อมูล
            const form = document.createElement('form');
            form.method = 'post';
            form.action = 'export_selected.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_names';
            input.value = JSON.stringify(selected);
            form.appendChild(input);
            
            document.body.appendChild(form);
            form.submit();
        }

        // Real-time search
        let searchTimeout;
        document.querySelector('input[name="search"]').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (e.target.value.length >= 2 || e.target.value.length === 0) {
                    e.target.form.submit();
                }
            }, 500);
        });
    </script>
</body>
</html>
