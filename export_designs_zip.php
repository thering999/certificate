<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// ใช้ Prepared Statement เพื่อดึง designs ของ user นี้
$sql = "SELECT id, design_name, template_id, bg_image, text_elements, created_at, updated_at 
        FROM certificate_designs 
        WHERE user_id = ? 
        ORDER BY COALESCE(updated_at, created_at) DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Prepare error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Execute error: ' . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
$designs = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $designs[] = $row;
    }
}

if (empty($designs)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No designs found']);
    exit;
}

// สร้าง ZIP file (ZipArchive เป็น PHP built-in extension)
$zipFileName = 'designs_' . $user_id . '_' . date('YmdHis') . '.zip';
$zipFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zipFileName;

$zip = new ZipArchive();
$res = $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

if ($res === true) {
    // เพิ่มไฟล์ JSON ที่มี metadata ของ designs
    $designsData = [
        'user_id' => $user_id,
        'exported_at' => date('Y-m-d H:i:s'),
        'total_designs' => count($designs),
        'designs' => $designs
    ];
    $zip->addFromString('designs.json', json_encode($designsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // เพิ่มแต่ละ design เป็นไฟล์ HTML สำหรับพิมพ์
    foreach ($designs as $index => $design) {
        $fileName = 'Design_' . ($index + 1) . '_' . sanitizeFileName($design['design_name']) . '.html';
        $htmlContent = generateDesignHTML($design);
        $zip->addFromString($fileName, $htmlContent);
    }

    $zip->close();

    // ส่ง file
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
    header('Content-Length: ' . filesize($zipFilePath));
    readfile($zipFilePath);
    
    // ลบ temp file
    unlink($zipFilePath);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create ZIP file']);
    exit;
}

// ฟังก์ชัน sanitize ชื่อไฟล์
function sanitizeFileName($fileName) {
    $fileName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '', $fileName);
    return substr($fileName, 0, 50); // จำกัดความยาว
}

// ฟังก์ชันสร้าง HTML จาก design data
function generateDesignHTML($design) {
    $designName = htmlspecialchars($design['design_name']);
    $textElements = json_decode($design['text_elements'], true) ?? [];
    
    $htmlContent = <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$designName}</title>
    <style>
        * { margin: 0; padding: 0; }
        body { font-family: 'Cordia New', 'TH SarabunPSK', Arial, sans-serif; background: #f5f5f5; }
        .preview { width: 210mm; height: 297mm; margin: 20px auto; background: white; position: relative; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        @media print { body { margin: 0; background: white; } .preview { width: 100%; height: 100%; margin: 0; box-shadow: none; } }
    </style>
</head>
<body>
    <div class="preview" style="background-image: url('{$design['bg_image']}'); background-size: cover; background-position: center;">
        <!-- Design content will be rendered here -->
        <div style="position: absolute; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
            <div style="text-align: center;">
                <h2>{$designName}</h2>
                <p>Template: {$design['template_id']}</p>
                <p>Created: {$design['created_at']}</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    return $htmlContent;
}
?>
