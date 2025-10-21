<?php
require_once 'db.php';
session_start();

// Prevent caching - always fresh content
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

$redirect_login = false;
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// Refresh user role from database on each page load
if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare('SELECT role FROM users WHERE id = ?');
  if ($stmt) {
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
      $row = $result->fetch_assoc();
      $_SESSION['role'] = $row['role']; // Update session with current role from DB
    }
    $stmt->close();
  }
}

$alert = '';
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    unset($_SESSION['alert']);
}
# Initialize variables
$count_result = $conn->query('SELECT COUNT(*) AS total FROM certificate_names');
$total_names = ($count_result && $count_result->num_rows > 0) ? $count_result->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="cache-control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="expires" content="0">
    <title>ระบบออกแบบใบประกาศ</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  </head>
  <body class="bg-light">
  <link rel="stylesheet" href="assets/style.css">
  
  <!-- Hero Section -->
  <div class="hero-section text-center py-5 mb-4">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-8 mx-auto">
          <h1 class="hero-title display-4 fw-bold text-white mb-3">
            🏆 ระบบออกแบบใบประกาศ
          </h1>
          <p class="hero-subtitle lead text-white-50 mb-4">
            สร้างใบประกาศสวยงามด้วยระบบที่ทันสมัย รองรับการปรับแต่งแบบ Real-time
          </p>
          <div class="user-info-badge">
            <span class="badge bg-white text-dark px-4 py-2 me-3">
              <i class="fas fa-user-circle me-2"></i>
              <?= isset($_SESSION['role']) ? ($_SESSION['role'] === 'admin' ? '👑 Admin' : '👤 User') : '👤 User' ?>
              : <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
            </span>
            <a href="dashboard.php" class="btn btn-primary btn-sm me-2">
              <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a href="logout.php" class="btn btn-outline-light btn-sm">
              <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="container py-4">
    <?php if ($alert): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
      <i class="fas fa-check-circle me-2"></i><?= $alert ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    <!-- Main Content Grid -->
    <div class="row g-4">
      <!-- Upload Section -->
      <div class="col-lg-6" style="order: 1;">
        <div class="card feature-card h-100">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-upload me-2"></i>นำเข้าข้อมูล</h5>
          </div>
          <div class="card-body">
            <form action="upload.php" method="post" enctype="multipart/form-data">
              <div class="mb-3">
                <label for="excelFile" class="form-label">
                  <i class="fas fa-file-csv me-2"></i>อัปโหลดไฟล์รายชื่อ (.csv)
                </label>
                <input type="file" class="form-control" id="excelFile" name="excelFile" accept=".csv" required>
                <div class="form-text">
                  <a href="assets/sample.csv" class="text-decoration-none">
                    <i class="fas fa-download me-1"></i>ดาวน์โหลดตัวอย่างไฟล์ .csv
                  </a>
                </div>
              </div>
              <div class="mb-3">
                <label for="bgFile" class="form-label">
                  <i class="fas fa-image me-2"></i>อัปโหลดภาพพื้นหลังใบประกาศ (Preview)
                </label>
                <input type="file" class="form-control" id="bgFile" accept=".jpg,.jpeg,.png,.gif,.webp">
                <div class="form-text">
                  รองรับไฟล์: JPG, PNG, GIF, WebP เท่านั้น
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-upload me-2"></i>อัปโหลด
                <div class="spinner-border spinner-border-sm ms-2 d-none" id="spinner-upload"></div>
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="col-lg-6" style="order: 2;">
        <div class="card feature-card h-100">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-tools me-2"></i>เครื่องมือ</h5>
          </div>
          <div class="card-body d-flex flex-column">
            <div class="quick-actions flex-grow-1">
              <a href="designer.php" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-magic me-2"></i>Designer แบบลากเอา (Drag & Drop)
              </a>
              <a href="quick_reference.php" class="btn btn-warning w-100 mb-3" target="_blank">
                <i class="fas fa-print me-2"></i>Quick Reference Card
              </a>
              <a href="template_inspiration.php" class="btn btn-info w-100 mb-3">
                <i class="fas fa-lightbulb me-2"></i>ไอเดีย & แนวทาง (7 สไตล์)
              </a>
              <a href="background_templates.php" class="btn btn-outline-info w-100 mb-3">
                <i class="fas fa-images me-2"></i>Background Gallery
              </a>
              <a href="logo_editor.php" class="btn btn-success w-100 mb-3">
                <i class="fas fa-image me-2"></i>Logo Editor - เพิ่มโลโก้
              </a>
              <a href="upload_bg.php" class="btn btn-outline-secondary w-100 mb-3">
                <i class="fas fa-image me-2"></i>อัปโหลดภาพพื้นหลังใบประกาศ
              </a>
              <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
              <a href="test_gd.php" class="btn btn-outline-info w-100 mb-3">
                <i class="fas fa-cog me-2"></i>ทดสอบระบบ GD
              </a>
              <?php endif; ?>
            </div>
            <div class="stats-info mt-auto">
              <div class="row text-center">
                <div class="col-6">
                  <div class="stat-item">
                    <h4 class="text-primary mb-0"><?= $total_names ?></h4>
                    <small class="text-muted">รายชื่อทั้งหมด</small>
                  </div>
                </div>
                <div class="col-6">
                  <div class="stat-item">
                    <h4 class="text-success mb-0">🎨</h4>
                    <small class="text-muted">พร้อมออกแบบ</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Certificate Designer -->
    <div class="card feature-card mt-4">
      <div class="card-header bg-gradient-primary text-white">
        <h5 class="mb-0"><i class="fas fa-palette me-2"></i>ออกแบบใบประกาศ</h5>
      </div>
      <div class="card-body">
        <form id="certForm" action="export.php" method="post">
      <div class="row">
        <div class="col-md-3">
          <label class="form-label">ตำแหน่งชื่อ (X)</label>
          <input type="number" class="form-control" name="pos_x" id="pos_x" value="200" min="0">
        </div>
        <div class="col-md-3">
          <label class="form-label">ตำแหน่งชื่อ (Y)</label>
          <input type="number" class="form-control" name="pos_y" id="pos_y" value="300" min="0">
        </div>
        <div class="col-md-2">
          <label class="form-label">ขนาดฟอนต์</label>
          <input type="number" class="form-control" name="font_size" id="font_size" value="32" min="10" max="100">
        </div>
        <div class="col-md-2">
          <label class="form-label">ฟอนต์</label>
          <select class="form-select" name="font_family" id="font_family">
            <option value="sans-serif">Sans-serif</option>
            <option value="serif">Serif</option>
            <option value="THSarabunNew">TH Sarabun New</option>
            <option value="Tahoma">Tahoma</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">สีข้อความ</label>
          <input type="color" class="form-control form-control-color" name="font_color" id="font_color" value="#222">
        </div>
        <div class="col-md-2">
          <label class="form-label">จัดตำแหน่ง</label>
          <select class="form-select" name="font_align" id="font_align">
            <option value="left">ซ้าย</option>
            <option value="center">กลาง</option>
            <option value="right">ขวา</option>
          </select>
        </div>
      </div>
          <div class="export-actions mt-4">
            <button type="submit" class="btn btn-success btn-lg me-3">
              <i class="fas fa-file-pdf me-2"></i>ส่งออกใบประกาศ PDF
            </button>
            <a href="#" id="exportPngBtn" class="btn btn-warning btn-lg">
              <i class="fas fa-file-image me-2"></i>ส่งออกใบประกาศ PNG
            </a>
            <div class="spinner-border text-success ms-3 d-none" id="spinner-export"></div>
          </div>
        </form>
      </div>
    </div>
    <!-- Preview Certificate -->
    <div class="card feature-card mt-4">
      <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-eye me-2"></i>ตัวอย่างใบประกาศ (Preview)</h5>
      </div>
      <div class="card-body">
      <?php
        $names_result = $conn->query('SELECT name FROM certificate_names');
        $names = [];
        if ($names_result && $names_result->num_rows > 0) {
          while ($row = $names_result->fetch_assoc()) {
            $names[] = $row['name'];
          }
        }
      ?>
      <div class="mb-3">
        <label class="form-label">เลือกชื่อสำหรับ Preview</label>
        <select id="previewSelect" class="form-select" style="max-width:300px;">
          <?php foreach ($names as $n): ?>
            <option value="<?= htmlspecialchars($n) ?>"><?= htmlspecialchars($n) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="certPreview" style="position:relative; width:100%; max-width:700px; height:400px; background:#f5f5f5; border:1px solid #ddd; margin:auto; overflow:hidden;">
        <img id="previewBg" src="<?= isset($_SESSION['bg_path']) ? $_SESSION['bg_path'] : '' ?>" alt="" style="position:absolute; left:0; top:0; width:100%; height:100%; object-fit:cover; z-index:0; <?= isset($_SESSION['bg_path']) ? 'display:block;' : 'display:none;' ?>">
        <span id="previewName" style="position:absolute; left:200px; top:300px; font-size:32px; color:#222; font-family:sans-serif; text-align:left; width:400px; z-index:1;"><?= count($names) ? htmlspecialchars($names[0]) : 'นายสมชาย ใจดี' ?></span>
      </div>
    </div>

    <!-- Names Management -->
    <div class="card feature-card mt-4">
      <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          <i class="fas fa-users me-2"></i>รายชื่อที่นำเข้า 
          <span class="badge bg-light text-dark ms-2"><?= $total_names ?> คน</span>
        </h5>
        <div>
          <a href="view_names.php" class="btn btn-sm btn-info me-2">
            <i class="fas fa-eye me-1"></i>ดูและแก้ไข
          </a>
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="delete_all_names.php" class="btn btn-sm btn-danger" onclick="return confirm('ลบรายชื่อทั้งหมด?')">
              <i class="fas fa-trash-alt me-1"></i>ลบทั้งหมด
            </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-custom">
            <thead><tr><th width="60">#</th><th>ชื่อ-นามสกุล</th><th width="120">การกระทำ</th></tr></thead>
            <tbody>
      <?php
      $result = $conn->query('SELECT id, name FROM certificate_names LIMIT 5');
      if ($result && $result->num_rows > 0) {
        $idx = 1;
        while ($row = $result->fetch_assoc()) {
          echo '<tr><td>' . $idx . '</td><td>' . htmlspecialchars($row['name']) . '</td>';
          echo '<td>';
          echo '<a href="delete_name.php?id=' . $row['id'] . '" class="btn btn-sm btn-danger btn-custom" onclick="return confirm(\'ยืนยันการลบ?\')"><i class="fas fa-trash"></i></a>';
          echo '</td></tr>';
          $idx++;
        }
      } else {
        echo '<tr><td colspan="3" class="text-center text-muted">ยังไม่มีข้อมูล</td></tr>';
      }
      ?>
        </tbody>
      </table>
      </div>
      <div class="card-footer text-muted text-center">
        <small><i class="fas fa-info-circle"></i> แสดง 5 รายการแรก <a href="view_names.php">ดูทั้งหมด →</a></small>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Spinner loading for upload/export
    document.querySelector('form[action="upload.php"]').addEventListener('submit', function() {
      document.getElementById('spinner-upload').classList.remove('d-none');
    });
    document.querySelector('form[action="export.php"]').addEventListener('submit', function() {
      document.getElementById('spinner-export').classList.remove('d-none');
    });
    // Export PNG button
    document.getElementById('exportPngBtn').addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('spinner-export').classList.remove('d-none');
      // สร้างฟอร์มส่งค่าไป export_png.php
      var form = document.createElement('form');
      form.method = 'post';
      form.action = 'export_png.php';
      form.style.display = 'none';
      // ดึงค่าจากฟอร์ม
      ['pos_x','pos_y','font_size','font_family','font_color','font_align'].forEach(function(name) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = document.getElementById(name).value;
        form.appendChild(input);
      });
      document.body.appendChild(form);
      form.submit();
    });
    // Interactive certificate preview
    function updatePreview() {
      var x = parseInt(document.getElementById('pos_x').value) || 0;
      var y = parseInt(document.getElementById('pos_y').value) || 0;
      var fontSize = parseInt(document.getElementById('font_size').value) || 32;
      var fontFamily = document.getElementById('font_family').value;
      var fontColor = document.getElementById('font_color').value;
      var fontAlign = document.getElementById('font_align').value;
      var previewName = document.getElementById('previewName');
      var previewSelect = document.getElementById('previewSelect');
      previewName.style.left = x + 'px';
      previewName.style.top = y + 'px';
      previewName.style.fontSize = fontSize + 'px';
      previewName.style.fontFamily = fontFamily;
      previewName.style.color = fontColor;
      previewName.style.textAlign = fontAlign;
      previewName.style.width = '400px';
      previewName.textContent = previewSelect ? previewSelect.value : 'นายสมชาย ใจดี';
    }
    document.getElementById('pos_x').addEventListener('input', updatePreview);
    document.getElementById('pos_y').addEventListener('input', updatePreview);
    document.getElementById('font_size').addEventListener('input', updatePreview);
    document.getElementById('font_family').addEventListener('change', updatePreview);
    document.getElementById('font_color').addEventListener('input', updatePreview);
    document.getElementById('font_align').addEventListener('change', updatePreview);
    if (document.getElementById('previewSelect')) {
      document.getElementById('previewSelect').addEventListener('change', updatePreview);
    }
    updatePreview();
    // Preview background image
    document.getElementById('bgFile').addEventListener('change', function(e) {
      var file = e.target.files[0];
      var previewBg = document.getElementById('previewBg');
      if (file) {
        var reader = new FileReader();
        reader.onload = function(ev) {
          previewBg.src = ev.target.result;
          previewBg.style.display = 'block';
        };
        reader.readAsDataURL(file);
      } else {
        previewBg.src = '';
        previewBg.style.display = 'none';
      }
    });
  </script>
  </body>