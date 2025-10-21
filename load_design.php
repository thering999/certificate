<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$design_id = intval($_GET['id'] ?? 0);

if (!$design_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Design ID required']);
    exit;
}

// Get design data - ใช้ Prepared Statement
$sql = "SELECT id, design_name, template_id, bg_image, text_elements 
        FROM certificate_designs 
        WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $design_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $design = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'design' => [
            'id' => $design['id'],
            'name' => $design['design_name'],
            'template_id' => $design['template_id'],
            'bg_image' => $design['bg_image'],
            'text_elements' => json_decode($design['text_elements'], true)
        ]
    ]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Design not found']);
}
?>
