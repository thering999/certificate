<?php
/**
 * view_names.php
 * แสดงรายชื่อที่อัปโหลด ดูแต่ละรายการ แก้ไข ลบ
 */
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$edit_id = null;
$edit_name = '';

// ลบรายชื่อ
if (isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM certificate_names WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $delete_id, $user_id);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ลบสำเร็จ</div>';
    } else {
        $message = '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ลบไม่สำเร็จ</div>';
    }
    $stmt->close();
}

// แก้ไขรายชื่อ
if (isset($_POST['save_edit'])) {
    $edit_id = intval($_POST['edit_id']);
    $new_name = trim($_POST['new_name']);
    
    if (empty($new_name)) {
        $message = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ชื่อไม่ได้ว่างเปล่า</div>';
    } else {
        $stmt = $conn->prepare("UPDATE certificate_names SET name = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param('sii', $new_name, $edit_id, $user_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> บันทึกสำเร็จ</div>';
            $edit_id = null;
        } else {
            $message = '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> บันทึกไม่สำเร็จ</div>';
        }
        $stmt->close();
    }
}

// ดึงรายชื่อทั้งหมด
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;

$total_result = $conn->prepare("SELECT COUNT(*) as total FROM certificate_names WHERE user_id = ?");
$total_result->bind_param("i", $user_id);
$total_result->execute();
$total_result = $total_result->get_result();
$total = $total_result->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT id, name, created_at FROM certificate_names WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param('iii', $user_id, $limit, $offset);
$stmt->execute();
$names_result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ดูและแก้ไขรายชื่อ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 30px 0; }
        .card { border-radius: 1rem; box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: bold; }
        .table-hover tbody tr:hover { background: #f5f5f5; }
        .name-row { padding: 12px; border-bottom: 1px solid #ddd; }
        .name-item { display: flex; justify-content: space-between; align-items: center; }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i> ดูและแก้ไขรายชื่อ
                    <span class="float-end badge bg-info">รวม <?php echo $total; ?> รายชื่อ</span>
                </div>
                <div class="card-body">
                    
                    <?php echo $message; ?>
                    
                    <?php if ($total === 0): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> ยังไม่มีรายชื่อ
                            <a href="index.php">กลับไปอัปโหลด</a>
                        </div>
                    <?php else: ?>
                        
                        <!-- ตัวเลือกแสดงจำนวน -->
                        <div class="mb-3">
                            <label class="form-label">แสดงจำนวน:</label>
                            <div class="btn-group">
                                <a href="?limit=10&offset=0" class="btn btn-sm <?php echo $limit === 10 ? 'btn-primary' : 'btn-outline-primary'; ?>">10</a>
                                <a href="?limit=25&offset=0" class="btn btn-sm <?php echo $limit === 25 ? 'btn-primary' : 'btn-outline-primary'; ?>">25</a>
                                <a href="?limit=50&offset=0" class="btn btn-sm <?php echo $limit === 50 ? 'btn-primary' : 'btn-outline-primary'; ?>">50</a>
                                <a href="?limit=<?php echo $total; ?>&offset=0" class="btn btn-sm btn-outline-primary">ทั้งหมด</a>
                            </div>
                        </div>
                        
                        <!-- ตารางรายชื่อ -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60">#</th>
                                        <th>ชื่อ</th>
                                        <th width="120">วันที่</th>
                                        <th width="150">การกระทำ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $index = $offset + 1;
                                    while ($row = $names_result->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?php echo $index; ?></td>
                                            <td>
                                                <?php if ($edit_id === $row['id']): ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="edit_id" value="<?php echo $row['id']; ?>">
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" name="new_name" class="form-control" value="<?php echo htmlspecialchars($row['name']); ?>" autofocus>
                                                            <button type="submit" name="save_edit" class="btn btn-success">บันทึก</button>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($row['name']); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($edit_id !== $row['id']): ?>
                                                    <a href="?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i> แก้ไข
                                                    </a>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('ลบ?')">
                                                            <i class="fas fa-trash"></i> ลบ
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php $index++; ?>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total > $limit): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <?php 
                                    $total_pages = ceil($total / $limit);
                                    $current_page = floor($offset / $limit) + 1;
                                    
                                    for ($i = 1; $i <= $total_pages; $i++):
                                        $page_offset = ($i - 1) * $limit;
                                    ?>
                                        <li class="page-item <?php echo $current_page === $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?limit=<?php echo $limit; ?>&offset=<?php echo $page_offset; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                    
                    <!-- ปุ่มกลับ -->
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> กลับหน้าหลัก
                        </a>
                        <a href="export.php" class="btn btn-info" title="ส่งออกรายชื่อ">
                            <i class="fas fa-download"></i> ส่งออก
                        </a>
                    </div>
                    
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
