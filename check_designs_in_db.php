<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die('⚠️ ไม่ได้ login');
}

$user_id = $_SESSION['user_id'];
echo "Current user_id: " . $user_id . "<br>";
echo "Session data: " . print_r($_SESSION, true) . "<br><br>";

// Check table exists
$result = $conn->query("SHOW TABLES LIKE 'certificate_designs'");
if (!$result || $result->num_rows === 0) {
    die('❌ Table certificate_designs ยังไม่มี');
}
echo "✓ Table certificate_designs exists<br><br>";

// Check all designs for this user - ใช้ Prepared Statement
$sql = "SELECT * FROM certificate_designs WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

echo "SQL: " . $sql . "<br>";
echo "Found: " . ($result ? $result->num_rows : 'ERROR') . " designs<br><br>";

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Design Name</th><th>Template</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['design_name'] . "</td>";
        echo "<td>" . $row['template_id'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ ไม่มี design สำหรับ user นี้";
}

// Also check all designs in table (admin view)
echo "<br><hr><br>";
echo "<h3>All designs in database:</h3>";
$result_all = $conn->query("SELECT * FROM certificate_designs ORDER BY created_at DESC LIMIT 10");
if ($result_all && $result_all->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Design Name</th><th>Created</th></tr>";
    while ($row = $result_all->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['design_name'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
