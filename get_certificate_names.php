<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// ดึงรายชื่อทั้งหมดของ user นี้ - ใช้ Prepared Statement
$sql = "SELECT name FROM certificate_names WHERE user_id = ? ORDER BY id ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error' => 'Prepare error: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    echo json_encode([
        'success' => false,
        'error' => 'Execute error: ' . $stmt->error
    ]);
    exit;
}

$result = $stmt->get_result();
$names = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $names[] = $row['name'];
    }
}

echo json_encode([
    'success' => true,
    'names' => $names,
    'count' => count($names)
]);
?>
