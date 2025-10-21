<?php
// logo_editor.php - Advanced Image Editor สำหรับเพิ่มโลโก้บนใบประกาศ
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// สร้างโฟลเดอร์เก็บโลโก้
$logoDir = __DIR__ . '/assets/logos';
if (!is_dir($logoDir)) {
    mkdir($logoDir, 0777, true);
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $file = $_FILES['logo'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (in_array($file['type'], $allowedTypes)) {
        $filename = 'logo_' . time() . '_' . basename($file['name']);
        $targetPath = $logoDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $_SESSION['uploaded_logo'] = 'assets/logos/' . $filename;
            $message = '✅ อัปโหลดโลโก้สำเร็จ!';
        } else {
            $message = '❌ อัปโหลดล้มเหลว!';
        }
    } else {
        $message = '❌ รองรับเฉพาะไฟล์ JPG, PNG, GIF, WebP';
    }
}

// ดึงรายการโลโก้ที่มี
$logos = [];
if (is_dir($logoDir)) {
    $files = scandir($logoDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
            $logos[] = 'assets/logos/' . $file;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Logo Editor - เพิ่มโลโก้บนใบประกาศ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
    <style>
        .canvas-container { border: 2px solid #ddd; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .logo-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; }
        .logo-item { cursor: pointer; border: 2px solid transparent; border-radius: 8px; overflow: hidden; transition: all 0.2s; }
        .logo-item:hover { border-color: #007bff; transform: scale(1.05); }
        .logo-item img { width: 100%; height: 100px; object-fit: contain; background: #f8f9fa; }
        .control-panel { background: #f8f9fa; padding: 15px; border-radius: 10px; }
    </style>
</head>
<body class="bg-light">
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <i class="fas fa-edit me-2"></i>Logo Editor
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
<div class="container-fluid">
    <div class="navbar navbar-dark bg-primary mb-4">
        <div class="container">
            <div class="navbar-brand">
                <i class="fas fa-certificate"></i> ระบบใบประกาศ
            </div>
            <span class="text-white">
                <i class="fas fa-image me-2"></i>Logo Editor
            </span>
        </div>
    </nav>

    <div class="container pb-5">
        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Canvas Area -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-magic me-2"></i>พื้นที่ออกแบบ</h5>
                    </div>
                    <div class="card-body text-center">
                        <canvas id="canvas" width="800" height="600" style="border: 1px solid #dee2e6;"></canvas>
                        <div class="mt-3 text-muted">
                            <small>
                                <i class="fas fa-lightbulb me-1"></i>
                                <strong>คำแนะนำ:</strong> อัปโหลดโลโก้ → คลิกโลโก้เพื่อเพิ่ม → ลากเพื่อย้าย → ใช้ Slider ปรับแต่ง
                            </small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-success" onclick="exportImage()">
                            <i class="fas fa-download"></i> ดาวน์โหลด
                        </button>
                        <button class="btn btn-danger" onclick="clearCanvas()">
                            <i class="fas fa-trash"></i> ล้างทั้งหมด
                        </button>
                        <button class="btn btn-warning" onclick="deleteSelected()">
                            <i class="fas fa-times"></i> ลบที่เลือก
                        </button>
                    </div>
                </div>
            </div>

            <!-- Control Panel -->
            <div class="col-lg-4">
                <!-- Upload Logo -->
                <div class="card mb-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-upload me-2"></i>อัปโหลดโลโก้</h6>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <input type="file" name="logo" class="form-control mb-2" accept="image/*" required>
                            <button type="submit" class="btn btn-info w-100">
                                <i class="fas fa-upload"></i> อัปโหลด
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Logo Gallery -->
                <div class="card mb-3">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-images me-2"></i>คลังโลโก้</h6>
                    </div>
                    <div class="card-body">
                        <div class="logo-gallery">
                            <?php if (empty($logos)): ?>
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>ยังไม่มีโลโก้</strong><br>
                                    <small>กรุณาอัปโหลดโลโก้ก่อนใช้งาน</small>
                                </div>
                            <?php else: ?>
                                <?php foreach ($logos as $logo): ?>
                                    <div class="logo-item" onclick="addLogoToCanvas('<?= $logo ?>')">
                                        <img src="<?= $logo ?>" alt="Logo">
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-sliders-h me-2"></i>การควบคุม</h6>
                    </div>
                    <div class="card-body control-panel">
                        <div class="mb-3">
                            <label class="form-label">ขนาด</label>
                            <input type="range" class="form-range" min="0.1" max="3" step="0.1" value="1" id="scaleSlider" oninput="scaleObject(this.value)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">หมุน (องศา)</label>
                            <input type="range" class="form-range" min="0" max="360" value="0" id="rotateSlider" oninput="rotateObject(this.value)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">โปร่งใส</label>
                            <input type="range" class="form-range" min="0" max="1" step="0.1" value="1" id="opacitySlider" oninput="setOpacity(this.value)">
                        </div>
                        <div class="d-grid gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="bringToFront()">
                                <i class="fas fa-arrow-up"></i> นำหน้าสุด
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="sendToBack()">
                                <i class="fas fa-arrow-down"></i> ส่งหลังสุด
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const canvas = new fabric.Canvas('canvas');
        
        // โหลดพื้นหลัง (ถ้ามี)
        <?php if (isset($_SESSION['bg_path']) && $_SESSION['bg_path']): ?>
        fabric.Image.fromURL('<?= $_SESSION['bg_path'] ?>', function(img) {
            img.scaleToWidth(canvas.width);
            img.scaleToHeight(canvas.height);
            img.selectable = false;
            canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas));
        });
        <?php else: ?>
        // ถ้าไม่มีพื้นหลัง ใส่สีพื้นฐาน
        canvas.backgroundColor = '#f8f9fa';
        canvas.renderAll();
        <?php endif; ?>

        // เพิ่มโลโก้ลง Canvas
        function addLogoToCanvas(logoPath) {
            fabric.Image.fromURL(logoPath, function(img) {
                img.scale(0.3);
                img.set({
                    left: canvas.width / 2,
                    top: canvas.height / 2,
                    originX: 'center',
                    originY: 'center'
                });
                canvas.add(img);
                canvas.setActiveObject(img);
                canvas.renderAll();
            });
        }

        // Scale object
        function scaleObject(value) {
            const activeObject = canvas.getActiveObject();
            if (activeObject) {
                activeObject.scale(parseFloat(value));
                canvas.renderAll();
            }
        }

        // Rotate object
        function rotateObject(value) {
            const activeObject = canvas.getActiveObject();
            if (activeObject) {
                activeObject.rotate(parseInt(value));
                canvas.renderAll();
            }
        }

        // Set opacity
        function setOpacity(value) {
            const activeObject = canvas.getActiveObject();
            if (activeObject) {
                activeObject.opacity = parseFloat(value);
                canvas.renderAll();
            }
        }

        // Bring to front
        function bringToFront() {
            const activeObject = canvas.getActiveObject();
            if (activeObject) {
                canvas.bringToFront(activeObject);
                canvas.renderAll();
            }
        }

        // Send to back
        function sendToBack() {
            const activeObject = canvas.getActiveObject();
            if (activeObject) {
                canvas.sendToBack(activeObject);
                canvas.renderAll();
            }
        }

        // Delete selected
        function deleteSelected() {
            const activeObjects = canvas.getActiveObjects();
            if (activeObjects.length) {
                activeObjects.forEach(obj => canvas.remove(obj));
                canvas.discardActiveObject();
                canvas.renderAll();
            }
        }

        // Clear canvas
        function clearCanvas() {
            if (confirm('ล้างทั้งหมด?')) {
                canvas.clear();
                // โหลดพื้นหลังใหม่
                <?php if (isset($_SESSION['bg_path']) && $_SESSION['bg_path']): ?>
                fabric.Image.fromURL('<?= $_SESSION['bg_path'] ?>', function(img) {
                    img.scaleToWidth(canvas.width);
                    img.scaleToHeight(canvas.height);
                    img.selectable = false;
                    canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas));
                });
                <?php endif; ?>
            }
        }

        // Export image
        function exportImage() {
            const dataURL = canvas.toDataURL({
                format: 'png',
                quality: 1
            });
            
            const link = document.createElement('a');
            link.download = 'certificate_with_logo_' + Date.now() + '.png';
            link.href = dataURL;
            link.click();
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Delete') {
                deleteSelected();
            }
        });

        // Update sliders when object is selected
        canvas.on('selection:created', updateSliders);
        canvas.on('selection:updated', updateSliders);

        function updateSliders() {
            const activeObject = canvas.getActiveObject();
            if (activeObject) {
                document.getElementById('scaleSlider').value = activeObject.scaleX;
                document.getElementById('rotateSlider').value = activeObject.angle;
                document.getElementById('opacitySlider').value = activeObject.opacity;
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
