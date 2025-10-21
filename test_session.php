<?php
session_start();

echo "<h2>Session Test</h2>";
echo "<pre>";
echo "SESSION ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "Login Time: " . ($_SESSION['login_time'] ?? 'NOT SET') . "\n";
echo "</pre>";

echo "<hr>";
echo "<a href='logout.php' class='btn btn-danger'>Logout</a>";
echo " | <a href='designer.php' class='btn btn-primary'>Designer</a>";
echo " | <a href='test_session.php' class='btn btn-secondary'>Refresh</a>";
?>
