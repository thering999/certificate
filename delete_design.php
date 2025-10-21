<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$design_id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

if (!$design_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Design ID required']);
    exit;
}

// Delete design (only if it belongs to current user) - ใช้ Prepared Statement
$sql = "DELETE FROM certificate_designs 
        WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $design_id, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Design deleted successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $conn->error
    ]);
}
?>
