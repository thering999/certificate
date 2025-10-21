<?php
// admin_manage_users.php
// Admin จัดการ user (เพิ่ม/ลบ/แก้ไข/รีเซ็ตรหัสผ่าน)
session_start();
require_once "db.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}

$msg = "";
// เพิ่ม user
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    // ตรวจสอบว่ามี username ซ้ำหรือไม่
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $msg = "Username '$username' มีอยู่แล้ว กรุณาใช้ชื่ออื่น";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);
        if ($stmt->execute()) $msg = "เพิ่มผู้ใช้สำเร็จ"; else $msg = "เพิ่มผู้ใช้ไม่สำเร็จ";
    }
}
// ลบ user
if (isset($_POST['delete_user'])) {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) $msg = "ลบผู้ใช้สำเร็จ"; else $msg = "ลบผู้ใช้ไม่สำเร็จ";
}
// รีเซ็ตรหัสผ่าน
if (isset($_POST['reset_pass'])) {
    $id = intval($_POST['id']);
    $newpass = substr(str_shuffle('abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789'), 0, 8);
    $hash = password_hash($newpass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $id);
    if ($stmt->execute()) $msg = "รหัสใหม่: <b>$newpass</b>"; else $msg = "รีเซ็ตรหัสผ่านไม่สำเร็จ";
}
// ดึงรายชื่อ user
$users = $conn->query("SELECT id, username, role FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin จัดการผู้ใช้</title>
    
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3>Admin จัดการผู้ใช้</h3>
    <?php if ($msg): ?><div class="alert alert-info"><?php echo $msg; ?></div><?php endif; ?>
    <form method="post" class="row g-2 mb-4">
        <div class="col-md-3"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
        <div class="col-md-3"><input type="text" name="password" class="form-control" placeholder="Password" required></div>
        <div class="col-md-2">
            <select name="role" class="form-select">
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="col-md-2"><button type="submit" name="add_user" class="btn btn-success">เพิ่มผู้ใช้</button></div>
    </form>
    <table class="table table-bordered bg-white">
        <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Action</th></tr></thead>
        <tbody>
        <?php while ($row = $users->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo $row['role']; ?></td>
                <td>
                    <form method="post" style="display:inline-block">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="reset_pass" class="btn btn-warning btn-sm">รีเซ็ตรหัสผ่าน</button>
                    </form>
                    <?php if ($row['username'] !== 'admin'): ?>
                    <form method="post" style="display:inline-block">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm" onclick="return confirm('ลบผู้ใช้นี้?')">ลบ</button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted small">ไม่สามารถลบได้</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <a href="index.php" class="btn btn-link">กลับหน้าหลัก</a>
</div>
</body>
</html>