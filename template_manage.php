<?php
// Template Management CRUD - with Validation & Error Handling (Phase 2 Enhancement)
session_start();
require_once "db.php";
require_once "assets/validation.class.php";
require_once "assets/error_handler.php";

// Create table for templates if not exists
$conn->query("CREATE TABLE IF NOT EXISTS certificate_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    preview VARCHAR(255),
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Initialize error/success messages
$error = "";
$success = "";
$errors = [];

// Handle CRUD actions
$action = $_GET['action'] ?? '';

// Create - with Validation (Phase 2 Enhancement)
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validation();
    
    // Validate input fields
    $validator->required('name', $_POST['name'] ?? '', 'ชื่อ Template');
    $validator->length('name', $_POST['name'] ?? '', 2, 100, 'ชื่อ Template');
    $validator->required('category', $_POST['category'] ?? '', 'หมวดหมู่');
    $validator->enum('category', $_POST['category'] ?? '', ['ราชการ', 'โมเดิร์น', 'หรูหรา'], 'หมวดหมู่');
    $validator->required('file_path', $_POST['file_path'] ?? '', 'ไฟล์ Template');
    
    if ($validator->isValid()) {
        $name = Validation::sanitizeForDB($_POST['name']);
        $category = Validation::sanitizeForDB($_POST['category']);
        $file_path = Validation::sanitizeForDB($_POST['file_path']);
        $preview = Validation::sanitizeForDB($_POST['preview'] ?? '');
        
        try {
            $stmt = $conn->prepare("INSERT INTO certificate_templates (name, category, file_path, preview) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ssss", $name, $category, $file_path, $preview);
            
            if ($stmt->execute()) {
                $success = "✓ เพิ่ม Template เรียบร้อยแล้ว";
                ErrorHandler::log('Template created: ' . $name, 'INFO');
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "✗ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage());
            ErrorHandler::logDB('Template creation failed', $e->getMessage());
        }
    } else {
        $errors = $validator->getErrors();
        $error = "✗ โปรดตรวจสอบข้อมูลที่กรอก";
    }
    
    if (empty($errors) && empty($error)) {
        header("Location: template_manage.php?success=1");
        exit;
    }
}

// Delete - with Error Handling
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id > 0) {
        try {
            $stmt = $conn->prepare("DELETE FROM certificate_templates WHERE id=?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = "✓ ลบ Template เรียบร้อยแล้ว";
                ErrorHandler::log('Template deleted: ID ' . $id, 'INFO');
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "✗ ไม่สามารถลบ Template ได้";
            ErrorHandler::logDB('Template deletion failed', $e->getMessage());
        }
    }
    
    header("Location: template_manage.php" . ($error ? "?error=1" : "?success=1"));
    exit;
}

// Read all templates
$res = $conn->query("SELECT * FROM certificate_templates ORDER BY created_at DESC");
$templates = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$categories = ['ราชการ', 'โมเดิร์น', 'หรูหรา'];

// Check for success/error from redirect
if (isset($_GET['success'])) {
    $success = "✓ ดำเนินการเรียบร้อยแล้ว";
}
if (isset($_GET['error'])) {
    $error = "✗ ไม่สามารถดำเนินการได้";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Template Management</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-layer-group me-2"></i>Template Management
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
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-layer-group"></i> จัดการ Template ใบประกาศ</h2>
        </div>
    </div>
    
    <!-- Success/Error Messages (Phase 2 Enhancement) -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Validation Errors Display (Phase 2 Enhancement) -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h6><i class="fas fa-exclamation-triangle"></i> โปรดตรวจสอบข้อมูล:</h6>
            <ul class="mb-0">
                <?php foreach ($errors as $field => $messages): ?>
                    <?php foreach ((array)$messages as $message): ?>
                        <li><?= htmlspecialchars($message) ?></li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <form method="post" action="?action=create" class="mb-4 p-4 bg-white rounded shadow-sm">
        <h5 class="mb-3"><i class="fas fa-plus"></i> เพิ่ม Template ใหม่</h5>
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label">ชื่อ Template <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="เช่น ใบประกาศราชการ" 
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                <small class="text-muted">ความยาว 2-100 ตัวอักษร</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                <select name="category" class="form-select" required>
                    <option value="">เลือกหมวดหมู่...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" 
                            <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">ไฟล์ Template <span class="text-danger">*</span></label>
                <input type="text" name="file_path" class="form-control" placeholder="templates/template1.png" 
                       value="<?= htmlspecialchars($_POST['file_path'] ?? '') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">URL รูป Preview</label>
                <input type="text" name="preview" class="form-control" placeholder="https://..." 
                       value="<?= htmlspecialchars($_POST['preview'] ?? '') ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-success mt-3">
            <i class="fas fa-save"></i> เพิ่ม Template
        </button>
    </form>
    
    <form method="post" action="?action=create" class="mb-4">
        <?php foreach ($templates as $tpl): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if ($tpl['preview']): ?>
                        <img src="<?= $tpl['preview'] ?>" class="card-img-top" alt="Preview">
                    <?php else: ?>
                        <div class="card-img-top bg-secondary text-white d-flex align-items-center justify-content-center" style="height:180px;">ไม่มีรูป Preview</div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($tpl['name']) ?></h5>
                        <p class="card-text">หมวดหมู่: <?= htmlspecialchars($tpl['category']) ?></p>
                        <p class="card-text"><small class="text-muted">สร้างเมื่อ <?= $tpl['created_at'] ?></small></p>
                        <a href="?action=delete&id=<?= $tpl['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ลบ Template นี้?')">ลบ</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
