<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['design_name']) || !isset($data['text_elements'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$user_id = $_SESSION['user_id'];
$design_name = $data['design_name'];
$template_id = isset($data['template_id']) ? $data['template_id'] : '';
$bg_image = isset($data['bg_image']) ? $data['bg_image'] : '';
$text_elements = json_encode($data['text_elements']);

// Check if design already exists (update) or new (insert)
$design_id = isset($data['design_id']) ? intval($data['design_id']) : 0;

if ($design_id > 0) {
    // Update existing design - ใช้ Prepared Statement
    $sql = "UPDATE certificate_designs 
            SET design_name = ?,
                template_id = ?,
                bg_image = ?,
                text_elements = ?
            WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiii", $design_name, $template_id, $bg_image, $text_elements, $design_id, $user_id);
    $success = $stmt->execute();
} else {
    // Insert new design - ใช้ Prepared Statement
    $sql = "INSERT INTO certificate_designs 
            (user_id, design_name, template_id, bg_image, text_elements)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $design_name, $template_id, $bg_image, $text_elements);
    $success = $stmt->execute();
}

if ($success) {
    $design_id = $design_id > 0 ? $design_id : $conn->insert_id;
    echo json_encode([
        'success' => true,
        'design_id' => $design_id,
        'message' => 'Design saved successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $conn->error
    ]);
}
?>
