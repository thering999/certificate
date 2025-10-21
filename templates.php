<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$alert = '';

// ประมวลผลการอัปโหลดเทมเพลต
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['template'])) {
    $file = $_FILES['template'];
    $template_name = trim($_POST['template_name'] ?? '');
    
    if (empty($template_name)) {
        $alert = 'กรุณากรอกชื่อเทมเพลต';
    } elseif ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        
        if (in_array($ext, $allowed)) {
            // สร้างโฟลเดอร์ templates หากไม่มี
            $templates_dir = 'assets/templates';
            if (!is_dir($templates_dir)) {
                mkdir($templates_dir, 0755, true);
            }
            
            $target = $templates_dir . '/template_' . time() . '.' . $ext;
            
            if (move_uploaded_file($file['tmp_name'], $target)) {
                // บันทึกข้อมูลเทมเพลตในฐานข้อมูล
                $stmt = $conn->prepare("INSERT INTO certificate_templates (name, file_path) VALUES (?, ?)");
                $stmt->bind_param("ss", $template_name, $target);
                
                if ($stmt->execute()) {
                    $alert = 'อัปโหลดเทมเพลตสำเร็จ!';
                } else {
                    $alert = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
                    unlink($target); // ลบไฟล์ที่อัปโหลด
                }
            } else {
                $alert = 'เกิดข้อผิดพลาดในการบันทึกไฟล์';
            }
        } else {
            $alert = 'ไฟล์ไม่ใช่รูปภาพที่รองรับ';
        }
    } else {
        $alert = 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์';
    }
}

// ลบเทมเพลต
if (isset($_GET['delete']) && $_GET['delete']) {
    $template_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("SELECT file_path FROM certificate_templates WHERE id = ?");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $file_path = $row['file_path'];
        
        // ลบไฟล์
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // ลบข้อมูลจากฐานข้อมูล
        $stmt = $conn->prepare("DELETE FROM certificate_templates WHERE id = ?");
        $stmt->bind_param("i", $template_id);
        
        if ($stmt->execute()) {
            $alert = 'ลบเทมเพลตสำเร็จ!';
        } else {
            $alert = 'เกิดข้อผิดพลาดในการลบข้อมูล';
        }
    }
}

// ดึงรายการเทมเพลต
$templates = [];
$result = $conn->query("SELECT * FROM certificate_templates ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเทมเพลตใบประกาศ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="card card-custom">
            <h2 class="mb-4">จัดการเทมเพลตใบประกาศ</h2>
            
            <?php if ($alert): ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($alert) ?>
            </div>
            <?php endif; ?>
            
            <!-- ฟอร์มอัปโหลดเทมเพลต -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">เพิ่มเทมเพลตใหม่</h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="template_name" class="form-label">ชื่อเทมเพลต</label>
                                    <input type="text" class="form-control" id="template_name" name="template_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="template" class="form-label">ไฟล์เทมเพลต</label>
                                    <input type="file" class="form-control" id="template" name="template" accept="image/*" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-custom">อัปโหลดเทมเพลต</button>
                    </form>
                </div>
            </div>
            
            <!-- รายการเทมเพลต -->
            <h5>รายการเทมเพลตที่มี (<?= count($templates) ?> เทมเพลต)</h5>
            
            <?php if (count($templates) > 0): ?>
            <div class="row">
                <?php foreach ($templates as $template): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <img src="<?= htmlspecialchars($template['file_path']) ?>" 
                             class="card-img-top" 
                             style="height: 200px; object-fit: cover;" 
                             alt="<?= htmlspecialchars($template['name']) ?>">
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($template['name']) ?></h6>
                            <p class="card-text">
                                <small class="text-muted">
                                    อัปโหลดเมื่อ: <?= date('d/m/Y H:i', strtotime($template['created_at'])) ?>
                                </small>
                            </p>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-success btn-custom" 
                                        onclick="setTemplate('<?= htmlspecialchars($template['file_path']) ?>', '<?= htmlspecialchars($template['name']) ?>')">
                                    ใช้เทมเพลตนี้
                                </button>
                                <a href="?delete=<?= $template['id'] ?>" 
                                   class="btn btn-sm btn-danger btn-custom" 
                                   onclick="return confirm('ยืนยันการลบเทมเพลต?')">
                                    ลบ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                ยังไม่มีเทมเพลต กรุณาอัปโหลดเทมเพลตใบประกาศ
            </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="index.php" class="btn btn-secondary btn-custom">กลับหน้าหลัก</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setTemplate(filePath, templateName) {
            // ตั้งค่าเทมเพลตเป็น background ปัจจุบัน
            fetch('set_template.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'template_path=' + encodeURIComponent(filePath) + '&template_name=' + encodeURIComponent(templateName)
            })
            .then(response => response.text())
            .then(data => {
                alert('ตั้งค่าเทมเพลต "' + templateName + '" เป็นพื้นหลังปัจจุบันแล้ว');
                window.location.href = 'index.php';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการตั้งค่าเทมเพลต');
            });
        }
    </script>
</body>
</html>