<?php
// Organization Management - CRUD หน่วยงาน (Phase 2 Enhancement)
session_start();
require_once "db.php";
require_once "assets/validation.class.php";
require_once "assets/error_handler.php";

// สร้างตาราง organizations ถ้ายังไม่มี
$conn->query("CREATE TABLE IF NOT EXISTS organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Handle CRUD actions
$action = $_GET['action'] ?? '';
$error = "";
$success = "";
$errors = [];

// Create - with Validation (Phase 2 Enhancement)
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validation();
    
    // Validate input fields
    $validator->required('name', $_POST['name'] ?? '', 'ชื่อหน่วยงาน');
    $validator->length('name', $_POST['name'] ?? '', 2, 100, 'ชื่อหน่วยงาน');
    if (!empty($_POST['code'])) {
        $validator->length('code', $_POST['code'], 2, 20, 'รหัสหน่วยงาน');
    }
    
    if ($validator->isValid()) {
        $name = Validation::sanitizeForDB($_POST['name']);
        $code = Validation::sanitizeForDB($_POST['code'] ?? '');
        $description = Validation::sanitizeForDB($_POST['description'] ?? '');
        
        try {
            $stmt = $conn->prepare("INSERT INTO organizations (name, code, description) VALUES (?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("sss", $name, $code, $description);
            
            if ($stmt->execute()) {
                $success = "✓ เพิ่มหน่วยงานเรียบร้อยแล้ว";
                ErrorHandler::log('Organization created: ' . $name, 'INFO');
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "✗ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage());
            ErrorHandler::logDB('Organization creation failed', $e->getMessage());
        }
    } else {
        $errors = $validator->getErrors();
        $error = "✗ โปรดตรวจสอบข้อมูลที่กรอก";
    }
    
    if (empty($errors) && empty($error)) {
        header("Location: organization_manage.php?success=1");
        exit;
    }
}

// Update - with Validation (Phase 2 Enhancement)
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new Validation();
    
    // Validate input fields
    $validator->required('id', $_POST['id'] ?? '', 'ID');
    $validator->integer('id', $_POST['id'] ?? '', 'ID');
    $validator->required('name', $_POST['name'] ?? '', 'ชื่อหน่วยงาน');
    $validator->length('name', $_POST['name'] ?? '', 2, 100, 'ชื่อหน่วยงาน');
    if (!empty($_POST['code'])) {
        $validator->length('code', $_POST['code'], 2, 20, 'รหัสหน่วยงาน');
    }
    
    if ($validator->isValid()) {
        $id = intval($_POST['id']);
        $name = Validation::sanitizeForDB($_POST['name']);
        $code = Validation::sanitizeForDB($_POST['code'] ?? '');
        $description = Validation::sanitizeForDB($_POST['description'] ?? '');
        
        try {
            $stmt = $conn->prepare("UPDATE organizations SET name=?, code=?, description=? WHERE id=?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("sssi", $name, $code, $description, $id);
            
            if ($stmt->execute()) {
                $success = "✓ แก้ไขหน่วยงานเรียบร้อยแล้ว";
                ErrorHandler::log('Organization updated: ' . $name, 'INFO');
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "✗ ไม่สามารถแก้ไขหน่วยงานได้";
            ErrorHandler::logDB('Organization update failed', $e->getMessage());
        }
    } else {
        $errors = $validator->getErrors();
        $error = "✗ โปรดตรวจสอบข้อมูลที่กรอก";
    }
    
    if (empty($errors) && empty($error)) {
        header("Location: organization_manage.php?success=1");
        exit;
    }
}

// Delete - with Error Handling
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id > 0) {
        try {
            $stmt = $conn->prepare("DELETE FROM organizations WHERE id=?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = "✓ ลบหน่วยงานเรียบร้อยแล้ว";
                ErrorHandler::log('Organization deleted: ID ' . $id, 'INFO');
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "✗ ไม่สามารถลบหน่วยงานได้";
            ErrorHandler::logDB('Organization deletion failed', $e->getMessage());
        }
    }
    
    header("Location: organization_manage.php" . ($error ? "?error=1" : "?success=1"));
    exit;
}

// Read all organizations
$res = $conn->query("SELECT * FROM organizations ORDER BY name ASC");
$organizations = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Get organization for edit
$editOrg = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $editRes = $conn->query("SELECT * FROM organizations WHERE id=$id");
    $editOrg = $editRes ? $editRes->fetch_assoc() : null;
}

// Check for success/error from redirect
if (isset($_GET['success'])) {
    $success = "✓ ดำเนินการเรียบร้อยแล้ว";
}
if (isset($_GET['error'])) {
    $error = "✗ เกิดข้อผิดพลาด โปรดลองใหม่อีกครั้ง";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Organization Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-building me-2"></i>Organization
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
        <h2><i class="fas fa-building"></i> จัดการหน่วยงาน</h2>
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
    
    <!-- Form เพิ่ม/แก้ไข -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title"><?= $editOrg ? 'แก้ไขหน่วยงาน' : 'เพิ่มหน่วยงานใหม่' ?></h5>
            <form method="post" action="?action=<?= $editOrg ? 'update' : 'create' ?>">
                <?php if ($editOrg): ?>
                    <input type="hidden" name="id" value="<?= $editOrg['id'] ?>">
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">ชื่อหน่วยงาน <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editOrg['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">รหัสหน่วยงาน</label>
                        <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($editOrg['code'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">คำอธิบาย</label>
                        <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($editOrg['description'] ?? '') ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= $editOrg ? 'บันทึกการแก้ไข' : 'เพิ่มหน่วยงาน' ?>
                    </button>
                    <?php if ($editOrg): ?>
                        <a href="organization_manage.php" class="btn btn-secondary">ยกเลิก</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ตารางแสดงหน่วยงาน -->
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">รายการหน่วยงานทั้งหมด (<?= count($organizations) ?>)</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อหน่วยงาน</th>
                            <th>รหัส</th>
                            <th>คำอธิบาย</th>
                            <th>สร้างเมื่อ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($organizations) > 0): ?>
                            <?php foreach ($organizations as $org): ?>
                                <tr>
                                    <td><?= $org['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($org['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($org['code'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($org['description'] ?? '-') ?></td>
                                    <td><?= $org['created_at'] ?></td>
                                    <td>
                                        <a href="?action=edit&id=<?= $org['id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i> แก้ไข
                                        </a>
                                        <a href="?action=delete&id=<?= $org['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบหน่วยงาน <?= htmlspecialchars($org['name']) ?>?')">
                                            <i class="fas fa-trash"></i> ลบ
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">ยังไม่มีหน่วยงานในระบบ</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
