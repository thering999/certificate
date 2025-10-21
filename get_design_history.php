<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// ป้องกัน cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Type: application/json; charset=utf-8');

// Debug: Check table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'certificate_designs'");
$tableExists = $tableCheck && $tableCheck->num_rows > 0;

// Debug: Check ALL designs in table (for admin view)
$allDesignsCheck = $conn->query("SELECT COUNT(*) as total FROM certificate_designs");
$allDesignsCount = $allDesignsCheck ? $allDesignsCheck->fetch_assoc()['total'] : 0;

// Get all designs for this user - ใช้ Prepared Statement
$sql = "SELECT id, design_name, template_id, created_at, updated_at 
        FROM certificate_designs 
        WHERE user_id = ? 
        ORDER BY COALESCE(updated_at, created_at) DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error' => 'Prepare error: ' . $conn->error,
        'table_exists' => $tableExists,
        'current_user_id' => $user_id,
        'all_designs_in_db' => $allDesignsCount
    ]);
    exit;
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    echo json_encode([
        'success' => false,
        'error' => 'Execute error: ' . $stmt->error,
        'table_exists' => $tableExists,
        'current_user_id' => $user_id,
        'all_designs_in_db' => $allDesignsCount
    ]);
    exit;
}

$result = $stmt->get_result();
$designs = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $designs[] = [
            'id' => $row['id'],
            'name' => $row['design_name'],
            'template' => $row['template_id'],
            'created' => $row['created_at'],
            'updated' => $row['updated_at']
        ];
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => true,
    'user_id' => $user_id,
    'db_count' => $result ? $result->num_rows : 0,
    'designs' => $designs,
    'count' => count($designs),
    'table_exists' => $tableExists,
    'all_designs_in_db' => $allDesignsCount,
    'error' => $conn->error ?: null
]);
?>
