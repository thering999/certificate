<?php
// API Verify Endpoint
header('Content-Type: application/json; charset=utf-8');
require_once "../db.php";

$code = $_GET['code'] ?? '';

if (!$code) {
    http_response_code(400);
    echo json_encode(['error' => 'Verification code is required']);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, created_at FROM certificate_names WHERE qr_code = ?");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();
$certificate = $result->fetch_assoc();

if ($certificate) {
    echo json_encode([
        'success' => true,
        'valid' => true,
        'certificate' => $certificate
    ]);
} else {
    echo json_encode([
        'success' => true,
        'valid' => false,
        'message' => 'Certificate not found'
    ]);
}
?>
