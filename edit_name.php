<?php
require_once 'db.php';
require_once 'assets/validation.class.php';
require_once 'assets/error_handler.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: index.php');
  exit;
}
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
  $_SESSION['alert'] = 'ไม่พบข้อมูลรายชื่อ';
  header('Location: index.php');
  exit;
}
// รับข้อมูลเดิม - ใช้ Prepared Statement
$stmt = $conn->prepare("SELECT name FROM certificate_names WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows == 0) {
  $_SESSION['alert'] = 'ไม่พบข้อมูลรายชื่อ';
  header('Location: index.php');
  exit;
}
$row = $result->fetch_assoc();
$name = $row['name'];
$alert = "";

// บันทึกการแก้ไข (Phase 2 Enhanced with Validation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $validator = new Validation();
  
  // Validate input
  $validator->required('name', $_POST['name'] ?? '', 'ชื่อรายชื่อ');
  $validator->length('name', $_POST['name'] ?? '', 2, 255, 'ชื่อรายชื่อ');
  
  if ($validator->isValid()) {
    $new_name = Validation::sanitizeForDB($_POST['name']);
    try {
      $stmt = $conn->prepare('UPDATE certificate_names SET name=?, updated_at=NOW() WHERE id=?');
      if (!$stmt) throw new Exception($conn->error);
      $stmt->bind_param('si', $new_name, $id);
      
      if ($stmt->execute()) {
        $alert = '✓ แก้ไขรายชื่อเรียบร้อยแล้ว';
        ErrorHandler::log('Name updated: ' . $new_name, 'INFO');
        $name = $new_name;
        $_SESSION['alert'] = 'แก้ไขชื่อสำเร็จ';
        header('Location: index.php');
        exit;
      } else {
        $alert = '✗ ไม่สามารถแก้ไขรายชื่อได้';
        ErrorHandler::logDB('Name update failed', $stmt->error);
      }
      $stmt->close();
    } catch (Exception $e) {
      $alert = '✗ เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage());
      ErrorHandler::logDB('Name edit error', $e->getMessage());
    }
  } else {
    $errors = $validator->getErrors();
    $alert = '✗ โปรดตรวจสอบข้อมูล';
  }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>แก้ไขรายชื่อ</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <h2>แก้ไขรายชื่อ</h2>
  <?php if (!empty($alert)): ?>
    <div class="alert alert-warning"> <?= $alert ?> </div>
  <?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">ชื่อ-นามสกุลใหม่</label>
      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
    </div>
    <button type="submit" class="btn btn-success">บันทึก</button>
    <a href="index.php" class="btn btn-secondary ms-2">กลับ</a>
  </form>
</div>
</body>
</html>
