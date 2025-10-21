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

// รับ JSON data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$name = trim($input['name'] ?? '');
$old_name = trim($input['old_name'] ?? '');

if (!$action || !$name) {
    echo json_encode(['success' => false, 'error' => 'Missing action or name']);
    exit;
}

switch ($action) {
    case 'add':
        addName($conn, $user_id, $name);
        break;
    case 'edit':
        editName($conn, $user_id, $old_name, $name);
        break;
    case 'delete':
        deleteName($conn, $user_id, $name);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

function addName($conn, $user_id, $name) {
    // ตรวจสอบว่ามีชื่อนี้แล้วหรือไม่
    $checkSql = "SELECT id FROM certificate_names WHERE user_id = ? AND name = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("is", $user_id, $name);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'ชื่อนี้มีในระบบแล้ว']);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    // เพิ่มชื่อใหม่
    $sql = "INSERT INTO certificate_names (user_id, name) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare error: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("is", $user_id, $name);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'เพิ่มชื่อสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Execute error: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function editName($conn, $user_id, $old_name, $new_name) {
    // ตรวจสอบว่า old_name มีอยู่
    $checkSql = "SELECT id FROM certificate_names WHERE user_id = ? AND name = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("is", $user_id, $old_name);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'ชื่อเดิมไม่พบ']);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    // ตรวจสอบว่า new_name มีแล้วหรือไม่
    $checkSql = "SELECT id FROM certificate_names WHERE user_id = ? AND name = ? AND name != ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("iss", $user_id, $new_name, $old_name);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'ชื่อใหม่นี้มีในระบบแล้ว']);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    // แก้ไขชื่อ
    $sql = "UPDATE certificate_names SET name = ? WHERE user_id = ? AND name = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare error: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("sis", $new_name, $user_id, $old_name);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'แก้ไขชื่อสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Execute error: ' . $stmt->error]);
    }
    
    $stmt->close();
}

function deleteName($conn, $user_id, $name) {
    // ตรวจสอบว่า name มีอยู่
    $checkSql = "SELECT id FROM certificate_names WHERE user_id = ? AND name = ?";
    $stmt = $conn->prepare($checkSql);
    $stmt->bind_param("is", $user_id, $name);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'ชื่อนี้ไม่พบ']);
        $stmt->close();
        return;
    }
    $stmt->close();
    
    // ลบชื่อ
    $sql = "DELETE FROM certificate_names WHERE user_id = ? AND name = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare error: ' . $conn->error]);
        return;
    }
    
    $stmt->bind_param("is", $user_id, $name);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'ลบชื่อสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Execute error: ' . $stmt->error]);
    }
    
    $stmt->close();
}
?>
