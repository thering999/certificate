<?php
require_once 'db.php';
require_once 'assets/validation.class.php';
require_once 'assets/error_handler.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id > 0) {
        try {
            $stmt = $conn->prepare('DELETE FROM certificate_names WHERE id=?');
            if (!$stmt) throw new Exception($conn->error);
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                $_SESSION['alert'] = '✓ ลบรายชื่อเรียบร้อยแล้ว';
                ErrorHandler::log('Name deleted: ID ' . $id, 'INFO');
            } else {
                $_SESSION['alert'] = '✗ ไม่สามารถลบรายชื่อได้';
                ErrorHandler::logDB('Name deletion failed', $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $_SESSION['alert'] = '✗ เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage());
            ErrorHandler::logDB('Delete name error', $e->getMessage());
        }
    } else {
        $_SESSION['alert'] = '✗ ข้อมูลรายชื่อไม่ถูกต้อง';
    }
}
header('Location: index.php');
exit;
