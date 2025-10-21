<?php
$servername = "mysql-docker";
$username = "root";
$password = "123456";
$dbname = "certificate_db";

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS `" . $dbname . "` CHARACTER SET utf8mb4");
$conn->select_db($dbname);
$conn->set_charset("utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS certificate_names (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), user_id INT DEFAULT 1, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB");
$conn->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE, password VARCHAR(255), role ENUM('user','admin') DEFAULT 'user', email VARCHAR(255), reset_token VARCHAR(255), reset_token_expiry DATETIME) ENGINE=InnoDB");
$conn->query("CREATE TABLE IF NOT EXISTS certificate_templates (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), file_path VARCHAR(500), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
$conn->query("CREATE TABLE IF NOT EXISTS certificate_designs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, design_name VARCHAR(255), template_id VARCHAR(255), bg_image LONGTEXT, text_elements LONGTEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, KEY idx_user_id (user_id), KEY idx_updated_at (updated_at)) ENGINE=InnoDB");

// Add missing columns to users table if they don't exist
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'email'");
if ($result && $result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN email VARCHAR(255)");
}

$result = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
if ($result && $result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255)");
}

$result = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token_expiry'");
if ($result && $result->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN reset_token_expiry DATETIME");
}

// Add missing columns to certificate_names table if they don't exist
$result = $conn->query("SHOW COLUMNS FROM certificate_names LIKE 'user_id'");
if ($result && $result->num_rows == 0) {
    $conn->query("ALTER TABLE certificate_names ADD COLUMN user_id INT DEFAULT 1");
}