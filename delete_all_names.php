<?php
require_once 'db.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
$conn->query('DELETE FROM certificate_names');
$_SESSION['alert'] = 'ลบรายชื่อทั้งหมดเรียบร้อยแล้ว';
header('Location: index.php');
exit;
