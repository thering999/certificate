<?php
// API Endpoints for Certificate System
header('Content-Type: application/json; charset=utf-8');
require_once "../db.php";

// API Key validation
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
if (!$api_key) {
    http_response_code(401);
    echo json_encode(['error' => 'API Key required']);
    exit;
}

// Simple API key validation (ควรใช้วิธีที่ปลอดภัยกว่าในโปรเจคจริง)
// ตัวอย่าง: ตรวจสอบจาก database หรือ config

$method = $_SERVER['REQUEST_METHOD'];

// GET - ดึงรายการใบประกาศ
if ($method === 'GET') {
    $limit = intval($_GET['limit'] ?? 100);
    $offset = intval($_GET['offset'] ?? 0);
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT id, name, qr_code, created_at FROM certificate_names";
    if ($search) {
        $sql .= " WHERE name LIKE '%" . $conn->real_escape_string($search) . "%'";
    }
    $sql .= " ORDER BY id DESC LIMIT {$offset}, {$limit}";
    
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $total = $conn->query("SELECT COUNT(*) as count FROM certificate_names")->fetch_assoc()['count'];
    
    echo json_encode([
        'success' => true,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'data' => $data
    ]);
}

// POST - สร้างใบประกาศใหม่
elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = $input['name'] ?? '';
    
    if (!$name) {
        http_response_code(400);
        echo json_encode(['error' => 'Name is required']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO certificate_names (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'id' => $conn->insert_id,
            'message' => 'Certificate created successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create certificate']);
    }
}

// PUT - อัปเดตใบประกาศ
elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    $name = $input['name'] ?? '';
    
    if (!$id || !$name) {
        http_response_code(400);
        echo json_encode(['error' => 'ID and Name are required']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE certificate_names SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Certificate updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update certificate']);
    }
}

// DELETE - ลบใบประกาศ
elseif ($method === 'DELETE') {
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }
    
    if ($conn->query("DELETE FROM certificate_names WHERE id = " . intval($id))) {
        echo json_encode([
            'success' => true,
            'message' => 'Certificate deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete certificate']);
    }
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
