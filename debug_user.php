<?php
require_once 'db.php';
session_start();

echo '<pre>';
echo "=== DEBUG USER INFO ===\n\n";

// Show session info
echo "Session User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Session Username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "Session Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n\n";

// Show all users in database
echo "=== ALL USERS IN DATABASE ===\n";
$users = $conn->query("SELECT id, username, role FROM users");
if ($users && $users->num_rows > 0) {
    while ($user = $users->fetch_assoc()) {
        echo "ID: {$user['id']}, Username: {$user['username']}, Role: {$user['role']}\n";
    }
} else {
    echo "No users found\n";
}

echo "\n=== DESIGNS IN DATABASE ===\n";
$designs = $conn->query("SELECT DISTINCT user_id FROM certificate_designs ORDER BY user_id");
if ($designs && $designs->num_rows > 0) {
    echo "Design owners (user_id):\n";
    while ($design = $designs->fetch_assoc()) {
        echo "- user_id: {$design['user_id']}\n";
    }
} else {
    echo "No designs found\n";
}

echo "\n=== DESIGNS FOR CURRENT USER ===\n";
if (isset($_SESSION['user_id'])) {
    $current_designs = $conn->query("SELECT id, design_name, created_at FROM certificate_designs WHERE user_id = " . $_SESSION['user_id']);
    if ($current_designs && $current_designs->num_rows > 0) {
        echo "Found " . $current_designs->num_rows . " designs:\n";
        while ($d = $current_designs->fetch_assoc()) {
            echo "- ID: {$d['id']}, Name: {$d['design_name']}, Created: {$d['created_at']}\n";
        }
    } else {
        echo "No designs for current user\n";
    }
}

echo "</pre>";
?>
