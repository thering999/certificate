<?php
require_once 'db.php';
require_once 'assets/template_gallery.class.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize template gallery
$gallery = new TemplateGallery($conn);

$alert = '';
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    unset($_SESSION['alert']);
}

$result = $conn->query('SELECT COUNT(*) as count FROM certificate_names');
$total_names = $result ? $result->fetch_assoc()['count'] : 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Generator - Drag & Drop Interface</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <!-- JSZip and FileSaver for client-side ZIP export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@100;200;300;400;500;600;700;800&family=Prompt:wght@100;200;300;400;500;600;700;800;900&family=Kanit:wght@100;200;300;400;500;600;700;800;900&family=IBM+Plex+Sans+Thai:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/style.css?v=<?= time() % 86400 ?>" rel="stylesheet">
    <link href="assets/designer.css?v=<?= time() % 86400 ?>" rel="stylesheet">
    <link href="assets/design-tips-panel.css?v=<?= time() % 86400 ?>" rel="stylesheet">
    <script>
        // Debug: Show current user_id in console on page load
        window.CURRENT_USER_ID = <?= $_SESSION['user_id'] ?>;
        console.log('🔍 Current logged-in user_id:', window.CURRENT_USER_ID);
    </script>
    <style>
        .design-area {
            position: relative;
            background-size: cover;
            background-position: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            cursor: crosshair;
        }
        
        .draggable-text {
            position: absolute;
            cursor: move;
            border: 2px dashed transparent;
            padding: 5px;
            min-width: 100px;
            background: rgba(255,255,255,0.9);
            border-radius: 4px;
            user-select: none;
            transition: all 0.2s;
        }
        
        .draggable-text:hover {
            border: 2px dashed #007bff;
            background: rgba(255,255,255,0.95);
        }
        
        .draggable-text.active {
            border: 2px solid #007bff;
            background: rgba(255,255,255,1);
            box-shadow: 0 0 10px rgba(0,123,255,0.3);
        }
        
        .text-toolbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .preview-container {
            position: relative;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .feature-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .feature-card:hover {
            transform: translateY(-2px);
        }
        
        /* Compact card design */
        .card-header {
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .fs-7 {
            font-size: 0.875rem;
        }
        
        /* History improvements */
        .history-item {
            background: white;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            font-size: 11px;
            line-height: 1.3;
        }
        
        @media (max-width: 992px) {
            .col-lg-8, .col-lg-4 {
                margin-bottom: 20px;
            }
            
            .design-area {
                height: 500px !important;
            }
        }
        
        @media (max-width: 768px) {
            .design-area {
                height: 400px !important;
            }
            
            .draggable-text {
                min-width: 80px;
                font-size: 12px !important;
            }
            
            .card-body {
                padding: 0.75rem !important;
            }
            
            .preview-container {
                height: 150px !important;
            }
        }
        
        .btn-toolbar {
            gap: 10px;
        }
        
        .empty-state {
            background: rgba(255,255,255,0.9);
            border-radius: 10px;
            padding: 30px;
            backdrop-filter: blur(5px);
        }
        
        .template-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .template-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .history-item {
            cursor: pointer;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            overflow: hidden;
            transition: all 0.2s;
        }
        
        .history-item:hover {
            border-color: #007bff;
            transform: scale(1.02);
        }
        
        .history-thumbnail {
            height: 60px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-certificate me-2"></i>Certificate Generator Pro
            </a>
            <div class="navbar-nav ms-auto">
                <span class="badge bg-light text-dark px-3 py-2 me-3" style="font-size: 11px;">
                    <i class="fas fa-user-circle me-2"></i>
                    <?= isset($_SESSION['role']) ? ($_SESSION['role'] === 'admin' ? '👑 Admin' : '👤 User') : '👤 User' ?>
                    : <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
                </span>
                <a href="index.php" class="nav-link"><i class="fas fa-home me-1"></i>หน้าหลัก</a>
                <a href="dashboard.php" class="nav-link"><i class="fas fa-th-large me-1"></i>Dashboard</a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-3 px-3">
        <?php if ($alert): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $alert ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Main Interface -->
        <div class="d-flex flex-column gap-0">
            <!-- Top Row: Design Area + Right Sidebar -->
            <div class="row g-0">
                <!-- Design Area - LEFT SIDE -->
                <div class="col-lg-8">
                    <div class="card feature-card w-100">
                        <div class="card-header bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-palette me-2"></i>พื้นที่ออกแบบใบประกาศ
                                </h5>
                                <div class="btn-group">
                                    <button class="btn btn-success btn-sm" onclick="addNewText()">
                                        <i class="fas fa-plus me-1"></i>เพิ่มข้อความ
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" onclick="showTemplateGallery()">
                                        <i class="fas fa-images me-1"></i>Template Gallery
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="exportCertificates()">
                                        <i class="fas fa-download me-1"></i>ส่งออก PNG
                                    </button>
                                    <button class="btn btn-success btn-sm" onclick="exportDesignsZip()">
                                        <i class="fas fa-file-archive me-1"></i>ส่งออก ZIP
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            <div id="design-area" class="design-area position-relative" style="height: 600px; width: 100%; border: 2px solid #dee2e6; border-radius: 8px; overflow: hidden; background-image: url('<?= isset($_SESSION['bg_path']) ? $_SESSION['bg_path'] : 'assets/default_bg.jpg' ?>');">
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted" id="empty-state">
                                    <div class="text-center">
                                        <i class="fas fa-mouse-pointer fa-3x mb-3"></i>
                                        <h5>คลิก "เพิ่มข้อความ" เพื่อเริ่มออกแบบ</h5>
                                        <p>ลากเพื่อย้ายตำแหน่ง | ดับเบิลคลิกเพื่อแก้ไข</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar: Upload + History + Preview -->
                <div class="col-lg-4" style="padding-left: 0;">
                    <div class="d-flex flex-column gap-2" style="height: 100%;">
                        <!-- ⬆️ Upload Section - TOP -->
                        <div class="card feature-card shadow-sm" style="flex-shrink: 0;">
                            <div class="card-header bg-info text-white py-1">
                                <h6 class="mb-0 fs-7"><i class="fas fa-upload me-1"></i>นำเข้าข้อมูล / พื้นหลัง</h6>
                            </div>
                            <div class="card-body p-1">
                                <!-- CSV Upload -->
                                <form action="upload.php" method="post" enctype="multipart/form-data">
                                    <div class="mb-1">
                                        <label class="form-label small mb-1" style="font-size: 10px; color: #333; font-weight: 600;">
                                            📄 ไฟล์รายชื่อ (.csv)
                                            <a href="download_sample_csv.php" class="text-decoration-none float-end" style="font-size: 9px;" title="ดาวน์โหลดตัวอย่าง">
                                                <i class="fas fa-download"></i> ตัวอย่าง
                                            </a>
                                        </label>
                                        <input type="file" class="form-control form-control-sm" name="excelFile" accept=".csv">
                                    </div>
                                    <button type="submit" class="btn btn-info btn-sm w-100 mb-1" style="font-size: 10px; padding: 5px 2px;">
                                        <i class="fas fa-upload me-1"></i>อัปโหลด CSV
                                    </button>
                                </form>
                                <div class="text-center mb-1">
                                    <span class="badge bg-info" style="font-size: 9px;"><?= $total_names ?> คน</span>
                                </div>
                                
                                <!-- Background Upload -->
                                <form id="bgUploadForm" enctype="multipart/form-data">
                                    <div class="mb-1">
                                        <label class="form-label small mb-1" style="font-size: 10px; color: #333; font-weight: 600;">🖼️ พื้นหลังใหม่</label>
                                        <input type="file" id="bgImageInput" class="form-control form-control-sm" name="bg_image" accept=".jpg,.jpeg,.png,.gif,.webp">
                                    </div>
                                    <button type="button" id="bgUploadBtn" class="btn btn-warning btn-sm w-100" onclick="uploadBackgroundImage()" style="font-size: 10px; padding: 5px 2px;">
                                        <i class="fas fa-upload me-1"></i>เปลี่ยนพื้นหลัง
                                    </button>
                                </form>
                                <div id="bgUploadStatus" class="mt-1"></div>
                            </div>
                        </div>

                        <!-- (Removed duplicate Design Tips in right sidebar - kept bottom Tips under design area) -->

                        <!-- � Design History -->
                        <div class="card feature-card shadow-sm" style="flex-shrink: 0;">
                            <div class="card-header bg-warning text-dark py-1">
                                <h6 class="mb-0 fs-7"><i class="fas fa-history me-1"></i>ประวัติ Design</h6>
                            </div>
                            <div class="card-body p-1" style="max-height: 150px; overflow-y: auto;">
                                <div id="designHistoryList" class="list-group list-group-flush">
                                    <div class="text-center text-muted py-3">
                                        <small><i class="fas fa-inbox"></i> ไม่มี Design ที่บันทึกไว้</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- �👁️ Preview -->
                        <div class="card feature-card shadow-sm" style="flex-shrink: 0;">
                            <div class="card-header bg-success text-white py-1">
                                <h6 class="mb-0 fs-7"><i class="fas fa-eye me-1"></i>ตัวอย่าง</h6>
                            </div>
                            <div class="card-body p-1">
                                <?php
                                // ดึงรายชื่อทั้งหมด ไม่เพียง 10 คน - ใช้ Prepared Statement
                                $user_id = $_SESSION['user_id'];
                                $names_result = $conn->prepare("SELECT id, name FROM certificate_names WHERE user_id = ? ORDER BY id ASC");
                                $names_result->bind_param("i", $user_id);
                                $names_result->execute();
                                $names_result = $names_result->get_result();
                                $names = [];
                                if ($names_result && $names_result->num_rows > 0) {
                                    while ($row = $names_result->fetch_assoc()) {
                                        $names[] = $row['name'];
                                    }
                                }
                                ?>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label" style="font-size: 10px; font-weight: 600; color: #333; margin-bottom: 0;">เลือกชื่อ Preview (<?php echo count($names); ?> คน)</label>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="showNamesManagement()" style="font-size: 9px; padding: 0.2rem 0.5rem;">
                                            <i class="fas fa-edit me-1"></i>จัดการรายชื่อ
                                        </button>
                                    </div>
                                    <select id="previewSelect" class="form-select form-select-sm" style="font-size: 10px;">
                                        <?php if (empty($names)): ?>
                                            <option>นายสมชาย ใจดี</option>
                                        <?php else: ?>
                                            <?php foreach ($names as $name): ?>
                                            <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                
                                <div class="preview-container border rounded" style="height: 280px; background-color: #f8f9fa; overflow: visible; position: relative; margin-top: 0.5rem;">
                                    <div id="preview-area" class="w-100 position-relative" style="height: 280px; background-image: url('<?= isset($_SESSION['bg_path']) ? $_SESSION['bg_path'] : 'assets/default_bg.jpg' ?>'); background-size: cover; background-position: center; overflow: visible; display: flex; align-items: center; justify-content: center;">
                                    </div>
                                </div>
                                
                                <!-- ปุ่ม Save Design -->
                                <div class="mt-2">
                                    <button type="button" class="btn btn-success btn-sm w-100" onclick="saveDesign()">
                                        <i class="fas fa-save me-1"></i>บันทึก Design
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- � Design Tips Panel -->
                        <div class="card feature-card shadow-sm" style="flex-shrink: 0;">
                            <div class="card-header bg-primary text-white py-1">
                        <!-- �🖼️ Background Examples (BOTTOM RIGHT) -->
                        <div class="card feature-card shadow-sm border-success border-2 flex-shrink: 0;" style="flex-shrink: 0;">
                            <div class="card-header bg-success text-white py-1">
                                <h6 class="mb-0 fs-7"><i class="fas fa-images me-1"></i>พื้นหลัง</h6>
                            </div>
                            <div class="card-body p-2" style="max-height: 140px; overflow-y: auto; background-color: #f8f9fa;">
                                <div class="row g-2">
                                <?php
                                // ดึงไฟล์ตัวอย่างจากโฟลเดอร์ templete
                                $template_dir = 'templete';
                                $template_files = [];
                                
                                if (is_dir($template_dir)) {
                                    $files = array_diff(scandir($template_dir), ['.', '..']);
                                    foreach ($files as $file) {
                                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                            $template_files[] = $file;
                                        }
                                    }
                                }
                                
                                if (count($template_files) > 0):
                                    foreach ($template_files as $template_file):
                                        $template_path = $template_dir . '/' . $template_file;
                                        $sanitized_name = htmlspecialchars(pathinfo($template_file, PATHINFO_FILENAME));
                                ?>
                                <div class="col-4">
                                    <div class="card bg-light border-0 shadow-sm template-card" onclick="selectBackgroundTemplate('<?= addslashes($template_path) ?>')" style="cursor: pointer; transition: all 0.3s;">
                                        <div style="height: 70px; overflow: hidden; border-radius: 8px 8px 0 0; background: #f5f5f5;">
                                            <img src="<?= $template_path ?>" alt="<?= $sanitized_name ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                        <div class="card-body p-1">
                                            <small class="text-center d-block text-truncate" title="<?= $sanitized_name ?>" style="font-size: 7px; line-height: 1.1; color: #333;">
                                                ✓<?= substr($sanitized_name, 0, 8) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                    endforeach;
                                else:
                                ?>
                                <div class="col-12">
                                    <small class="text-muted d-block text-center" style="font-size: 8px;">ไม่มีรูปตัวอย่าง</small>
                                </div>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>

                <!-- REMOVED: Old History and Preview sections were here -->

    <!-- Template Gallery Modal -->
    <div class="modal fade" id="templateGalleryModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-images me-2"></i>Template Gallery - เลือกแบบใบประกาศ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Government Templates -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2">
                                <i class="fas fa-university me-2"></i>แบบราชการ / ทางการ
                            </h6>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="template-card card h-100" onclick="loadTemplateFromGallery('gov_standard')">
                                <div class="card-img-top bg-gradient" style="height: 150px; background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%); position: relative;">
                                    <div class="text-white text-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px;">
                                        <div class="fw-bold">กระทรวงศึกษาธิการ</div>
                                        <div style="font-size: 16px; margin: 8px 0;">ใบประกาศเกียรติคุณ</div>
                                        <div>ขอมอบให้แก่<br><span class="text-warning">ชื่อ-นามสกุล</span></div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">ใบประกาศราชการมาตรฐาน</h6>
                                    <p class="card-text small text-muted">เหมาะสำหรับงานทางการและรัฐบาล</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="template-card card h-100" onclick="loadTemplateFromGallery('gov_formal')">
                                <div class="card-img-top bg-gradient" style="height: 150px; background: linear-gradient(135deg, #2e7d32 0%, #66bb6a 100%); position: relative;">
                                    <div class="text-white text-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px;">
                                        <div class="fw-bold">หลักสูตร</div>
                                        <div style="font-size: 16px; margin: 8px 0;">วิทยาการข้อมูลและอินเทอร์เน็ต</div>
                                        <div><span class="text-warning">ชื่อ-นามสกุล</span><br>ผ่านการอบรม</div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">ใบประกาศการอบรม</h6>
                                    <p class="card-text small text-muted">สำหรับหลักสูตรและการฝึกอบรม</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="template-card card h-100" onclick="loadTemplateFromGallery('gov_achievement')">
                                <div class="card-img-top bg-gradient" style="height: 150px; background: linear-gradient(135deg, #c2185b 0%, #e91e63 100%); position: relative;">
                                    <div class="text-white text-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px;">
                                        <div style="font-size: 16px; margin: 8px 0;">รางวัลความสำเร็จ</div>
                                        <div><span class="text-warning">ชื่อ-นามสกุล</span></div>
                                        <div class="fw-bold">ผู้ปฏิบัติงานดีเด่น</div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">ใบประกาศรางวัล</h6>
                                    <p class="card-text small text-muted">สำหรับการมอบรางวัลและความสำเร็จ</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modern Templates -->
                        <div class="col-12 mt-4">
                            <h6 class="text-success border-bottom pb-2">
                                <i class="fas fa-star me-2"></i>แบบโมเดิร์น / สร้างสรรค์
                            </h6>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="template-card card h-100" onclick="loadTemplateFromGallery('modern_tech')">
                                <div class="card-img-top bg-gradient" style="height: 150px; background: linear-gradient(135deg, #1e88e5 0%, #42a5f5 100%); position: relative;">
                                    <div class="text-white text-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px;">
                                        <div style="font-size: 18px; font-weight: bold;">CERTIFICATE</div>
                                        <div class="my-2">of Technology Excellence</div>
                                        <div><span class="text-warning">ชื่อ-นามสกุล</span></div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">ใบประกาศเทคโนโลยี</h6>
                                    <p class="card-text small text-muted">เหมาะสำหรับงานด้าน IT และเทคโนโลยี</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="template-card card h-100" onclick="loadTemplateFromGallery('modern_creative')">
                                <div class="card-img-top bg-gradient" style="height: 150px; background: linear-gradient(135deg, #9c27b0 0%, #ba68c8 100%); position: relative;">
                                    <div class="text-white text-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px;">
                                        <div style="font-size: 16px;">🎨 CREATIVE AWARD 🎨</div>
                                        <div class="my-2"><span class="text-warning">ชื่อ-นามสกุล</span></div>
                                        <div>นักออกแบบดีเด่น</div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">ใบประกาศสร้างสรรค์</h6>
                                    <p class="card-text small text-muted">สำหรับงานศิลปะและการออกแบบ</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="template-card card h-100" onclick="loadTemplateFromGallery('modern_business')">
                                <div class="card-img-top bg-gradient" style="height: 150px; background: linear-gradient(135deg, #37474f 0%, #607d8b 100%); position: relative;">
                                    <div class="text-white text-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px;">
                                        <div class="fw-bold">BUSINESS EXCELLENCE</div>
                                        <div class="my-2">Certificate of Achievement</div>
                                        <div><span class="text-warning">ชื่อ-นามสกุล</span></div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">ใบประกาศธุรกิจ</h6>
                                    <p class="card-text small text-muted">เหมาะสำหรับองค์กรและธุรกิจ</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Elegant Templates -->
                        <div class="col-12 mt-4">
                            <h6 class="text-warning border-bottom pb-2">
                                <i class="fas fa-crown me-2"></i>แบบหรูหรา / พิเศษ
                            </h6>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="template-card card h-100" onclick="loadTemplateFromGallery('elegant_gold')">
                                <div class="card-img-top bg-gradient" style="height: 150px; background: linear-gradient(135deg, #bf9000 0%, #ffcc02 100%); position: relative;">
                                    <div class="text-dark text-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px;">
                                        <div style="font-size: 16px;">✨ GOLDEN AWARD ✨</div>
                                        <div class="my-2 fw-bold">ใบประกาศเกียรติยศ</div>
                                        <div><span style="color: #8b4513;">ชื่อ-นามสกุล</span></div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">ใบประกาศทองคำ</h6>
                                    <p class="card-text small text-muted">สำหรับรางวัลพิเศษและเกียรติยศสูง</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="template-card card h-100" onclick="loadTemplateFromGallery('elegant_royal')">
                                <div class="card-img-top bg-gradient" style="height: 150px; background: linear-gradient(135deg, #4a148c 0%, #8e24aa 100%); position: relative;">
                                    <div class="text-white text-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 11px;">
                                        <div>👑 ROYAL CERTIFICATE 👑</div>
                                        <div class="my-2">ใบประกาศเกียरติยศสูงสุด</div>
                                        <div><span class="text-warning">ชื่อ-นามสกุล</span></div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">ใบประกาศหลวง</h6>
                                    <p class="card-text small text-muted">สำหรับเกียรติยศและความสำเร็จสูงสุด</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="template-card card h-100" onclick="loadTemplateFromGallery('elegant_classic')">
                                <div class="card-img-top bg-gradient" style="height: 150px; background: linear-gradient(135deg, #5d4037 0%, #8d6e63 100%); position: relative;">
                                    <div class="text-white text-center" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 12px;">
                                        <div>🏛️ CLASSIC HONOR 🏛️</div>
                                        <div class="my-2">Certificate of Merit</div>
                                        <div><span class="text-warning">ชื่อ-นามสกุล</span></div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">ใบประกาศคลาสสิค</h6>
                                    <p class="card-text small text-muted">สำหรับงานที่ต้องการความเป็นทางการ</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Text Edit Modal -->
    <div class="modal fade" id="textEditModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไขข้อความ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ข้อความ</label>
                        <input type="text" id="editTextContent" class="form-control" placeholder="ใช้ {name} สำหรับตำแหน่งชื่อ">
                        <div class="form-text">เช่น: {name} หรือ นาย/นาง {name}</div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label">ขนาดฟอนต์</label>
                            <input type="number" id="editFontSize" class="form-control" min="10" max="100" value="32">
                        </div>
                        <div class="col-6">
                            <label class="form-label">สีข้อความ</label>
                            <input type="color" id="editFontColor" class="form-control form-control-color" value="#000000">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-4">
                            <label class="form-label">ฟอนต์</label>
                            <select id="editFontFamily" class="form-select">
                                <optgroup label="ฟอนต์ราชการ / ทางการ">
                                    <option value="Sarabun">Sarabun (สระบุรี - ราชการใหม่)</option>
                                    <option value="TH Sarabun New">TH Sarabun New (ฟอนต์ราชการ)</option>
                                    <option value="Angsana New">Angsana New (อังสนา นิว)</option>
                                    <option value="Cordia New">Cordia New (คอร์เดีย นิว)</option>
                                </optgroup>
                                <optgroup label="ฟอนต์ไทยสวย / โมเดิร์น">
                                    <option value="Prompt">Prompt (พรอมพ์ท์ - สวยโมเดิร์น)</option>
                                    <option value="Kanit">Kanit (กนิษฐ์ - เรียบหรู)</option>
                                    <option value="IBM Plex Sans Thai">IBM Plex Sans Thai (ไอบีเอ็ม - เทคโนโลยี)</option>
                                </optgroup>
                                <optgroup label="ฟอนต์ไทยคลาสสิค">
                                    <option value="DilleniaUPC">DilleniaUPC (ดิลเลเนีย - สวยงาม)</option>
                                    <option value="EucrosiaUPC">EucrosiaUPC (ยูโครเซีย - หรูหรา)</option>
                                    <option value="IrisUPC">IrisUPC (ไอริส - นุ่มนวล)</option>
                                    <option value="JasmineUPC">JasmineUPC (จัสมิน - อ่อนหวาน)</option>
                                    <option value="KodchiangUPC">KodchiangUPC (โกดเจียง - โบราณ)</option>
                                    <option value="LilyUPC">LilyUPC (ลิลลี่ - สดใส)</option>
                                </optgroup>
                                <optgroup label="ฟอนต์อังกฤษ">
                                    <option value="Times New Roman">Times New Roman (ทางการ)</option>
                                    <option value="Georgia">Georgia (สง่างาม)</option>
                                    <option value="Arial">Arial (เรียบง่าย)</option>
                                    <option value="Verdana">Verdana (ชัดเจน)</option>
                                    <option value="Tahoma">Tahoma (คมชัด)</option>
                                    <option value="Impact">Impact (หนา/โดดเด่น)</option>
                                    <option value="Trebuchet MS">Trebuchet MS (โมเดิร์น)</option>
                                    <option value="Comic Sans MS">Comic Sans MS (น่ารัก)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label">น้ำหนักฟอนต์</label>
                            <select id="editFontWeight" class="form-select">
                                <option value="100">บางมาก (100)</option>
                                <option value="200">บาง (200)</option>
                                <option value="300">เบา (300)</option>
                                <option value="400" selected>ปกติ (400)</option>
                                <option value="500">กึ่งหนา (500)</option>
                                <option value="600">หนาพอดี (600)</option>
                                <option value="700">หนา (700)</option>
                                <option value="800">หนามาก (800)</option>
                                <option value="900">หนาสุด (900)</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label">การจัดตำแหน่ง</label>
                            <select id="editTextAlign" class="form-select">
                                <option value="left">ซ้าย</option>
                                <option value="center">กลาง</option>
                                <option value="right">ขวา</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-6">
                            <label class="form-label">การตกแต่งข้อความ</label>
                            <select id="editTextDecoration" class="form-select">
                                <option value="none">ปกติ</option>
                                <option value="underline">ขีดเส้นใต้</option>
                                <option value="overline">ขีดเส้นบน</option>
                                <option value="line-through">ขีดฆ่า</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">เงาข้อความ</label>
                            <select id="editTextShadow" class="form-select">
                                <option value="none">ไม่มีเงา</option>
                                <option value="light">เงาอ่อน</option>
                                <option value="medium">เงาปานกลาง</option>
                                <option value="strong">เงาเข้ม</option>
                                <option value="glow">เรืองแสง</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="deleteTextBtn">ลบข้อความ</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="saveTextBtn">บันทึก</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let textElements = [];
        let currentEditingElement = null;
        let elementCounter = 0;

        // Upload Background Image with AJAX
        function uploadBackgroundImage() {
            const form = document.getElementById('bgUploadForm');
            const input = document.getElementById('bgImageInput');
            const statusDiv = document.getElementById('bgUploadStatus');
            const uploadBtn = document.getElementById('bgUploadBtn');
            
            if (!input.files || input.files.length === 0) {
                statusDiv.innerHTML = '<div class="alert alert-warning alert-sm py-1 mb-0">กรุณาเลือกไฟล์ก่อน</div>';
                return;
            }
            
            // Validate file size
            const file = input.files[0];
            const maxSize = 50 * 1024 * 1024; // 50MB
            if (file.size > maxSize) {
                statusDiv.innerHTML = '<div class="alert alert-danger alert-sm py-1 mb-0">ไฟล์ใหญ่เกินไป (จำกัดที่ 50MB)</div>';
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                statusDiv.innerHTML = '<div class="alert alert-danger alert-sm py-1 mb-0">ประเภทไฟล์ไม่ถูกต้อง (JPG, PNG, GIF, WebP เท่านั้น)</div>';
                return;
            }
            
            // Show uploading status
            uploadBtn.disabled = true;
            statusDiv.innerHTML = '<div class="alert alert-info alert-sm py-1 mb-0"><i class="fas fa-spinner fa-spin me-1"></i>กำลังอัปโหลด...</div>';
            
            const formData = new FormData();
            formData.append('bg_image', file);
            
            fetch('upload_bg.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                // Extract success message from response
                if (result.includes('สำเร็จ')) {
                    // Update background immediately
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const imgUrl = e.target.result;
                        document.getElementById('design-area').style.backgroundImage = `url('${imgUrl}')`;
                        document.getElementById('preview-area').style.backgroundImage = `url('${imgUrl}')`;
                        
                        // Store in session storage for persistence
                        sessionStorage.setItem('bgImage', imgUrl);
                        
                        statusDiv.innerHTML = '<div class="alert alert-success alert-sm py-1 mb-0"><i class="fas fa-check-circle me-1"></i>อัปโหลดสำเร็จ!</div>';
                        input.value = '';
                        
                        // Clear status after 3 seconds
                        setTimeout(() => {
                            statusDiv.innerHTML = '';
                        }, 3000);
                    };
                    reader.readAsDataURL(file);
                } else if (result.includes('ไม่')) {
                    statusDiv.innerHTML = '<div class="alert alert-danger alert-sm py-1 mb-0"><i class="fas fa-exclamation-circle me-1"></i>' + result.substring(0, 50) + '</div>';
                } else {
                    statusDiv.innerHTML = '<div class="alert alert-danger alert-sm py-1 mb-0"><i class="fas fa-exclamation-circle me-1"></i>อัปโหลดไม่สำเร็จ</div>';
                }
                uploadBtn.disabled = false;
            })
            .catch(error => {
                statusDiv.innerHTML = '<div class="alert alert-danger alert-sm py-1 mb-0"><i class="fas fa-exclamation-circle me-1"></i>เกิดข้อผิดพลาด: ' + error.message + '</div>';
                uploadBtn.disabled = false;
            });
        }

        // เพิ่มข้อความใหม่
        function addNewText() {
            elementCounter++;
            const newElement = {
                id: 'text_' + elementCounter,
                content: '{name}',
                x: 100 + (elementCounter * 20),
                y: 100 + (elementCounter * 30),
                fontSize: 40,
                fontFamily: 'Sarabun',
                fontWeight: '600',
                textDecoration: 'none',
                textShadow: 'medium',
                color: '#1a237e',
                align: 'center'
            };
            
            textElements.push(newElement);
            renderTextElements();
            updatePreview();
            
            // ซ่อน empty state
            document.getElementById('empty-state').style.display = 'none';
        }

        // แสดงข้อความทั้งหมดในพื้นที่ออกแบบ
        function renderTextElements(customName = null) {
            const container = document.getElementById('design-area');
            const displayName = customName || document.getElementById('previewSelect')?.value || 'ตัวอย่าง';
            
            // ลบ text elements เก่า
            container.querySelectorAll('.draggable-text').forEach(el => el.remove());
            
            textElements.forEach(element => {
                const textDiv = document.createElement('div');
                textDiv.className = 'draggable-text';
                textDiv.id = element.id;
                textDiv.textContent = element.content.replace(/{name}/g, displayName);
                // สร้าง text shadow CSS
                let shadowCSS = 'none';
                switch(element.textShadow || 'none') {
                    case 'light': shadowCSS = '1px 1px 2px rgba(0,0,0,0.3)'; break;
                    case 'medium': shadowCSS = '2px 2px 4px rgba(0,0,0,0.5)'; break;
                    case 'strong': shadowCSS = '3px 3px 6px rgba(0,0,0,0.7)'; break;
                    case 'glow': shadowCSS = '0 0 10px rgba(255,255,255,0.8), 0 0 20px rgba(255,255,255,0.6)'; break;
                }
                
                textDiv.style.cssText = `
                    left: ${element.x}px;
                    top: ${element.y}px;
                    font-size: ${element.fontSize}px;
                    font-family: '${element.fontFamily}', sans-serif;
                    font-weight: ${element.fontWeight || '400'};
                    text-decoration: ${element.textDecoration || 'none'};
                    text-shadow: ${shadowCSS};
                    color: ${element.color};
                    text-align: ${element.align};
                    position: absolute;
                    cursor: move;
                    white-space: nowrap;
                    user-select: none;
                `;
                
                // เพิ่ม delete button overlay
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'delete-text-btn';
                deleteBtn.innerHTML = '×';
                deleteBtn.style.cssText = `
                    position: absolute;
                    top: -10px;
                    right: -10px;
                    width: 24px;
                    height: 24px;
                    padding: 0;
                    background: #dc3545;
                    border: 2px solid white;
                    color: white;
                    border-radius: 50%;
                    cursor: pointer;
                    font-size: 18px;
                    line-height: 1;
                    display: none;
                    z-index: 10;
                    font-weight: bold;
                    transition: all 0.2s;
                `;
                
                deleteBtn.onclick = function(e) {
                    e.stopPropagation();
                    if (confirm('ต้องการลบข้อความนี้หรือไม่?')) {
                        textElements = textElements.filter(el => el.id !== element.id);
                        renderTextElements();
                        updatePreview();
                        if (textElements.length === 0) {
                            document.getElementById('empty-state').style.display = 'flex';
                        }
                    }
                };
                
                deleteBtn.onmouseover = function() {
                    this.style.background = '#bb2d28';
                    this.style.boxShadow = '0 0 8px rgba(220, 53, 69, 0.5)';
                };
                
                deleteBtn.onmouseout = function() {
                    this.style.background = '#dc3545';
                    this.style.boxShadow = 'none';
                };
                
                textDiv.appendChild(deleteBtn);
                
                // Show delete button on hover
                textDiv.addEventListener('mouseenter', function() {
                    deleteBtn.style.display = 'block';
                });
                
                textDiv.addEventListener('mouseleave', function() {
                    deleteBtn.style.display = 'none';
                });
                
                // เพิ่ม double click เพื่อแก้ไข
                textDiv.addEventListener('dblclick', function() {
                    editTextElement(element.id);
                });
                
                // เพิ่ม right click context menu
                textDiv.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                    if (confirm('ต้องการลบข้อความนี้หรือไม่?')) {
                        textElements = textElements.filter(el => el.id !== element.id);
                        renderTextElements();
                        updatePreview();
                        if (textElements.length === 0) {
                            document.getElementById('empty-state').style.display = 'flex';
                        }
                    }
                });
                
                // เพิ่ม drag functionality
                makeDraggable(textDiv, element);
                
                container.appendChild(textDiv);
            });
        }

        // ทำให้ element สามารถลากได้
        function makeDraggable(element, dataElement) {
            let isDragging = false;
            let startX, startY, initialX, initialY;
            
            element.addEventListener('mousedown', function(e) {
                // Don't drag if clicking on delete button
                if (e.target.classList.contains('delete-text-btn')) {
                    return;
                }
                
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                initialX = dataElement.x;
                initialY = dataElement.y;
                
                element.classList.add('active');
                
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
                
                e.preventDefault();
            });
            
            function onMouseMove(e) {
                if (!isDragging) return;
                
                const deltaX = e.clientX - startX;
                const deltaY = e.clientY - startY;
                
                dataElement.x = Math.max(0, Math.min(800, initialX + deltaX));
                dataElement.y = Math.max(0, Math.min(500, initialY + deltaY));
                
                element.style.left = dataElement.x + 'px';
                element.style.top = dataElement.y + 'px';
                
                updatePreview();
            }
            
            function onMouseUp() {
                isDragging = false;
                element.classList.remove('active');
                
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);
            }
        }

        // แก้ไขข้อความ
        function editTextElement(elementId) {
            const element = textElements.find(el => el.id === elementId);
            if (!element) return;
            
            currentEditingElement = element;
            
            document.getElementById('editTextContent').value = element.content;
            document.getElementById('editFontSize').value = element.fontSize;
            document.getElementById('editFontColor').value = element.color;
            document.getElementById('editFontFamily').value = element.fontFamily;
            document.getElementById('editFontWeight').value = element.fontWeight || '400';
            document.getElementById('editTextDecoration').value = element.textDecoration || 'none';
            document.getElementById('editTextShadow').value = element.textShadow || 'none';
            document.getElementById('editTextAlign').value = element.align;
            
            new bootstrap.Modal(document.getElementById('textEditModal')).show();
        }

        // บันทึกการแก้ไข
        document.getElementById('saveTextBtn').addEventListener('click', function() {
            if (!currentEditingElement) return;
            
            currentEditingElement.content = document.getElementById('editTextContent').value;
            currentEditingElement.fontSize = parseInt(document.getElementById('editFontSize').value);
            currentEditingElement.color = document.getElementById('editFontColor').value;
            currentEditingElement.fontFamily = document.getElementById('editFontFamily').value;
            currentEditingElement.fontWeight = document.getElementById('editFontWeight').value;
            currentEditingElement.textDecoration = document.getElementById('editTextDecoration').value;
            currentEditingElement.textShadow = document.getElementById('editTextShadow').value;
            currentEditingElement.align = document.getElementById('editTextAlign').value;
            
            renderTextElements();
            updatePreview();
            
            bootstrap.Modal.getInstance(document.getElementById('textEditModal')).hide();
        });

        // ลบข้อความ
        document.getElementById('deleteTextBtn').addEventListener('click', function() {
            if (!currentEditingElement) return;
            
            textElements = textElements.filter(el => el.id !== currentEditingElement.id);
            renderTextElements();
            updatePreview();
            
            if (textElements.length === 0) {
                document.getElementById('empty-state').style.display = 'flex';
            }
            
            bootstrap.Modal.getInstance(document.getElementById('textEditModal')).hide();
        });

        // อัปเดต preview - ให้ mirror design area ด้านซ้าย 100%
        function updatePreview() {
            const previewArea = document.getElementById('preview-area');
            const previewName = document.getElementById('previewSelect') ? document.getElementById('previewSelect').value : 'ตัวอย่าง';
            
            console.log('updatePreview called:', {
                textElementsCount: textElements.length,
                previewName: previewName,
                previewAreaExists: !!previewArea
            });
            
            if (!previewArea) {
                console.error('preview-area not found!');
                return;
            }
            
            // อัปเดต design area ด้วย
            renderTextElements(previewName);
            
            // ลบ preview elements เก่า
            previewArea.querySelectorAll('.preview-text').forEach(el => el.remove());
            
            // คำนวณ scale จากขนาด preview container เทียบกับ design canvas (800x600)
            // Preview ต้อง mirror design area พอดี ไม่มี offset centering
            const previewWidth = previewArea.offsetWidth || 280;
            const previewHeight = previewArea.offsetHeight || 280;
            const designCanvasWidth = 800;
            const designCanvasHeight = 600;
            
            // Scale เพื่อให้ preview fit ในขนาดของ container
            const scale = Math.min(previewWidth / designCanvasWidth, previewHeight / designCanvasHeight);
            
            console.log(`Preview scale: ${scale} (preview: ${previewWidth}x${previewHeight}, design: ${designCanvasWidth}x${designCanvasHeight})`);
            
            // เพิ่ม preview elements ใหม่ - ใช้ตำแหน่ง x,y เดียวกับ design area แล้ว scale ลง
            textElements.forEach((element, index) => {
                console.log(`Adding text element ${index}:`, element.content);
                const textDiv = document.createElement('div');
                textDiv.className = 'preview-text';
                textDiv.textContent = element.content.replace(/{name}/g, previewName);
                
                // สร้าง text shadow CSS สำหรับ preview
                let previewShadow = 'none';
                switch(element.textShadow || 'none') {
                    case 'light': previewShadow = '0.3px 0.3px 0.6px rgba(0,0,0,0.3)'; break;
                    case 'medium': previewShadow = '0.6px 0.6px 1.2px rgba(0,0,0,0.5)'; break;
                    case 'strong': previewShadow = '0.9px 0.9px 1.8px rgba(0,0,0,0.7)'; break;
                    case 'glow': previewShadow = '0 0 3px rgba(255,255,255,0.8), 0 0 6px rgba(255,255,255,0.6)'; break;
                }
                
                // ตำแหน่งใน preview = ตำแหน่งใน design * scale (ไม่มี offset)
                const previewX = element.x * scale;
                const previewY = element.y * scale;
                
                textDiv.style.cssText = `
                    position: absolute;
                    left: ${previewX}px;
                    top: ${previewY}px;
                    font-size: ${element.fontSize * scale}px;
                    font-family: '${element.fontFamily}', sans-serif;
                    font-weight: ${element.fontWeight || '400'};
                    text-decoration: ${element.textDecoration || 'none'};
                    text-shadow: ${previewShadow};
                    color: ${element.color};
                    text-align: ${element.align};
                    pointer-events: none;
                    white-space: nowrap;
                    overflow: visible;
                    display: inline-block;
                    min-width: max-content;
                `;
                previewArea.appendChild(textDiv);
            });
            
            console.log('✓ Preview updated to match design area');
        }

        // ส่งออกใบประกาศ
        function exportCertificates() {
            if (textElements.length === 0) {
                alert('กรุณาเพิ่มข้อความก่อนส่งออก');
                return;
            }

            // ดึงรายชื่อทั้งหมด
            const previewSelect = document.getElementById('previewSelect');
            const names = Array.from(previewSelect.options).map(opt => opt.value).filter(v => v);
            
            if (names.length === 0) {
                alert('ไม่มีชื่อให้ส่งออก กรุณา Upload CSV ก่อน');
                return;
            }

            // ถามว่า export ทั้งหมด หรือเฉพาะชื่อที่เลือก
            const exportAll = confirm(`พบ ${names.length} ชื่อ\n\nต้องการส่งออก:\n✓ OK = ทั้งหมด\n✗ Cancel = เฉพาะชื่อที่เลือก`);
            
            const namesToExport = exportAll ? names : [document.getElementById('previewSelect').value];
            
            // สร้าง ZIP เพื่อเก็บไฟล์หลายๆ ตัว (ถ้าเป็น modern browser ที่ support)
            exportMultipleCertificates(namesToExport);
        }

        // ส่งออก certificate หลายๆ ชื่อ
        async function exportMultipleCertificates(names) {
            let exported = 0;
            const total = names.length;
            
            for (let i = 0; i < names.length; i++) {
                const name = names[i];
                
                // สร้าง canvas สำหรับวาดภาพ
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                // ตั้งค่า canvas (800x600 pixels)
                canvas.width = 800;
                canvas.height = 600;
                
                // ดึงภาพพื้นหลัง
                const designArea = document.getElementById('design-area');
                const bgStyle = window.getComputedStyle(designArea).backgroundImage;
                
                // วาดและ download
                await drawAndDownloadCertificate(canvas, ctx, bgStyle, name, i, total);
            }
            
            alert(`✓ ส่งออกเสร็จ ${total} ใบ`);
        }

        // วาดและ download certificate เดี่ยว
        function drawAndDownloadCertificate(canvas, ctx, bgStyle, name, index, total) {
            return new Promise((resolve) => {
                // วาดพื้นหลัง
                const bgImg = new Image();
                bgImg.crossOrigin = 'anonymous';
                
                bgImg.onload = function() {
                    ctx.drawImage(bgImg, 0, 0, 800, 600);
                    drawTextElementsWithName(ctx, name);
                    downloadPNG(canvas, name);
                    
                    // ดีเลย์เล็กน้อยเพื่อให้ browser ไม่ lag
                    setTimeout(resolve, 100);
                };
                
                bgImg.onerror = function() {
                    // ถ้ารูปไม่ได้ใช้สีขาวแทน
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, 800, 600);
                    drawTextElementsWithName(ctx, name);
                    downloadPNG(canvas, name);
                    
                    setTimeout(resolve, 100);
                };
                
                // ดึง URL จาก background-image style
                if (bgStyle && bgStyle !== 'none') {
                    const urlMatch = bgStyle.match(/url\(['"]?(.+?)['"]?\)/);
                    if (urlMatch) {
                        bgImg.src = urlMatch[1];
                    } else {
                        bgImg.onerror();
                    }
                } else {
                    bgImg.onerror();
                }
            });
        }

        // ส่งออก designs ทั้งหมดเป็น ZIP file (client-side: PNG + ZIP)
        async function exportDesignsZip() {
            console.log('Exporting designs as ZIP (client-side)...');

            // ดึงชื่อจาก preview select
            const previewSelect = document.getElementById('previewSelect');
            const names = previewSelect ? Array.from(previewSelect.options).map(o => o.value).filter(v => v) : [];
            if (names.length === 0) {
                alert('ไม่มีชื่อให้ส่งออก กรุณา Upload CSV ก่อน');
                return;
            }

            const zip = new JSZip();
            const folder = zip.folder('designs');

            // สร้าง canvas ขนาดเดียวกับ exportCertificates
            for (let i = 0; i < names.length; i++) {
                const name = names[i];
                const canvas = document.createElement('canvas');
                canvas.width = 800;
                canvas.height = 600;
                const ctx = canvas.getContext('2d');

                // วาดพื้นหลัง และ text elements
                try {
                    await drawToCanvasForExport(canvas, ctx, name);
                } catch (err) {
                    console.error('Error drawing for', name, err);
                }

                // แปลงเป็น blob
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                const safeName = name.replace(/[^a-zA-Z0-900-\u0E7F]/g, '_').substring(0, 50);
                folder.file(`certificate_${safeName}_${Date.now()}.png`, blob);
            }

            // สร้าง ZIP และดาวน์โหลด
            const content = await zip.generateAsync({ type: 'blob' });
            const userId = '<?= $_SESSION['user_id'] ?>';
            saveAs(content, `designs_${userId}_${new Date().toISOString().replace(/[:.]/g,'')}.zip`);
            console.log('ZIP download triggered');
        }

        // วาดลง canvas สำหรับ export (ใช้ฟังก์ชันเดียวกับ exportCertificates แต่แยกเป็น async helper)
        function drawToCanvasForExport(canvas, ctx, name) {
            return new Promise((resolve) => {
                const designArea = document.getElementById('design-area');
                const bgStyle = window.getComputedStyle(designArea).backgroundImage;

                const bgImg = new Image();
                bgImg.crossOrigin = 'anonymous';
                bgImg.onload = function() {
                    ctx.drawImage(bgImg, 0, 0, canvas.width, canvas.height);
                    drawTextElementsWithName(ctx, name);
                    resolve();
                };
                bgImg.onerror = function() {
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    drawTextElementsWithName(ctx, name);
                    resolve();
                };

                if (bgStyle && bgStyle !== 'none') {
                    const urlMatch = bgStyle.match(/url\(['"]?(.+?)['"]?\)/);
                    if (urlMatch) bgImg.src = urlMatch[1]; else bgImg.onerror();
                } else {
                    bgImg.onerror();
                }
            });
        }

        // วาด text elements กับชื่อที่กำหนด
        function drawTextElementsWithName(ctx, displayName) {
            textElements.forEach((element, index) => {
                // ตั้งค่า font
                ctx.font = element.fontWeight + ' ' + element.fontSize + 'px ' + element.fontFamily;
                ctx.fillStyle = element.color;
                ctx.textBaseline = 'top';  // ให้ y coordinate เป็น top ของ text เหมือน DOM
                
                // ตั้งค่า alignment
                switch(element.align) {
                    case 'center': ctx.textAlign = 'center'; break;
                    case 'right': ctx.textAlign = 'right'; break;
                    default: ctx.textAlign = 'left';
                }
                
                // เพิ่ม shadow ถ้ามี
                switch(element.textShadow) {
                    case 'light':
                        ctx.shadowColor = 'rgba(0,0,0,0.3)';
                        ctx.shadowBlur = 2;
                        ctx.shadowOffsetX = 1;
                        ctx.shadowOffsetY = 1;
                        break;
                    case 'medium':
                        ctx.shadowColor = 'rgba(0,0,0,0.5)';
                        ctx.shadowBlur = 4;
                        ctx.shadowOffsetX = 2;
                        ctx.shadowOffsetY = 2;
                        break;
                    case 'strong':
                        ctx.shadowColor = 'rgba(0,0,0,0.7)';
                        ctx.shadowBlur = 6;
                        ctx.shadowOffsetX = 3;
                        ctx.shadowOffsetY = 3;
                        break;
                    default:
                        ctx.shadowColor = 'transparent';
                }
                
                // วาดข้อความ (replace {name} ด้วยชื่อจริง)
                const displayText = element.content.replace(/{name}/g, displayName);
                console.log(`Export canvas text ${index}: pos(${element.x}, ${element.y}), text="${displayText.substring(0, 20)}", align=${element.align}, size=${element.fontSize}`);
                ctx.fillText(displayText, element.x, element.y);
                
                // ล้าง shadow
                ctx.shadowColor = 'transparent';
            });
        }

        // Download PNG
        function downloadPNG(canvas, name) {
            canvas.toBlob(function(blob) {
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                
                // สร้างชื่อไฟล์ที่ปลอดภัย
                const safeName = name.replace(/[^a-zA-Z0-9\u0E00-\u0E7F]/g, '_').substring(0, 50);
                link.download = `certificate_${safeName}_${new Date().getTime()}.png`;
                
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            });
        }

        // Template presets
        function loadTemplate(templateType) {
            clearAll();
            
            switch(templateType) {
                case 'government':
                    // Template ราชการ
                    textElements = [
                        {
                            id: 'text_header',
                            content: 'กระทรวงสาธารณสุข',
                            x: 300, y: 80,
                            fontSize: 28, fontFamily: 'Sarabun', fontWeight: '600',
                            textDecoration: 'none', textShadow: 'none',
                            color: '#1a237e', align: 'center'
                        },
                        {
                            id: 'text_title', 
                            content: 'ใบประกาศเกียรติคุณ',
                            x: 300, y: 140,
                            fontSize: 48, fontFamily: 'Sarabun', fontWeight: '700',
                            textDecoration: 'none', textShadow: 'light',
                            color: '#c62828', align: 'center'
                        },
                        {
                            id: 'text_name',
                            content: 'ขอมอบให้แก่\\n{name}',
                            x: 300, y: 220,
                            fontSize: 36, fontFamily: 'TH Sarabun New', fontWeight: '500',
                            textDecoration: 'none', textShadow: 'none',
                            color: '#1565c0', align: 'center'
                        },
                        {
                            id: 'text_desc',
                            content: 'ที่ได้แสดงความสามารถดีเด่น\\nและเป็นที่ประจักษ์',
                            x: 300, y: 320,
                            fontSize: 24, fontFamily: 'Sarabun', fontWeight: '400',
                            textDecoration: 'none', textShadow: 'none',
                            color: '#424242', align: 'center'
                        }
                    ];
                    break;
                    
                case 'modern':
                    // Template โมเดิร์น
                    textElements = [
                        {
                            id: 'text_title',
                            content: 'CERTIFICATE',
                            x: 300, y: 100,
                            fontSize: 52, fontFamily: 'Kanit', fontWeight: '800',
                            textDecoration: 'none', textShadow: 'glow',
                            color: '#e91e63', align: 'center'
                        },
                        {
                            id: 'text_subtitle',
                            content: 'ของความสำเร็จ',
                            x: 300, y: 160,
                            fontSize: 28, fontFamily: 'Prompt', fontWeight: '300',
                            textDecoration: 'none', textShadow: 'light',
                            color: '#9c27b0', align: 'center'
                        },
                        {
                            id: 'text_name',
                            content: '{name}',
                            x: 300, y: 250,
                            fontSize: 44, fontFamily: 'Prompt', fontWeight: '600',
                            textDecoration: 'underline', textShadow: 'medium',
                            color: '#1e88e5', align: 'center'
                        },
                        {
                            id: 'text_footer',
                            content: 'ขอแสดงความยินดี\\nกับความสำเร็จของคุณ',
                            x: 300, y: 340,
                            fontSize: 20, fontFamily: 'IBM Plex Sans Thai', fontWeight: '400',
                            textDecoration: 'none', textShadow: 'none',
                            color: '#455a64', align: 'center'
                        }
                    ];
                    break;
                    
                case 'elegant':
                    // Template หรูหรา
                    textElements = [
                        {
                            id: 'text_ornament_top',
                            content: '✧･ﾟ: *✧･ﾟ:* CERTIFICATE *:･ﾟ✧*:･ﾟ✧',
                            x: 300, y: 80,
                            fontSize: 24, fontFamily: 'Georgia', fontWeight: '400',
                            textDecoration: 'none', textShadow: 'light',
                            color: '#8e24aa', align: 'center'
                        },
                        {
                            id: 'text_title',
                            content: 'ใบประกาศเกียรติยศ',
                            x: 300, y: 140,
                            fontSize: 42, fontFamily: 'EucrosiaUPC', fontWeight: '600',
                            textDecoration: 'none', textShadow: 'medium',
                            color: '#4a148c', align: 'center'
                        },
                        {
                            id: 'text_name',
                            content: '~ {name} ~',
                            x: 300, y: 220,
                            fontSize: 38, fontFamily: 'DilleniaUPC', fontWeight: '500',
                            textDecoration: 'none', textShadow: 'light',
                            color: '#c2185b', align: 'center'
                        },
                        {
                            id: 'text_desc',
                            content: 'ด้วยความภาคภูมิใจ\\nในความสำเร็จอันงดงาม',
                            x: 300, y: 300,
                            fontSize: 22, fontFamily: 'JasmineUPC', fontWeight: '400',
                            textDecoration: 'none', textShadow: 'none',
                            color: '#6a1b9a', align: 'center'
                        }
                    ];
                    break;
            }
            
            elementCounter = textElements.length;
            renderTextElements();
            updatePreview();
            document.getElementById('empty-state').style.display = 'none';
        }
        
        // ล้างข้อความทั้งหมด
        function clearAll(showConfirm = true) {
            if (showConfirm && !confirm('ต้องการล้างข้อความทั้งหมดหรือไม่?')) {
                return;
            }
            textElements = [];
            elementCounter = 0;
            renderTextElements();
            updatePreview();
            document.getElementById('empty-state').style.display = 'flex';
        }

        // Event listeners
        document.getElementById('previewSelect')?.addEventListener('change', updatePreview);

        // แสดง Template Gallery
        function showTemplateGallery() {
            new bootstrap.Modal(document.getElementById('templateGalleryModal')).show();
        }
        
        // โหลด template จาก gallery
        function loadTemplateFromGallery(templateId) {
            clearAll(false); // ไม่แสดง confirm
            
            const templates = {
                'gov_standard': [
                    {
                        id: 'text_ministry', content: 'กระทรวงศึกษาธิการ',
                        x: 400, y: 80, fontSize: 28, fontFamily: 'Sarabun', fontWeight: '600',
                        textDecoration: 'none', textShadow: 'none', color: '#1a237e', align: 'center'
                    },
                    {
                        id: 'text_title', content: 'ใบประกาศเกียรติคุณ',
                        x: 400, y: 140, fontSize: 48, fontFamily: 'Sarabun', fontWeight: '700',
                        textDecoration: 'none', textShadow: 'light', color: '#c62828', align: 'center'
                    },
                    {
                        id: 'text_name', content: 'ขอมอบให้แก่\\n{name}',
                        x: 400, y: 220, fontSize: 36, fontFamily: 'TH Sarabun New', fontWeight: '500',
                        textDecoration: 'none', textShadow: 'none', color: '#1565c0', align: 'center'
                    }
                ],
                'gov_formal': [
                    {
                        id: 'text_course', content: 'หลักสูตร',
                        x: 400, y: 100, fontSize: 32, fontFamily: 'Sarabun', fontWeight: '600',
                        textDecoration: 'none', textShadow: 'none', color: '#2e7d32', align: 'center'
                    },
                    {
                        id: 'text_title', content: 'วิทยาการข้อมูลและอินเทอร์เน็ตของสรรพสิ่ง',
                        x: 400, y: 150, fontSize: 28, fontFamily: 'Prompt', fontWeight: '500',
                        textDecoration: 'none', textShadow: 'light', color: '#1b5e20', align: 'center'
                    },
                    {
                        id: 'text_name', content: '{name}\\nผ่านการอบรมเรียบร้อยแล้ว',
                        x: 400, y: 250, fontSize: 32, fontFamily: 'Sarabun', fontWeight: '600',
                        textDecoration: 'none', textShadow: 'medium', color: '#4caf50', align: 'center'
                    }
                ],
                'gov_achievement': [
                    {
                        id: 'text_award', content: '🏆 รางวัลความสำเร็จ 🏆',
                        x: 400, y: 120, fontSize: 36, fontFamily: 'Kanit', fontWeight: '700',
                        textDecoration: 'none', textShadow: 'glow', color: '#c2185b', align: 'center'
                    },
                    {
                        id: 'text_name', content: '{name}',
                        x: 400, y: 200, fontSize: 40, fontFamily: 'Prompt', fontWeight: '600',
                        textDecoration: 'underline', textShadow: 'medium', color: '#e91e63', align: 'center'
                    },
                    {
                        id: 'text_position', content: 'ผู้ปฏิบัติงานดีเด่นประจำปี',
                        x: 400, y: 280, fontSize: 28, fontFamily: 'Sarabun', fontWeight: '500',
                        textDecoration: 'none', textShadow: 'light', color: '#880e4f', align: 'center'
                    }
                ],
                'modern_tech': [
                    {
                        id: 'text_title', content: 'CERTIFICATE',
                        x: 400, y: 100, fontSize: 52, fontFamily: 'Kanit', fontWeight: '800',
                        textDecoration: 'none', textShadow: 'glow', color: '#1e88e5', align: 'center'
                    },
                    {
                        id: 'text_subtitle', content: 'of Technology Excellence',
                        x: 400, y: 160, fontSize: 24, fontFamily: 'IBM Plex Sans Thai', fontWeight: '400',
                        textDecoration: 'none', textShadow: 'light', color: '#42a5f5', align: 'center'
                    },
                    {
                        id: 'text_name', content: '{name}',
                        x: 400, y: 240, fontSize: 42, fontFamily: 'Prompt', fontWeight: '600',
                        textDecoration: 'none', textShadow: 'medium', color: '#0d47a1', align: 'center'
                    }
                ],
                'modern_creative': [
                    {
                        id: 'text_title', content: '🎨 CREATIVE AWARD 🎨',
                        x: 400, y: 120, fontSize: 36, fontFamily: 'Kanit', fontWeight: '700',
                        textDecoration: 'none', textShadow: 'glow', color: '#9c27b0', align: 'center'
                    },
                    {
                        id: 'text_name', content: '{name}',
                        x: 400, y: 200, fontSize: 40, fontFamily: 'Prompt', fontWeight: '600',
                        textDecoration: 'none', textShadow: 'medium', color: '#ba68c8', align: 'center'
                    },
                    {
                        id: 'text_desc', content: 'นักออกแบบดีเด่น\\nแห่งปี 2568',
                        x: 400, y: 280, fontSize: 24, fontFamily: 'Sarabun', fontWeight: '500',
                        textDecoration: 'none', textShadow: 'light', color: '#7b1fa2', align: 'center'
                    }
                ],
                'modern_business': [
                    {
                        id: 'text_title', content: 'BUSINESS EXCELLENCE',
                        x: 400, y: 110, fontSize: 38, fontFamily: 'Kanit', fontWeight: '700',
                        textDecoration: 'none', textShadow: 'medium', color: '#37474f', align: 'center'
                    },
                    {
                        id: 'text_subtitle', content: 'Certificate of Achievement',
                        x: 400, y: 160, fontSize: 22, fontFamily: 'IBM Plex Sans Thai', fontWeight: '400',
                        textDecoration: 'none', textShadow: 'light', color: '#607d8b', align: 'center'
                    },
                    {
                        id: 'text_name', content: '{name}',
                        x: 400, y: 230, fontSize: 36, fontFamily: 'Prompt', fontWeight: '600',
                        textDecoration: 'underline', textShadow: 'medium', color: '#263238', align: 'center'
                    }
                ],
                'elegant_gold': [
                    {
                        id: 'text_title', content: '✨ GOLDEN AWARD ✨',
                        x: 400, y: 120, fontSize: 40, fontFamily: 'Georgia', fontWeight: '700',
                        textDecoration: 'none', textShadow: 'glow', color: '#bf9000', align: 'center'
                    },
                    {
                        id: 'text_subtitle', content: 'ใบประกาศเกียรติยศ',
                        x: 400, y: 180, fontSize: 32, fontFamily: 'EucrosiaUPC', fontWeight: '600',
                        textDecoration: 'none', textShadow: 'medium', color: '#ff8f00', align: 'center'
                    },
                    {
                        id: 'text_name', content: '{name}',
                        x: 400, y: 250, fontSize: 38, fontFamily: 'DilleniaUPC', fontWeight: '600',
                        textDecoration: 'none', textShadow: 'strong', color: '#e65100', align: 'center'
                    }
                ],
                'elegant_royal': [
                    {
                        id: 'text_title', content: '👑 ROYAL CERTIFICATE 👑',
                        x: 400, y: 110, fontSize: 32, fontFamily: 'Georgia', fontWeight: '700',
                        textDecoration: 'none', textShadow: 'glow', color: '#4a148c', align: 'center'
                    },
                    {
                        id: 'text_subtitle', content: 'ใบประกาศเกียรติยศสูงสุด',
                        x: 400, y: 170, fontSize: 28, fontFamily: 'EucrosiaUPC', fontWeight: '600',
                        textDecoration: 'none', textShadow: 'medium', color: '#7b1fa2', align: 'center'
                    },
                    {
                        id: 'text_name', content: '{name}',
                        x: 400, y: 230, fontSize: 36, fontFamily: 'DilleniaUPC', fontWeight: '600',
                        textDecoration: 'none', textShadow: 'strong', color: '#8e24aa', align: 'center'
                    }
                ],
                'elegant_classic': [
                    {
                        id: 'text_title', content: '🏛️ CLASSIC HONOR 🏛️',
                        x: 400, y: 120, fontSize: 34, fontFamily: 'Times New Roman', fontWeight: '700',
                        textDecoration: 'none', textShadow: 'medium', color: '#5d4037', align: 'center'
                    },
                    {
                        id: 'text_subtitle', content: 'Certificate of Merit',
                        x: 400, y: 180, fontSize: 26, fontFamily: 'Georgia', fontWeight: '400',
                        textDecoration: 'none', textShadow: 'light', color: '#6d4c41', align: 'center'
                    },
                    {
                        id: 'text_name', content: '{name}',
                        x: 400, y: 240, fontSize: 32, fontFamily: 'JasmineUPC', fontWeight: '600',
                        textDecoration: 'underline', textShadow: 'medium', color: '#8d6e63', align: 'center'
                    }
                ]
            };
            
            if (templates[templateId]) {
                textElements = templates[templateId];
                elementCounter = textElements.length;
                document.body.setAttribute('data-current-template', templateId);
                renderTextElements();
                updatePreview();
                document.getElementById('empty-state').style.display = 'none';
                
                // ซ่อน modal (ถ้ามี)
                const modalElement = document.getElementById('templateGalleryModal');
                if (modalElement) {
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
                
                // บันทึกลงประวัติ
                saveToHistory(templateId);
            }
        }
        
        // บันทึกลงประวัติ
        function saveToHistory(templateId) {
            let history = JSON.parse(localStorage.getItem('certificateHistory') || '[]');
            
            const historyItem = {
                id: templateId,
                timestamp: new Date().toISOString(),
                elements: JSON.parse(JSON.stringify(textElements)) // deep copy
            };
            
            // เพิ่มไอเทมใหม่ที่ด้านบน
            history.unshift(historyItem);
            
            // จำกัดให้เก็บแค่ 6 รายการล่าสุด
            history = history.slice(0, 6);
            
            localStorage.setItem('certificateHistory', JSON.stringify(history));
            renderHistory();
        }
        
        // แสดงประวัติ
        function renderHistory() {
            const container = document.getElementById('history-container');
            
            // ถ้า container ไม่พบ ให้ออก (element ยังไม่พร้อม)
            if (!container) {
                console.warn('⚠️ history-container element not found yet, skipping renderHistory');
                return;
            }
            
            const history = JSON.parse(localStorage.getItem('certificateHistory') || '[]');
            
            if (history.length === 0) {
                container.innerHTML = '<div class=\"col-12 text-center text-muted small\">ยังไม่มีประวัติการสร้าง</div>';
                return;
            }
            
            container.innerHTML = '';
            
            history.forEach((item, index) => {
                const date = new Date(item.timestamp).toLocaleDateString('th-TH', {
                    day: '2-digit',
                    month: '2-digit',
                    year: '2-digit'
                });
                const time = new Date(item.timestamp).toLocaleTimeString('th-TH', {hour: '2-digit', minute: '2-digit'});
                
                // สร้างไอคอนตาม template type
                let icon = '📄';
                if (item.id.includes('gov')) icon = '🏛️';
                else if (item.id.includes('modern')) icon = '⭐';
                else if (item.id.includes('elegant')) icon = '👑';
                else if (item.id.includes('custom')) icon = '✨';
                
                const historyHtml = `
                    <div class=\"col-6 col-lg-12 mb-2\">
                        <div class=\"history-item border rounded p-2\" onclick=\"loadFromHistory(${index})\" title=\"คลิกเพื่อโหลด\">
                            <div class=\"text-center\">
                                <div class=\"fs-6 mb-1\">${icon}</div>
                                <div class=\"fw-bold\" style=\"font-size: 10px; color: #495057;\">#${index + 1}</div>
                                <div style=\"font-size: 9px; color: #6c757d; line-height: 1.2;\">
                                    ${date}<br>${time}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                container.insertAdjacentHTML('beforeend', historyHtml);
            });
        }
        
        // โหลดจากประวัติ
        function loadFromHistory(index) {
            const history = JSON.parse(localStorage.getItem('certificateHistory') || '[]');
            if (history[index]) {
                clearAll(false);
                textElements = JSON.parse(JSON.stringify(history[index].elements)); // deep copy
                elementCounter = textElements.length;
                renderTextElements();
                updatePreview();
                document.getElementById('empty-state').style.display = 'none';
            }
        }
        
        // ปรับปรุง clearAll function
        function clearAll(showConfirm = true) {
            if (showConfirm && !confirm('ต้องการล้างข้อความทั้งหมดหรือไม่?')) {
                return;
            }
            textElements = [];
            elementCounter = 0;
            renderTextElements();
            updatePreview();
            document.getElementById('empty-state').style.display = 'flex';
        }

        // ✨ Select Background Template Function
        function selectBackgroundTemplate(templatePath) {
            // เปลี่ยน Design Area background
            const designArea = document.getElementById('design-area');
            designArea.style.backgroundImage = `url('${templatePath}')`;
            
            // เปลี่ยน Preview Area background ด้วย
            const previewArea = document.getElementById('preview-area');
            previewArea.style.backgroundImage = `url('${templatePath}')`;
            previewArea.style.backgroundSize = 'cover';
            previewArea.style.backgroundPosition = 'center';
            previewArea.style.backgroundRepeat = 'no-repeat';
            previewArea.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
            
            // Highlight ตัวอย่างที่เลือก
            document.querySelectorAll('.template-card').forEach(card => {
                card.style.opacity = '0.6';
                card.style.border = 'none';
            });
            event.currentTarget.style.opacity = '1';
            event.currentTarget.style.border = '3px solid #28a745';
            
            // แสดง notification
            showNotification('✅ เลือกพื้นหลังสำเร็จ!', 'success');
        }
        
        // Notification Function
        function showNotification(message, type = 'info') {
            // สร้าง notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 250px; animation: slideIn 0.3s ease;';
            notification.innerHTML = message;
            
            document.body.appendChild(notification);
            
            // ลบหลังจาก 3 วินาที
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // CSS Animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
            .template-card {
                position: relative;
            }
            .template-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15) !important;
            }
        `;
        document.head.appendChild(style);

        // บันทึก Design ลง Database และ LocalStorage
        async function saveDesign() {
            const designName = prompt('กรุณาตั้งชื่อ Design:', 'Design ' + new Date().toLocaleString('th-TH'));
            if (!designName) return;

            const templateId = document.body.getAttribute('data-current-template') || 'custom';
            const bgImage = document.getElementById('design-area')?.style.backgroundImage?.replace(/url\(["']?([^"']+)["']?\)/g, '$1') || '';

            try {
                const response = await fetch('save_design.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        design_name: designName,
                        template_id: templateId,
                        bg_image: bgImage,
                        text_elements: textElements
                    })
                });

                const result = await response.json();
                if (result.success) {
                    // บันทึกลง LocalStorage ด้วย (เพื่อ offline/cache)
                    saveToLocalStorage(result.design_id, designName, templateId, bgImage, textElements);
                    
                    alert('✓ บันทึก Design สำเร็จ');
                    loadDesignHistory();
                } else {
                    alert('✗ เกิดข้อผิดพลาด: ' + result.error);
                }
            } catch (error) {
                console.error('Error saving design:', error);
                alert('✗ เกิดข้อผิดพลาดในการบันทึก');
            }
        }
        
        // บันทึก Design ลง LocalStorage
        function saveToLocalStorage(id, name, template, bg, elements) {
            let designs = JSON.parse(localStorage.getItem('user_designs_local') || '[]');
            
            // ตรวจสอบว่า ID นี้มีอยู่แล้วไหม
            const index = designs.findIndex(d => d.id == id);
            
            const designObj = {
                id: id,
                name: name,
                template_id: template,
                bg_image: bg,
                text_elements: elements,
                saved_at: new Date().toISOString()
            };
            
            if (index >= 0) {
                designs[index] = designObj;
            } else {
                designs.unshift(designObj);
            }
            
            // เก็บแค่ 50 รายการล่าสุด
            designs = designs.slice(0, 50);
            
            localStorage.setItem('user_designs_local', JSON.stringify(designs));
            console.log('Saved to LocalStorage:', designObj.name);
        }

        // โหลด Design History จาก API + LocalStorage
        async function loadDesignHistory() {
            console.log('🟢 loadDesignHistory called');
            
            // รอให้ element พร้อม (retry หลายครั้ง)
            let historyList = null;
            let retries = 0;
            const maxRetries = 30; // เพิ่มจาก 10 เป็น 30
            const retryDelay = 150; // ลดจาก 200 เป็น 150ms เพื่อให้เร็ว
            
            while (!historyList && retries < maxRetries) {
                historyList = document.getElementById('designHistoryList');
                if (!historyList) {
                    console.warn(`⏳ Retry ${retries + 1}/${maxRetries}: designHistoryList not found, waiting ${retryDelay}ms...`);
                    await new Promise(resolve => setTimeout(resolve, retryDelay));
                    retries++;
                } else {
                    console.log(`✅ Found designHistoryList after ${retries} retries (${retries * retryDelay}ms total)`);
                }
            }
            
            if (!historyList) {
                console.error('❌ designHistoryList element NOT FOUND after', maxRetries, 'retries (total', maxRetries * retryDelay, 'ms). Element may not exist in HTML.');
                return;
            }
            
            console.log('✓✓ designHistoryList element found and ready');
            
            try {
                // โหลดจากฐานข้อมูล พร้อม cache busting
                console.log('🌐 Fetching design history from API...');
                const url = 'get_design_history.php?t=' + Date.now(); // ป้องกัน browser cache
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Cache-Control': 'no-cache, no-store, must-revalidate',
                        'Pragma': 'no-cache'
                    },
                    cache: 'no-store'
                });
                
                console.log('📡 API response status:', response.status);
                
                if (!response.ok) {
                    console.error('❌ API returned HTTP error:', response.status);
                    throw new Error(`HTTP Error: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('📥 API result received:', result);
                console.log('📊 DB count from API:', result.db_count || 0);
                console.log('📋 Designs array from API:', result.designs);
                console.log('📋 Designs length:', result.designs ? result.designs.length : 'undefined');

                // รวม DB + LocalStorage (DB เป็นหลัก)
                let allDesigns = [];
                
                // โหลดจากฐานข้อมูลก่อน (เป็นข้อมูลที่บันทึก)
                if (result.success && result.designs && result.designs.length > 0) {
                    allDesigns = result.designs.map(dbDesign => ({
                        id: dbDesign.id,
                        name: dbDesign.name,
                        template_id: dbDesign.template,
                        bg_image: '',
                        text_elements: [],
                        saved_at: dbDesign.updated ? dbDesign.updated : (dbDesign.created || new Date().toISOString())
                    }));
                    console.log('✓✓ Loaded from DB:', allDesigns.length, 'designs');
                    console.log('✓✓ First design sample:', allDesigns[0]);
                } else {
                    console.warn('⚠️ No DB designs or success = false. Result:', result);
                }
                
                // เพิ่มจาก LocalStorage ที่ยังไม่บันทึก (offline)
                const localDesigns = JSON.parse(localStorage.getItem('user_designs_local') || '[]');
                console.log('✓ LocalStorage designs:', localDesigns.length);
                
                localDesigns.forEach(localDesign => {
                    const exists = allDesigns.find(d => d.id == localDesign.id);
                    if (!exists) {
                        allDesigns.push(localDesign);
                    }
                });

                if (allDesigns.length === 0) {
                    historyList.innerHTML = '<div class="text-center text-muted py-3"><small><i class="fas fa-inbox"></i> ไม่มี Design ที่บันทึกไว้</small></div>';
                    console.log('⚠️ No designs found total');
                    return;
                }

                // แสดงประวัติ (เรียงจากใหม่ไปเก่า)
                historyList.innerHTML = allDesigns
                    .sort((a, b) => new Date(b.saved_at || 0) - new Date(a.saved_at || 0))
                    .map(design => `
                    <div class="list-group-item p-2 border-bottom" style="background: #f9f9f9; cursor: pointer;" 
                         onmouseover="this.style.background='#e8f4f8'" 
                         onmouseout="this.style.background='#f9f9f9'"
                         onclick="loadSavedDesign(${design.id})">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <small class="fw-bold text-truncate d-block">${design.name}</small>
                                <small class="text-muted d-block" style="font-size: 9px;">
                                    ${design.saved_at ? new Date(design.saved_at).toLocaleString('th-TH') : 'ไม่ทราบวันที่'}
                                </small>
                            </div>
                            <button type="button" class="btn btn-sm btn-danger btn-sm p-0" 
                                    style="width: 20px; height: 20px; line-height: 1;"
                                    onclick="deleteDesign(event, ${design.id})" title="ลบ">
                                ×
                            </button>
                        </div>
                    </div>
                `).join('');
                console.log('✓✓✓ Design history rendered:', allDesigns.length, 'items');
            } catch (error) {
                console.error('Error loading history:', error);
                // ถ้า API error ให้ใช้ LocalStorage เพียงอย่างเดียว
                const localDesigns = JSON.parse(localStorage.getItem('user_designs_local') || '[]');
                console.log('Fallback to LocalStorage:', localDesigns.length);
                
                if (localDesigns.length > 0) {
                    historyList.innerHTML = localDesigns
                        .sort((a, b) => new Date(b.saved_at || 0) - new Date(a.saved_at || 0))
                        .map(design => `
                        <div class="list-group-item p-2 border-bottom" style="background: #f9f9f9; cursor: pointer;" 
                             onmouseover="this.style.background='#e8f4f8'" 
                             onmouseout="this.style.background='#f9f9f9'"
                             onclick="loadSavedDesign(${design.id})">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <small class="fw-bold text-truncate d-block">${design.name}</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger p-0" 
                                        style="width: 20px; height: 20px; line-height: 1;"
                                        onclick="deleteDesign(event, ${design.id})" title="ลบ">
                                    ×
                                </button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    historyList.innerHTML = '<div class="text-center text-muted py-3"><small><i class="fas fa-inbox"></i> ไม่มี Design ที่บันทึกไว้</small></div>';
                }
            }
        }

        // โหลด Saved Design
        async function loadSavedDesign(designId) {
            try {
                // ลองโหลดจาก LocalStorage ก่อน
                const localDesigns = JSON.parse(localStorage.getItem('user_designs_local') || '[]');
                const localDesign = localDesigns.find(d => d.id == designId);
                
                if (localDesign && localDesign.text_elements && localDesign.text_elements.length > 0) {
                    // โหลดจาก LocalStorage สำเร็จ
                    textElements = localDesign.text_elements || [];
                    document.body.setAttribute('data-current-template', localDesign.template_id || 'custom');
                    
                    if (localDesign.bg_image) {
                        document.getElementById('design-area').style.backgroundImage = `url('${localDesign.bg_image}')`;
                        document.getElementById('preview-area').style.backgroundImage = `url('${localDesign.bg_image}')`;
                    }
                    
                    renderTextElements();
                    updatePreview();
                    alert('✓ โหลด Design สำเร็จ (จาก cache)');
                    return;
                }
                
                // ถ้า LocalStorage ไม่มี ให้ลองเอาจาก API
                const response = await fetch(`load_design.php?id=${designId}`);
                const result = await response.json();

                if (result.success) {
                    const design = result.design;
                    textElements = design.text_elements || [];
                    document.body.setAttribute('data-current-template', design.template_id);
                    
                    if (design.bg_image) {
                        document.getElementById('design-area').style.backgroundImage = `url('${design.bg_image}')`;
                        document.getElementById('preview-area').style.backgroundImage = `url('${design.bg_image}')`;
                        sessionStorage.setItem('bg_path', design.bg_image);
                    }
                    
                    // บันทึก LocalStorage เพื่อ offline ครั้งต่อไป
                    saveToLocalStorage(design.id, design.name, design.template_id, design.bg_image, design.text_elements);
                    
                    renderTextElements();
                    updatePreview();
                    alert('✓ โหลด Design สำเร็จ');
                } else {
                    alert('✗ ไม่สามารถโหลด Design ได้');
                }
            } catch (error) {
                console.error('Error loading design:', error);
                alert('✗ เกิดข้อผิดพลาดในการโหลด');
            }
        }

        // ลบ Design
        async function deleteDesign(event, designId) {
            event.stopPropagation();
            if (!confirm('ต้องการลบ Design นี้จริงไหม?')) return;

            try {
                // ลบจาก LocalStorage
                let localDesigns = JSON.parse(localStorage.getItem('user_designs_local') || '[]');
                localDesigns = localDesigns.filter(d => d.id != designId);
                localStorage.setItem('user_designs_local', JSON.stringify(localDesigns));
                console.log('Deleted from LocalStorage:', designId);
                
                // ลบจากฐานข้อมูล (ถ้า server สำเร็จ)
                const response = await fetch('delete_design.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + designId
                });

                const result = await response.json();
                if (result.success) {
                    alert('✓ ลบ Design สำเร็จ');
                    loadDesignHistory();
                } else {
                    alert('✗ ไม่สามารถลบได้: ' + result.error);
                }
            } catch (error) {
                console.error('Error deleting design:', error);
                // ยังคง reload history แม้ว่า server error เพราะลบจาก LocalStorage สำเร็จแล้ว
                loadDesignHistory();
            }
        }

        // เริ่มต้นด้วย template ราชการ
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔵 DOMContentLoaded fired');
            renderHistory();
            loadTemplateFromGallery('gov_standard');
            console.log('🔵 About to call loadDesignHistory after 500ms');
            
            // เรียก loadDesignHistory หลังจาก 500ms เพื่อให้ element พร้อม
            setTimeout(() => {
                console.log('🔵 Calling loadDesignHistory (try 1)');
                loadDesignHistory();
            }, 500);
            
            // เรียก loadDesignHistory อีกครั้งหลังจาก 1.5 วินาที เพื่อให้แน่ใจว่าข้อมูลจากฐานข้อมูลโหลดเสร็จ
            setTimeout(() => {
                console.log('🔵 Reloading design history after 1.5 seconds (try 2)');
                loadDesignHistory();
            }, 1500);
            
            // Bind preview select dropdown
            const previewSelect = document.getElementById('previewSelect');
            if (previewSelect) {
                previewSelect.addEventListener('change', function() {
                    console.log('Dropdown changed to:', this.value);
                    updatePreview();
                });
            } else {
                console.error('previewSelect dropdown not found');
            }
        });

        // ===== NAMES MANAGEMENT MODAL FUNCTIONS =====

        // อัปเดต dropdown เลือกชื่อ Preview ทันที (โดยไม่ reload page)
        async function refreshPreviewDropdown() {
            try {
                const response = await fetch('get_certificate_names.php');
                const result = await response.json();
                
                if (result.success && result.names) {
                    const previewSelect = document.getElementById('previewSelect');
                    const currentValue = previewSelect.value;
                    
                    // ล้าง options เก่า
                    previewSelect.innerHTML = '';
                    
                    // เพิ่ม options ใหม่
                    result.names.forEach(name => {
                        const option = document.createElement('option');
                        option.value = name;
                        option.textContent = name;
                        previewSelect.appendChild(option);
                    });
                    
                    // เก็บตำแหน่ง select เดิม ถ้า option ยังมี
                    if (result.names.includes(currentValue)) {
                        previewSelect.value = currentValue;
                    } else if (result.names.length > 0) {
                        previewSelect.value = result.names[0];
                    }
                    
                    console.log('✓ Preview dropdown refreshed');
                    updatePreview(); // อัปเดต preview
                }
            } catch (error) {
                console.error('Error refreshing dropdown:', error);
            }
        }

        // แสดง modal จัดการรายชื่อ
        async function showNamesManagement() {
            console.log('Loading names management modal...');
            
            // โหลดรายชื่อจาก API
            try {
                const response = await fetch('get_certificate_names.php');
                const result = await response.json();
                
                if (result.success && result.names) {
                    displayNamesModal(result.names);
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error loading names:', error);
                alert('ไม่สามารถโหลดรายชื่อได้');
            }
        }

        // แสดง modal
        function displayNamesModal(names) {
            let htmlContent = `
            <div class="modal fade" id="namesManagementModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title"><i class="fas fa-users me-2"></i>จัดการรายชื่อ (${names.length} คน)</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <!-- ฟอร์มเพิ่มรายชื่อใหม่ -->
                            <div class="row g-2 mb-3">
                                <div class="col-sm-9">
                                    <input type="text" id="newNameInput" class="form-control form-control-sm" placeholder="ชื่อใหม่...">
                                </div>
                                <div class="col-sm-3">
                                    <button type="button" class="btn btn-success btn-sm w-100" onclick="addNewName()">
                                        <i class="fas fa-plus me-1"></i>เพิ่ม
                                    </button>
                                </div>
                            </div>

                            <!-- รายชื่อ -->
                            <div class="list-group" style="max-height: 400px; overflow-y: auto;">
            `;

            names.forEach((name, index) => {
                htmlContent += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>${index + 1}. ${name}</span>
                        <div>
                            <button type="button" class="btn btn-warning btn-sm me-2" onclick="editName('${name}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteName('${name}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            htmlContent += `
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        </div>
                    </div>
                </div>
            </div>
            `;

            // ลบ modal เก่า ถ้ามี และ backdrop ที่อาจหลงเหลือ
            const oldModal = document.getElementById('namesManagementModal');
            if (oldModal) oldModal.remove();
            // ลบ backdrop ที่ค้างอยู่
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            // เอา class modal-open ออกจาก body
            document.body.classList.remove('modal-open');

            // เพิ่ม HTML ใหม่
            document.body.insertAdjacentHTML('beforeend', htmlContent);

            // แสดง modal
            const modalEl = document.getElementById('namesManagementModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            // เมื่อ modal ถูกซ่อน ให้ล้าง DOM และ backdrop อีกครั้ง
            modalEl.addEventListener('hidden.bs.modal', function() {
                modalEl.remove();
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
            });
        }

        // เพิ่มรายชื่อใหม่
        async function addNewName() {
            const input = document.getElementById('newNameInput');
            const newName = input.value.trim();

            if (!newName) {
                alert('กรุณากรอกชื่อ');
                return;
            }

            try {
                const response = await fetch('manage_certificate_names.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'add', name: newName })
                });

                const result = await response.json();
                if (result.success) {
                    input.value = '';
                    console.log('✓ Name added successfully');
                    // อัปเดต dropdown ทันที
                    await refreshPreviewDropdown();
                    // โหลด modal ใหม่
                    setTimeout(() => showNamesManagement(), 300);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error adding name:', error);
                alert('ไม่สามารถเพิ่มรายชื่อได้');
            }
        }

        // แก้ไขรายชื่อ
        async function editName(oldName) {
            const newName = prompt(`แก้ไขชื่อ:\n(เดิม: ${oldName})`, oldName);
            
            if (newName === null || newName === oldName) return;
            if (!newName.trim()) {
                alert('กรุณากรอกชื่อ');
                return;
            }

            try {
                const response = await fetch('manage_certificate_names.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'edit', old_name: oldName, name: newName.trim() })
                });

                const result = await response.json();
                if (result.success) {
                    console.log('✓ Name updated successfully');
                    // อัปเดต dropdown ทันที
                    await refreshPreviewDropdown();
                    setTimeout(() => showNamesManagement(), 300);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error editing name:', error);
                alert('ไม่สามารถแก้ไขชื่อได้');
            }
        }

        // ลบรายชื่อ
        async function deleteName(name) {
            if (!confirm(`คุณแน่ใจหรือว่าต้องการลบ "${name}"?`)) return;

            try {
                const response = await fetch('manage_certificate_names.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', name: name })
                });

                const result = await response.json();
                if (result.success) {
                    console.log('✓ Name deleted successfully');
                    // อัปเดต dropdown ทันที
                    await refreshPreviewDropdown();
                    setTimeout(() => showNamesManagement(), 300);
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error deleting name:', error);
                alert('ไม่สามารถลบชื่อได้');
            }
        }
    </script>
</body>
</html>
